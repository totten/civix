<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Utils\Path;

class BuildCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('build:zip')
      ->setDescription('Build a zip file for a CiviCRM Extension');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);

    $ctx['zipFile'] = $basedir->string('build', $ctx['fullName'] . '.zip');
    $cmdArgs = [
      '-r',
      $ctx['zipFile'],
      $ctx['fullName'],
      '--exclude',
      'build/*',
      '*~',
      '*.bak',
      '*.git*',
    ];
    $cmd = 'zip ' . implode(' ', array_map('escapeshellarg', $cmdArgs));

    chdir($basedir->string('..'));
    $process = new Process($cmd);
    $process->run(function ($type, $buffer) use ($output) {
      $output->writeln($buffer);
    });
    if (!$process->isSuccessful()) {
      throw new \RuntimeException('Failed to create zip');
    }
    print $process->getOutput();
    return 0;
  }

}
