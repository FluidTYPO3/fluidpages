<?php
// Register composer autoloader
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new \RuntimeException(
        'Could not find vendor/autoload.php, make sure you ran composer.'
    );
}

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../vendor/autoload.php';
$autoloader->addPsr4('FluidTYPO3\\Flux\\Tests\\Fixtures\\', __DIR__ . '/../typo3conf/ext/flux/Tests/Fixtures/');
$autoloader->addPsr4('FluidTYPO3\\Flux\\Tests\\Unit\\', __DIR__ . '/../typo3conf/ext/flux/Tests/Unit/');
$autoloader->addPsr4('TYPO3\\CMS\\Core\\Tests\\', __DIR__ . '/../vendor/typo3/cms/typo3/sysext/core/Tests/');

\FluidTYPO3\Development\Bootstrap::initialize(
    $autoloader,
    array(
        'fluid_template' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
        'cache_core' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
        'flux' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
        'cache_runtime' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
        'l10n' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
        'extbase_object' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
        'extbase_reflection' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
        'extbase_datamapfactory_datamap' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
        'extbase_typo3dbbackend_tablecolumns' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
        'extbase_typo3dbbackend_queries' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL
    )
);
