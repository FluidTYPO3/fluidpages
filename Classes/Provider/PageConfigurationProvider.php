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
	protected $parentFieldName = 'pid';

	/**
	 * @var string
	 */
	protected $fieldName = 'tx_fed_page_flexform';

	/**
	 * @var string
	 */
	protected $extensionKey = 'fluidpages';

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
	 * @return string
	 */
	public function getExtensionKey(array $row) {
		$paths = $this->getTemplatePaths($row);
		if (TRUE === isset($paths['extensionKey'])) {
			return $paths['extensionKey'];
		}
		$controllerExtensionKey = $this->getControllerExtensionKeyFromRecord($row);
		if (FALSE === empty($controllerExtensionKey)) {
			return $controllerExtensionKey;
		}
		return parent::getExtensionKey($row);
	}

	/**
	 * @param array $row
	 * @return array|NULL
	 */
	public function getTemplatePaths(array $row) {
		$paths = NULL;
		try {
			$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
			if ($configuration['tx_fed_page_controller_action']) {
				$action = $configuration['tx_fed_page_controller_action'];
				list ($extensionName, $action) = explode('->', $action);
				$paths = $this->configurationService->getPageConfiguration($extensionName);
			}
		} catch (Exception $error) {
			$this->configurationService->debug($error);
		}
		$paths = Tx_Flux_Utility_Path::translatePath($paths);
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
				$templateRootPath = $paths['templateRootPath'];
				if ('/' === substr($templateRootPath, -1)) {
					$templateRootPath = substr($templateRootPath, 0, -1);
				}
				$templatePathAndFilename = $templateRootPath . '/Page/' . $action . '.html';
				if (TRUE === isset($paths['overlays']) && TRUE === is_array($paths['overlays'])) {
					foreach ($paths['overlays'] as $possibleOverlayPaths) {
						if (TRUE === isset($possibleOverlayPaths['templateRootPath'])) {
							$overlayTemplateRootPath = $possibleOverlayPaths['templateRootPath'];
							$overlayTemplateRootPath = rtrim($overlayTemplateRootPath, '/');
							$possibleOverlayFile = t3lib_div::getFileAbsFileName($overlayTemplateRootPath . '/Page/' . $action . '.html');
							if (TRUE === file_exists($possibleOverlayFile)) {
								$templatePathAndFilename = $possibleOverlayFile;
								break;
							}
						}
					}
				}
			}
		}
		$templatePathAndFilename = t3lib_div::getFileAbsFileName($templatePathAndFilename);
		return $templatePathAndFilename;
	}

	/**
	 * @param array $row
	 * @return array|NULL
	 */
	public function getTemplateVariables(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		$action = $configuration['tx_fed_page_controller_action'];
		list ($extensionName, $action) = explode('->', $action);
		$paths = Tx_Flux_Utility_Path::translatePath((array) $this->configurationService->getPageConfiguration($extensionName));
		$templateRootPath = $paths['templateRootPath'];
		if ('/' === substr($templateRootPath, -1)) {
			$templateRootPath = substr($templateRootPath, 0, -1);
		}
		$templatePathAndFilename = $templateRootPath . '/Page/' . $action . '.html';
		if (FALSE === file_exists($templatePathAndFilename)) {
			return NULL;
		}
		$stored = $this->configurationService->getStoredVariable($templatePathAndFilename, 'storage', 'Configuration', $paths, $extensionName);
		if (NULL === $stored) {
			$this->configurationService->message('A valid configuration could not be retrieved from file ' . $templatePathAndFilename .
				' - processing aborted; see earlier errors', t3lib_div::SYSLOG_SEVERITY_FATAL);
			return NULL;
		}
		$this->configurationService->message('Flux is able to read template variables from file ' . $templatePathAndFilename, t3lib_div::SYSLOG_SEVERITY_INFO);
		return $stored;
	}

	/**
	 * Gets an inheritance tree (ordered parent -> ... -> this record)
	 * of record arrays containing raw values.
	 *
	 * @param array $row
	 * @return array
	 */
	public function getInheritanceTree(array $row) {
		$main = 'tx_fed_page_controller_action';
		$sub = 'tx_fed_page_controller_action_sub';
		$records = parent::getInheritanceTree($row);
		if (0 === count($records)) {
			return $records;
		}
		$template = $records[0][$sub];
		foreach ($records as $index => $record) {
			if ((FALSE === empty($record[$main]) && $template !== $record[$main]) || (FALSE === empty($record[$sub]) && $template !== $record[$sub])) {
				return array_slice($records, $index);
			}
		}
		return $records;
	}

	/**
	 * Post-process the TCEforms DataStructure for a record associated
	 * with this ConfigurationProvider
	 *
	 * @param array $row
	 * @param mixed $dataStructure
	 * @param array $conf
	 * @return NULL
	 */
	public function postProcessDataStructure(array &$row, &$dataStructure, array $conf) {
		$selectedPageTemplate = $this->pageService->getPageTemplateConfiguration($row['uid']);
		if (TRUE === empty($selectedPageTemplate['tx_fed_page_controller_action'])) {
			$config['parameters'] = array(
				'userFunction' => 'Tx_Flux_UserFunction_NoSelection->renderField'
			);
			$dataStructure = $this->objectManager->get('Tx_Flux_Provider_Structure_FallbackStructureProvider')->render($config);
			return NULL;
		}
		parent::postProcessDataStructure($row, $dataStructure, $conf);
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerExtensionKeyFromRecord(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		$action = $configuration['tx_fed_page_controller_action'];
		$extensionName = array_shift(explode('->', $action));
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
		return $extensionKey;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionFromRecord(array $row) {
		$action = $this->getControllerActionReferenceFromRecord($row);
		$controllerActionName = array_pop(explode('->', $action));
		$controllerActionName{0} = strtolower($controllerActionName{0});
		return $controllerActionName;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		return $configuration['tx_fed_page_controller_action'];
	}

}
