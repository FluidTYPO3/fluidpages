<?php
/*****************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Claus Due <claus@wildside.dk>, Wildside A/S
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 *****************************************************************/

/**
 * Page Configuration Provider
 *
 * @author Claus Due <claus@wildside.dk>, Wildside A/S
 * @package Fluidpages
 * @subpackage Provider
 */
class Tx_Fluidpages_Provider_PageConfigurationProvider extends Tx_Flux_Provider_AbstractConfigurationProvider implements Tx_Flux_Provider_ConfigurationProviderInterface {

	/**
	 * @var string
	 */
	protected $tableName = 'pages';

	/**
	 * @var string
	 */
	protected $fieldName = 'tx_fed_page_flexform';

	/**
	 * @var string
	 */
	protected $extensionKey = 'fed';

	/**
	 * @var string
	 */
	protected $configurationSectionName = 'Configuration';

	/**
	 * @var t3lib_flexFormTools
	 */
	protected $flexformTool;

	/**
	 * @var Tx_Fluidpages_Service_PageService
	 */
	protected $pageService;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Fluidpages_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var integer
	 */
	protected $priority = 100;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$this->flexformTool = t3lib_div::makeInstance('t3lib_flexFormTools');
	}

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param Tx_Fluidpages_Service_PageService $pageService
	 * @return void
	 */
	public function injectPageService(Tx_Fluidpages_Service_PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param Tx_Fluidpages_Service_ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(Tx_Fluidpages_Service_ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param array $row
	 * @return array|NULL
	 */
	public function getTemplatePaths(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		$paths = NULL;
		if ($configuration['tx_fed_page_controller_action']) {
			$action = $configuration['tx_fed_page_controller_action'];
			list ($extensionName, $action) = explode('->', $action);
			$paths = $this->configurationService->getPageConfiguration($extensionName);
		}
		return $paths;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTemplatePathAndFilename(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		$paths = $this->getTemplatePaths($row);
		if ($configuration['tx_fed_page_controller_action']) {
			$action = $configuration['tx_fed_page_controller_action'];
			list ($extensionName, $action) = explode('->', $action);
			if (is_array($paths)) {
				$templatePathAndFilename = $paths['templateRootPath'] . '/Page/' . $action . '.html';
			}
		} elseif ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fed']['setup']['enableFallbackFluidPageTemplate']) {
			$templatePathAndFilename = $this->pageService->getFallbackPageTemplatePathAndFilename();
		}
		return $templatePathAndFilename;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getTemplateVariables(array $row) {
		try {
			$this->flexFormService->setContentObjectData($row['tx_fed_page_flexform']);
		} catch (Exception $error) {
			return array();
		}
		try {
			$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
			$flexform = $this->flexFormService->getAll();
			if ($configuration['tx_fed_page_controller_action']) {
				$action = $configuration['tx_fed_page_controller_action'];
				list ($extensionName, $action) = explode('->', $action);
				$paths = Tx_Flux_Utility_Path::translatePath((array) $this->configurationService->getPageConfiguration($extensionName));
				$templatePathAndFilename = $paths['templateRootPath'] . '/Page/' . $action . '.html';
			} elseif ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fed']['setup']['enableFallbackFluidPageTemplate']) {
				$templatePathAndFilename = $this->pageService->getFallbackPageTemplatePathAndFilename();
			} else {
				return array();
			}

			/** @var Tx_Flux_MVC_View_ExposedStandaloneView $view */
			$view = $this->objectManager->get('Tx_Flux_MVC_View_ExposedStandaloneView');
			$view->setTemplatePathAndFilename($templatePathAndFilename);
			$view->setPartialRootPath($paths['partialRootPath']);
			$view->setLayoutRootPath($paths['layoutRootPath']);
			$view->assignMultiple($flexform);
			$stored = $view->getStoredVariable('Tx_Flux_ViewHelpers_FlexformViewHelper', 'storage', 'Configuration');
			$stored['sheets'] = array();
			foreach ($stored['fields'] as $field) {
				$groupKey = $field['sheets']['name'];
				$groupLabel = $field['sheets']['label'];
				if (is_array($stored['sheets'][$groupKey]) === FALSE) {
					$stored['sheets'][$groupKey] = array(
						'name' => $groupKey,
						'label' => $groupLabel,
						'fields' => array()
					);
				}
				array_push($stored['sheets'][$groupKey]['fields'], $field);
			}
			return $stored;
		} catch (Exception $error) {
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['debugMode'] > 0) {
				throw $error;
			}
		}
		return array();
	}

	/**
	 * Pre-process page's FlexForm configuration. Builds an XML array temporarily which
	 * will contain all configuration of the parent page. If no values are changed, this
	 * temporary XML is removed after database operation.
	 *
	 * @param array $row the record data, by reference. Changing fields' values changes the record's values just before saving
	 * @param integer $id The ID of the current record (which is sometimes now included in $row
	 * @param t3lib_TCEmain $reference A reference to the t3lib_TCEmain object that is currently saving the record
	 * @return void
	 */
	public function preProcessRecord(array &$row, $id, t3lib_TCEmain $reference) {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fed']['setup']['enableFluidPageTemplateVariableInheritance'] < 1) {
			return;
		}
		if (strpos($id, 'NEW') === FALSE) {
			$newElement = FALSE;
			$existingPageRecord = array_pop($GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages', "uid = '" . $id . "'"));
			$existingConfigurationString = $existingPageRecord['tx_fed_page_flexform'];
		} else {
			$existingConfigurationString = NULL;
			$newElement = TRUE;
		}
		$pageSelect = new t3lib_pageSelect();
		$pages = $pageSelect->getRootLine($newElement ? $row['pid'] : $id);
		$inheritedConfiguration = NULL;
		$inheritedConfigurationString = NULL;
		foreach ($pages as $page) {
			if (empty($page['tx_fed_page_flexform']) === FALSE) {
				$inheritedConfigurationString = $page['tx_fed_page_flexform'];
				$inheritedConfiguration = t3lib_div::xml2array($inheritedConfigurationString);
				if ($existingConfigurationString === NULL) {
					$existingConfigurationString = $inheritedConfigurationString;
				}
				break;
			}
		}
		if ($inheritedConfiguration === NULL) {
				// no configuration exists in rootline - no need to proceed
			return;
		} elseif ($newElement) {
			$row['tx_fed_page_flexform'] = $inheritedConfiguration;
		}
		$selectedTemplate = $row['tx_fed_page_controller_action'];
		if (empty($selectedTemplate) === TRUE) {
			foreach ($pages as $page) {
				if (empty($page['tx_fed_page_controller_action_sub']) === FALSE) {
					$selectedTemplate = $page['tx_fed_page_controller_action_sub'];
					break;
				}
			}
		}
		if (empty($row['tx_fed_page_flexform']) === TRUE) {
				// this page has no configuration, read the configuration from parent
			$row['tx_fed_page_flexform'] = $inheritedConfigurationString;
		}
		if ($inheritedConfigurationString === $row['tx_fed_page_flexform']) {
				// quick decision on raw string comparison which would indicate an old page that has previously stored an inherited configuration
			$configurationsMatch = TRUE;
		} else {
			$currentConfiguration = is_array($row['tx_fed_page_flexform']) === TRUE ? $row['tx_fed_page_flexform'] : (array) t3lib_div::xml2array($row['tx_fed_page_flexform']);
			$configurationsMatch = $this->assertMultidimensionalArraysAreIdentical($currentConfiguration, $inheritedConfiguration);
		}
		if ($configurationsMatch === TRUE) {
				// inherited configuration is the same as the
			$row['tx_fed_page_flexform'] = $inheritedConfiguration;
		}
		$existingConfiguration = (array) t3lib_div::xml2array($existingConfigurationString);
		$newConfiguration = is_array($row['tx_fed_page_flexform']) === TRUE ? $row['tx_fed_page_flexform'] : (array) t3lib_div::xml2array($row['tx_fed_page_flexform']);
		$newConfigurationString = t3lib_div::array2xml($newConfiguration, '', 0, 'T3FlexForms');
		$configurationsMatch = $this->assertMultidimensionalArraysAreIdentical($newConfiguration, $inheritedConfiguration);
		if ($configurationsMatch === TRUE) {
			// ensure that this page has exactly the same FlexForm XML as its parent if the fields match.
			// this will synchronize this page with its parent, so that whenever the parent is updated
			// this page will also be updated.
			$existingConfigurationString = $inheritedConfigurationString;
			$overrideValues = array('tx_fed_page_flexform' => $inheritedConfigurationString);
		} else {
			$overrideValues = array('tx_fed_page_flexform' => $newConfigurationString);
		}
		$treeChildrenWhichRequireUpdate = $this->getAllSubPageIdsWhichInheritConfiguration($id, $selectedTemplate, $existingConfigurationString, $existingConfiguration);
		if (count($treeChildrenWhichRequireUpdate) > 0) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', "uid IN (" . implode(',', $treeChildrenWhichRequireUpdate) . ")", $overrideValues);
		}
		unset($reference);
	}

	/**
	 * Post-process database operations on the "pages" table, triggering configuration comparison
	 * with the new parent (if parent has changed, naturally).
	 *
	 * @param string $status TYPO3 operation identifier, i.e. "new" etc.
	 * @param integer $id The ID of the current record (which is sometimes now included in $row
	 * @param array $row The record's data, by reference. Changing fields' values changes the record's values just before saving after operation
	 * @param t3lib_TCEmain $reference A reference to the t3lib_TCEmain object that is currently performing the database operation
	 * @return void
	 */
	public function postProcessDatabaseOperation($status, $id, &$row, t3lib_TCEmain $reference) {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fed']['setup']['enableFluidPageTemplateVariableInheritance'] < 1) {
			return;
		}
		$pageSelect = new t3lib_pageSelect();
		$pages = $pageSelect->getRootLine($id);
		$inheritedConfiguration = NULL;
		$inheritedConfigurationString = NULL;
		foreach ($pages as $page) {
			if (empty($page['tx_fed_page_flexform']) === FALSE) {
				$inheritedConfigurationString = $page['tx_fed_page_flexform'];
				$inheritedConfiguration = t3lib_div::xml2array($inheritedConfigurationString);
				break;
			}
		}
		if ($inheritedConfiguration === NULL) {
				// no manipulation necessary, there is no inherited configuration
			return;
		}
		if (empty($row['tx_fed_page_flexform']) === TRUE) {
				// no manipulation necessary, record does not have a flexform
			return;
		}
		unset($status, $reference);
	}

	/**
	 * Returns an array of every child subpage of the current page, which
	 * has requested to inherit configuration and has not changed any variables
	 * which are currently set in the configuration. The result can then be used for
	 * a bulk update.
	 *
	 * @param integer $pid
	 * @param string $selectedTemplate
	 * @param string $configurationString
	 * @param array $configuration
	 * @return array
	 */
	protected function getAllSubPageIdsWhichInheritConfiguration($pid, $selectedTemplate, $configurationString, $configuration) {
		$subpages = array();
		$clause = "pid = '" . $pid . "' AND (tx_fed_page_controller_action = '' OR tx_fed_page_controller_action = '" . $selectedTemplate . "')";
		$subpagesWithSameOrNoController = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,tx_fed_page_flexform,tx_fed_page_controller_action_sub',
			'pages',
			$clause
		);
		foreach ($subpagesWithSameOrNoController as $page) {
			if ($page['tx_fed_page_flexform'] === $configurationString || empty($page['tx_fed_page_flexform']) === TRUE) {
				array_push($subpages, $page['uid']);
				continue;
			}
			$pageConfiguration = (array) t3lib_div::xml2array($page['tx_fed_page_flexform']);
			if ($this->assertMultidimensionalArraysAreIdentical($pageConfiguration, $configuration) === TRUE) {
				array_push($subpages, $page['uid']);
				continue;
			}
			if (empty($page['tx_fed_page_controller_action_sub']) === FALSE && $page['tx_fed_page_controller_action_sub'] != $selectedTemplate) {
					// changed templates, do not propagate to children
				continue;
			}
			$children = $this->getAllSubPageIdsWhichInheritConfiguration($page['uid'], $selectedTemplate, $configurationString, $configuration);
			if (count($children) > 0) {
				array_merge($subpages, $children);
			}
		}
		return $subpages;
	}

	/**
	 * @param array $a First multidimensional array
	 * @param array $b Second multidimensional array
	 * @return boolean
	 */
	protected function assertMultidimensionalArraysAreIdentical(array $a, array $b) {
		foreach ($a as $index => $value) {
			if (isset($b[$index]) === FALSE) {
				return FALSE;
			} elseif (is_array($value)) {
				if ($this->assertMultidimensionalArraysAreIdentical($value, $b[$index]) === FALSE) {
					return FALSE;
				}
			} else {
				if ($value != $b[$index]) {
					return FALSE;
				}
			}
		}
		return (boolean) (count(array_diff(array_keys($a), array_keys($b)) > 0));
	}

}
