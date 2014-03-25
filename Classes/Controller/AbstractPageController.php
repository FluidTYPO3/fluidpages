<?php
namespace FluidTYPO3\Fluidpages\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
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

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Controller\AbstractFluxController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Page Controller
 *
 * @package Fluidpages
 * @subpackage Controller
 * @route off
 */
abstract class AbstractPageController extends AbstractFluxController implements PageControllerInterface {

	/**
	 * @var string
	 */
	protected $fluxRecordField = 'tx_fed_page_flexform';

	/**
	 * @var string
	 */
	protected $fluxTableName = 'pages';

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\PageService
	 */
	protected $pageService;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param \FluidTYPO3\Fluidpages\Service\PageService $pageService
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param \FluidTYPO3\Fluidpages\Service\ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 * @return void
	 */
	public function initializeView(ViewInterface $view) {
		$this->configurationManager->getContentObject()->data = $GLOBALS['TSFE']->page;
		parent::initializeView($view);
	}

	/**
	 * @return array
	 */
	public function getRecord() {
		return $GLOBALS['TSFE']->page;
	}

}
