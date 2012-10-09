<?php

namespace CRM\ClientBundle;

/**
 * An adaptor which allows us to use CiviCRM's 'class.api.php' as
 * a Symfony service.
 */
class ClientFactory {

    /**
     * Instantiate a configured API connection
     *
     * @return civicrm_api3
     */
    public function get($server, $path, $api_key, $key, $conf_path) {
        require_once __DIR__ . '/class.api.php';
        $config = array();
        foreach (array('server', 'path', 'api_key', 'key', 'conf_path') as $var) {
            if (!empty($$var)) {
                $config[$var] = $$var;
            }
        }
        if (empty($config['server']) && empty($config['conf_path'])) {
            throw new \Exception('Cannot instantiate API client -- please set connection options in parameters.yml');
        }
        return new \civicrm_api3($config);
    }
}
