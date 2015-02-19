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

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'FluidTYPO3.Fluidpages',
	'Page',
	array(
		'Page' => 'render,error',
	),
	array(
	),
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ($GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] == '' ? '' : ',') .
	'tx_fed_page_controller_action,tx_fed_page_controller_action_sub,tx_fed_page_flexform,tx_fed_page_flexform_sub,';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['fluidpages'] =
	'FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider';

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'][] = 'FluidTYPO3\\Fluidpages\\Hooks\\BackendDocumentTemplate->removeFluidContentAreaSection';
}
