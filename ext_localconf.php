<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($_EXTCONF);

\FluidTYPO3\Flux\Core::unregisterConfigurationProvider('Tx_Fed_Provider_Configuration_PageConfigurationProvider');
\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\PageProvider');
\FluidTYPO3\Flux\Core::addStaticTypoScript('EXT:fluidpages/Configuration/TypoScript/');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$_EXTKEY,
	'Page',
	array(
		'Page' => 'render,error',
	),
	array(
	),
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ($GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] == '' ? '' : ',') . 'tx_fed_page_controller_action,tx_fed_page_controller_action_sub,tx_fed_page_flexform,tx_fed_page_flexform_sub,';

if (6.2 <= (float) substr(TYPO3_version, 0, 3)) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['fluidpages'] =
		'FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider';
} else {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\View\\BackendLayoutView'] =
		array('className' => 'FluidTYPO3\Fluidpages\Override\Backend\View\BackendLayoutView');
}


