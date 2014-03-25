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

use FluidTYPO3\Flux\Service\ContentService;
use FluidTYPO3\Flux\Utility\VersionUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for backend layouts
 *
 * @package	Fluidpages
 * @subpackage Backend
 */
class BackendLayout implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\PageService
	 */
	protected $pageService;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->configurationService = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService');
		$this->pageService = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Service\\PageService');
	}

	/**
	 * Postprocesses a selected backend layout
	 *
	 * @param integer $pageUid Starting page UID in the rootline (this current page)
	 * @param array $backendLayout The backend layout which was detected from page id
	 * @return NULL|void
	 */
	public function postProcessBackendLayout(&$pageUid, &$backendLayout) {
		try {
			$record = $this->pageService->getPage($pageUid);

			// Stop processing if no fluidpages template configured in rootline
			if (NULL === $record) {
				return NULL;
			}

			$provider = $this->configurationService->resolvePrimaryConfigurationProvider('pages', 'tx_fed_page_flexform', $record);
			$action = $provider->getControllerActionFromRecord($record);
			if (TRUE === empty($action)) {
				$this->configurationService->message('No template selected - backend layout will not be rendered', GeneralUtility::SYSLOG_SEVERITY_INFO);
				return NULL;
			}
			$paths = $provider->getTemplatePaths($record);
			if (0 === count($paths)) {
				if (VersionUtility::assertCoreVersionIsAtLeastSixPointZero()) {
					if (FALSE === (boolean) GeneralUtility::_GET('redirected')) {
						// BUG: TYPO3 6.0 exhibits an odd behavior in some circumstances; reloading the page seems to completely avoid problems
						$get = GeneralUtility::_GET();
						unset($get['id']);
						$get['redirected'] = 1;
						$params = GeneralUtility::implodeArrayForUrl('', $get);
						header('Location: ?id=' . $pageUid . $params);
						exit();
					}
					return NULL;
				}
				$this->configurationService->message('Unable to detect a configuration. If it is not intentional, check that you '
					. 'have included the TypoScript for the desired template collection.', GeneralUtility::SYSLOG_SEVERITY_NOTICE);
				return NULL;
			}
			$grid = $provider->getGrid($record)->build();
			if (FALSE === is_array($grid) || 0 === count($grid['rows'])) {
				// no grid is defined; we use the "raw" BE layout as a default behavior
				$this->configurationService->message('The selected page template does not contain a grid but the template is itself valid.');
				return NULL;
			}
		} catch (\Exception $error) {
			$this->configurationService->debug($error);
			return NULL;
		}

		$config = array(
			'backend_layout.' => array(
				'colCount' => 0,
				'rowCount' => 0,
				'rows.' => array()
			)
		);
		$colPosList = array();
		$items = array();
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
					'colPos' => $column['colPos'] >= 0 ? $column['colPos'] : $config['backend_layout.']['colCount']
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
				++ $index;
			}
			$config['backend_layout.']['colCount'] = max($config['backend_layout.']['colCount'], $colCount);
			$config['backend_layout.']['rowCount']++;
			$config['backend_layout.']['rows.'][$rowKey] = array(
				'columns.' => $columns
			);
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
		array_push($params['items'], array('Fluid Content Area', ContentService::COLPOS_FLUXCONTENT, NULL));
	}

}
