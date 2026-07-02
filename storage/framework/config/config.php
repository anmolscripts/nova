<?php

return array (
  'app' => 
  array (
    'name' => 'Nova',
    'env' => 'production',
    'debug' => false,
    'url' => 'http://localhost:8000',
    'timezone' => 'UTC',
    'providers' => 
    array (
    ),
    'optimize' => 
    array (
      'compilers' => 
      array (
        0 => 'Nova\\Console\\Optimization\\ConfigCompiler',
        1 => 'Nova\\Console\\Optimization\\RouteCompiler',
        2 => 'Nova\\Console\\Optimization\\PageCompiler',
        3 => 'Nova\\Console\\Optimization\\LayoutCompiler',
        4 => 'Nova\\Console\\Optimization\\ComponentCompiler',
        5 => 'Nova\\Console\\Optimization\\ViewCompiler',
        6 => 'Nova\\Console\\Optimization\\AssetCompiler',
      ),
    ),
  ),
  'auth' => 
  array (
    'driver' => 'session',
    'login' => '/login',
    'redirect' => '/',
    'provider' => 
    array (
      'driver' => 'database',
      'connection' => NULL,
      'table' => 'users',
      'identifier' => 'email',
      'id' => 'id',
      'password' => 'password',
      'remember_token' => 'remember_token',
      'class' => NULL,
    ),
    'remember' => 
    array (
      'enabled' => true,
      'cookie' => 'nova_remember',
      'lifetime' => 2592000,
      'secure' => false,
      'http_only' => true,
      'same_site' => 'Lax',
    ),
  ),
  'cache' => 
  array (
    'path' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/cache',
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'logging' => 
    array (
      'enabled' => false,
      'path' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/logs/database.log',
    ),
    'connections' => 
    array (
      'mysql' => 
      array (
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'nova',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'nova',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'nova',
        'username' => 'postgres',
        'password' => '',
      ),
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'database' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/database.sqlite',
      ),
    ),
  ),
  'middleware' => 
  array (
    'global' => 
    array (
      0 => 'Nova\\Middleware\\StartSession',
      1 => 'Nova\\Middleware\\RestoreRememberedUser',
      2 => 'Nova\\Middleware\\VerifyCsrfToken',
      3 => 'Nova\\Middleware\\PageMiddleware',
      4 => 'Nova\\Middleware\\DispatchServerAction',
    ),
  ),
  'session' => 
  array (
    'name' => 'nova_session',
    'lifetime' => 120,
    'path' => '/',
    'secure' => false,
    'http_only' => true,
    'same_site' => 'Lax',
  ),
  'storage' => 
  array (
    'default' => 'local',
    'temporary_lifetime' => 3600,
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/app',
      ),
      'public' => 
      array (
        'driver' => 'public',
        'root' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/public',
        'url' => 'http://localhost:8000/storage',
      ),
      'temporary' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/temporary',
      ),
      'uploads' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/uploads',
      ),
      'memory' => 
      array (
        'driver' => 'memory',
      ),
    ),
  ),
  'ui' => 
  array (
    'toast' => 
    array (
      'duration' => 4000,
    ),
    'loading' => 
    array (
      'class' => 'nova-is-loading',
    ),
    'progress' => 
    array (
      'color' => '#2563eb',
    ),
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'C:\\Users\\User\\Documents\\php\\framework\\resources/views',
      1 => 'C:\\Users\\User\\Documents\\php\\framework\\app',
      2 => 'C:\\Users\\User\\Documents\\php\\framework\\components',
    ),
    'cache' => 'C:\\Users\\User\\Documents\\php\\framework\\storage/framework/views',
    'layout' => 'layout.latte',
    'assets' => 
    array (
      'dev_server' => 'http://127.0.0.1:5173',
      'manifest' => 'C:\\Users\\User\\Documents\\php\\framework\\public/assets/manifest.json',
      'build_path' => 'assets',
      'entries' => 
      array (
        'app.ts' => 'resources/ts/app.ts',
        'app.scss' => 'resources/scss/app.scss',
      ),
    ),
  ),
);
