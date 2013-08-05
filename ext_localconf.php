<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

Tx_Flux_Core::unregisterConfigurationProvider('Tx_Fed_Provider_Configuration_PageConfigurationProvider');
Tx_Flux_Core::registerConfigurationProvider('Tx_Fluidpages_Provider_PageProvider');

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'Page',
	array(
		'Page' => 'render,error',
	),
	array(
	),
	Tx_Extbase_Utility_Extension::PLUGIN_TYPE_PLUGIN
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ($GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] == '' ? '' : ',') . 'tx_fed_page_controller_action,tx_fed_page_controller_action_sub,tx_fed_page_flexform,';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\View\\BackendLayoutView'] =
	array('className' => 'Tx_Fluidpages_Override_Backend_View_BackendLayoutView');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\View\\PageLayoutView'] =
	array('className' => 'Tx_Fluidpages_Override_Backend_View_PageLayoutView');