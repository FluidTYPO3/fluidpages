<?php
namespace FluidTYPO3\Fluidpages\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Flux\Service\ContentService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Class for backend layouts
 */
class BackendLayout implements SingletonInterface
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
    protected $workspacesAwareRecordService;

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
        $this->workspacesAwareRecordService = $workspacesAwareRecordService;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationService = $this->objectManager->get(ConfigurationService::class);
        $this->workspacesAwareRecordService = $this->objectManager->get(WorkspacesAwareRecordService::class);
    }

    /**
     * Postprocesses a selected backend layout
     *
     * @param integer $pageUid Starting page UID in the rootline (this current page)
     * @param array $backendLayout The backend layout which was detected from page id
     * @return NULL|void
     */
    public function postProcessBackendLayout(&$pageUid, &$backendLayout)
    {
        try {
            $record = $this->workspacesAwareRecordService->getSingle('pages', '*', $pageUid);

            // Stop processing if no fluidpages template configured in rootline
            if (null === $record) {
                return null;
            }

            $provider = $this->configurationService->resolvePrimaryConfigurationProvider(
                'pages',
                'tx_fed_page_flexform',
                $record
            );
            $action = $provider->getControllerActionFromRecord($record);
            if (true === empty($action)) {
                $this->configurationService->message(
                    'No template selected - backend layout will not be rendered',
                    GeneralUtility::SYSLOG_SEVERITY_INFO
                );
                return null;
            }
            $grid = $provider->getGrid($record)->build();
            if (false === is_array($grid) || 0 === count($grid['rows'])) {
                // no grid is defined; we use the "raw" BE layout as a default behavior
                $this->configurationService->message(
                    'The selected page template does not contain a grid but the template is itself valid.'
                );
                return null;
            }
        } catch (\RuntimeException $error) {
            $this->configurationService->debug($error);
            return null;
        }

        $config = [
            'backend_layout.' => [
                'colCount' => 0,
                'rowCount' => 0,
                'rows.' => []
            ]
        ];
        $colPosList = [];
        $items = [];
        $rowIndex = 0;
        foreach ($grid['rows'] as $row) {
            $index = 0;
            $colCount = 0;
            $rowKey = ($rowIndex + 1) . '.';
            $columns = [];
            foreach ($row['columns'] as $column) {
                $key = ($index + 1) . '.';
                $columns[$key] = [
                    'name' => $column['label'],
                    'colPos' => $column['colPos'] >= 0 ? $column['colPos'] : $config['backend_layout.']['colCount']
                ];
                if ($column['colspan']) {
                    $columns[$key]['colspan'] = $column['colspan'];
                }
                if ($column['rowspan']) {
                    $columns[$key]['rowspan'] = $column['rowspan'];
                }
                array_push($colPosList, $columns[$key]['colPos']);
                array_push($items, [$columns[$key]['name'], $columns[$key]['colPos'], null]);
                $colCount += $column['colspan'] ? $column['colspan'] : 1;
                ++ $index;
            }
            $config['backend_layout.']['colCount'] = max($config['backend_layout.']['colCount'], $colCount);
            $config['backend_layout.']['rowCount']++;
            $config['backend_layout.']['rows.'][$rowKey] = [
                'columns.' => $columns
            ];
            ++ $rowIndex;
        }
        unset($backendLayout['config']);
        $backendLayout['__config'] = $config;
        $backendLayout['__colPosList'] = $colPosList;
        $backendLayout['__items'] = $items;
    }

    /**
     * Preprocesses the page id used to detect the backend layout record
     *
     * @param integer $id Starting page id when parsing the rootline
     * @return void
     */
    public function preProcessBackendLayoutPageUid(&$id)
    {
    }

    /**
     * Postprocesses the colpos list
     *
     * @param integer $id Starting page id when parsing he rootline
     * @param array $tcaItems The current set of colpos TCA items
     * @param FormEngine $tceForms A back reference to the TCEforms object which generated the item list
     * @return void
     */
    public function postProcessColPosListItemsParsed(&$id, array &$tcaItems, &$tceForms)
    {
    }

    /**
     * Allows manipulation of the colPos selector option values
     *
     * @param array $params Parameters for the selector
     * @return void
     */
    public function postProcessColPosProcFuncItems(array &$params)
    {
        array_push($params['items'], ['Fluid Content Area', ContentService::COLPOS_FLUXCONTENT, null]);
    }
}
