<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($_EXTCONF);

if (FALSE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload'])
	|| TRUE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload']) {
	\FluidTYPO3\Flux\Core::addStaticTypoScript('EXT:fluidpages/Configuration/TypoScript/');
}

\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\PageProvider');
\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\SubPageProvider');
\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\PageLanguageOverlayProvider');
\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\SubPageLanguageOverlayProvider');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'FluidTYPO3.Fluidpages',
	'Page',
	[
		'Page' => 'render,error',
	],
	[],
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['fluidpages'] = 'FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] = \FluidTYPO3\Fluidpages\Hooks\PagePreviewRenderer::class . '->render';

if (TRUE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload'])
    && FALSE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluidpages PAGE rendering');
}

$doktypeIcon = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fluidpages') . 'doktype_icon.png';
$GLOBALS['PAGES_TYPES'][\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = [
    'type' => 'web',
    'icon' => $doktypeIcon,
    'allowedTables' => '*'
];


$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-pages-' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = $doktypeIcon;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
    'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW . ')'
);

unset($doktypeIcon);
