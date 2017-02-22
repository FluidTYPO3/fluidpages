<?php
namespace FluidTYPO3\Fluidpages\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\View\TemplatePaths;
use FluidTYPO3\Flux\View\ViewContext;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
     */
    public function getPageTemplateConfiguration($pageUid)
    {
        $pageUid = (integer) $pageUid;
        if (1 > $pageUid) {
            return null;
        }
        $cacheId = 'fluidpages-template-configuration-' . $pageUid;
        $runtimeCache = $this->getRuntimeCache();
        $fromCache = $runtimeCache->get($cacheId);
        if ($fromCache) {
            return $fromCache;
        }
        $fieldList = 'tx_fed_page_controller_action_sub,t3ver_oid,pid,uid';
        $page = $this->workspacesAwareRecordService->getSingle(
            'pages',
            'tx_fed_page_controller_action,' . $fieldList,
            $pageUid
        );

        // Initialize with possibly-empty values and loop root line
        // to fill values as they are detected.
        $resolvedMainTemplateIdentity = $page['tx_fed_page_controller_action'];
        $resolvedSubTemplateIdentity = $page['tx_fed_page_controller_action_sub'];
        do {
            $containsSubDefinition = (false !== strpos($page['tx_fed_page_controller_action_sub'], '->'));
            $isCandidate = ((integer) $page['uid'] !== $pageUid);
            if (true === $containsSubDefinition && true === $isCandidate) {
                $resolvedSubTemplateIdentity = $page['tx_fed_page_controller_action_sub'];
                if (true === empty($resolvedMainTemplateIdentity)) {
                    // Conditions met: current page is not $pageUid, original page did not
                    // contain a "this page" layout, current rootline page has "sub" selection.
                    // Then, set our "this page" value to use the "sub" selection that was detected.
                    $resolvedMainTemplateIdentity = $resolvedSubTemplateIdentity;
                }
                break;
            }
            // Note: 't3ver_oid' is analysed in order to make versioned records inherit the original record's
            // configuration as an emulated first parent page.
            $resolveParentPageUid = (integer) (0 > $page['pid'] ? $page['t3ver_oid'] : $page['pid']);
            $page = $this->workspacesAwareRecordService->getSingle(
                'pages',
                $fieldList,
                $resolveParentPageUid
            );
        } while (null !== $page);
        if (true === empty($resolvedMainTemplateIdentity) && true === empty($resolvedSubTemplateIdentity)) {
            // Neither directly configured "this page" nor inherited "sub" contains a valid value;
            // no configuration was detected at all.
            return null;
        }
        $configurarion = [
            'tx_fed_page_controller_action' => $resolvedMainTemplateIdentity,
            'tx_fed_page_controller_action_sub' => $resolvedSubTemplateIdentity
        ];
        $runtimeCache->set($cacheId, $configurarion);
        return $configurarion;
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
        foreach ((array) $typoScript as $extensionName => $group) {
            if (true === isset($group['enable']) && 1 > $group['enable']) {
                continue;
            }
            $output[$extensionName] = [];
            $templatePaths = new TemplatePaths($group);
            $templateRootPaths = $templatePaths->getTemplateRootPaths();
            foreach ($templateRootPaths as $templateRootPath) {
                $configuredPath = $templateRootPath . 'Page/';
                if (false === is_dir($configuredPath)) {
                    $this->configurationService->message(
                        sprintf(
                            'The template group "%s" has been configured to use the templateRootPath "' .
                            '%s" but this directory does not exist.',
                            $extensionName,
                            $configuredPath
                        ),
                        GeneralUtility::SYSLOG_SEVERITY_FATAL
                    );
                    continue;
                }
                $files = scandir($configuredPath);
                foreach ($files as $key => $file) {
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
                    $viewContext = new ViewContext($configuredPath . $file, $extensionName, 'Page');
                    $viewContext->setSectionName('Configuration');
                    $viewContext->setTemplatePaths($templatePaths);
                    $form = $this->configurationService->getFormFromTemplateFile($viewContext);
                    $templatePathAndFilename = $form->getOption(Form::OPTION_TEMPLATEFILE);
                    if (false === $form instanceof Form) {
                        $this->configurationService->message(
                            'Template file ' . $viewContext . ' contains an unparsable Form definition',
                            GeneralUtility::SYSLOG_SEVERITY_FATAL
                        );
                        continue;
                    }
                    if (false === $form->getEnabled()) {
                        $this->configurationService->message(
                            'Template file ' . $templatePathAndFilename . ' is disabled by configuration',
                            GeneralUtility::SYSLOG_SEVERITY_NOTICE
                        );
                        continue;
                    }
                    $form->setOption(Form::OPTION_TEMPLATEFILE, $configuredPath . $file);
                    $output[$extensionName][$filename] = $form;
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
