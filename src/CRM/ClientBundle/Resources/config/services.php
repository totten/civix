<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

$container->setDefinition('civicrm_api3_factory', new Definition(
    'CRM\ClientBundle\ClientFactory'
));

$container->setDefinition(
    'civicrm_api3',
    new Definition(
        'Ignore',
        array(
            new Parameter('civicrm_api3_server'),
            new Parameter('civicrm_api3_path'),
            new Parameter('civicrm_api3_api_key'),
            new Parameter('civicrm_api3_key'),
            new Parameter('civicrm_api3_conf_path'),
        )
    )
)->setFactoryService(
  'civicrm_api3_factory'
)->setFactoryMethod(
  'get'
);
