<?php
echo "<?php\n";
?>

class <?php echo $baoClassName ?> extends <?php echo $daoClassName ?> {

  /**
   * Create a new <?php echo $entityNameCamel ?> based on array-data
   *
   * @param array $params key-value pairs
   * @return <?php echo $daoClassName ?>|NULL
   *
  public static function create($params) {
    $className = '<?php echo $daoClassName ?>';
    $entityName = '<?php echo $entityNameCamel ?>';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
