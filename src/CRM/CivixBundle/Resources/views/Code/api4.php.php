<?php echo "<?php\n"; ?>
namespace Civi\Api4;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\BasicGetFieldsAction;

/**
*  Class <?php echo $entityNameCamel ?>.
*
* Provided by the <?php echo $extensionName ?> extension.
*
* @package Civi\Api4
*/
class <?php echo $entityNameCamel ?> extends Generic\AbstractEntity {

  /**
   * <?php echo $entityNameCamel ?> <?php echo $actionNameCamel ?>.
   *
   * @return \Civi\Api4\Action\<?php echo $entityNameCamel ?>\<?php echo $actionNameCamel ?>
   *
   * @param bool $checkPermissions
   *
   * @throws \API_Exception
   */
  public static function <?php echo $actionNameLower ?> ($checkPermissions = TRUE): Action\<?php echo $entityNameCamel ?>\<?php echo $actionNameCamel ?> {
    return (new \Civi\Api4\Action\<?php echo $entityNameCamel ?>\<?php echo $actionNameCamel ?>(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

 /**
  * Get permissions.
  *
  * It may be that we don't need a permission check on this api at all at there is a check on the entity
  * retrieved.
  *
  * @return array
  */
  public static function permissions():array {
    return ['<?php echo $actionNameLower ?>' => 'administer CiviCRM'];
  }

 /**
  * @return \Civi\Api4\Generic\BasicGetFieldsAction
  */
  public static function getFields() {
    return new BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    });
  }

}
