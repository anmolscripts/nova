<?php

return array (
  '/admin/reports/sales' => 
  array (
    'path' => 'app/admin/reports/sales',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
      1 => 'app/admin/layout.latte',
      2 => 'app/admin/reports/layout.latte',
    ),
    'assets' => 
    array (
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
    ),
    'regex' => '#^/admin/reports/sales$#',
  ),
  '/users/[id]/orders' => 
  array (
    'path' => 'app/users/[id]/orders',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
      1 => 'app/users/layout.latte',
    ),
    'assets' => 
    array (
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
      'id' => 'single',
    ),
    'regex' => '#^/users/(?P<id>[^/]+)/orders$#',
  ),
  '/products/[category]/[product]' => 
  array (
    'path' => 'app/products/[category]/[product]',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
    ),
    'assets' => 
    array (
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
      'category' => 'single',
      'product' => 'single',
    ),
    'regex' => '#^/products/(?P<category>[^/]+)/(?P<product>[^/]+)$#',
  ),
  '/users/[id]' => 
  array (
    'path' => 'app/users/[id]',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
      1 => 'app/users/layout.latte',
    ),
    'assets' => 
    array (
      0 => 'page.ts',
      1 => 'page.scss',
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
      'id' => 'single',
    ),
    'regex' => '#^/users/(?P<id>[^/]+)$#',
  ),
  '/about' => 
  array (
    'path' => 'app/about',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
    ),
    'assets' => 
    array (
      0 => 'page.ts',
      1 => 'page.scss',
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
    ),
    'regex' => '#^/about$#',
  ),
  '/dashboard' => 
  array (
    'path' => 'app/dashboard',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
    ),
    'assets' => 
    array (
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
    ),
    'regex' => '#^/dashboard$#',
  ),
  '/users' => 
  array (
    'path' => 'app/users',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
      1 => 'app/users/layout.latte',
    ),
    'assets' => 
    array (
      0 => 'page.scss',
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
    ),
    'regex' => '#^/users$#',
  ),
  '/' => 
  array (
    'path' => 'app',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
    ),
    'assets' => 
    array (
      0 => 'page.ts',
      1 => 'page.scss',
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
    ),
    'regex' => '#^/$#',
  ),
  '/docs/[...slug]' => 
  array (
    'path' => 'app/docs/[...slug]',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
    ),
    'assets' => 
    array (
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
      'slug' => 'catchall',
    ),
    'regex' => '#^/docs/(?P<slug>.+)$#',
  ),
  '/blog/[[...slug]]' => 
  array (
    'path' => 'app/blog/[[...slug]]',
    'template' => 'page.latte',
    'server' => 'page.php',
    'layouts' => 
    array (
      0 => 'app/layout.latte',
    ),
    'assets' => 
    array (
    ),
    'loading' => NULL,
    'error' => NULL,
    'routeParameters' => 
    array (
      'slug' => 'catchall',
    ),
    'regex' => '#^/blog(?:/(?P<slug>.*))?$#',
  ),
);
