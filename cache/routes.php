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
    'middlewares' => 
    array (
      0 => 'App\\Http\\Middleware\\LogRequestMiddleware',
    ),
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
    'middlewares' => 
    array (
      0 => 'App\\Http\\Middleware\\LogRequestMiddleware',
    ),
  ),
  2 => 
  array (
    'path' => '/info/{aid}',
    'preg_path' => '#^/info/(?P<aid>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getInfo',
    'middlewares' => 
    array (
    ),
  ),
  3 => 
  array (
    'path' => '/department',
    'preg_path' => '#^/department$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'createDepartment',
    'middlewares' => 
    array (
    ),
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
    'middlewares' => 
    array (
    ),
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
    'middlewares' => 
    array (
    ),
  ),
  6 => 
  array (
    'path' => '/users',
    'preg_path' => '#^/users$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'createUser',
    'middlewares' => 
    array (
    ),
  ),
);