<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($_EXTCONF);

if (TRUE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload'])
	&& FALSE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload']) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluidpages PAGE rendering');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
	'tx_fed_page_controller_action' => [
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_controller_action',
		'config' => [
			'type' => 'user',
			'userFunc' => 'FluidTYPO3\Fluidpages\Backend\PageLayoutSelector->renderField'
		]
	],
	'tx_fed_page_controller_action_sub' => [
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_controller_action_sub',
		'config' => [
			'type' => 'user',
			'userFunc' => 'FluidTYPO3\Fluidpages\Backend\PageLayoutSelector->renderField'
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

$doktypeIcon = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fluidpages') . 'doktype_icon.png';

$GLOBALS['PAGES_TYPES'][\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = [
	'type' => 'web',
	'icon' => $doktypeIcon,
	'allowedTables' => '*'
];

$GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'][] = [
	'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.doktype.' .
		\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW,
	\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW,
	$doktypeIcon
];

$GLOBALS['TCA']['pages_language_overlay']['columns']['doktype']['config']['items'][] = [
	'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.doktype.' .
		\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW,
	\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW,
	$doktypeIcon
];

$GLOBALS['TCA']['pages']['types'][\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW]['showitem'] = '
--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
	--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;visibility,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
	--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.metatags;metatags,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
	--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.links;links,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.caching;caching,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.language;language,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;miscellaneous,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.module;module,
	--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
	--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.storage;storage,
	--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category, categories';

$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-pages-' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = $doktypeIcon;
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = 'tcarecords-pages-' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
	'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW . ')'
);

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
		'config' => [
			'type' => 'input',
			'eval' => 'trim',
			'placeholder' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fluidpages_templatefile.placeholder',
			'wizards' => [
				'_PADDING' => 2,
				'link' => [
					'type' => 'popup',
					'title' => 'LLL:EXT:cms/locallang_ttc.xml:header_link_formlabel',
					'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages', 'tx_fluidpages_templatefile, tx_fluidpages_layout', \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW, 'before:title'
);
$GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= ',tx_fluidpages_templatefile';

unset($doktypes, $additionalDoktypes, $doktypeIcon);

if (TRUE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['pagesLanguageConfigurationOverlay'])
	&& TRUE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['pagesLanguageConfigurationOverlay']) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages_language_overlay', [
		'tx_fed_page_flexform' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_flexform',
			'config' => [
				'type' => 'flex',
			]
		],
		'tx_fed_page_flexform_sub' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_flexform_sub',
			'config' => [
				'type' => 'flex',
			]
		],
	]);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
		'pages_language_overlay',
		'--div--;LLL:EXT:fluidpages/Resources/Private/Language/locallang.xlf:pages.tx_fed_page_configuration,tx_fed_page_flexform,tx_fed_page_flexform_sub'
	);

	$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_fed_page_flexform,tx_fed_page_flexform_sub';
}
