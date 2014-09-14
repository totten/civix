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
    array()
  )
)->setFactoryService(
    'civicrm_api3_factory'
  )->setFactoryMethod(
    'get'
  );
