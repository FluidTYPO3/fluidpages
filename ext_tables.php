<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluid Pages: PAGE');

t3lib_div::loadTCA('pages');
t3lib_extMgm::addTCAcolumns('pages', array(
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
), 1);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($_EXTCONF);
$doktypes = '0,1,4';
$fields = 'tx_fed_page_controller_action,tx_fed_page_controller_action_sub,tx_fed_page_flexform';
$position = 'before:layout';
$additionalDoktypes = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['doktypes'], ',');
if (FALSE === empty($additionalDoktypes)) {
	$doktypes .= ',' . $additionalDoktypes;
	$fields = '--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xml:pages.tx_fed_page_layoutselect,' . $fields;
	$position = '';
}
t3lib_extMgm::addToAllTCAtypes(
	'pages',
	$fields,
	$doktypes,
	$position
);
