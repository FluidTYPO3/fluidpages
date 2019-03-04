<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

(function() use ($_EXTKEY, $_EXTCONF) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup'] = unserialize($_EXTCONF);

    \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\PageProvider');
    \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\SubPageProvider');
    if (version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('core'), 9.0, '<')) {
        \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\PageLanguageOverlayProvider');
        \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidpages\Provider\SubPageLanguageOverlayProvider');
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FluidTYPO3.Fluidpages',
        'Page',
        [
            'Page' => 'render,error',
        ],
        [],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['fluidpages'] = \FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] = \FluidTYPO3\Fluidpages\Hooks\PagePreviewRenderer::class . '->render';

    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['autoload'] ?? true) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fluidpages', 'Configuration/TypoScript/constants.txt')));
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fluidpages', 'Configuration/TypoScript/setup.txt')));
    }

    $doktypeIcon = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:fluidpages/doktype_icon.png');
    $GLOBALS['PAGES_TYPES'][\FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = [
        'type' => 'web',
        'icon' => $doktypeIcon,
        'allowedTables' => '*'
    ];


    $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-pages-' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW] = $doktypeIcon;

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . \FluidTYPO3\Fluidpages\Controller\PageControllerInterface::DOKTYPE_RAW . ')'
    );


    if (TRUE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['pagesLanguageConfigurationOverlay'])
        && TRUE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidpages']['setup']['pagesLanguageConfigurationOverlay']) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_fed_page_flexform,tx_fed_page_flexform_sub';
    }

    $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ($GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] == '' ? '' : ',') .
        'tx_fed_page_controller_action,tx_fed_page_controller_action_sub,tx_fed_page_flexform,tx_fed_page_flexform_sub,';

    unset($doktypeIcon);
})();
