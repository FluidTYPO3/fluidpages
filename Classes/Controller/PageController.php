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
		$content = NULL;
		try {
			$row = $this->configurationManager->getContentObject()->data;
			$extensionKey = $this->provider->getExtensionKey($row);
			$extensionName = ucfirst(t3lib_div::underscoredToLowerCamelCase($extensionKey));
			$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
			$action = $configuration['tx_fed_page_controller_action'];
			$controllerActionName = array_pop(explode('->', $action));
			$controllerActionName{0} = strtolower($controllerActionName{0});
			// failure toggles. Instructs ConfigurationService to throw Exceptions when not being able to detect. We capture these and pass to debug.
			$failHardClass = TRUE;
			$failHardAction = TRUE;
			$potentialControllerClassName = $this->configurationService->resolveFluxControllerClassName($action, 'Page', $failHardClass, $failHardAction);
			if (NULL !== $potentialControllerClassName) {
				$request = clone $this->request;
				$request->setControllerExtensionName($extensionName);
				$request->setControllerActionName($controllerActionName);
				/** @var $response Tx_Extbase_MVC_Web_Response */
				$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
				/** @var $controller Tx_Extbase_Mvc_Controller_ControllerInterface */
				$controller = $this->objectManager->create($potentialControllerClassName);
				$content = $controller->processRequest($request, $response);
			}
		} catch (Exception $error) {
			$this->debugService->debug($error);
			$code = $error->getCode();
			if (1364498093 !== $code && 1364498223 !== $code) {
				$this->request->setErrors(array('page' => $error));
				$this->request->setControllerActionName('error');
				$this->forward('error');
			}
			$content = $this->view->render();
		}
		return $content;
	}

	/**
	 * @return string
	 */
	public function errorAction() {
		$setup = $this->getSetup();
		$this->view->assign('errors', $this->request->getErrors());
		$this->view->setTemplatePathAndFilename($setup['templateRootPath'] . 'Page/Error.' . $this->request->getFormat());
	}

}
