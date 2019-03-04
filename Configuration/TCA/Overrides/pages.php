<?php
defined ('TYPO3_MODE') or die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluidpages']);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_fed_page_controller_action' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_controller_action',
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => 'FluidTYPO3\Fluidpages\Backend\PageLayoutDataProvider->addItems',
            'fieldWizard' => [
                'selectIcons' => [
                    'disabled' => false
                ]
            ]
        ]
    ],
    'tx_fed_page_controller_action_sub' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_controller_action_sub',
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => 'FluidTYPO3\Fluidpages\Backend\PageLayoutDataProvider->addItems',
            'fieldWizard' => [
                'selectIcons' => [
                    'disabled' => false
                ]
            ]
        ]
    ],
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

$doktypes = '0,1,4';
$additionalDoktypes = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['doktypes'], ',');
if (FALSE === empty($additionalDoktypes)) {
    $doktypes .= ',' . $additionalDoktypes;
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_layoutselect,tx_fed_page_controller_action,tx_fed_page_controller_action_sub',
    $doktypes
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_configuration,tx_fed_page_flexform,tx_fed_page_flexform_sub',
    $doktypes
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_fluidpages_templatefile' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fluidpages_templatefile',
        'onChange' => 'reload',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputLink',
            'eval' => 'trim',
            'placeholder' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fluidpages_templatefile.placeholder',
            'wizards' => [
                '_PADDING' => 2,
                'link' => [
                    'type' => 'popup',
                    'title' => 'LLL:EXT:cms/locallang_ttc.xml:header_link_formlabel',
                    'icon' => 'actions-wizard-link',
                    'module' => [
                        'name' => 'wizard_link',
                        'urlParameters' => [
                            'mode' => 'wizard',
                            'act' => 'file'
                        ]
                    ],
                    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                ],
            ]
        ]
    ],
    'tx_fluidpages_layout' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fluidpages_layout',
        'displayCond' => 'FIELD:tx_fluidpages_templatefile:!=:',
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => 'FluidTYPO3\Fluidpages\Backend\TemplateFileLayoutSelector->addLayoutOptions',
            'arguments' => [
                'referring_field' => 'tx_fluidpages_templatefile'
            ]
        ]
    ],
]);

unset($doktypes, $additionalDoktypes, $doktypeIcon);
