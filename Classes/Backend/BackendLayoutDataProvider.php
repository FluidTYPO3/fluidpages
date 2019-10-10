<?php
namespace FluidTYPO3\Fluidpages\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Class for backend layouts
 */
class BackendLayoutDataProvider extends DefaultDataProvider implements DataProviderInterface
{

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var WorkspacesAwareRecordService
     */
    protected $recordService;

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
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
        $this->recordService = $workspacesAwareRecordService;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->injectConfigurationService($this->objectManager->get(ConfigurationService::class));
        $this->injectWorkspacesAwareRecordService($this->objectManager->get(WorkspacesAwareRecordService::class));
    }

    /**
     * Adds backend layouts to the given backend layout collection.
     *
     * @param DataProviderContext $dataProviderContext
     * @param BackendLayoutCollection $backendLayoutCollection
     * @return void
     */
    public function addBackendLayouts(
        DataProviderContext $dataProviderContext,
        BackendLayoutCollection $backendLayoutCollection
    ) {
        $backendLayout = $this->getBackendLayout('fluidpages', $dataProviderContext->getPageId());
        if ($backendLayout) {
            $backendLayoutCollection->add($backendLayout);
        }
    }

    /**
     * Gets a backend layout by (regular) identifier.
     *
     * @param string $identifier
     * @param integer $pageUid
     * @return BackendLayout
     */
    public function getBackendLayout($identifier, $pageUid)
    {
        $record = $this->recordService->getSingle('pages', '*', $pageUid);
        if (null === $record) {
            return new BackendLayout('grid', 'Empty', '');
        }
        $grid = $this->resolveProvider($record)->getGrid($record);
        if (!$grid->hasChildren()) {
            return new BackendLayout('grid', 'Empty', '');
        }
        return $grid->buildBackendLayout(0);
    }

    /**
     * @param int $pageUid
     * @return ProviderInterface
     */
    protected function resolveProvider(array $record)
    {
        $record = $this->recordService->getSingle('pages', '*', $record['uid']);

        // Stop processing if no fluidpages template configured in rootline
        if (null === $record) {
            return [];
        }

        return $this->configurationService->resolvePageProvider($record);
    }
}
