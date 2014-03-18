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
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class that renders a Page template selection field.
 *
 * @package	Fluidpages
 * @subpackage Backend
 */
class PageLayoutSelector {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var array
	 */
	protected $recognizedFormats = array('html', 'xml', 'txt', 'json', 'js', 'css');

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\PageService
	 */
	protected $pageService;

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');
		$this->configurationService = $objectManager->get('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService');
		$this->pageService = $objectManager->get('FluidTYPO3\\Fluidpages\\Service\\PageService');
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
		if (FALSE === strpos($name, 'tx_fed_controller_action_sub')) {
			$onChange = 'onclick="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };"';
		}
		$selector = '<div>';
		$typoScript = $this->configurationManager->getTypoScriptSetup();
		$hideInheritFieldSiteRoot = (boolean) (TRUE === isset($typoScript['plugin.']['tx_fluidpages.']['siteRootInheritance']) ? 1 > $typoScript['plugin.']['tx_fluidpages.']['siteRootInheritance'] : FALSE);
		$pageIsSiteRoot = (boolean) ($parameters['row']['is_siteroot']);
		$forceDisplayInheritSiteRoot = (boolean) ('tx_fed_page_controller_action_sub' === $parameters['field']);
		$forceHideInherit = (boolean) (0 === intval($parameters['row']['pid']));
		if (FALSE === $pageIsSiteRoot || TRUE === $forceDisplayInheritSiteRoot || FALSE === $hideInheritFieldSiteRoot) {
			if (FALSE === $forceHideInherit) {
				$emptyLabel = LocalizationUtility::translate('pages.tx_fed_page_controller_action.default', 'Fluidpages');
				$selected = TRUE === empty($value) ? ' checked="checked" ' : NULL;
				$selector .= '<label>';
				$selector .= '<input type="radio" name="' . $name . '" ' . $onChange . '" value="" ' . $selected . '/> ' . $emptyLabel . LF;
				$selector .= '</label>' . LF;
			}
		}
		foreach ($availableTemplates as $extension=>$group) {
			$extensionKey = ExtensionNamingUtility::getExtensionKey($extension);
			if (FALSE === ExtensionManagementUtility::isLoaded($extensionKey)) {
				$groupTitle = ucfirst($extension);
			} else {
				$emConfigFile = ExtensionManagementUtility::extPath($extensionKey, 'ext_emconf.php');
				require $emConfigFile;
				$groupTitle = $EM_CONF['']['title'];
			}

			$packageLabel = LocalizationUtility::translate('pages.tx_fed_page_package', 'Fluidpages');
			$selector .= '<h4 style="clear: both; margin-top: 1em;">' . $packageLabel . ': ' . $groupTitle . '</h4>' . LF;
			foreach ($group as $template) {
				try {
					$paths = $this->configurationService->getPageConfiguration($extension);
					$extensionName = ExtensionNamingUtility::getExtensionName($extension);
					$templatePathAndFilename = $this->pageService->expandPathsAndTemplateFileToTemplatePathAndFilename($paths, $template);
					if (FALSE === file_exists($templatePathAndFilename)) {
						$this->configurationService->message('Missing template file: ' . $templatePathAndFilename, GeneralUtility::SYSLOG_SEVERITY_WARNING);
						continue;
					}
					$form = $this->configurationService->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', $paths, $extensionName);
					if (FALSE === $form instanceof Form) {
						$this->configurationService->message('Template file ' . $templatePathAndFilename . ' contains an unparsable Form definition', GeneralUtility::SYSLOG_SEVERITY_FATAL);
						continue;
					}
					if (FALSE === $form->getEnabled()) {
						continue;
					}
					$thumbnail = $form->getIcon();
					$label = $form->getLabel();
					$translatedLabel = LocalizationUtility::translate($label, $extensionName);
					if (NULL !== $translatedLabel) {
						$label = $translatedLabel;
					}
					$optionValue = $extension . '->' . $template;
					$selected = ($optionValue == $value ? ' checked="checked"' : '');
					$option = '<label style="padding: 0.5em; border: 1px solid #CCC; display: inline-block; vertical-align: bottom; margin: 0 1em 1em 0; cursor: pointer; ' . ($selected ? 'background-color: #DDD;' : '')  . '">';
					$option .= '<img src="' . $thumbnail . '" alt="' . $label . '" style="margin: 0.5em 0 0.5em 0; max-width: 196px; max-height: 128px;"/><br />';
					$option .= '<input type="radio" value="' . $optionValue . '"' . $selected . ' name="' . $name . '" ' . $onChange . ' /> ' . $label;
					$option .= '</label>';
					$selector .= $option . LF;
				} catch (\Exception $error) {
					$this->configurationService->debug($error);
				}
			}
		}
		$selector .= '</div>' . LF;
		unset($pObj);
		return $selector;
	}

}
