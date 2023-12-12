<?php
namespace CRM\CivixBundle\Command;

use Civix;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddManagedEntityCommand extends AbstractCommand {

  /**
   * Fields that most probably should be wrapped in E::ts()
   * @var array
   */
  private $localizable = ['title', 'label', 'description', 'text'];

  protected function configure() {
    parent::configure();
    $this
      ->setName('export')
      ->setDescription('(Experimental) Exports a record in packaged format for distribution in this extension')
      ->addArgument('<EntityName>', InputArgument::REQUIRED, 'API entity name (Ex: "SavedSearch")')
      ->addArgument('<EntityId>', InputArgument::REQUIRED, 'Id of entity to be exported (or name if exporting an Afform)')
      ->setHelp('Uses APIv4 Export to save existing records as .mgd.php files.
Specify the name of the entity and the id.
The file will be saved to the managed directory.

This command also works to export Afforms to the ang directory.

The command has some support for updating (re-exporting) managed records.
However, this is experimental. At time of writing, it does not interoperate
with most existing extensions+generators.
');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();

    $entityName = $input->getArgument('<EntityName>');
    $entityId = $input->getArgument('<EntityId>');

    // Boot CiviCRM to use api4
    Civix::boot(['output' => $output]);

    $upgrader = $this->getUpgrader();
    $upgrader->addMixins(['mgd-php@1.0']);
    if ($entityName === 'Afform') {
      $upgrader->exportAfform($entityId);
    }
    else {
      $upgrader->exportMgd($entityName, $entityId);
    }

    return 0;
  }

}
