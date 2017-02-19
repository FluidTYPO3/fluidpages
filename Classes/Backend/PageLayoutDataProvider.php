<?php
namespace FluidTYPO3\Fluidpages\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class for provisioning page layout selections for backend form fields
 */
class PageLayoutDataProvider
{

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var array
     */
    protected $recognizedFormats = ['html', 'xml', 'txt', 'json', 'js', 'css'];

    /**
     * @var PageService
     */
    protected $pageService;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param ConfigurationService $configurationService
     * @return void
     */
    public function injectConfigurationService(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param PageService $pageService
     * @return void
     */
    public function injectPageService(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->injectConfigurationManager($objectManager->get(ConfigurationManagerInterface::class));
        $this->injectConfigurationService($objectManager->get(ConfigurationService::class));
        $this->injectPageService($objectManager->get(PageService::class));
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function addItems(array $parameters)
    {
        $typoScript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $settings = GeneralUtility::removeDotsFromTS((array) $typoScript['plugin.']['tx_fluidpages.']);
        if (isset($settings['siteRootInheritance'])) {
            $hideInheritFieldSiteRoot = 1 > $settings['siteRootInheritance'];
        } else {
            $hideInheritFieldSiteRoot = false;
        }
        $pageIsSiteRoot = (boolean) ($parameters['row']['is_siteroot']);
        $forceDisplayInheritSiteRoot = 'tx_fed_page_controller_action_sub' === $parameters['field']
            && !$hideInheritFieldSiteRoot;
        $forceHideInherit = (boolean) (0 === intval($parameters['row']['pid']));
        if (!$forceHideInherit) {
            if (!$pageIsSiteRoot || $forceDisplayInheritSiteRoot || !$hideInheritFieldSiteRoot) {
                $emptyLabel = LocalizationUtility::translate(
                    'pages.tx_fed_page_controller_action.default',
                    'Fluidpages'
                );
                $parameters['items'][] = [
                    $emptyLabel,
                    '',
                    'actions-move-down'
                ];
            }
        }
        $availableTemplates = $this->pageService->getAvailablePageTemplateFiles();
        foreach ($availableTemplates as $extension => $group) {
            $parameters['items'] = array_merge(
                $parameters['items'],
                $this->renderOptions($extension, $group, $parameters)
            );
        }
    }

    /**
     * @param string $extension
     * @param Form[] $group
     * @param array $parameters
     * @return string
     */
    protected function renderOptions($extension, array $group, array $parameters)
    {
        $options = [];
        if (false === empty($group)) {
            $extensionKey = ExtensionNamingUtility::getExtensionKey($extension);
            if (false === ExtensionManagementUtility::isLoaded($extensionKey)) {
                $groupTitle = ucfirst($extension);
            } else {
                $emConfigFile = ExtensionManagementUtility::extPath($extensionKey, 'ext_emconf.php');
                require $emConfigFile;
                $groupTitle = $EM_CONF['']['title'];
            }

            $options[] = [$groupTitle, '--div--'];
            foreach ($group as $form) {
                $options[] = $this->renderOption($form, $parameters);
            }
        }
        return $options;
    }

    /**
     * @param Form $form
     * @param array $parameters
     * @return string
     */
    protected function renderOption(Form $form, array $parameters)
    {
        $option = [];
        try {
            $extension = $form->getExtensionName();
            $thumbnail = MiscellaneousUtility::getIconForTemplate($form);
            if (NULL !== $thumbnail) {
                $thumbnail = ltrim($thumbnail, '/');
                $thumbnail = MiscellaneousUtility::createIcon(GeneralUtility::getFileAbsFileName($thumbnail), 64, 64);
            }
            $template = pathinfo($form->getOption(Form::OPTION_TEMPLATEFILE), PATHINFO_FILENAME);
            $formLabel = $form->getLabel();
            if (strpos($formLabel, 'LLL:') === 0) {
                $label = LocalizationUtility::translate($formLabel, $extension);
            } else {
                $label = $formLabel;
            }
            $optionValue = $extension . '->' . lcfirst($template);
            $option = [$label, $optionValue, $thumbnail];

        } catch (\RuntimeException $error) {
            $this->configurationService->debug($error);
        }
        return $option;
    }
}
