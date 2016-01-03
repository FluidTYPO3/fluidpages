<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Controller\PageController;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\View\ExposedTemplateView;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use FluidTYPO3\Fluidpages\Tests\Fixtures\Controller\DummyPageController;
use FluidTYPO3\Flux\Provider\Provider;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PageControllerTest
 */
class PageControllerTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testPerformsInjections() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidpages\\Controller\\PageController');
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'pageConfigurationService', $instance);
	}

	/**
	 * @return void
	 */
	public function testGetRecordDelegatesToRecordService() {
		/** @var PageController $subject */
		$subject = $this->getMock('FluidTYPO3\\Fluidpages\\Controller\\PageController', array('dummy'));
		/** @var WorkspacesAwareRecordService|\PHPUnit_Framework_MockObject_MockObject $mockService */
		$mockService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', array('getSingle'));
		$mockService->expects($this->once())->method('getSingle');
		$subject->injectWorkspacesAwareRecordService($mockService);
		$subject->getRecord();
	}

	public function testInitializeView() {
		/** @var PageController|\PHPUnit_Framework_MockObject_MockObject $instance */
		$instance = $this->getAccessibleMock(
			'FluidTYPO3\\Fluidpages\\Controller\\PageController',
			array(
				'getRecord', 'initializeProvider', 'initializeSettings', 'initializeOverriddenSettings',
				'initializeViewObject', 'initializeViewVariables'
			)
		);
		/** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
		$configurationManager = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
			array('getContentObject', 'getConfiguration')
		);
		$contentObject = new \stdClass();
		$configurationManager->expects($this->once())->method('getContentObject')->willReturn($contentObject);
		$configurationManager->expects($this->once())->method('getConfiguration')->willReturn(array('foo' => 'bar'));
		$instance->expects($this->once())->method('getRecord')->willReturn(array('uid' => 0));
		$GLOBALS['TSFE'] = (object) array('page' => 'page', 'fe_user' => (object) array('user' => 'user'));
		/** @var StandaloneView $view */
		$view = $this->getMock('FluidTYPO3\\Flux\\View\\ExposedTemplateView', array('assign', 'renderStandaloneSection'));
		$instance->injectConfigurationManager($configurationManager);
		$instance->_set('response', $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response'));
		$instance->_set('provider', $this->getMock('FluidTYPO3\\Flux\\Provider\\ProviderInterface'));
		$instance->initializeView($view);
	}

	public function testInitializeProvider() {
		/** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $pageConfigurationService */
		$pageConfigurationService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			array(
				'resolvePrimaryConfigurationProvider',
			)
		);
		/** @var PageService $pageService */
		$pageService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\PageService',
			array(
				'getPageTemplateConfiguration'
			)
		);
		$pageConfigurationService->expects($this->once())->method('resolvePrimaryConfigurationProvider');
		/** @var PageController|\PHPUnit_Framework_MockObject_MockObject $instance */
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Controller\\PageController', array('getRecord'));
		$instance->expects($this->once())->method('getRecord')->willReturn(array());
		$instance->injectpageConfigurationService($pageConfigurationService);
		$instance->injectPageService($pageService);
		$this->callInaccessibleMethod($instance, 'initializeProvider');
	}

	public function testRawAction() {
		$instance = new DummyPageController();
		/** @var ExposedTemplateView $view */
		$view = $this->getMock('FluidTYPO3\\Flux\\View\\ExposedTemplateView', array('assign'));
		/** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $pageConfigurationService */
		$pageConfigurationService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			array(
				'convertFileReferenceToTemplatePathAndFilename',
				'getViewConfigurationByFileReference',
			)
		);
		$pageConfigurationService->expects($this->once())->method('convertFileReferenceToTemplatePathAndFilename')->willReturn('test');
		$pageConfigurationService->expects($this->once())->method('getViewConfigurationByFileReference')->willReturn(array());
		$instance->injectpageConfigurationService($pageConfigurationService);
		$instance->setProvider(new Provider());
		$instance->setView($view);
		$instance->rawAction();
	}

}
