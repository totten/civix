<?php echo "<?php\n"; ?>
namespace Civi\Api4\Action\<?php echo $entityNameCamel ?>;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 *  Class <?php echo $actionNameCamel ?>.
 *
 * Provided by the <?php echo $extensionName ?> extension.
 *
 * @package Civi\Api4
 */
class <?php echo $actionNameCamel ?> extends AbstractAction {

 /**
  * @inheritDoc
  *
  * @param \Civi\Api4\Generic\Result $result
  *
  * @throws \API_Exception
  */
  public function _run(Result $result) {

  }

}
