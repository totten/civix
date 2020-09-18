<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>
use <?php echo $_namespace ?>_ExtensionUtil as E;

/**
 * <?php echo $entityNameCamel ?>.create API specification (optional).
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _<?php echo $apiFunctionPrefix ?>create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * <?php echo $entityNameCamel ?>.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function <?php echo $apiFunctionPrefix ?>create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, <?php var_export($entityNameCamel); ?>);
}

/**
 * <?php echo $entityNameCamel ?>.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function <?php echo $apiFunctionPrefix ?>delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * <?php echo $entityNameCamel ?>.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function <?php echo $apiFunctionPrefix ?>get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, <?php var_export($entityNameCamel); ?>);
}
