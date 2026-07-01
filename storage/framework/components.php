<?php

return array (
  'global:Button' => 
  array (
    'name' => 'Button',
    'path' => 'components/Button',
    'template' => 'component.latte',
    'server' => 'component.php',
    'assets' => 
    array (
      0 => 'component.ts',
      1 => 'component.scss',
    ),
    'scope' => 'global',
  ),
  'global:Card' => 
  array (
    'name' => 'Card',
    'path' => 'components/Card',
    'template' => 'component.latte',
    'server' => 'component.php',
    'assets' => 
    array (
      0 => 'component.scss',
    ),
    'scope' => 'global',
  ),
  'global:Icon' => 
  array (
    'name' => 'Icon',
    'path' => 'components/Icon',
    'template' => 'component.latte',
    'server' => NULL,
    'assets' => 
    array (
      0 => 'component.scss',
    ),
    'scope' => 'global',
  ),
  'global:StatsCard' => 
  array (
    'name' => 'StatsCard',
    'path' => 'components/StatsCard',
    'template' => 'component.latte',
    'server' => 'component.php',
    'assets' => 
    array (
      0 => 'component.scss',
    ),
    'scope' => 'global',
  ),
  'app/dashboard:StatsCard' => 
  array (
    'name' => 'StatsCard',
    'path' => 'app/dashboard/components/StatsCard',
    'template' => 'component.latte',
    'server' => 'component.php',
    'assets' => 
    array (
      0 => 'component.ts',
      1 => 'component.scss',
    ),
    'scope' => 'app/dashboard',
  ),
);
