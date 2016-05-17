<?php
namespace FluidTYPO3\Fluidpages\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\View\ViewContext;
use FluidTYPO3\Flux\View\TemplatePaths;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class that renders a Page template selection field.
 */
class PageLayoutSelector {

	/**
	 * @var BackendConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var array
	 */
	protected $recognizedFormats = array('html', 'xml', 'txt', 'json', 'js', 'css');

	/**
	 * @var PageService
	 */
	protected $pageService;

	/**
	 * @param ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param PageService $pageService
	 * @return void
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->injectConfigurationManager($objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface'));
		$this->injectConfigurationService($objectManager->get('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'));
		$this->injectPageService($objectManager->get('FluidTYPO3\\Fluidpages\\Service\\PageService'));
	}

	/**
	 * Renders a Fluid Page Layout file selector
	 *
	 * @param array $parameters
	 * @param mixed $pObj
	 * @return string
	 */
	public function renderField(&$parameters, &$pObj) {
		$availableTemplates = $this->pageService->getAvailablePageTemplateFiles();
		$selector = '<div>';
		$selector .= $this->renderInheritanceField($parameters);
		foreach ($availableTemplates as $extension => $group) {
			$selector .= $this->renderOptions($extension, $group, $parameters);
		}
		$selector .= '</div>';
		return $selector;
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	protected function renderInheritanceField(array $parameters) {
		$selector = '';
		$onChange = 'onclick="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };"';
		$pageIsSiteRoot = (boolean) ($parameters['row']['is_siteroot']);
		$name = $parameters['itemFormElName'];
		$value = $parameters['itemFormElValue'];
		$typoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$settings = GeneralUtility::removeDotsFromTS((array) $typoScript['plugin.']['tx_fluidpages.']);
		$hideInheritFieldSiteRoot = (boolean) (TRUE === isset($settings['siteRootInheritance']) ? 1 > $settings['siteRootInheritance'] : FALSE);
		$forceDisplayInheritSiteRoot = (boolean) ('tx_fed_page_controller_action_sub' === $parameters['field'] && FALSE === $hideInheritFieldSiteRoot);
		$forceHideInherit = (boolean) (0 === intval($parameters['row']['pid']));
		if (FALSE === $forceHideInherit) {
			if (FALSE === $pageIsSiteRoot || TRUE === $forceDisplayInheritSiteRoot || FALSE === $hideInheritFieldSiteRoot) {
				$emptyLabel = LocalizationUtility::translate('pages.tx_fed_page_controller_action.default', 'Fluidpages');
				$selected = TRUE === empty($value) ? ' checked="checked" ' : NULL;
				$selector .= '<label>';
				$selector .= '<input type="radio" name="' . $name . '" ' . $onChange . '" value="" ' . $selected . '/> ' . $emptyLabel . LF;
				$selector .= '</label>' . LF;
			}
		}
		return $selector;
	}

	/**
	 * @param string $extension
	 * @param array $group
	 * @param array $parameters
	 * @return string
	 */
	protected function renderOptions($extension, array $group, array $parameters) {
		$selector = '';
		if (FALSE === empty($group)) {
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
			foreach ($group as $form) {
				$selector .= $this->renderOption($form, $parameters);
			}
		}
		return $selector;
	}

	/**
	 * @param Form $form
	 * @param array $parameters
	 * @return string
	 */
	protected function renderOption(Form $form, array $parameters) {
		$name = $parameters['itemFormElName'];
		$value = $parameters['itemFormElValue'];
		$onChange = 'onclick="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };"';
		$selector = '';
		try {
			$extension = $form->getExtensionName();
			$thumbnail = MiscellaneousUtility::getIconForTemplate($form);
			$template = pathinfo($form->getOption(Form::OPTION_TEMPLATEFILE), PATHINFO_FILENAME);
			$formLabel = $form->getLabel();
			$label = strpos($formLabel, 'LLL:') === 0 ? LocalizationUtility::translate($formLabel, $extension) : $formLabel;
			$optionValue = $extension . '->' . lcfirst($template);
			$selected = ($optionValue == $value ? ' checked="checked"' : '');
			$option = '<label style="padding: 0.5em; border: 1px solid #CCC; display: inline-block; vertical-align: bottom; margin: 0 1em 1em 0; cursor: pointer; ' . ($selected ? 'background-color: #DDD;' : '')  . '">';
			$option .= '<img src="' . $thumbnail . '" alt="' . $label . '" style="margin: 0.5em 0 0.5em 0; max-width: 196px; max-height: 128px;"/><br />';
			$option .= '<input type="radio" value="' . $optionValue . '"' . $selected . ' name="' . $name . '" ' . $onChange . ' /> ' . $label;
			$option .= '</label>';
			$selector .= $option . LF;
		} catch (\RuntimeException $error) {
			$this->configurationService->debug($error);
		}
		return $selector;
	}

}
