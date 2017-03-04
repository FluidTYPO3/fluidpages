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
use FluidTYPO3\Fluidpages\Tests\Fixtures\Controller\DummyPageController;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Provider\Provider;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\View\ExposedTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PageControllerTest
 */
class PageControllerTest extends AbstractTestCase
{

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->get('FluidTYPO3\\Fluidpages\\Controller\\PageController');
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'pageConfigurationService', $instance);
    }

    /**
     * @return void
     */
    public function testGetRecordDelegatesToRecordService()
    {
        /** @var PageController $subject */
        $subject = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Controller\\PageController')->setMethods(array('dummy'))->getMock();
        /** @var WorkspacesAwareRecordService|\PHPUnit_Framework_MockObject_MockObject $mockService */
        $mockService = $this->getMockBuilder('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService')->setMethods(array('getSingle'))->getMock();
        $mockService->expects($this->once())->method('getSingle');
        $subject->injectWorkspacesAwareRecordService($mockService);
        $subject->getRecord();
    }

    public function testInitializeView()
    {
        /** @var PageController|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Controller\\PageController'
        )->setMethods(
            array(
                'getRecord', 'initializeProvider', 'initializeSettings', 'initializeOverriddenSettings',
                'initializeViewObject', 'initializeViewVariables', 'initializeViewHelperVariableContainer'
            )
        )->getMock();
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMockBuilder(
            'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager'
        )->setMethods(
            array('getContentObject', 'getConfiguration')
        )->getMock();
        $contentObject = new \stdClass();
        $configurationManager->expects($this->once())->method('getContentObject')->willReturn($contentObject);
        $configurationManager->expects($this->once())->method('getConfiguration')->willReturn(array('foo' => 'bar'));
        $instance->expects($this->once())->method('getRecord')->willReturn(array('uid' => 0));
        $GLOBALS['TSFE'] = (object) array('page' => 'page', 'fe_user' => (object) array('user' => 'user'));
        /** @var StandaloneView $view */
        $view = $this->getMockBuilder('FluidTYPO3\\Flux\\View\\ExposedTemplateView')->setMethods(array('assign', 'renderStandaloneSection'))->getMock();
        $instance->injectConfigurationManager($configurationManager);
        ObjectAccess::setProperty($instance, 'response', $this->getMockBuilder('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response')->getMock(), true);
        ObjectAccess::setProperty($instance, 'provider', $this->getMockBuilder('FluidTYPO3\\Flux\\Provider\\ProviderInterface')->getMock(), true);
        $instance->initializeView($view);
    }

    public function testInitializeProvider()
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $pageConfigurationService */
        $pageConfigurationService = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array(
                'resolvePrimaryConfigurationProvider',
            )
        )->getMock();
        /** @var PageService $pageService */
        $pageService = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\PageService'
        )->setMethods(
            array(
                'getPageTemplateConfiguration'
            )
        )->getMock();
        $pageConfigurationService->expects($this->once())->method('resolvePrimaryConfigurationProvider');
        /** @var PageController|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Controller\\PageController')->setMethods(array('getRecord'))->getMock();
        $instance->expects($this->once())->method('getRecord')->willReturn(array());
        $instance->injectpageConfigurationService($pageConfigurationService);
        $instance->injectPageService($pageService);
        $this->callInaccessibleMethod($instance, 'initializeProvider');
    }

    public function testRawAction()
    {
        $instance = new DummyPageController();
        /** @var ExposedTemplateView $view */
        $view = $this->getMockBuilder('FluidTYPO3\\Flux\\View\\ExposedTemplateView')->setMethods(array('assign'))->getMock();
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $pageConfigurationService */
        $pageConfigurationService = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array(
                'convertFileReferenceToTemplatePathAndFilename',
                'getViewConfigurationByFileReference',
            )
        )->getMock();
        $pageConfigurationService->expects($this->once())->method('convertFileReferenceToTemplatePathAndFilename')->willReturn('test');
        $pageConfigurationService->expects($this->once())->method('getViewConfigurationByFileReference')->willReturn(array());
        $instance->injectpageConfigurationService($pageConfigurationService);
        $instance->setProvider(new Provider());
        $instance->setView($view);
        $instance->rawAction();
    }
}
