<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Tests\Fixtures\Controller\DummyPageController;
use FluidTYPO3\Flux\Provider\Provider;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractPageControllerTest
 */
class AbstractPageControllerTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testGetRecordDelegatesToRecordService() {
		$subject = $this->getMockForAbstractClass('FluidTYPO3\\Fluidpages\\Controller\\AbstractPageController');
		$mockService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', array('getSingle'));
		$mockService->expects($this->once())->method('getSingle');
		$subject->injectWorkspacesAwareRecordService($mockService);
		$subject->getRecord();
	}

	public function testInitializeView() {
		$instance = $this->getMockForAbstractClass(
			'FluidTYPO3\\Fluidpages\\Controller\\AbstractPageController',
			array(), '', FALSE, FALSE, FALSE,
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
