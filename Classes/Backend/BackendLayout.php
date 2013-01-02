<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Claus Due <claus@wildside.dk>, Wildside A/S
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

/**
 * Class for backend layouts
 *
 * @package	Fluidpages
 * @subpackage Backend
 */
class Tx_Fluidpages_Backend_BackendLayout implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var Tx_Fluidpages_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var Tx_Flux_Service_Grid
	 */
	protected $gridService;

	/**
	 * @var Tx_Fluidpages_Service_PageService
	 */
	protected $pageService;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->configurationService = $this->objectManager->get('Tx_Fluidpages_Service_ConfigurationService');
		$this->gridService = $this->objectManager->get('Tx_Flux_Service_Grid');
		$this->pageService = $this->objectManager->get('Tx_Fluidpages_Service_PageService');
	}

	/**
	 * Postprocesses a selected backend layout
	 *
	 * @param integer $pageUid Starting page UID in the rootline (this current page)
	 * @param array $backendLayout The backend layout which was detected from page id
	 * @return void
	 */
	public function postProcessBackendLayout(&$pageUid, &$backendLayout) {
		$record = $this->pageService->getPageTemplateConfiguration($pageUid);
		$variables = array();
		list ($extensionName, $action) = explode('->', $record['tx_fed_page_controller_action']);
		$paths = $this->configurationService->getPageConfiguration($extensionName);
		if (count($paths) === 0) {
			return;
		}
		$templatePathAndFileName = $paths['templateRootPath'] . 'Page/' . $action . '.html';
		$grid = $this->gridService->getGridFromTemplateFile($templatePathAndFileName, $variables, 'Configuration', $paths);
		if (is_array($grid) === FALSE) {
			// no grid is defined; we use the "raw" BE layout as a default behavior
			return;
		}

		$config = array(
			'colCount' => 0,
			'rowCount' => 0,
			'backend_layout.' => array(
				'rows.' => array()
			)
		);
		$colPosList = array();
		$items = array();

		foreach ($grid as $rowIndex => $row) {
			$colCount = 0;
			$rowKey = ($rowIndex + 1) . '.';
			$columns = array();
			foreach ($row as $index => $column) {
				$key = ($index + 1) . '.';
				$columns[$key] = array(
					'name' => $column['name'],
					'colPos' => $column['colPos'] >= 0 ? $column['colPos'] : $config['colCount'],
				);
				if ($column['colspan']) {
					$columns[$key]['colspan'] = $column['colspan'];
				}
				if ($column['rowspan']) {
					$columns[$key]['rowspan'] = $column['rowspan'];
				}
				array_push($colPosList, $columns[$key]['colPos']);
				array_push($items, array($columns[$key]['name'], $columns[$key]['colPos'], NULL));
				$colCount++;
			}
			$config['colCount'] = max($config['colCount'], $colCount);
			$config['rowCount']++;
			$config['backend_layout.']['rows.'][$rowKey] = array(
				'columns.' => $columns
			);
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
	public function preProcessBackendLayoutPageUid(&$id) {
	}

	/**
	 * Postprocesses the colpos list
	 *
	 * @param integer $id Starting page id when parsing he rootline
	 * @param array $tcaItems The current set of colpos TCA items
	 * @param t3lib_TCEForms $tceForms A back reference to the TCEforms object which generated the item list
	 * @return void
	 */
	public function postProcessColPosListItemsParsed(&$id, array &$tcaItems, t3lib_TCEForms &$tceForms) {
	}

	/**
	 * Allows manipulation of the colPos selector option values
	 *
	 * @param array $params Parameters for the selector
	 * @return void
	 */
	public function postProcessColPosProcFuncItems(array &$params) {
	}

}
