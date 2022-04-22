<?php
namespace CRM\CivixBundle;

use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The "Upgrader" class is a utility provided to various upgrade-scripts.
 */
class Upgrader {

  /**
   * @var \Symfony\Component\Console\Input\InputInterface
   * @readonly
   */
  public $input;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   * @readonly
   */
  public $output;

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   * @readonly
   */
  public $io;

  /**
   * @var \CRM\CivixBundle\Utils\Path
   * @readonly
   */
  public $baseDir;

  /**
   * Reference to the parsed `info.xml` file.
   *
   * @var \CRM\CivixBundle\Builder\Info
   * @readonly
   */
  public $infoXml;

  private $_ctx;

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param \CRM\CivixBundle\Utils\Path $baseDir
   *   The folder that contains the extension.
   */
  public function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, Path $baseDir) {
    $this->input = $input;
    $this->output = $output;
    $this->io = new SymfonyStyle($input, $output);
    $this->baseDir = $baseDir;
    $this->reloadInfo();
  }

  // -----------------------------------------
  // These filters are fairly general-purpose.

  /**
   * Apply a filter to the extension's main `.php` file.
   *
   * @param callable $function
   *   Filter the content of the main `.php` file.
   *   Signature: `function(string $content): string`
   */
  public function updateModulePhp(callable $function): void {
    $fileName = $this->baseDir->string($this->infoXml->getFile() . '.php');
    $oldContent = file_get_contents($fileName);
    $newContent = $function($this->infoXml, $oldContent);
    if ($newContent !== $oldContent) {
      $this->output->writeln('<info>Write</info> ' . $fileName);
      file_put_contents($fileName, $newContent);
    }
  }

  /**
   * Apply a filter to the `info.xml` file.
   *
   * @param callable $function
   *   This is a filter function which revises the info.xml.
   *   Signature: `function(Info $info): void`
   */
  public function updateInfo(callable $function): void {
    $function($this->infoXml);
    $this->infoXml->save($this->_ctx, $this->output);
  }

  /**
   * Apply a filter to the "Mixins" list.
   *
   * @param callable $function
   *   signature: `function(Mixins $mixins): void`
   */
  public function updateMixins(callable $function): void {
    $mixins = new Mixins($this->infoXml, $this->baseDir->string('mixin'));
    $function($mixins);
    $mixins->save($this->_ctx, $this->output);
    $this->infoXml->save($this->_ctx, $this->output);
  }

  // -------------------------------------------------
  // These filters are for fairly specific situations.

  /**
   * Find any calls to a stub function and disable them.
   *
   * @param string[] $names
   *   Ex: '_foobar_civix_civicrm_managed'
   */
  public function removeHookDelegation(array $names): void {
    $this->updateModulePhp(function(\CRM\CivixBundle\Builder\Info $info, string $content) use ($names) {
      $mainPhp = $this->infoXml->getFile() . '.php';
      $oldLines = explode("\n", $content);
      $newLines = [];

      foreach ($oldLines as $lineNum => $line) {
        foreach ($names as $name) {
          $nameQuoted = preg_quote($name, '|');
          if (preg_match("|^(\s*)//(\s*)($nameQuoted\(.*)|", $line, $m)) {
            // ok, already disabled
          }
          elseif (preg_match("|^(\s*)($nameQuoted\([^;]*;\s*)$|", $line, $m)) {
            // Easy case - we can disable it.
            $this->io->writeln(sprintf(
              "<info>Found reference to obsolete function </info>%s()<info> at </info>%s:%d<info>.</info>\n",
              $name, $mainPhp, 1 + $lineNum
            ));
            $this->showLine($oldLines, $lineNum);
            $this->io->writeln(sprintf("<info>Removing line </info>%s:%d<info></info>\n", $mainPhp, 1 + $lineNum));
            $line = NULL;
          }
          elseif (preg_match("|$nameQuoted|", $line, $m)) {
            $this->io->writeln(sprintf(
              "<info>Found reference to obsolete function </info>%s()<info> at </info>%s:%d<info>.</info>\n",
              $name, $mainPhp, 1 + $lineNum
            ));
            $this->showLine($oldLines, $lineNum);
            $this->io->writeln("\n<info>Obsolete functions should usually be removed, but this line looks unusual.</info>");
            $action = $this->io->choice("What should we do with this line?", [
              'r' => 'Remove this line',
              'k' => 'Keep this line',
              'c' => 'Comment out this line',
              'f' => 'Add "FIXME" comment',
            ]);
            switch ($action) {
              case 'c':
                $line = '// ' . $line;
                break;

              case 'f':
                $line = "// FIXME: The function $name() is obsolete.\n" . $line;
                break;

              case 'r':
                $line = NULL;
                break;

              case 'k':
                break;

              default:
                throw new \RuntimeException("Error: Unrecongized option ($action)");
            }
          }
        }

        if ($line !== NULL) {
          $newLines[] = $line;
        }
      }
      return implode("\n", $newLines);
    });
  }

  public function addMixins(array $mixinConstraints): void {
    $msg = count($mixinConstraints) > 1 ? 'Enable mixins' : 'Enable mixin';
    $this->io->writeln("<info>$msg</info> " . implode(', ', $mixinConstraints));
    $this->updateMixins(function (\CRM\CivixBundle\Builder\Mixins $mixins) use ($mixinConstraints) {
      foreach ($mixinConstraints as $mixinConstraint) {
        $mixins->addMixin($mixinConstraint);
      }
    });
  }

  // -------------------------------------------------
  // These are some helper utilities.

  /**
   * Re-read the `info.xml` file.
   *
   * @return array
   */
  public function reloadInfo(): array {
    $this->_ctx = [];
    $this->_ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $this->_ctx['type'] = 'module';
    $this->infoXml = new Info($this->baseDir->string('info.xml'));
    $this->infoXml->load($this->_ctx);
    return [$this->infoXml, $this->_ctx];
  }

  /**
   * Show a list of lines and highlight a specific line.
   * @param array $lines
   * @param int $focusLine
   */
  protected function showLine(array $lines, int $focusLine): void {
    $low = max(0, $focusLine - 2);
    $high = min(count($lines), $focusLine + 2);
    for ($i = $low; $i <= $high; $i++) {
      $fmt = sprintf('% 5d', 1 + $i);
      if ($i === $focusLine) {
        $this->io->write("<error>*{$fmt}: ");
        $this->io->write($lines[$i], SymfonyStyle::OUTPUT_RAW);
        $this->io->write("</error>");
      }
      else {
        $this->io->write("<comment> {$fmt}:</comment> ");
        $this->io->writeln($lines[$i], SymfonyStyle::OUTPUT_RAW);
      }
    }
  }

}
