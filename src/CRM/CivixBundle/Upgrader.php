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
   *   Signature: `function($info, string $content): string`
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
   * @param string $newVersion
   */
  public function updateFormatVersion(string $newVersion) {
    $this->updateInfo(function (Info $info) use ($newVersion) {
      $this->io->writeln("<info>Set civix format to </info>$newVersion<info> in </info>info.xml");
      $info->get()->civix->format = $newVersion;
    });
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

  /**
   * Update the content of a series of text files.
   *
   * Useful if you just want to do regex.
   *
   * @param iterable $fileNames
   *   List of files to examine.
   * @param callable $function
   *   Filter-function to define updated content.
   *   signature: `function(string $file, string $content): string`
   */
  public function updateTextFiles(iterable $fileNames, callable $function) {
    foreach ($fileNames as $fileName) {
      $oldContent = file_get_contents($fileName);
      $newContent = $function($fileName, $oldContent);
      if ($oldContent !== $newContent) {
        $this->output->writeln('<info>Write</info> ' . $fileName);
        file_put_contents($fileName, $newContent);
      }
    }
  }

  // -------------------------------------------------
  // These filters are for fairly specific situations.

  /**
   * Add a hook-delegation stub to the main `mymodule.php` file.
   *
   * @param string $hook
   *   Ex: 'civicrm_entityTypes'
   * @param string $mainArg
   *   Ex: '&$entityTypes'
   * @param string|null $help
   *   Explanatory message. Help the user decide whether to add the hook.
   */
  public function addHookDelegation(string $hook, string $mainArg, ?string $help = NULL): void {
    $this->updateModulePhp(function(\CRM\CivixBundle\Builder\Info $infoXml, string $content) use ($hook, $mainArg, $help) {
      $io = $this->io;
      $contains = function ($needle) use (&$content) {
        return strpos($content, $needle) !== FALSE;
      };

      $mainFunc = $infoXml->getFile() . '_' . $hook;
      $delegateFunc = '_' . $infoXml->getFile() . '_civix_' . $hook;
      $delegateArg = str_replace('&', '', $mainArg);

      if ($contains($delegateFunc)) {
        // They've already dealt with this.
        return $content;
      }

      $fullHook = 'hook_' . $hook;
      $stub = [
        "/**",
        " * Implements {$fullHook}().",
        " *",
        " * @link https://docs.civicrm.org/dev/en/latest/hooks/{$fullHook}",
        " */",
        "function {$mainFunc}({$mainArg}) {",
        "  {$delegateFunc}($delegateArg);",
        "}",
      ];
      $io->writeln("<info>This extension does not implement </info>{$fullHook}<info>, but this is included in newer templates. This is a typical example:</info>");
      $io->write("\n");
      $this->showCode($stub);
      if ($help) {
        $io->note($help);
      }

      $actions = [
        'y' => 'Yes. Add live-code.',
        'c' => 'Yes. Add comment-code.',
        'n' => 'No. Do not add anything.',
      ];
      if ($contains($mainFunc)) {
        $io->note([
          "This extension may already have a customized version of \"$mainFunc()\".\n" .
          "civix will not add live-code because that could create a conflict.",
        ]);
        unset($actions['y']);
      }
      if ($help || $contains($mainFunc)) {
        $io->note([
          "If you are unsure what to do, you can safely add comment-code. " .
          "Comment-code will not change the behavior. " .
          "It merely provides an example to reference if you encounter issues in the future.",
        ]);
      }
      $action = $io->choice("Add {$mainFunc}?", $actions, $contains($mainFunc) ? 'c' : 'y');

      switch ($action) {
        case 'y':
          $content = rtrim($content, "\n") . "\n\n" . implode("\n", $stub) . "\n";
          break;

        case 'c':
          $content = rtrim($content, "\n") . "\n\n" . implode("\n", array_map(
              function($line) {
                return "// $line";
              },
              $stub
            )) . "\n";
          break;

        case 'n':
          break;
      }

      return $content;
    });
  }

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
          elseif (preg_match("|^(\s*)return ($nameQuoted\([^;]*;\s*)$|", $line, $m)) {
            // Easy case - we can disable it.
            $this->io->writeln(sprintf(
              "<info>Found reference to obsolete function </info>%s()<info> at </info>%s:%d<info>.</info>\n",
              $name, $mainPhp, 1 + $lineNum
            ));
            $this->showLine($oldLines, $lineNum);
            $this->io->writeln(sprintf("<info>Redacting call in </info>%s:%d<info></info>\n", $mainPhp, 1 + $lineNum));
            $line = $m[1] . 'return;';
          }
          elseif (preg_match("|^(\s*)?($nameQuoted\([^;]*;\s*)$|", $line, $m)) {
            // Easy case - we can disable it.
            $this->io->writeln(sprintf(
              "<info>Found reference to obsolete function </info>%s()<info> at </info>%s:%d<info>.</info>\n",
              $name, $mainPhp, 1 + $lineNum
            ));
            $this->showLine($oldLines, $lineNum);
            $this->io->writeln(sprintf("<info>Removing line </info>%s:%d<info></info>\n", $mainPhp, 1 + $lineNum));
            $line = NULL;
          }
          elseif (preg_match("|$nameQuoted|", (string) $line, $m)) {
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

  /**
   * Find any functions like `mymodle_civicrm_foobar(...) {}` (ie with an empty body).
   */
  public function cleanEmptyHooks() {
    $this->updateModulePhp(function($infoXml, $content) {
      $comment = "/\*\*\n( \*.*\n)* \*/";
      $funcName = $infoXml->getFile() . "_civicrm_[a-zA-Z0-9_]+";
      $funcArgs = "\([^\)]*\)";
      $typeHint = ":[ ]*[|?a-z]+";
      $startBody = "\{[^\}]*\}"; /* For empty functions, this grabs everything. For non-empty functions, this may just grab the opening segment. */
      $content = preg_replace_callback(";($comment)?\n\s*function ($funcName)($funcArgs)[ ]*($typeHint)?\s*($startBody)\n*;m", function ($m) {
        $func = $m[3];

        // Is our start-body basically empty (notwithstanding silly things - like `{}`, `//Comment`, and `return;`)?
        $mStartBody = explode("\n", $m[6]);
        $mStartBody = preg_replace(';^\s*;', '', $mStartBody);
        $mStartBody = preg_grep(';^\/\/;', $mStartBody, PREG_GREP_INVERT);
        $mStartBody = preg_grep('/^$/', $mStartBody, PREG_GREP_INVERT);
        $mStartBody = preg_grep('/^[\{\}]$/', $mStartBody, PREG_GREP_INVERT);
        $mStartBody = preg_grep('/^return;$/', $mStartBody, PREG_GREP_INVERT);
        $mStartBody = preg_grep('/^\{\s*\}$/', $mStartBody, PREG_GREP_INVERT);
        if (!empty($mStartBody)) {
          // There is some kind of substance in here...
          return $m[0];
        }

        $this->io->note("The function \"{$func}()\" now appears to be empty.");
        $this->showCode(explode("\n", $m[0]));
        if ($this->io->confirm("Delete the empty function \"{$func}()\"?")) {
          return "\n\n";
        }
        else {
          return $m[0];
        }
      }, $content);

      $content = preg_replace("|\n}\n\n+|m", "\n}\n\n", $content);

      return $content;
    });
  }

  /**
   * Ensure that the `mixin/` folder is in-sync with the current 'info.xml'.
   */
  public function reconcileMixins(): void {
    $this->updateMixins(function (\CRM\CivixBundle\Builder\Mixins $mixins) {
      // noop
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

  /**
   * Since 5.38 core supports an <upgrader> tag in liu of hooks, and a common base class.
   *
   * Remove hook delegations from module.php and add <upgrader> to info.xml
   * Switch upgrader base class to use the one in core and remove the boilerplate version
   */
  public function cleanUpgraderBase(): void {
    $compatVer = $this->infoXml->getCompatibilityVer();
    $useCore = version_compare($compatVer, '5.38', '>=');
    if ($useCore) {
      $prefix = $this->infoXml->getFile();
      $this->removeHookDelegation([
        // Needed by mixin-polyfill: "_{$prefix}_civix_civicrm_install",
        "_{$prefix}_civix_civicrm_postInstall",
        "_{$prefix}_civix_civicrm_uninstall",
        // Needed by mixin-polyfill: "_{$prefix}_civix_civicrm_enable",
        "_{$prefix}_civix_civicrm_disable",
        "_{$prefix}_civix_civicrm_upgrade",
      ]);
      $nameSpace = $this->infoXml->getNamespace();
      $upgraderFile = $this->baseDir->string($nameSpace . DIRECTORY_SEPARATOR . 'Upgrader.php');
      $upgraderBaseFile = $this->baseDir->string($nameSpace . DIRECTORY_SEPARATOR . 'Upgrader' . DIRECTORY_SEPARATOR . 'Base.php');
      if (file_exists($upgraderFile)) {
        $crmPrefix = preg_replace(':/:', '_', $nameSpace);
        // Add <upgrader> tag
        if (!$this->infoXml->get()->xpath('upgrader')) {
          $this->infoXml->get()->addChild('upgrader', $crmPrefix . '_Upgrader');
          $this->infoXml->save($this->_ctx, $this->output);
        }
        // Switch base class
        file_put_contents($upgraderFile,
          str_replace("{$crmPrefix}_Upgrader_Base", 'CRM_Extension_Upgrader_Base', file_get_contents($upgraderFile))
        );
      }
      if (file_exists($upgraderBaseFile)) {
        unlink($upgraderBaseFile);
      }
    }
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
  public function showLine(array $lines, int $focusLine): void {
    $low = max(0, $focusLine - 2);
    $high = min(count($lines), $focusLine + 2);
    $this->showCode($lines, $low, $high, $focusLine, $focusLine);
  }

  /**
   * Show a chunk of code.
   *
   * @param array $lines
   * @param int|null $low
   *   The first line to show
   * @param int|null $high
   *   The last line to show
   * @param int|null $focusStart
   *   The first line to highlight (within the overall code)
   * @param int|null $focusEnd
   *   The last line to highlight (within the overall code).
   */
  public function showCode(array $lines, ?int $low = NULL, ?int $high = NULL, ?int $focusStart = NULL, ?int $focusEnd = NULL): void {
    if ($low === NULL || $low < 0) {
      $low = 0;
    }
    if ($high === NULL || $high >= count($lines)) {
      $high = count($lines) - 1;
    }
    for ($i = $low; $i <= $high; $i++) {
      $fmt = sprintf('% 5d', 1 + $i);
      if ($focusStart !== NULL && $focusEnd !== NULL && $i >= $focusStart && $i <= $focusEnd) {
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
