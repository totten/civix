<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('CRMClientBundle_homepage', new Route('/hello/{name}', array(
    '_controller' => 'CRMClientBundle:Default:index',
)));

return $collection;
