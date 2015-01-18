<?php
namespace FluidTYPO3\Fluidpages\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

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
