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

use FluidTYPO3\Flux\Form;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that renders a Layout selector based on a template file
 *
 * @package	Fluidpages
 * @subpackage Backend
 */
class TemplateFileLayoutSelector {

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $pageService;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->pageService = $objectManager->get('FluidTYPO3\\Fluidpages\\Service\\PageService');
		$this->configurationService = $objectManager->get('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService');
	}

	/**
	 * Renders a Fluid Template Layout select field
	 *
	 * @param array $parameters
	 * @param mixed $pObj
	 * @return string
	 */
	public function addLayoutOptions(&$parameters, &$pObj) {
		$referringField = $parameters['config']['arguments']['referring_field'];
		$currentValue = $parameters['row'][$referringField];
		$configuration = $this->configurationService->getViewConfigurationByFileReference($currentValue);
		$layoutRootPath = $configuration['layoutRootPath'];
		$layoutRootPath = GeneralUtility::getFileAbsFileName($layoutRootPath);
		$files = array();
		$files = TRUE === is_dir($layoutRootPath) ? GeneralUtility::getAllFilesAndFoldersInPath($files, $layoutRootPath) : array();
		foreach ($files as $file) {
			$file = substr($file, strlen($layoutRootPath));
			if (0 !== strpos($file, '.')) {
				$dir = pathinfo($file, PATHINFO_DIRNAME);
				$file = pathinfo($file, PATHINFO_FILENAME);
				if ('.' !== $dir) {
					$file = $dir . '/' . $file;
				}
				array_push($parameters['items'], array($file, $file));
			}
		}
	}

}
