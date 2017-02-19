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
use FluidTYPO3\Flux\View\TemplatePaths;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that renders a Layout selector based on a template file
 */
class TemplateFileLayoutSelector
{

    /**
     * @var PageService
     */
    protected $pageService;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

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
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->injectConfigurationService($objectManager->get('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'));
        $this->injectPageService($objectManager->get('FluidTYPO3\\Fluidpages\\Service\\PageService'));
    }

    /**
     * Renders a Fluid Template Layout select field
     *
     * @param array $parameters
     * @param mixed $pObj
     * @return string
     */
    public function addLayoutOptions(&$parameters, &$pObj)
    {
        $referringField = $parameters['config']['arguments']['referring_field'];
        $currentValue = $parameters['row'][$referringField];
        $configuration = $this->configurationService->getViewConfigurationByFileReference($currentValue);
        $templatePaths = new TemplatePaths($configuration);
        $files = $templatePaths->resolveAvailableLayoutFiles();
        $files = array_map('basename', $files);
        foreach ($files as $file) {
            if (0 !== strpos($file, '.')) {
                $file = pathinfo($file, PATHINFO_FILENAME);
                array_push($parameters['items'], [$file, $file]);
            }
        }
    }
}
