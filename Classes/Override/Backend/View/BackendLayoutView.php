<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 GridView Team
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Backend layout for CMS
 *
 * @author GridView Team
 */
class Tx_Fluidpages_Override_Backend_View_BackendLayoutView extends TYPO3\CMS\Backend\View\BackendLayoutView {

	/**
	 * @var Tx_Fluidpages_Backend_BackendLayout
	 */
	protected $backendLayout;

	/**
	 * @param Tx_Fluidpages_Backend_BackendLayout $backendLayout
	 * @return void
	 */
	public function injectBackendLayout(Tx_Fluidpages_Backend_BackendLayout $backendLayout) {
		$this->backendLayout = $backendLayout;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		/** @var $objectManager Tx_Extbase_Object_ObjectManager */
		$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		/** @var $backendLayout Tx_Fluidpages_Backend_BackendLayout */
		$backendLayout = $objectManager->get('Tx_Fluidpages_Backend_BackendLayout');
		$this->injectBackendLayout($backendLayout);
		if (TRUE === method_exists($this, '__construct')) {
			parent::__construct();
		}
	}

	/**
	 * ItemProcFunc for colpos items
	 *
	 * @param array $params
	 * @return void
	 */
	public function colPosListItemProcFunc(&$params) {
		parent::colPosListItemProcFunc($params);
		$this->backendLayout->postProcessColPosProcFuncItems($params);
	}

	/**
	 * Gets the list of available columns for a given page id
	 *
	 * @param integer $id
	 * @return array $tcaItems
	 */
	public function getColPosListItemsParsed($id) {
		$layout = $this->getSelectedBackendLayout($id);
		return $layout['__items'];
	}

	/**
	 * Gets the selected backend layout
	 *
	 * @param integer $id
	 * @return array|NULL $backendLayout
	 */
	public function getSelectedBackendLayout($id) {
		$this->backendLayout->preProcessBackendLayoutPageUid($id);
		$backendLayout = parent::getSelectedBackendLayout($id);
		$this->backendLayout->postProcessBackendLayout($id, $backendLayout);
		return array(
			'__config' => $backendLayout['__config'],
			'__items' => $backendLayout['__items'],
			'__colPosList' => $backendLayout['__colPosList'],
		);
	}

}
