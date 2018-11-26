<?php
namespace FluidTYPO3\Fluidpages\UserFunction;

use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Decide if fluidpages shall be used to render a page
 */
class LayoutSelect
{
    /**
     * @var WorkspacesAwareRecordService
     */
    protected $workspacesAwareRecordService;

    /**
     * Decide if the "Page layouts" fields and "Page configuration" flexform
     * shall be shown in the page settings.
     *
     * The first conditionParameters parameter determines if the subpages
     * settings are meant or not.
     *
     * @param array $parameters Parameters with key:
     *                     - record (page db row)
     *                     - flexformValueKey
     *                     - conditionParameters (displayCond config)
     * @param EvaluateDisplayConditions $caller Object calling the method
     *
     * @return boolean True if it shall be shown
     */
    public function doShowPageConfiguration($parameters, EvaluateDisplayConditions $caller)
    {
        $sub = (boolean) $parameters['conditionParameters'][0];

        $conf = $this->getPageTemplateConfiguration($parameters['record']['uid']);
        if (null === $conf) {
            return false;
        }

        if ($sub) {
            return false === empty($conf['tx_fed_page_controller_action_sub']);
        } else {
            return false === empty($conf['tx_fed_page_controller_action']);
        }
    }

    /**
     * Process RootLine to find first usable, configured Fluid Page Template.
     * WARNING: do NOT use the output of this feature to overwrite $row - the
     * record returned may or may not be the same record as defined in $id.
     *
     * @param integer $pageUid
     * @return array|NULL Array with two keys:
     *                    - tx_fed_page_controller_action
     *                    - tx_fed_page_controller_action_sub
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
        $fieldList = 'tx_fed_page_controller_action_sub,backend_layout,backend_layout_next_level,t3ver_oid,pid,uid';
        $page = $this->getWorkspacesAwareRecordService()->getSingle(
            'pages',
            'tx_fed_page_controller_action,' . $fieldList,
            $pageUid
        );

        $checkUsage = true;
        $useMainTemplate = $this->isFluidpagesBackendLayout($page['backend_layout']);
        $useSubTemplate = $this->isFluidpagesBackendLayout($page['backend_layout_next_level']);
        $mainTemplate = $page['tx_fed_page_controller_action'];
        $subTemplate = $page['tx_fed_page_controller_action_sub'];
        $isFirstPage = true;

        do {
            if (false === $isFirstPage) {
                if ($checkUsage) {
                    if ($this->isFluidpagesBackendLayout($page['backend_layout_next_level'])) {
                        $useMainTemplate = true;
                        $useSubTemplate  = true;
                    } else if (false === empty($page['backend_layout_next_level'])) {
                        //we have a different layout in between, so do not look further up
                        $checkUsage = false;
                    }
                }
                $containsSubDefinition = (false !== strpos($page['tx_fed_page_controller_action_sub'], '->'));
                if ($containsSubDefinition) {
                    if (empty($mainTemplate)) {
                        $mainTemplate = $page['tx_fed_page_controller_action_sub'];
                    }
                    if (empty($subTemplate)) {
                        $subTemplate = $page['tx_fed_page_controller_action_sub'];
                    }
                }
            }
            // Note: 't3ver_oid' is analysed in order to make versioned records inherit the original record's
            // configuration as an emulated first parent page.
            $resolveParentPageUid = (integer) (0 > $page['pid'] ? $page['t3ver_oid'] : $page['pid']);
            $page = $this->getWorkspacesAwareRecordService()->getSingle(
                'pages',
                $fieldList,
                $resolveParentPageUid
            );
            $isFirstPage = false;
        } while ($page);

        if (empty($mainTemplate)) {
            $useMainTemplate = false;
        }
        if (empty($subTemplate)) {
            $useSubTemplate = false;
        }
        if (false === $useMainTemplate && false === $useSubTemplate) {
            //BC return value
            return null;
        }
        $configuration = [
            'tx_fed_page_controller_action' => $useMainTemplate ? $mainTemplate : null,
            'tx_fed_page_controller_action_sub' => $useSubTemplate  ? $subTemplate  : null
        ];
        $runtimeCache->set($cacheId, $configuration);
        return $configuration;
    }

    /**
     * Determine if the given backend layout string is a fluidpages layout
     *
     * @param string $belayout Page row backend_layout value
     *
     * @return boolean True if fluidpages should be used to render
     */
    public function isFluidpagesBackendLayout($belayout)
    {
        return substr($belayout, 0, 12) == 'fluidpages__';
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    protected function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }

    /**
     * @return WorkspacesAwareRecordService
     */
    protected function getWorkspacesAwareRecordService()
    {
        if (null === $this->workspacesAwareRecordService) {
            $this->workspacesAwareRecordService = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(WorkspacesAwareRecordService::class);
        }
        return $this->workspacesAwareRecordService;
    }

    /**
     * Used in unit tests.
     *
     * @param WorkspacesAwareRecordService $workspacesAwareRecordService
     *
     * @return void
     */
    public function setWorkspacesAwareRecordService(WorkspacesAwareRecordService $workspacesAwareRecordService)
    {
        $this->workspacesAwareRecordService = $workspacesAwareRecordService;
    }
}
?>
