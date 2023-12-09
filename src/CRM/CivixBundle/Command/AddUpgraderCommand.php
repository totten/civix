<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Application;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;

class AddUpgraderCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:upgrader')
      ->setDescription('Add an example upgrader class to a CiviCRM Module-Extension');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();
    if ($attrs['type'] != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return 1;
    }

    $dirs = [
      $basedir->string('sql'),
      $basedir->string($ctx['namespace']),
    ];
    (new Dirs($dirs))->save($ctx, $output);

    $crmPrefix = preg_replace(':/:', '_', $ctx['namespace']);
    $ctx['baseUpgrader'] = 'CRM_Extension_Upgrader_Base';

    $phpFile = $basedir->string($ctx['namespace'], 'Upgrader.php');
    if (!file_exists($phpFile)) {
      $output->writeln(sprintf('<info>Write</info> %s', Files::relativize($phpFile)));
      file_put_contents($phpFile, Services::templating()
        ->render('upgrader.php.php', $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists, defer to customized version</error>', Files::relativize($phpFile)));
    }

    if (!$info->get()->xpath('upgrader')) {
      $info->get()->addChild('upgrader', $crmPrefix . '_Upgrader');
    }
    $info->raiseCompatibilityMinimum('5.38');
    $info->save($ctx, $output);

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    return 0;
  }

}
