<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Claus Due <claus@wildside.dk>, Wildside A/S
 *
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
 * Page Service
 *
 * Service for interacting with Pages - gets content elements and page configuration
 * options.
 *
 * @package Fluidpages
 * @subpackage Service
 */
class Tx_Fluidpages_Service_PageService implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Fluidpages_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param Tx_Fluidpages_Service_ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(Tx_Fluidpages_Service_ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * Gets an array of the column definition in a BackendLayout object
	 *
	 * @return array
	 * @api
	 */
	public function getColumnConfiguration() {
		$config = $page->getBackendLayout()->getConfig();
		$parser = $this->objectManager->get('t3lib_tsparser');
		$parser->parse($config);
		$array = $parser->setup;
		$columns = array();
		foreach ($array['rows'] as $row) {
			foreach ($row['columns'] as $column) {
				$columns[$column['colPos']] = $column['name'];
			}
		}
		return $columns;
	}

	/**
	 * Process RootLine to find first usable, configured Fluid Page Template.
	 * WARNING: do NOT use the output of this feature to overwrite $row - the
	 * record returned may or may not be the same recod as defined in $id.
	 *
	 * @param integer $pageUid
	 * @return array
	 * @api
	 */
	public function getPageTemplateConfiguration($pageUid) {
		if ($pageUid < 1) {
			return NULL;
		}
		$pageSelect = new t3lib_pageSelect();
		$rootLine = $pageSelect->getRootLine($pageUid);
		$rootLine = array_values($rootLine);
		foreach ($rootLine as $index => $row) {
			if ($index == 0 && strpos($row['tx_fed_page_controller_action'], '->')) {
				return $row;
			} elseif ($index > 0 && strpos($row['tx_fed_page_controller_action_sub'], '->')) {
				$row['tx_fed_page_controller_action'] = $row['tx_fed_page_controller_action_sub'];
				return $row;
			}
		}
		return NULL;
	}

	/**
	 * Gets the fallback Fluid Page Template defined in TypoScript
	 *
	 * @param boolean $translatePath If FALSE, does not translate the TypoScript path
	 * @return string
	 */
	public function getFallbackPageTemplatePathAndFilename($translatePath = TRUE) {
		$fallbackTemplatePathAndFilename = $this->settings['defaults']['templates']['fallbackFluidPageTemplate'];
		if ($translatePath === TRUE) {
			$fallbackTemplatePathAndFilename = t3lib_div::getFileAbsFileName($fallbackTemplatePathAndFilename);
		}
		if (file_exists($fallbackTemplatePathAndFilename) || ($translatePath === FALSE)) {
			return $fallbackTemplatePathAndFilename;
		} else {
			return t3lib_extMgm::extPath('fluidpages', 'Resources/Private/Templates/Page/Render.html');
		}
	}

	/**
	 * Get a usable page configuration flexform from rootline
	 *
	 * @param integer $pageUid
	 * @return string
	 * @api
	 */
	public function getPageFlexFormSource($pageUid) {
		if ($pageUid < 1) {
			return NULL;
		}
		$pageSelect = new t3lib_pageSelect();
		$rootLine = $pageSelect->getRootLine($pageUid);
		foreach ($rootLine as $row) {
			if (!empty($row['tx_fed_page_flexform'])) {
				return $row['tx_fed_page_flexform'];
			}
		}
		return NULL;
	}

	/**
	 * Gets a human-readable label from a Fluid Page template file
	 *
	 * @param string $extensionName
	 * @param string $templateFile
	 * @return string
	 * @api
	 */
	public function getPageTemplateLabel($extensionName, $templateFile) {
		if ($extensionName === NULL) {
			$extensionName = 'fed';
		}
		$config = $this->configurationService->getPageConfiguration($extensionName);
		if (file_exists($templateFile) === TRUE) {
			$templatePathAndFilename = $templateFile;
		} else {
			$templatePathAndFilename = $config['templateRootPath'] . 'Page/' . $templateFile . '.html';
		}
		$exposedView = $this->objectManager->get('Tx_Flux_MVC_View_ExposedStandaloneView');
		$exposedView->setTemplatePathAndFilename($templatePathAndFilename);
		$exposedView->setLayoutRootPath($config['layoutRootPath']);
		$exposedView->setPartialRootPath($config['partialRootPath']);
		$page = $exposedView->getStoredVariable('Tx_Flux_ViewHelpers_FlexformViewHelper', 'storage', 'Configuration');
		return $page['label'] ? $page['label'] : $templateFile . '.html';
	}

	/**
	 * Gets a list of usable Page Templates from defined page template TypoScript
	 *
	 * @param string $format
	 * @return array
	 * @api
	 */
	public function getAvailablePageTemplateFiles($format = 'html') {
		$typoScript = $this->configurationService->getPageConfiguration();
		$output = array();
		if (is_array($typoScript) === FALSE) {
			return $output;
		}
		foreach ($typoScript as $extensionName=>$group) {
			if (isset($group['enable']) === TRUE && $group['enable'] < 1) {
				continue;
			}
			$path = $group['templateRootPath'] . 'Page' . '/';
			$files = scandir($path);
			$output[$extensionName] = array();
			foreach ($files as $k=>$file) {
				$pathinfo = pathinfo($path . $file);
				$extension = $pathinfo['extension'];
				if (substr($file, 0, 1) === '.') {
					unset($files[$k]);
				} else if (strtolower($extension) != strtolower($format)) {
					unset($files[$k]);
				} else {
					$output[$extensionName][] = $pathinfo['filename'];
				}
			}
		}
		return $output;
	}

}
