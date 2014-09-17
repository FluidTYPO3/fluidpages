<?php
namespace FluidTYPO3\Fluidpages\Backend;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Class for backend layouts
 *
 * @package	Fluidpages
 * @subpackage Backend
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
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->configurationService = $this->objectManager->get('FluidTYPO3\Fluidpages\Service\ConfigurationService');
		$this->pageService = $this->objectManager->get('FluidTYPO3\Fluidpages\Service\PageService');
		$this->recordService = $this->objectManager->get('FluidTYPO3\Flux\Service\WorkspacesAwareRecordService');
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
	 * @return NULL|BackendLayout
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

			$provider = $this->configurationService->resolvePrimaryConfigurationProvider('pages', 'tx_fed_page_flexform', $record);
			$action = $provider->getControllerActionFromRecord($record);
			if (TRUE === empty($action)) {
				$this->configurationService->message('No template selected - backend layout will not be rendered', GeneralUtility::SYSLOG_SEVERITY_INFO);
				return array();
			}
			$paths = $provider->getTemplatePaths($record);
			if (0 === count($paths)) {
				$this->configurationService->message('Unable to detect a configuration. If it is not intentional, check that you '
					. 'have included the TypoScript for the desired template collection.', GeneralUtility::SYSLOG_SEVERITY_NOTICE);
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
				$columnName = $GLOBALS['LANG']->sL($column['label']);
				if (TRUE === empty($columnName)) {
					$columnName = $column['name'];
				}
				$columns[$key] = array(
					'name' => $columnName,
					'colPos' => $column['colPos'] >= 0 ? $column['colPos'] : $config['colCount']
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
		return $config;
	}

}
