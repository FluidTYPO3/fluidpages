<?php
namespace FluidTYPO3\Fluidpages\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\UserFunction\LayoutSelect;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\ViewHelpers\FormViewHelper;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\Exception\InvalidSectionException;

/**
 * Page Service
 *
 * Service for interacting with Pages - gets content elements and page configuration
 * options.
 */
class PageService implements SingletonInterface
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var WorkspacesAwareRecordService
     */
    protected $workspacesAwareRecordService;

    /**
     * @param ObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

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
     * @param WorkspacesAwareRecordService $workspacesAwareRecordService
     * @return void
     */
    public function injectWorkspacesAwareRecordService(WorkspacesAwareRecordService $workspacesAwareRecordService)
    {
        $this->workspacesAwareRecordService = $workspacesAwareRecordService;
    }

    /**
     * Process RootLine to find first usable, configured Fluid Page Template.
     * WARNING: do NOT use the output of this feature to overwrite $row - the
     * record returned may or may not be the same record as defined in $id.
     *
     * @param integer $pageUid
     * @return array|NULL
     * @api
     * @deprecated
     */
    public function getPageTemplateConfiguration($pageUid)
    {
        $layoutSelect = GeneralUtility::makeInstance(LayoutSelect::class);
        return $layoutSelect->getPageTemplateConfiguration($pageUid);
    }

    /**
     * Get a usable page configuration flexform from rootline
     *
     * @param integer $pageUid
     * @return string
     * @api
     */
    public function getPageFlexFormSource($pageUid)
    {
        $pageUid = (integer) $pageUid;
        if (1 > $pageUid) {
            return null;
        }
        $fieldList = 'uid,pid,t3ver_oid,tx_fed_page_flexform';
        $page = $this->workspacesAwareRecordService->getSingle('pages', $fieldList, $pageUid);
        while (null !== $page && 0 !== (integer) $page['uid'] && true === empty($page['tx_fed_page_flexform'])) {
            $resolveParentPageUid = (integer) (0 > $page['pid'] ? $page['t3ver_oid'] : $page['pid']);
            $page = $this->workspacesAwareRecordService->getSingle('pages', $fieldList, $resolveParentPageUid);
        }
        return $page['tx_fed_page_flexform'];
    }

    /**
     * Gets a list of usable Page Templates from defined page template TypoScript.
     * Returns a list of Form instances indexed by the path ot the template file.
     *
     * @param string $format
     * @return Form[]
     * @api
     */
    public function getAvailablePageTemplateFiles($format = 'html')
    {
        $typoScript = $this->configurationService->getPageConfiguration();
        $output = [];
        $view = $this->objectManager->get(TemplateView::class);
        foreach ((array) $typoScript as $extensionName => $group) {
            if (true === isset($group['enable']) && 1 > $group['enable']) {
                continue;
            }
            $output[$extensionName] = [];
            $extensionKey = ExtensionNamingUtility::getExtensionKey($extensionName);
            $templatePaths = new TemplatePaths($extensionKey);
            foreach ($templatePaths->resolveAvailableTemplateFiles('Page') as $file) {
                $pathinfo = pathinfo($file);
                $extension = $pathinfo['extension'];
                if ('.' === substr($file, 0, 1)) {
                    continue;
                } elseif (strtolower($extension) !== strtolower($format)) {
                    continue;
                }
                $filename = $pathinfo['filename'];
                if (isset($output[$extensionName][$filename])) {
                    continue;
                }

                $view->setTemplatePathAndFilename($file);
                try {
                    $view->renderSection('Configuration');
                    $form = $view->getRenderingContext()->getViewHelperVariableContainer()->get(FormViewHelper::class, 'form');

                    if (false === $form instanceof Form) {
                        $this->configurationService->message(
                            'Template file ' . $file . ' contains an unparsable Form definition',
                            GeneralUtility::SYSLOG_SEVERITY_FATAL
                        );
                        continue;
                    } elseif (false === $form->getEnabled()) {
                        $this->configurationService->message(
                            'Template file ' . $file . ' is disabled by configuration',
                            GeneralUtility::SYSLOG_SEVERITY_NOTICE
                        );
                        continue;
                    }
                    $form->setOption(Form::OPTION_TEMPLATEFILE, $file);
                    $form->setExtensionName($extensionName);
                    $output[$extensionName][$filename] = $form;
                } catch (InvalidSectionException $error) {
                    GeneralUtility::sysLog($error->getMessage() . ' (file: ' . $file . ')', 'fluidpages', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                }
            }
        }
        return $output;
    }

    /**
     * @return VariableFrontend
     */
    protected function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }
}
