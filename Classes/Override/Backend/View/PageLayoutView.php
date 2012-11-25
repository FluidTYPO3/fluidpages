<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Include file extending db_list.inc for use with the web_layout module
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Child class for the Web > Page module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class Tx_Fluidpages_Override_Backend_View_PageLayoutView extends TYPO3\CMS\Backend\View\PageLayoutView {

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
	 * @return void
	 */
	public function __construct() {
		/** @var $objectManager Tx_Extbase_Object_ObjectManagerInterfacee */
		$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		/** @var $backendLayout Tx_Fluidpages_Backend_BackendLayout */
		$backendLayout = $objectManager->get('Tx_Fluidpages_Backend_BackendLayout');
		$this->injectBackendLayout($backendLayout);
	}

	/**
	 * Get backend layout configuration
	 *
	 * @return array
	 */
	public function getBackendLayoutConfiguration() {
		$backendLayoutUid = $this->getSelectedBackendLayoutUid($this->id);
		if (!$backendLayoutUid) {
			$config = array();
			$this->backendLayout->postProcessBackendLayout($this->id, $config);
			$typoScriptArray = $config['__config'];
			$typoScriptArray['rows.'] = $config['__config']['backend_layout.']['rows.'];
			unset($typoScriptArray['backend_layout.']);
			$config['config'] = $this->compactTypoScriptArray(array('backend_layout.' => $typoScriptArray));
			return $config;
		}
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('backend_layout', intval($backendLayoutUid));
	}

	/**
	 * @param array $array
	 * @param string $string
	 * @return string
	 */
	protected function compactTypoScriptArray($array, $indent = 0) {
		$indentation = str_repeat("\t", $indent);
		$string = '';
		foreach ($array as $index => $value) {
			if (is_array($value)) {
				$string .= ($indentation . substr($index, 0, -1) . ' { ' . LF);
				$string .= $this->compactTypoScriptArray($value, $indent + 1);
				$string .= ($indentation . '}' . LF);
			} else {
				$string .= ($indentation . $index . ' = ' . $value . LF);
			}
		}
		return $string;
	}

}


?>