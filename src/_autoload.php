<?php

namespace Greebo\Mustache;
$_map = array (
  'Greebo\\Mustache\\Mustache' => __DIR__ . '/Mustache.php',
);
spl_autoload_register(function($class) use ($_map) {
    if (array_key_exists($class, $_map)) {
        require_once $_map[$class];
    }
});