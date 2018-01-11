<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Menu;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;
use Exception;

abstract class AbstractAddPageCommand extends Command {
  protected function configure() {
    $this
      ->addArgument('<ClassName>', InputArgument::REQUIRED, 'Base name of the controller class name (eg "MyPage")')
      ->addArgument('<web/path>', InputArgument::REQUIRED, 'The path which maps to this page (eg "civicrm/my-page")');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!preg_match('/^civicrm\//', $input->getArgument('<web/path>'))) {
      throw new Exception("Web path must begin with 'civicrm/'");
    }
    if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $input->getArgument('<ClassName>'))) {
      throw new Exception("Class name should be valid (alphanumeric beginning with uppercase)");
    }

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $ctx['shortClassName'] = $input->getArgument('<ClassName>');
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();
    if ($attrs['type'] != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return;
    }

    $ctx['fullClassName'] = $this->createClassName($input, $ctx);
    $phpFile = $basedir->string(str_replace('_', '/', $ctx['fullClassName']) . '.php');
    $tplFile = $basedir->string('templates', $this->createTplName($input, $ctx));

    if (preg_match('/^CRM_/', $input->getArgument('<ClassName>'))) {
      throw new Exception("Class name looks suspicious. Please note the final class would be \"{$ctx['fullClassName']}\"");
    }

    $dirs = new Dirs([
      $basedir->string('xml', 'Menu'),
      dirname($phpFile),
      dirname($tplFile),
    ]);
    $dirs->save($ctx, $output);

    $menu = new Menu($basedir->string('xml', 'Menu', $ctx['mainFile'] . '.xml'));
    $menu->loadInit($ctx);
    if (!$menu->hasPath($input->getArgument('<web/path>'))) {
      $menu->addItem($ctx, $input->getArgument('<ClassName>'), $ctx['fullClassName'], $input->getArgument('<web/path>'));
      $menu->save($ctx, $output);
    }
    else {
      $output->writeln(sprintf('<error>Failed to bind %s to class %s; %s is already bound</error>',
        $input->getArgument('<web/path>'),
        $input->getArgument('<ClassName>'),
        $input->getArgument('<web/path>')
      ));
    }

    if (!file_exists($phpFile)) {
      $output->writeln(sprintf('<info>Write %s</info>', $phpFile));
      file_put_contents($phpFile, Services::templating()
        ->render($this->getPhpTemplate($input), $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $phpFile));
    }

    if (!file_exists($tplFile)) {
      $output->writeln(sprintf('<info>Write %s</info>', $tplFile));
      file_put_contents($tplFile, Services::templating()
        ->render($this->getTplTemplate($input), $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $tplFile));
    }

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);
  }

  abstract protected function getPhpTemplate(InputInterface $input);

  abstract protected function getTplTemplate(InputInterface $input);

  abstract protected function createClassName(InputInterface $input, $ctx);

  abstract protected function createTplName(InputInterface $input, $ctx);

}
