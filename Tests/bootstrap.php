<?php
// Register composer autoloader
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
	throw new \RuntimeException(
		'Could not find vendor/autoload.php, make sure you ran composer.'
	);
}
require_once __DIR__ . '/../vendor/autoload.php';

\FluidTYPO3\Development\Bootstrap::initialize(array(
	'cache_core' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
	'extbase_object' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
	'extbase_reflection' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL
));
