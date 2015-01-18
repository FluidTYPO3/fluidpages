<?php
namespace FluidTYPO3\Fluidpages\Override\Backend\View;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayout;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout for CMS
 *
 * @author GridView Team
 */
class BackendLayoutView extends \TYPO3\CMS\Backend\View\BackendLayoutView {

	/**
	 * @var \FluidTYPO3\Fluidpages\Backend\BackendLayout
	 */
	protected $backendLayout;

	/**
	 * @param \FluidTYPO3\Fluidpages\Backend\BackendLayout $backendLayout
	 * @return void
	 */
	public function injectBackendLayout(BackendLayout $backendLayout) {
		$this->backendLayout = $backendLayout;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \FluidTYPO3\Fluidpages\Backend\BackendLayout $backendLayout */
		$backendLayout = $objectManager->get('FluidTYPO3\\Fluidpages\\Backend\\BackendLayout');
		$this->injectBackendLayout($backendLayout);
		if (TRUE === in_array('__construct', get_class_methods('TYPO3\\CMS\\Backend\\View\\BackendLayoutView'))) {
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
