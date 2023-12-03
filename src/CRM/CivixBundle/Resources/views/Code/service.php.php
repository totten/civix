<?php
echo "<" . "?php\n";
if ($classNamespaceDecl) {
  echo "$classNamespaceDecl\n\n";
}
echo "$useE\n";
?>
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service <?php echo "$service\n"; ?>
 */
class <?php echo $className; ?> extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      // '&hook_civicrm_alterContent' => ['onAlterContent', 0],
      // '&hook_civicrm_postCommit::Contribution' => ['onContribute', 0],
      // TIP: For hooks based on GenericHookEvent, the "&" will expand arguments.
    ];
  }

  // /**
  //  * @see \CRM_Utils_Hook::alterContent
  //  */
  // public function onAlterContent(&$content, $context, $tplName, &$object) { ... }

  // /**
  //  * @see \CRM_Utils_Hook::postCommit
  //  */
  // public function onContribute($op, $objectName, $objectId, $objectRef = NULL) { ... }

}
