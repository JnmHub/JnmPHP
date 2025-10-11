<?php return array (
  0 => 
  array (
    'path' => '/index',
    'preg_path' => '#^/index$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'index',
  ),
  1 => 
  array (
    'path' => '/',
    'preg_path' => '#^/$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'index',
  ),
  2 => 
  array (
    'path' => '/info',
    'preg_path' => '#^/info$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getInfo',
  ),
  3 => 
  array (
    'path' => '/create',
    'preg_path' => '#^/create$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'createUser',
  ),
  4 => 
  array (
    'path' => '/user/{uid}/order/{oid}',
    'preg_path' => '#^/user/(?P<uid>[^/]*)/order/(?P<oid>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getOrder',
  ),
  5 => 
  array (
    'path' => '/user/{id}',
    'preg_path' => '#^/user/(?P<id>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getUser',
  ),
);