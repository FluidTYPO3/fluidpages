<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use FluidTYPO3\Fluidpages\Tests\Fixtures\Controller\DummyPageController;
use FluidTYPO3\Flux\Provider\Provider;

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
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
	}

	/**
	 * @return void
	 */
	public function testGetRecordDelegatesToRecordService() {
		$subject = $this->getMock('FluidTYPO3\\Fluidpages\\Controller\\PageController', array('dummy'));
		$mockService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', array('getSingle'));
		$mockService->expects($this->once())->method('getSingle');
		$subject->injectWorkspacesAwareRecordService($mockService);
		$subject->getRecord();
	}

	public function testInitializeView() {
		$instance = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Controller\\PageController',
			array(
				'getRecord', 'initializeProvider', 'initializeSettings', 'initializeOverriddenSettings',
				'initializeViewObject', 'initializeViewVariables'
			)
		);
		$configurationManager = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
			array('getContentObject', 'getConfiguration')
		);
		$contentObject = new \stdClass();
		$configurationManager->expects($this->once())->method('getContentObject')->willReturn($contentObject);
		$configurationManager->expects($this->once())->method('getConfiguration')->willReturn(array('foo' => 'bar'));
		$instance->expects($this->once())->method('getRecord')->willReturn(array('uid' => 0));
		$GLOBALS['TSFE'] = (object) array('page' => 'page', 'fe_user' => (object) array('user' => 'user'));
		$view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', array('assign'));
		$instance->injectConfigurationManager($configurationManager);
		$instance->initializeView($view);
	}

	public function testInitializeProvider() {
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			array(
				'resolvePrimaryConfigurationProvider',
			)
		);
		$pageService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\PageService',
			array(
				'getPageTemplateConfiguration'
			)
		);
		$configurationService->expects($this->once())->method('resolvePrimaryConfigurationProvider');
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Controller\\PageController', array('getRecord'));
		$instance->expects($this->once())->method('getRecord')->willReturn(array());
		$instance->injectConfigurationService($configurationService);
		$instance->injectPageService($pageService);
		$this->callInaccessibleMethod($instance, 'initializeProvider');
	}

	public function testRawAction() {
		$paths = array(
			'templateRootPath' => 'test',
			'partialRootPath' => 'test',
			'layoutRootPath' => 'test'
		);
		$instance = new DummyPageController();
		$view = $this->getMock('FluidTYPO3\\Flux\\View\\ExposedTemplateView', array('assign'));
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			array(
				'convertFileReferenceToTemplatePathAndFilename',
				'getViewConfigurationByFileReference',
			)
		);
		$configurationService->expects($this->once())->method('convertFileReferenceToTemplatePathAndFilename')->willReturn('test');
		$configurationService->expects($this->once())->method('getViewConfigurationByFileReference')->willReturn(array());
		$instance->injectConfigurationService($configurationService);
		$instance->setProvider(new Provider());
		$instance->setView($view);
		$instance->rawAction();
	}

}
