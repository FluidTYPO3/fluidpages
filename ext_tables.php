<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($_EXTCONF);
if (TRUE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoRender']) {
	Tx_Flux_Core::addGlobalTypoScript('EXT:fluidpages/Configuration/TypoScript');
} else {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluid Pages: PAGE');
}

\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('pages');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', array(
	'tx_fed_page_controller_action' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_controller_action',
		'config' => array (
			'type' => 'user',
			'userFunc' => 'Tx_Fluidpages_Backend_PageLayoutSelector->renderField'
		)
	),
	'tx_fed_page_controller_action_sub' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_controller_action_sub',
		'config' => array (
			'type' => 'user',
			'userFunc' => 'Tx_Fluidpages_Backend_PageLayoutSelector->renderField'
		)
	),
	'tx_fed_page_flexform' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_flexform',
		'config' => array (
			'type' => 'flex',
		)
	),
	'tx_fed_page_flexform_sub' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_flexform_sub',
		'config' => array (
			'type' => 'flex',
		)
	),
), 1);

$doktypes = '0,1,4';
$additionalDoktypes = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['doktypes'], ',');
if (FALSE === empty($additionalDoktypes)) {
	$doktypes .= ',' . $additionalDoktypes;
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages',
	'--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_layoutselect,tx_fed_page_controller_action,tx_fed_page_controller_action_sub',
	$doktypes
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages',
	'--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_configuration,tx_fed_page_flexform,tx_fed_page_flexform_sub',
	$doktypes
);

unset($doktypes, $additionalDoktypes);
