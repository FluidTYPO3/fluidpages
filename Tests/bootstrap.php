<?php
// Register composer autoloader
$autoloaderFolders = [
    trim(shell_exec('pwd')) . '/vendor/',
    __DIR__ . '/../vendor/'
];
foreach ($autoloaderFolders as $autoloaderFolder) {
    if (file_exists($autoloaderFolder . 'autoload.php')) {
        /** @var Composer\Autoload\ClassLoader $autoloader */
        $autoloader = require $autoloaderFolder . 'autoload.php';
        if (!getenv('TYPO3_PATH_ROOT')) {
            $path = realpath($autoloaderFolder . '../') . '/';
            $pwd = trim(shell_exec('pwd'));
            if (file_exists($pwd . '/composer.json')) {
                $json = json_decode(file_get_contents($pwd . '/composer.json'), true);
                if ($json['extra']['typo3/cms']['web-dir'] ?? false) {
                    $path .= $json['extra']['typo3/cms']['web-dir'] . '/';
                }
            }
            putenv('TYPO3_PATH_ROOT=' . $path);
        }
        break;
    }
}

if (!isset($autoloader)) {
    throw new \RuntimeException(
        'Could not find autoload.php, make sure you ran composer.'
    );
}

$autoloader->addPsr4('FluidTYPO3\\Flux\\Tests\\', __DIR__ . '/../typo3conf/ext/flux/Tests/');
$autoloader->addPsr4('FluidTYPO3\\Fluidpages\\Tests\\', __DIR__ . '/../typo3conf/ext/flux/Tests/');
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
