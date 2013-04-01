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
class Tx_Fluidpages_Controller_PageController extends Tx_Fluidpages_Controller_AbstractPageController {

	/**
	 * @return string
	 * @route off
	 */
	public function renderAction() {
		$row = $GLOBALS['TSFE']->page;
		$this->provider = $this->providerConfigurationService->resolvePrimaryConfigurationProvider($this->fluxTableName, $this->fluxRecordField, $row);
		$extensionKey = $this->provider->getExtensionKey($row);
		$extensionName = ucfirst(t3lib_div::underscoredToLowerCamelCase($extensionKey));
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		if (NULL === $configuration) {
			throw new Exception('No page template selected (or PageConfigurationProvider could not detect the configuration ' .
				'which defines the page templates that should be used)', 1364737534);
		}
		if (NULL === $configuration['tx_fed_page_controller_action']) {
			throw new Exception('No page template selected and none inherited from parents. To fix this problem, select a page template.', 1364737584);
		}
		$action = $configuration['tx_fed_page_controller_action'];
		$controllerActionName = array_pop(explode('->', $action));
		$controllerActionName{0} = strtolower($controllerActionName{0});
		// failure toggles. Instructs ConfigurationService to throw Exceptions when not being able to detect. We capture these and pass to debug.
		$failHardClass = TRUE;
		$failHardAction = TRUE;
		$potentialControllerClassName = $this->configurationService->resolveFluxControllerClassName($action, 'Page', $failHardClass, $failHardAction);
		if (NULL !== $potentialControllerClassName) {
			$this->request->setControllerObjectName($potentialControllerClassName);
			$this->forward('render');
		}
	}

	/**
	 * @return string
	 */
	public function errorAction() {
		try {
			parent::errorAction();
		} catch (Exception $error) {
			$code = $error->getCode();
			$this->view->assign('error', $error);
			$this->view->setTemplateRootPath(t3lib_extMgm::extPath('fluidpages', 'Resources/Private/Templates/'));
		}
	}

}
