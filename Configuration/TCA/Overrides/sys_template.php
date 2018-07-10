<?php
defined ('TYPO3_MODE') or die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluidpages']);

if (!($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload'] ?? true)) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('fluidpages', 'Configuration/TypoScript', 'Fluidpages PAGE rendering');
}
