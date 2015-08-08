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
use FluidTYPO3\Flux\Service\ContentService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class for backend layouts
 */
class BackendLayoutDataProvider implements DataProviderInterface {

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var PageService
	 */
	protected $pageService;

	/**
	 * @var WorkspacesAwareRecordService
	 */
	protected $recordService;

	/**
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param PageService $pageService
	 * @return void
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param WorkspacesAwareRecordService $workspacesAwareRecordService
	 * @return void
	 */
	public function injectWorkspacesAwareRecordService(WorkspacesAwareRecordService $workspacesAwareRecordService) {
		$this->recordService = $workspacesAwareRecordService;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->injectConfigurationService($this->objectManager->get('FluidTYPO3\Fluidpages\Service\ConfigurationService'));
		$this->injectPageService($this->objectManager->get('FluidTYPO3\Fluidpages\Service\PageService'));
		$this->injectWorkspacesAwareRecordService($this->objectManager->get('FluidTYPO3\Flux\Service\WorkspacesAwareRecordService'));
	}

	/**
	 * Adds backend layouts to the given backend layout collection.
	 *
	 * @param DataProviderContext $dataProviderContext
	 * @param BackendLayoutCollection $backendLayoutCollection
	 * @return void
	 */
	public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection) {
		$pageUid = $dataProviderContext->getPageId();
		$config = $this->getBackendLayoutConfiguration($pageUid);
		$configString = $this->encodeTypoScriptArray($config);
		$backendLayout = new BackendLayout('fluidpages', 'Fluidpages', $configString);
		$backendLayoutCollection->add($backendLayout);
	}

	/**
	 * Gets a backend layout by (regular) identifier.
	 *
	 * @param string $identifier
	 * @param integer $pageUid
	 * @return BackendLayout
	 */
	public function getBackendLayout($identifier, $pageUid) {
		$configuration = $this->getBackendLayoutConfiguration($pageUid);
		$configuration = $this->ensureDottedKeys($configuration);
		$configString = $this->encodeTypoScriptArray($configuration);
		$backendLayout = new BackendLayout($identifier, 'Fluidpages', $configString);
		return $backendLayout;
	}

	/**
	 * @param array $configuration
	 * @return string
	 */
	protected function encodeTypoScriptArray(array $configuration) {
		$configuration = $this->ensureDottedKeys($configuration);
		$typoScriptParser = new ExtendedTemplateService();
		$typoScriptParser->flattenSetup($configuration, 'backend_layout.', FALSE);
		$string = '';
		foreach ($typoScriptParser->flatSetup as $name => $value) {
			$string .= $name . ' = ' . $value . LF;
		}
		return $string;
	}

	/**
	 * @param array $configuration
	 * @return array
	 */
	protected function ensureDottedKeys(array $configuration) {
		$converted = array();
		foreach ($configuration as $key => $value) {
			if (TRUE === is_array($value)) {
				$key = rtrim($key, '.') . '.';
				$value = $this->ensureDottedKeys($value);
			}
			$converted[$key] = $value;
		}
		return $converted;
	}

	/**
	 * @param integer $pageUid Starting page UID in the rootline (this current page)
	 * @return array
	 */
	protected function getBackendLayoutConfiguration($pageUid) {
		try {
			$record = $this->recordService->getSingle('pages', '*', $pageUid);

			// Stop processing if no fluidpages template configured in rootline
			if (NULL === $record) {
				return array();
			}

			$provider = $this->configurationService->resolvePageProvider($record);
			$action = $provider->getControllerActionFromRecord($record);
			if (TRUE === empty($action)) {
				$this->configurationService->message('No template selected - backend layout will not be rendered', GeneralUtility::SYSLOG_SEVERITY_INFO);
				return array();
			}
			$grid = $provider->getGrid($record)->build();
			if (FALSE === is_array($grid) || 0 === count($grid['rows'])) {
				// no grid is defined; we use the "raw" BE layout as a default behavior
				$this->configurationService->message('The selected page template does not contain a grid but the template is itself valid.');
				return array();
			}
		} catch (\Exception $error) {
			$this->configurationService->debug($error);
			return array();
		}

		$config = array(
			'colCount' => 0,
			'rowCount' => 0,
			'rows.' => array()
		);
		$rowIndex = 0;
		foreach ($grid['rows'] as $row) {
			$index = 0;
			$colCount = 0;
			$rowKey = ($rowIndex + 1) . '.';
			$columns = array();
			foreach ($row['columns'] as $column) {
				$key = ($index + 1) . '.';
				$columns[$key] = array(
					'name' => $column['label'],
					'colPos' => $column['colPos'] >= 0 ? $column['colPos'] : NULL
				);
				if ($column['colspan']) {
					$columns[$key]['colspan'] = $column['colspan'];
				}
				if ($column['rowspan']) {
					$columns[$key]['rowspan'] = $column['rowspan'];
				}
				$colCount += $column['colspan'] ? $column['colspan'] : 1;
				++ $index;
			}
			$config['colCount'] = max($config['colCount'], $colCount);
			$config['rowCount']++;
			$config['rows.'][$rowKey] = array(
				'columns.' => $columns
			);
			++ $rowIndex;
		}
		if (FALSE === $this->isPageModuleLanguageView()) {
			$config['rows.'][($rowIndex + 1) . '.'] = array(
				'columns.' => array(
					'1.' => array(
						'name' => LocalizationUtility::translate('fluidContentArea', 'fluidpages'),
						'colPos' => ContentService::COLPOS_FLUXCONTENT
					)
				)
			);
		}
		return $config;
	}

	/**
	 * @return boolean
	 */
	protected function isPageModuleLanguageView() {
		$module = GeneralUtility::_GET('M') ? GeneralUtility::_GET('M') : 'web_layout';
		$function = TRUE === isset($GLOBALS['SOBE']->MOD_SETTINGS['function']) ? $GLOBALS['SOBE']->MOD_SETTINGS['function'] : NULL;
		return ('web_layout' === $module && 2 === (integer) $function);
	}

}
