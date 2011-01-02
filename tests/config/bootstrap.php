<?php
// library autoloader
include __DIR__ . '/../../src/_autoload.php';

// test autoloader
spl_autoload_register(function($class) {
  if (!preg_match('/^GreeboTest\\\\Mustache\\\\TestAsset/', $class)) {
    return;
  }
  $path = str_replace('GreeboTest\\Mustache\\', '', $class);
  $file = sprintf('%s/../%s.php', __DIR__, strtr($path, '\\', '/'));
  include_once $file;
});
