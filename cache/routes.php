<?php return array (
  0 => 
  array (
    'path' => '/admin/index',
    'preg_path' => '#^/admin/index$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'index',
    'middlewares' => 
    array (
      0 => 'App\\Middleware\\LogRequestMiddleware',
    ),
  ),
  1 => 
  array (
    'path' => '/admin/',
    'preg_path' => '#^/admin$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'index',
    'middlewares' => 
    array (
      0 => 'App\\Middleware\\LogRequestMiddleware',
    ),
  ),
  2 => 
  array (
    'path' => '/admin/info/{aid}',
    'preg_path' => '#^/admin/info/(?P<aid>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getInfo',
    'middlewares' => 
    array (
    ),
  ),
  3 => 
  array (
    'path' => '/admin/department',
    'preg_path' => '#^/admin/department$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'createDepartment',
    'middlewares' => 
    array (
    ),
  ),
  4 => 
  array (
    'path' => '/admin/user/{uid}/order/{oid}',
    'preg_path' => '#^/admin/user/(?P<uid>[^/]*)/order/(?P<oid>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getOrder',
    'middlewares' => 
    array (
    ),
  ),
  5 => 
  array (
    'path' => '/admin/user/{id}',
    'preg_path' => '#^/admin/user/(?P<id>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getUser',
    'middlewares' => 
    array (
    ),
  ),
  6 => 
  array (
    'path' => '/admin/users',
    'preg_path' => '#^/admin/users$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'createUser',
    'middlewares' => 
    array (
    ),
  ),
  7 => 
  array (
    'path' => '/admin/{id}/posts',
    'preg_path' => '#^/admin/(?P<id>[^/]*)/posts$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getUserWithPosts',
    'middlewares' => 
    array (
    ),
  ),
  8 => 
  array (
    'path' => '/admin/posts/{id}',
    'preg_path' => '#^/admin/posts/(?P<id>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getPost',
    'middlewares' => 
    array (
    ),
  ),
  9 => 
  array (
    'path' => '/admin/posts/{id}/tags',
    'preg_path' => '#^/admin/posts/(?P<id>[^/]*)/tags$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getPostWithTags',
    'middlewares' => 
    array (
    ),
  ),
  10 => 
  array (
    'path' => '/admin/postsa/tags',
    'preg_path' => '#^/admin/postsa/tags$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'getAllPostWithTags',
    'middlewares' => 
    array (
    ),
  ),
  11 => 
  array (
    'path' => '/admin/products',
    'preg_path' => '#^/admin/products$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\admin\\IndexController',
    'action' => 'createProduct',
    'middlewares' => 
    array (
    ),
  ),
  12 => 
  array (
    'path' => '/submit-data',
    'preg_path' => '#^/submit-data$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'submitData',
    'middlewares' => 
    array (
      0 => 'App\\Middleware\\VerifyCsrfTokenMiddleware',
    ),
  ),
  13 => 
  array (
    'path' => '/',
    'preg_path' => '#^/$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'indexView',
    'middlewares' => 
    array (
      0 => 'App\\Middleware\\VerifyCsrfTokenMiddleware',
    ),
  ),
  14 => 
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
      0 => 'App\\Middleware\\LogRequestMiddleware',
    ),
  ),
  15 => 
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
  16 => 
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
  17 => 
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
  18 => 
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
  19 => 
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
  20 => 
  array (
    'path' => '/{id}/posts',
    'preg_path' => '#^/(?P<id>[^/]*)/posts$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getUserWithPosts',
    'middlewares' => 
    array (
    ),
  ),
  21 => 
  array (
    'path' => '/posts/{id}',
    'preg_path' => '#^/posts/(?P<id>[^/]*)$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getPost',
    'middlewares' => 
    array (
    ),
  ),
  22 => 
  array (
    'path' => '/posts/{id}/tags',
    'preg_path' => '#^/posts/(?P<id>[^/]*)/tags$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getPostWithTags',
    'middlewares' => 
    array (
    ),
  ),
  23 => 
  array (
    'path' => '/postsa/tags',
    'preg_path' => '#^/postsa/tags$#',
    'methods' => 
    array (
      0 => 'GET',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'getAllPostWithTags',
    'middlewares' => 
    array (
    ),
  ),
  24 => 
  array (
    'path' => '/products',
    'preg_path' => '#^/products$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'createProduct',
    'middlewares' => 
    array (
    ),
  ),
  25 => 
  array (
    'path' => '/dto_demo',
    'preg_path' => '#^/dto_demo$#',
    'methods' => 
    array (
      0 => 'POST',
    ),
    'controller' => 'App\\Controller\\IndexController',
    'action' => 'dtoDemo',
    'middlewares' => 
    array (
    ),
  ),
);