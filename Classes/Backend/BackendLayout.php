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
	 * @var Tx_Fluidpages_Service_PageService
	 */
	protected $pageService;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->configurationService = $this->objectManager->get('Tx_Fluidpages_Service_ConfigurationService');
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
		try {
			$record = $this->pageService->getPageTemplateConfiguration($pageUid);
			$variables = array();
			list ($extensionName, $action) = explode('->', $record['tx_fed_page_controller_action']);
			if (TRUE === empty($action)) {
				$this->configurationService->message('No template selected - backend layout will not be rendered', t3lib_div::SYSLOG_SEVERITY_INFO);
				return;
			}
			$paths = $this->configurationService->getPageConfiguration($extensionName);
			if (0 === count($paths) && FALSE === (boolean) t3lib_div::_GET('redirected')) {
				if (Tx_Flux_Utility_Version::assertCoreVersionIsAtLeastSixPointZero()) {
					// BUG: TYPO3 6.0 exhibits an odd behavior in some circumstances; reloading the page seems to completely avoid problems
					$get = t3lib_div::_GET();
					unset($get['id']);
					$get['redirected'] = 1;
					$params = t3lib_div::implodeArrayForUrl('', $get);
					header('Location: ?id=' . $pageUid . $params);
					exit();
				}
				return;
			}
			if (0 === count($paths)) {
				$this->configurationService->message('Unable to detect a configuration. If it is not intentional, check that you '
					. 'have included the TypoScript for the desired template collection.', t3lib_div::SYSLOG_SEVERITY_NOTICE);
				return;
			}
			$flexFormSource = isset($record['tx_fed_page_flexform']) ? $record['tx_fed_page_flexform'] : NULL;
			if ($flexFormSource !== NULL) {
				$variables = $this->configurationService->convertFlexFormContentToArray($flexFormSource);
			}
			$templatePathAndFileName = $paths['templateRootPath'] . 'Page/' . $action . '.html';
			$grid = $this->configurationService->getGridFromTemplateFile($templatePathAndFileName, $variables, 'Configuration', $paths, $extensionName);
			if (is_array($grid) === FALSE) {
				// no grid is defined; we use the "raw" BE layout as a default behavior
				$this->configurationService->message('The selected page template does not contain a grid but the template is itself valid.');
				return;
			}
		} catch (Exception $error) {
			$this->configurationService->debug($error);
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
				$colCount += $column['colspan'] ? $column['colspan'] : 1;
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
	public function postProcessColPosListItemsParsed(&$id, array &$tcaItems, &$tceForms) {
	}

	/**
	 * Allows manipulation of the colPos selector option values
	 *
	 * @param array $params Parameters for the selector
	 * @return void
	 */
	public function postProcessColPosProcFuncItems(array &$params) {
		array_push($params['items'], array('Fluid Content Area', 18181, NULL));
	}

}
