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
 * You can use the following PageTSConfig to control the behaviour of this field
 * TCEFORM.pages.tx_fed_page_controller_action {
 * 		keepItems = MyVendor.MyExtension->myAction1,MyVendor.MyExtension->myAction2
 * 		removeItems = MyVendor.MyExtension->myAction2,MyVendor.MyExtension->myAction4
 * 		default = MyVendor.MyExtension->youAreTheChoosenAction,
 * 		hideTitle = 0
 * 		hideInheritanceField = 1
 * }
 */
class PageLayoutSelector {

	/**
	 * @var array
	 */
	protected $templates = array(
		'selector' => '<div style="margin: 15px;"><div class="row"> %s %s </div></div>',
		'field' => '<label class="col-xs-6 col-sm-4 col-md-3 img-thumbnail" style="padding-top:15px; padding-bottom:15px;cursor: pointer; %s">
						<div class="media">
  							<img class="img-responsive  media-object" src="%s" alt="%s" /><br />
							<div class="media-body">
								<input class="hidden" type="radio" value="%s" %s name="%s" %s />
								<h4 class="media-heading text-center">%s</h4>
							</div>
					</div>
					</label>',
		'inheritanceField' => '<label class="col-xs-12 img-thumbnail"><input type="radio" name="%s" %s " value="" %s/>  %s</label>',
		'title' => '<h4 class="small">%s: %s</h4>'
	);

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
		$options = '';
		foreach ($availableTemplates as $extension => $group) {
			$options .= $this->renderOptions($extension, $group, $parameters);
		}
		$selector = sprintf($this->templates['selector'], $this->renderInheritanceField($parameters), $options);
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
		$fieldTSConfig = $parameters['fieldTSConfig'];
		$default = isset($fieldTSConfig['default']) ? $fieldTSConfig['default'] : NULL;
		$typoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$settings = GeneralUtility::removeDotsFromTS((array) $typoScript['plugin.']['tx_fluidpages.']);
		$hideInheritFieldSiteRoot = (boolean) (TRUE === isset($settings['siteRootInheritance']) ? 1 > $settings['siteRootInheritance'] : FALSE);
		$tsHideInheritanceField = isset($fieldTSConfig['hideInheritanceField']) ? (boolean) $fieldTSConfig['hideInheritanceField'] : FALSE;
		$forceDisplayInheritSiteRoot = (boolean) ('tx_fed_page_controller_action_sub' === $parameters['field'] && (FALSE === $hideInheritFieldSiteRoot || FALSE === $tsHideInheritanceField));
		$forceHideInherit = (boolean) (0 === intval($parameters['row']['pid']));
		if (FALSE === $forceHideInherit) {
			if (FALSE === $pageIsSiteRoot || TRUE === $forceDisplayInheritSiteRoot || FALSE === $hideInheritFieldSiteRoot) {
				$emptyLabel = LocalizationUtility::translate('pages.tx_fed_page_controller_action.default', 'Fluidpages');
				$selected = TRUE === empty($value) && TRUE === empty($default) ? ' checked="checked" ' : NULL;
				$selector  = sprintf($this->templates['inheritanceField'], $name, $onChange, $selected, $emptyLabel);
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
			$fieldTSConfig = $parameters['fieldTSConfig'];
			$hideTitle = isset($fieldTSConfig['hideTitle']) ? $fieldTSConfig['hideTitle'] : NULL;
			if (TRUE === empty($hideTitle)) {
				$selector .= sprintf($this->templates['title'], $packageLabel, $groupTitle);
			}

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
		$fieldTSConfig = $parameters['fieldTSConfig'];
		$keepItems =  isset($fieldTSConfig['keepItems']) ? explode(',', $fieldTSConfig['keepItems']) : NULL;
		$removeItems =  isset($fieldTSConfig['removeItems']) ? explode(',', $fieldTSConfig['removeItems']) : NULL;
		$default = isset($fieldTSConfig['default']) ? $fieldTSConfig['default'] : NULL;
		$onChange = 'onclick="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };"';
		$selector = '';
		try {
			$extension = $form->getExtensionName();
			$thumbnail = MiscellaneousUtility::getIconForTemplate($form);
			$template = pathinfo($form->getOption(Form::OPTION_TEMPLATEFILE), PATHINFO_FILENAME);
			$label = $form->getLabel();
			$optionValue = $extension . '->' . lcfirst($template);
			$selected = FALSE;
			if (TRUE === empty($value)) {
				if (FALSE === empty($default)) {
					$selected = ($optionValue == $default ? ' checked="checked"' : '');
				}

			} else {
				$selected = ($optionValue == $value ? ' checked="checked"' : '');
			}

			$option = $this->getField($selected, $thumbnail, $label, $optionValue, $name, $onChange);
			if (FALSE === empty($keepItems)) {
				if (TRUE === in_array($optionValue, $keepItems)) {
					$selector .= $option . LF;
				}

			}  elseif (FALSE === empty($removeItems)) {
				if (TRUE === in_array($optionValue, $removeItems)) {
					$selector .= $option . LF;
				}

			} else {
				$selector .= $option . LF;
			}

		} catch (\RuntimeException $error) {
			$this->configurationService->debug($error);
		}
		return $selector;
	}

	/**
	 * @param $selected
	 * @param $thumbnail
	 * @param $label
	 * @param $optionValue
	 * @param $name
	 * @param $onChange
	 *
	 * @return string
	 */
	protected function getField($selected, $thumbnail, $label, $optionValue, $name, $onChange) {
		$color = $selected ? 'background-color: #ebf3fb; border-color:#6daae0; ' : '';
		return sprintf($this->templates['field'], $color, $thumbnail, $label, $optionValue, $selected, $name, $onChange, $label);
	}

}
