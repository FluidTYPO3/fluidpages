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
 * Class that renders a Page template selection field.
 *
 * @package	Fluidpages
 * @subpackage Backend
 */
class Tx_Fluidpages_Backend_PageLayoutSelector {

	/**
	 * @var Tx_Extbase_Configuration_BackendConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Fluidpages_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var array
	 */
	protected $recognizedFormats = array('html', 'xml', 'txt', 'json', 'js', 'css');

	/**
	 * @var Tx_Fluidpages_Service_PageService
	 */
	protected $pageService;

	/**
	 * @var Tx_Flux_Service_Flexform
	 */
	protected $flexformService;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->configurationManager = $objectManager->get('Tx_Extbase_Configuration_BackendConfigurationManager');
		$this->configurationService = $objectManager->get('Tx_Fluidpages_Service_ConfigurationService');
		$this->pageService = $objectManager->get('Tx_Fluidpages_Service_PageService');
		$this->flexformService = $objectManager->get('Tx_Flux_Service_Flexform');
	}

	/**
	 * Renders a Fluid Page Layout file selector
	 *
	 * @param array $parameters
	 * @param mixed $pObj
	 * @return string
	 */
	public function renderField(&$parameters, &$pObj) {
		$name = $parameters['itemFormElName'];
		$value = $parameters['itemFormElValue'];
		$availableTemplates = $this->pageService->getAvailablePageTemplateFiles();
		if (strpos($name, 'tx_fed_controller_action_sub') === FALSE) {
			$onChange = 'onchange="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };"';
		}
		$selector = '<select name="' . $name . '" class="formField select" ' . $onChange . '>' . LF;
		$selector .= '<option value="">' . $emptyLabel . '</option>' . LF;
		foreach ($availableTemplates as $extension=>$group) {
			if (!t3lib_extMgm::isLoaded($extension)) {
				$groupTitle = ucfirst($extension);
			} else {
				$emConfigFile = t3lib_extMgm::extPath($extension, 'ext_emconf.php');
				require $emConfigFile;
				$groupTitle = $EM_CONF['']['title'];
			}

			$selector .= '<optgroup label="' . $groupTitle . '">' . LF;
			foreach ($group as $template) {
				try {
					$paths = $this->configurationService->getPageConfiguration($extension);
					$templatePathAndFilename = $this->pageService->expandPathsAndTemplateFileToTemplatePathAndFilename($paths, $template);
					$configuration = $this->pageService->getStoredVariable($templatePathAndFilename, 'storage', $paths);
				} catch (Exception $error) {
					if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['debugMode'] > 0) {
						throw $error;
					}
					continue;
				}
				if (FALSE === (boolean) $configuration['enabled']) {
					continue;
				}
				$label = $configuration['label'];
				$optionValue = $extension . '->' . $template;
				$selected = ($optionValue == $value ? ' selected="formField selected"' : '');
				$option = '<option value="' . $optionValue . '"' . $selected . '>' . $label . '</option>';
				$selector .= $option . LF;
			}
			$selector .= '</optgroup>' . LF;
		}
		$selector .= '</select>' . LF;
		unset($pObj);
		return $selector;
	}

}
