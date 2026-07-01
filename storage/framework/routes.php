<?php

return array (
  0 => 
  array (
    'uri' => '/admin/reports/sales',
    'regex' => '#^/admin/reports/sales$#',
    'parameters' => 
    array (
    ),
    'priority' => 30003,
  ),
  1 => 
  array (
    'uri' => '/users/[id]/orders',
    'regex' => '#^/users/(?P<id>[^/]+)/orders$#',
    'parameters' => 
    array (
      'id' => 'single',
    ),
    'priority' => 21003,
  ),
  2 => 
  array (
    'uri' => '/products/[category]/[product]',
    'regex' => '#^/products/(?P<category>[^/]+)/(?P<product>[^/]+)$#',
    'parameters' => 
    array (
      'category' => 'single',
      'product' => 'single',
    ),
    'priority' => 12003,
  ),
  3 => 
  array (
    'uri' => '/users/[id]',
    'regex' => '#^/users/(?P<id>[^/]+)$#',
    'parameters' => 
    array (
      'id' => 'single',
    ),
    'priority' => 11002,
  ),
  4 => 
  array (
    'uri' => '/docs/[...slug]',
    'regex' => '#^/docs/(?P<slug>.+)$#',
    'parameters' => 
    array (
      'slug' => 'catchall',
    ),
    'priority' => 10102,
  ),
  5 => 
  array (
    'uri' => '/blog/[[...slug]]',
    'regex' => '#^/blog(?:/(?P<slug>.*))?$#',
    'parameters' => 
    array (
      'slug' => 'catchall',
    ),
    'priority' => 10002,
  ),
  6 => 
  array (
    'uri' => '/about',
    'regex' => '#^/about$#',
    'parameters' => 
    array (
    ),
    'priority' => 10001,
  ),
  7 => 
  array (
    'uri' => '/dashboard',
    'regex' => '#^/dashboard$#',
    'parameters' => 
    array (
    ),
    'priority' => 10001,
  ),
  8 => 
  array (
    'uri' => '/users',
    'regex' => '#^/users$#',
    'parameters' => 
    array (
    ),
    'priority' => 10001,
  ),
  9 => 
  array (
    'uri' => '/',
    'regex' => '#^/$#',
    'parameters' => 
    array (
    ),
    'priority' => 0,
  ),
);
