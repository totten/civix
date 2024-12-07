<?php
namespace CRM\CivixBundle;

use Civix;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\PHPUnitGenerateInitFiles;
use CRM\CivixBundle\Command\Mgd;
use CRM\CivixBundle\Utils\Files;
use CRM\CivixBundle\Utils\MixinLibraries;
use CRM\CivixBundle\Utils\Naming;
use CRM\CivixBundle\Utils\Path;
use PhpArrayDocument\Parser;
use PhpArrayDocument\Printer;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The "Generator" class is a utility provided to various upgrade-scripts.
 */
class Generator {

  /**
   * @var \Symfony\Component\Console\Input\InputInterface
   * @readonly
   */
  private $input;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   * @readonly
   */
  private $output;

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   * @readonly
   */
  private $io;

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

  /**
   * @var \CRM\CivixBundle\Utils\MixinLibraries
   */
  public $mixinLibraries;

  /**
   * @param \CRM\CivixBundle\Utils\Path $baseDir
   *   The folder that contains the extension.
   */
  public function __construct(Path $baseDir) {
    $this->input = \Civix::input();
    $this->output = \Civix::output();
    $this->io = \Civix::io();
    $this->baseDir = $baseDir;
    $this->mixinLibraries = new MixinLibraries($baseDir->path('mixin/lib'), \Civix::appDir('lib'));
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
   * Re-generate the 'module.civix.php' boilerplate.
   */
  public function updateModuleCivixPhp(): void {
    $ctx = $this->createDefaultCtx();
    $info = new Info($this->baseDir->string('info.xml'));
    $info->load($ctx);

    $module = new Module(Civix::templating());
    $module->loadInit($ctx);
    $module->save($ctx, \Civix::output());
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
    $ctx = $this->createDefaultCtx();
    $this->infoXml->save($ctx, $this->output);
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
    $ctx = $this->createDefaultCtx();
    $mixins->save($ctx, $this->output);
    $this->infoXml->save($ctx, $this->output);
  }

  /**
   * Apply a filter to the "mixin/lib" (Mixin Libraries).
   *
   * @param callable $function
   *   signature: `function(MixinLibraries $mixinLibraries): void`
   */
  public function updateMixinLibraries(callable $function): void {
    $function($this->mixinLibraries);
    if (\Civix::checker()->hasMixinLibrary() && !\Civix::checker()->coreHasPathload()) {
      $this->copyFile(Civix::appDir('lib/pathload-0.php'), Civix::extDir('mixin/lib/pathload-0.php'));
    }
    else {
      $this->removeFile(Civix::extDir('mixin/lib/pathload-0.php'));
    }
  }

  /**
   * Update a PHP-style data-file. (If the file is new, create it.)
   *
   * Ex: updatePhpData('foobar.mgd.php', \fn(PhpData $data) => $data->set([...]))
   *
   * TIP: You may want a more targeted helper like `updateMgdPhp()` or `updateAfformPhp`.
   *
   * @param string|Path $path
   * @param callable $filter
   *   Function(PhpData $phpData): void
   *   The callback function defines the actual changes to the mgd.
   * @return void
   */
  public function updatePhpData($path, callable $filter): void {
    $file = Path::for($path)->string();
    $ctx = $this->createDefaultCtx();
    $phpData = new PhpData($file);
    $phpData->loadInit($ctx);
    $filter($phpData);
    $phpData->save($ctx, $this->output);
  }

  /**
   * Update a PHP-style data-file. (If the file is new, create it.)
   *
   * Ex: updatePhpArrayDocument('foobar.mgd.php', fn(PhpArrayDocument $doc) => $doc->setInnerComment("Hello world"))
   *
   * TIP: updatePhpData() and updatePhpArrayDocument() fill a similar niche.
   * - updatePhpArrayDocument reveals and preserves more metadata.
   * - updatePhpData has a simpler API.
   *
   * @param $path
   * @param callable $filter
   */
  public function updatePhpArrayDocument($path, callable $filter): void {
    $file = Path::for($path)->string();
    $oldCode = file_get_contents($file);
    $doc = (new Parser())->parse($oldCode);
    $filter($doc);
    $newCode = (new Printer())->print($doc);
    $this->writeTextFile($file, $newCode, TRUE);
  }

  /**
   * Update a managed-entity data-file. (If the file is new, create it.)
   *
   * Ex: updateMgdPhp('foobar.mgd.php', \fn(PhpData $data) => $data->set([...]))
   *
   * @param string|Path $path
   * @param callable $filter
   *   Function(PhpData $phpData): void
   * @return void
   */
  public function updateMgdPhp($path, callable $filter): void {
    $this->updatePhpData($path, function(PhpData $phpData) use ($filter) {
      // PhpData handler doesn't know how to read/maintain the `use` and `ts()` parts.
      // Instead, force-enable for all *.mgd.php.
      $phpData->useExtensionUtil($this->infoXml->getExtensionUtilClass());

      $filter($phpData);

      $localizable = explode(',', PhpData::COMMON_LOCALIZBLE);
      // Lookup entity-specific fields that should be wrapped in E::ts()
      foreach ($phpData->get() as $item) {
        $fields = (array) \civicrm_api4($item['entity'], 'getFields', [
          'checkPermissions' => FALSE,
          'where' => [['localizable', '=', TRUE]],
        ], ['name']);
        $localizable = array_merge($localizable, $fields);
      }
      $phpData->useTs($localizable);
    });
  }

  /**
   * Update an afform metadata-file (PHP-style). (If the file is new, create it.)
   *
   * Ex: updateAfformPhp('foobar.aff.php', \fn(PhpData $data) => $data->set([...]))
   *
   * @param string|Path $path
   * @param callable $filter
   *   Function(PhpData $phpData): void
   * @return void
   */
  public function updateAfformPhp($path, callable $filter): void {
    $this->updatePhpData($path, function(PhpData $phpData) use ($filter) {
      // PhpData handler doesn't know how to read/maintain the `use` and `ts()` parts.
      // Instead, force-enable for all *.aff.php.
      $phpData->useExtensionUtil($this->infoXml->getExtensionUtilClass());
      $filter($phpData);
      $phpData->useTs(explode(',', PhpData::COMMON_LOCALIZBLE));
    });
  }

  /**
   * Read some entity/entities from the database and write them to an '*.mgd.php' file.
   *
   * @param string $entityName
   * @param scalar $id
   */
  public function exportMgd($entityName, $id): void {
    $export = (array) \civicrm_api4($entityName, 'export', [
      'checkPermissions' => FALSE,
      'id' => $id,
    ]);
    if (!$export) {
      throw new \Exception("$entityName $id not found.");
    }

    $managedName = $export[0]['name'];
    $managedFileName = $this->baseDir->string('managed', "$managedName.mgd.php");
    Mgd::assertManageableEntity($entityName, $id, $this->infoXml->getKey(), $managedName, $managedFileName);
    $this->updateMgdPhp($managedFileName, function(PhpData $data) use ($export) {
      $data->set($export);
    });
  }

  /**
   * Write the 'ang/*.aff.html' and 'ang/*.aff.php' files based on the live/effective definition
   * of the form. (*This basically copies from `[civicrm.files]/ang/XXX` and adds any searchkit dependencies.*)
   *
   * @param string $afformName
   * @return void
   * @throws \Exception
   */
  public function exportAfform(string $afformName) {
    $angPath = Path::for($this->baseDir, 'ang');
    $angPath->mkdir();

    // Will throw exception if not found
    $afform = \civicrm_api4('Afform', 'get', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $afformName]],
      'select' => ['*', 'search_displays'],
      'layoutFormat' => 'html',
    ])->single();

    // An Afform consists of 2 files - a layout file and a meta file
    $layoutFileName = $angPath->string("$afformName.aff.html");
    $metaFileName = $angPath->string("$afformName.aff.php");

    // Export layout file
    $this->writeTextFile($layoutFileName, $afform['layout'], 'overwrite');

    // Export meta file
    $this->updateAfformPhp($metaFileName, function(PhpData $phpData) use ($afform) {
      $fields = \civicrm_api4('Afform', 'getFields', [
        'checkPermissions' => FALSE,
        'where' => [['type', '=', 'Field']],
      ])->indexBy('name');

      $meta = $afform;
      unset($meta['name'], $meta['layout'], $meta['navigation'], $meta['search_displays']);
      // Simplify meta file by removing readonly fields and values that match the defaults
      foreach ($meta as $field => $value) {
        if ($fields[$field]['readonly'] ||
          ($field !== 'type' && $value == $fields[$field]['default_value'])
        ) {
          unset($meta[$field]);
        }
      }

      $phpData->set($meta);
    });

    // Export navigation menu item pointing to afform, if present
    if (!empty($afform['server_route'])) {
      $navigation = \civicrm_api4('Navigation', 'get', [
        'checkPermissions' => FALSE,
        'select' => ['id'],
        'where' => [['url', '=', $afform['server_route']], ['is_active', '=', TRUE]],
        // Just the first one; multiple domains are handled by `CRM_Core_ManagedEntities`
        'orderBy' => ['domain_id' => 'ASC'],
      ])->first();
      if ($navigation) {
        $this->exportMgd('Navigation', $navigation['id']);
      }
    }

    // Export embedded search display(s)
    if (!empty($afform['search_displays'])) {
      $searchNames = array_map(function ($item) {
        return explode('.', $item)[0];
      }, $afform['search_displays']);
      $searchIds = \civicrm_api4('SavedSearch', 'get', [
        'checkPermissions' => FALSE,
        'where' => [['name', 'IN', $searchNames]],
      ], ['id']);
      foreach ($searchIds as $id) {
        $this->exportMgd('SavedSearch', $id);
      }
    }
  }

  /**
   * @param $path
   * @param string $content
   * @param string|bool $overwrite
   *   One of: 'overwrite', 'skip', 'abort', 'ask', 'if-forced'
   * @return void
   */
  public function writeTextFile($path, string $content, $overwrite) {
    $file = Path::for($path)->string();
    Path::for(dirname($file))->mkdir();
    $relPath = Files::relativize($file, getcwd());

    if ('keep' === $this->checkOverwrite($file, $overwrite)) {
      return;
    }
    $this->output->writeln("<info>Write</info> " . $relPath);
    if (!file_put_contents($file, $content)) {
      throw new \RuntimeException("Failed to write $file");
    }
  }

  /**
   * @param string $class
   *   Ex: 'CRM_Foo_DAO_Bar'
   * @param string $tableName
   *   Ex: 'civicrm_foo_bar'
   * @param string $overwrite
   *   Ex: 'overwrite' or 'ask' (per checkOverwrite())
   * @param array $props
   *   Class properties to annotate
   * @return void
   */
  public function addDaoClass(string $class, string $tableName, string $overwrite, array $props = []): void {
    $namespace = Naming::coerceNamespace($this->infoXml->getNamespace(), 'CRM');

    $this->addClass($class, 'entity-dao.php.php', [
      'tableName' => $tableName,
      'daoBaseClass' => Naming::createClassName($namespace, 'DAO', 'Base'),
      'classRenaming' => FALSE,
      'properties' => $props,
    ], $overwrite);
  }

  /**
   * Create or update an exact copy of a file.
   *
   * If the file is the same, do nothing.
   *
   * @param string $src
   * @param string $dest
   */
  public function copyFile(string $src, string $dest) {
    if (!Files::isIdenticalFile($src, $dest)) {
      $relPath = Files::relativize($dest, getcwd());
      $this->output->writeln("<info>Write</info> " . $relPath);
      copy($src, $dest);
    }
  }

  /**
   * Remove a file (if it exists).
   *
   * @param string $file
   */
  public function removeFile(string $file) {
    if (file_exists($file)) {
      $relPath = Files::relativize($file, getcwd());
      $this->output->writeln("<info>Remove</info> " . $relPath);
      unlink($file);
    }
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
          if ($line === NULL) {
            // If any $name matches and causes removal, then we don't need to check the other $names.
            break;
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

  public function cleanEmptyLines(): void {
    $this->updateModulePhp(function($infoXml, $content) {
      // It might be better to cleanup more stuff, but this is enough to get IdempotentUpgradeTest to pass.
      return rtrim($content, "\n") . "\n";
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
   * Add skeletal initialization files for PHPUnit.
   *
   * @return void
   */
  public function addPhpunit(): void {
    $ctx = $this->createDefaultCtx();
    $phpUnitInitFiles = new PHPUnitGenerateInitFiles();
    $phpUnitInitFiles->initPhpunitXml($this->baseDir->string('phpunit.xml.dist'), $ctx, Civix::output());
    $phpUnitInitFiles->initPhpunitBootstrap($this->baseDir->string('tests', 'phpunit', 'bootstrap.php'), $ctx, Civix::output());
  }

  /**
   * Add a class file. The class-name and file-name are relative to your configured <namespace>.
   *
   * @param string $className
   *   Class-name
   *   Ex: "CRM_Mynamespace_Stuff" or "Civi\Mynamespace\Stuff"
   * @param string $template
   *   Logical name of the template.
   *   Corresponds to a file in `src/CRM/CivixBundle/Resources/`
   * @param array $tplData
   *   Open-ended data to pass into the template.
   *   Note: Some variables are defined automatically:
   *     - extBaseDir (e.g. "/var/www/civicrm/ext/foobar")
   *     - extMainFile (e.g. "myextension")
   *     - extKey (e.g. "org.example.myextension")
   *     - classFile (e.g. "Civi/Foo/Bar.php")
   *     - className (e.g. "Bar")
   *     - classNameFull (e.g. "Civi\Foo\Bar")
   *     - classNamespace (e.g. "Civi\Foo")
   *     - classNamespaceDecl (e.g. "namespace Civi\Foo;")
   *     - classRenaming (bool; whether developer should be allowed to change the class name)
   *     - useE (e.g. 'use CRM_Myextension_ExtensionUtil as E;')
   * @param string $overwrite
   *   Whether to overwrite existing files. (See options in checkOverwrite().)
   * @return void
   */
  public function addClass(string $className, string $template, array $tplData = [], string $overwrite = 'ask'): void {
    $tplData['classRenaming'] = $tplData['classRenaming'] ?? TRUE;
    $tplData = array_merge($this->createClassVars($className, $tplData['classRenaming']), $tplData);
    $classFile = $tplData['classFile'];
    $className = $tplData['className'];

    if ('keep' === $this->checkOverwrite($classFile, $overwrite)) {
      return;
    }

    $this->io->writeln(sprintf("<info>Write</info> %s", Files::relativize($classFile, getcwd())));
    $rendered = Civix::templating()->render($template, $tplData);
    Path::for(dirname($classFile))->mkdir();
    file_put_contents($classFile, $rendered);
  }

  /**
   * @param string $className
   * @param bool $classRenaming
   *   Whether developer should be allowed to change the class name
   * @return array
   * @internal
   */
  public function createClassVars($className, bool $classRenaming = TRUE): array {
    if ($classRenaming && $this->input->isInteractive()) {
      $className = $this->io->ask('Class name', $className);
    }
    $classFile = preg_replace(';[_/\\\];', '/', $className) . '.php';

    $tplData = [];
    $tplData['extBaseDir'] = \CRM\CivixBundle\Application::findExtDir();
    $tplData['extMainFile'] = $this->infoXml->getFile();
    $tplData['extKey'] = $this->infoXml->getKey();
    $tplData['useE'] = sprintf('use %s as E;', Naming::createUtilClassName($this->infoXml->getNamespace()));

    $tplData['classFile'] = $classFile;
    if (preg_match('/^CRM_/', $className)) {
      $tplData['classNameFull'] = $className;
      $tplData['className'] = $className;
      $tplData['classNamespace'] = '';
      $tplData['classNamespaceDecl'] = '';
    }
    else {
      $parts = explode('\\', $className);
      $tplData['classNameFull'] = $className;
      $tplData['className'] = array_pop($parts);
      $tplData['classNamespace'] = implode('\\', $parts);
      $tplData['classNamespaceDecl'] = sprintf('namespace %s;', $tplData['classNamespace']);
    }
    return $tplData;
  }

  /**
   * Add an "upgrader" class ("CRM_MyExtension_Upgrader")
   *
   * @param string $overwrite
   */
  public function addUpgrader(string $overwrite = 'ask'): void {
    // TODO: Re-test comprehensively to ensure that "Civi\Foo\Upgrader" is valid/workable. Drop coercion.
    $namespace = Naming::coerceNamespace($this->infoXml->getNamespace(), 'CRM');
    $className = Naming::createClassName($namespace, 'Upgrader');
    $this->addClass($className, 'upgrader.php.php', ['classRenaming' => FALSE], $overwrite);

    $this->updateInfo(function($info) {
      $info->get()->upgrader = sprintf('CiviMix\\Schema\\%s\\AutomaticUpgrader', Naming::createCamelName($info->getFile()));
      // <upgrader> tag only exists in 5.38+.
      $info->raiseCompatibilityMinimum('5.38');
    });
    $this->updateModuleCivixPhp();
  }

  // -------------------------------------------------
  // These are some helper utilities.

  /**
   * Determine whether file is writable.
   *
   * @param string|Path $path
   * @param string|bool $mode
   *   What to do if the file already exists
   *   'overwrite': Always overwrite
   *   'keep': Never overwrite
   *   'ignore': Never overwrite (alias for 'keep')
   *   'abort': Throw an exception to abort
   *   'ask': Check user-input to decide whether to overwrite. (Either --force` or interactive prompt.)
   *   'if-forced': Only overwrite if the `--force` flag is set
   *   TRUE: Always overwrite
   *   FALSE: Never overwrite
   *
   * @return string
   *   'write' if you should proceed with (over)writing.
   *   'keep' if you should skip generating this file.
   */
  public function checkOverwrite($path, $mode): string {
    $file = Path::for($path)->string();
    if (!file_exists($file)) {
      return 'write';
    }

    if ($mode === 'ask' && !$this->input->isInteractive()) {
      $mode = 'if-forced';
    }
    if ($mode === 'if-forced') {
      $mode = $this->input->hasOption('force') && $this->input->getOption('force') ? 'overwrite' : 'keep';
    }

    if ($mode === 'overwrite' || $mode === TRUE) {
      return 'write';
    }
    if ($mode === 'keep'|| $mode === FALSE) {
      $this->io->writeln("<error>Skip " . Files::relativize($file) . ": file already exists</error>");
      return 'keep';
    }
    if ($mode === 'abort') {
      throw new \RuntimeException("Abort: $file already exists.)");
    }
    if ($mode === 'ask' && $this->input->isInteractive()) {
      $relPath = Files::relativize($file, $this->baseDir->string());
      $action = mb_strtolower($this->io->choice("File \"$relPath\" already exists. What should we do?", [
        'o' => 'Overwrite the file',
        'k' => 'Keep the current file',
        'a' => 'Abort the process',
      ]));
      if ($action === 'o') {
        return 'write';
      }
      elseif ($action === 'k') {
        return 'keep';
      }
      else {
        throw new \RuntimeException("File $relPath already exists. Operation aborted");
      }
    }

    throw new \RuntimeException("Invalid argument checkOverwrite(...$mode)");
  }

  /**
   * Re-read the `info.xml` file.
   *
   * @return \CRM\CivixBundle\Builder\Info
   */
  public function reloadInfo() {
    $ctx = $this->createDefaultCtx();
    $this->infoXml = new Info($this->baseDir->string('info.xml'));
    $this->infoXml->load($ctx);
    return $this->infoXml;
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

  protected function createDefaultCtx(): array {
    $ctx = [];
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $ctx['type'] = 'module';
    return $ctx;
  }

}
