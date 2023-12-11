<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Menu;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;
use Exception;

abstract class AbstractAddPageCommand extends AbstractCommand {

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

    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $ctx['shortClassName'] = $input->getArgument('<ClassName>');
    $basedir = new Path($ctx['basedir']);

    $info = $this->getModuleInfo($ctx);

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
      $output->writeln(sprintf('<info>Write</info> %s', Files::relativize($phpFile)));
      file_put_contents($phpFile, Services::templating()
        ->render($this->getPhpTemplate($input), $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', Files::relativize($phpFile)));
    }

    if (!file_exists($tplFile)) {
      $output->writeln(sprintf('<info>Write</info> %s', Files::relativize($tplFile)));
      file_put_contents($tplFile, Services::templating()
        ->render($this->getTplTemplate($input), $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', Files::relativize($tplFile)));
    }

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    $mixins = new Mixins($info, $basedir->string('mixin'), ['menu-xml@1.0', 'smarty-v2@1.0']);
    $mixins->save($ctx, $output);
    $info->save($ctx, $output);
    return 0;
  }

  abstract protected function getPhpTemplate(InputInterface $input);

  abstract protected function getTplTemplate(InputInterface $input);

  abstract protected function createClassName(InputInterface $input, $ctx);

  abstract protected function createTplName(InputInterface $input, $ctx);

}
