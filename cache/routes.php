<?php return array (
  0 => 
  array (
    'path' => '/index',
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
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getUser',
  ),
  6 => 
  array (
    'path' => '/user/{uid}/order/{oid}',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'showOrder',
  ),
);