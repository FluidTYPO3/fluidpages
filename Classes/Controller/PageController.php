<?php
namespace FluidTYPO3\Fluidpages\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Flux\Controller\AbstractFluxController;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Page Controller
 *
 * @route off
 */
class PageController extends AbstractFluxController implements PageControllerInterface {

	/**
	 * @var string
	 */
	protected $fluxRecordField = 'tx_fed_page_flexform';

	/**
	 * @var string
	 */
	protected $fluxTableName = 'pages';

	/**
	 * @var PageService
	 */
	protected $pageService;

	/**
	 * @var ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param PageService $pageService
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param ViewInterface $view
	 * @return void
	 */
	public function initializeView(ViewInterface $view) {
		$this->configurationManager->getContentObject()->data = $this->getRecord();
		parent::initializeView($view);
	}

	/**
	 * @throws \RuntimeException
	 * @return void
	 */
	protected function initializeProvider() {
		$this->provider = $this->configurationService->resolvePageProvider($this->getRecord());
	}

	/**
	 * @return string
	 */
	public function rawAction() {
		$record = $this->getRecord();
		$templateFileReference = $record['tx_fluidpages_templatefile'];
		$templatePathAndFilename = $this->configurationService->convertFileReferenceToTemplatePathAndFilename($templateFileReference);
		$paths = $this->configurationService->getViewConfigurationByFileReference($templateFileReference);
		$this->provider->setTemplatePathAndFilename($templatePathAndFilename);
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$this->view->setTemplateRootPath($paths['templateRootPath']);
		$this->view->setPartialRootPath($paths['partialRootPath']);
		$this->view->setLayoutRootPath($paths['layoutRootPath']);
	}

	/**
	 * @return array
	 */
	public function getRecord() {
		return $this->workspacesAwareRecordService->getSingle($this->fluxTableName, '*', $GLOBALS['TSFE']->id);
	}

}
