<?php
// Register composer autoloader
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
	throw new \RuntimeException(
		'Could not find vendor/autoload.php, make sure you ran composer.'
	);
}

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../vendor/autoload.php';
$autoloader->addPsr4('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\', __DIR__ . '/Fixtures/');
$autoloader->addPsr4('FluidTYPO3\\Fluidpages\\Tests\\Unit\\', __DIR__ . '/Unit/');
$autoloader->addPsr4('FluidTYPO3\\Flux\\Tests\\Fixtures\\', __DIR__ . '/../vendor/fluidtypo3/flux/Tests/Fixtures/');
$autoloader->addPsr4('FluidTYPO3\\Flux\\Tests\\Unit\\', __DIR__ . '/../vendor/fluidtypo3/flux/Tests/Unit/');
$autoloader->addPsr4('TYPO3\\CMS\\Core\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/core/Classes/');
$autoloader->addPsr4('TYPO3\\CMS\\Core\\Tests\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/core/Tests/');
$autoloader->addPsr4('TYPO3\\CMS\\Extbase\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/extbase/Classes/');
$autoloader->addPsr4('TYPO3\\CMS\\Fluid\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/fluid/Classes/');
$autoloader->addPsr4('TYPO3\\CMS\\Backend\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/backend/Classes/');
$autoloader->addPsr4('TYPO3\\CMS\\Frontend\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/frontend/Classes/');

\FluidTYPO3\Development\Bootstrap::initialize(
	$autoloader,
	array(
		'fluid_template' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
		'cache_core' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
		'cache_runtime' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'l10n' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_object' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_reflection' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_datamapfactory_datamap' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_typo3dbbackend_tablecolumns' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_typo3dbbackend_queries' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL
	)
);

