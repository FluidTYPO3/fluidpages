<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Claus Due <claus@wildside.dk>, Wildside A/S
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
 * Page Controller
 *
 * @package Fluidpages
 * @subpackage Controller
 * @route off
 */
class Tx_Fluidpages_Controller_PageController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'Tx_Flux_MVC_View_ExposedTemplateView';

	/**
	 * @var Tx_Fluidpages_Service_PageService
	 */
	protected $pageService;

	/**
	 * @var Tx_Fluidpages_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var Tx_Flux_Provider_ConfigurationService
	 */
	protected $providerConfigurationService;

	/**
	 * @var Tx_Flux_Service_FlexForm
	 */
	protected $flexFormService;

	/**
	 * @param Tx_Fluidpages_Service_PageService $pageService
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
	 * @param Tx_Flux_Service_FlexForm $flexFormService
	 * @return void
	 */
	public function injectFlexFormService(Tx_Flux_Service_FlexForm $flexformService) {
		$this->flexFormService = $flexformService;
	}

	/**
	 * @param Tx_Flux_Provider_ConfigurationService $providerConfigurationService
	 * @return void
	 */
	public function injectProviderConfigurationService(Tx_Flux_Provider_ConfigurationService $providerConfigurationService) {
		$this->providerConfigurationService = $providerConfigurationService;
	}

	/**
	 * @param Tx_Flux_MVC_View_ExposedTemplateView $view
	 *
	 * @return void
	 */
	public function initializeView(Tx_Flux_MVC_View_ExposedTemplateView $view) {
		$row = $GLOBALS['TSFE']->page;
		$providers = $this->providerConfigurationService->resolveConfigurationProviders('pages', 'tx_fed_page_flexform', $row);
		$priority = 0;
		/** @var $pageConfigurationProvider Tx_Fluidpages_Provider_PageConfigurationProvider */
		$pageConfigurationProvider = NULL;
		foreach ($providers as $provider) {
			if ($provider->getPriority($row) >= $priority) {
				$pageConfigurationProvider = $provider;
			}
		}
		if (NULL === $pageConfigurationProvider) {
			throw new Exception('Unable to resolve the PageConfigurationProvider - this is grave error and indicates that EXT:fluidpages is broken', 1358693007);
		}
		$configuration = $this->pageService->getPageTemplateConfiguration($GLOBALS['TSFE']->id);
		list ($extensionName, $action) = explode('->', $configuration['tx_fed_page_controller_action']);
		$paths = $pageConfigurationProvider->getTemplatePaths($row);
		$flexformData = $pageConfigurationProvider->getFlexFormValues($row);
		$view->setLayoutRootPath($paths['layoutRootPath']);
		$view->setPartialRootPath($paths['partialRootPath']);
		$templatePathAndFilename = $provider->getTemplatePathAndFilename($row);
		if (file_exists($templatePathAndFilename) === TRUE) {
			$view->setTemplatePathAndFilename($templatePathAndFilename);
			$view->assignMultiple($flexformData);
			$view->assign('page', $GLOBALS['TSFE']->page);
			$view->assign('user', $GLOBALS['TSFE']->fe_user->user);
			$view->assign('cookies', $_COOKIE);
			$view->assign('session', $_SESSION);
		} else {
			$message = 'Template file "' . $templatePathAndFilename . '" does not exist.';
			if (pathinfo($templatePathAndFilename, PATHINFO_BASENAME) === '') {
				$message .= ' Additionally, the specified template file basename was empty.';
				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fed']['setup']['enableFallbackFluidPageTemplate']) {
					$message .= ' The fallback page template feature is enabled but the fallback template was not found.';
				} else {
					$message .= ' A fallback page template was not defined - which means that you probably just need to';
					$message .= ' select a page template for this page or make sure your page inherits its template ';
					$message .= ' from the parent page template.';
				}
			}
			$this->flashMessageContainer->add($message, 'Template file not found');
		}
		$this->view = $view;
	}

	/**
	 * @return string
	 * @route off
	 */
	public function renderAction() {
		$this->view->setControllerContext($this->controllerContext);
		return $this->view->render();
	}

}
