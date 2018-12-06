<?php
defined ('TYPO3_MODE') or die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluidpages']);


if (TRUE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['pagesLanguageConfigurationOverlay'])
    && TRUE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['pagesLanguageConfigurationOverlay']) {

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages_language_overlay', [
        'tx_fed_page_flexform' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_flexform',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '<T3DataStructure><ROOT><el></el></ROOT></T3DataStructure>'
                ]
            ]
        ],
        'tx_fed_page_flexform_sub' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_flexform_sub',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '<T3DataStructure><ROOT><el></el></ROOT></T3DataStructure>'
                ]                
            ]
        ],
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages_language_overlay',
        '--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_configuration,tx_fed_page_flexform,tx_fed_page_flexform_sub'
    );
}


