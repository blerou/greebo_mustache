<?php

// test assets
set_include_path(get_include_path().PATH_SEPARATOR.realpath(__DIR__.'/../'));

// source
$baseDir = __DIR__ . '/../../src/';
if (realpath($baseDir)) {
	set_include_path(get_include_path().PATH_SEPARATOR.$baseDir);
}

spl_autoload_register(function($class) {
	if (0 !== strpos($class, 'greebo\\')) {
		return false;
	}
	require_once str_replace('\\', '/', $class).'.php';
	return true;
});