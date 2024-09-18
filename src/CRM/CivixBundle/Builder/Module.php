<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Utils\MixinLibraries;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Utils\Path;

class Module implements Builder {

  /**
   * @var \Symfony\Component\Templating\EngineInterface
   */
  public $templateEngine;

  public function __construct($templateEngine) {
    $this->templateEngine = $templateEngine;
  }

  public function loadInit(&$ctx) {
  }

  public function init(&$ctx) {
  }

  public function load(&$ctx) {
  }

  public function save(&$ctx, OutputInterface $output) {
    $basedir = new Path($ctx['basedir']);

    \Civix::generator()->updateMixinLibraries(function(MixinLibraries $libs) {
      $useSchema = \Civix::checker()->hasUpgrader()
        && !\Civix::checker()->coreProvidesLibrary('civimix-schema@5');
      $libs->toggle('civimix-schema@5', $useSchema);
    });

    $module = new Template(
      'module.php.php',
      $basedir->string($ctx['mainFile'] . '.php'),
      'ignore',
      $this->templateEngine
    );
    $module->save($ctx, $output);

    $moduleCivix = new Template(
      'module.civix.php.php',
      $basedir->string($ctx['mainFile'] . '.civix.php'),
      TRUE,
      $this->templateEngine
    );

    $moduleCivix->save($ctx, $output);
  }

}
