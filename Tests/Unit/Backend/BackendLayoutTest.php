<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayout;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Form\Container\Grid;
use FluidTYPO3\Flux\Provider\Provider;
use FluidTYPO3\Flux\Service\ContentService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BackendLayoutTest
 */
class BackendLayoutTest extends AbstractTestCase
{

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->get('FluidTYPO3\\Fluidpages\\Backend\\BackendLayout');
        $this->assertAttributeInstanceOf('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', 'objectManager', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', 'workspacesAwareRecordService', $instance);
    }

    /**
     * @dataProvider getPostProcessBackendLayoutTestValues
     * @param Provider $provider
     * @param mixed $record
     * @param string $messageFunction
     * @param integer $messageCount
     * @param array $expected
     */
    public function testPostProcessBackendLayout(Provider $provider, $record, $messageFunction, $messageCount, array $expected)
    {
        $instance = new BackendLayout();
        $pageUid = 1;
        $backendLayout = array();
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $configurationService */
        $configurationService = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array('resolvePrimaryConfigurationProvider', 'debug', 'message')
        )->getMock();
        $configurationService->expects($this->exactly($messageCount))->method($messageFunction);
        if (null !== $record) {
            $configurationService->expects($this->once())->method('resolvePrimaryConfigurationProvider')
                ->with('pages', 'tx_fed_page_flexform', $record)->willReturn($provider);
        }
        /** @var WorkspacesAwareRecordService|\PHPUnit_Framework_MockObject_MockObject $recordService */
        $recordService = $this->getMockBuilder('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService')->setMethods(array('getSingle'))->getMock();
        $recordService->expects($this->once())->method('getSingle')->willReturn($record);
        $instance->injectConfigurationService($configurationService);
        $instance->injectWorkspacesAwareRecordService($recordService);
        $instance->postProcessBackendLayout($pageUid, $backendLayout);
        $this->assertEquals($expected, $backendLayout);
    }

    /**
     * @return array
     */
    public function getPostProcessBackendLayoutTestValues()
    {
        $form = Form::create(array('id' => 'formId'));
        /** @var Provider|\PHPUnit_Framework_MockObject_MockObject $standardProvider */
        $standardProvider = $this->getMockBuilder(
            'FluidTYPO3\\Flux\\Provider\\Provider'
        )->setMethods(
            array('getControllerActionFromRecord', 'getForm')
        )->getMock();
        $standardProvider->setTemplatePaths(array());
        $standardProvider->expects($this->any())->method('getForm')->willReturn($form);
        $actionLessProvider = clone $standardProvider;
        $exceptionProvider = clone $standardProvider;
        $emptyGridProvider = clone $standardProvider;
        $gridProvider = clone $standardProvider;
        $actionLessProvider->expects($this->any())->method('getControllerActionFromRecord')->willReturn(null);
        $exceptionProvider->expects($this->any())->method('getControllerActionFromRecord')->willThrowException(new \RuntimeException());
        /** @var Grid $grid */
        $grid = Grid::create();
        $grid->setParent($form);
        $emptyGridProvider->setGrid($grid);
        $emptyGridProvider->expects($this->any())->method('getControllerActionFromRecord')->willReturn('default');
        /** @var Grid $grid */
        $grid = Grid::create(array());
        $grid->setParent($form);
        $grid->createContainer('Row', 'row')->createContainer('Column', 'column')->setColSpan(3)->setRowSpan(3)->setColumnPosition(2);
        $gridProvider->setGrid($grid);
        $gridProvider->expects($this->any())->method('getControllerActionFromRecord')->willReturn('default');
        $gridArray = array(
            '__config' => array(
                'backend_layout.' => array(
                    'colCount' => 3,
                    'rowCount' => 1,
                    'rows.' => array(
                        '1.' => array(
                            'columns.' => array(
                                '1.' => array(
                                    'name' => 'LLL:EXT:flux/Resources/Private/Language/locallang.xlf:flux.formId.columns.column',
                                    'colPos' => 2,
                                    'colspan' => 3,
                                    'rowspan' => 3
                                )
                            )
                        )
                    )
                )
            ),
            '__colPosList' => array(2),
            '__items' => array(
                array('LLL:EXT:flux/Resources/Private/Language/locallang.xlf:flux.formId.columns.column', 2, null)
            )
        );
        return array(
            array($standardProvider, null, 'message', 0, array()),
            array($standardProvider, array(), 'message', 1, array()),
            array($actionLessProvider, array(), 'message', 1, array()),
            array($emptyGridProvider, array(), 'message', 1, array()),
            array($exceptionProvider, array(), 'debug', 1, array()),
            array($gridProvider, array(), 'message', 0, $gridArray),
        );
    }

    /**
     * @return void
     */
    public function testPreProcessBackendLayoutPageUidPerformsNoOperation()
    {
        $id = 1;
        $instance = new BackendLayout();
        $instance->preProcessBackendLayoutPageUid($id);
        $this->assertEquals(1, $id);
    }

    /**
     * @return void
     */
    public function testPostProcessColPosListItemsParsedPerformsNoOperation()
    {
        $id = 1;
        $tca = array('foo' => 'bar');
        /** @var FormEngine $mock */
        $mock = $this->getMockBuilder('TYPO3\CMS\Backend\Form\FormEngine')->setMethods(array('fake'))->disableOriginalConstructor()->getMock();
        $instance = new BackendLayout();
        $instance->postProcessColPosListItemsParsed($id, $tca, $mock);
        $this->assertEquals(1, $id);
        $this->assertEquals(array('foo' => 'bar'), $tca);
    }

    /**
     * @return void
     */
    public function testPostProcessColPosProcFuncItemsAppendsFluidContentArea()
    {
        $instance = new BackendLayout();
        $parameters = array(
            'items' => array()
        );
        $instance->postProcessColPosProcFuncItems($parameters);
        $this->assertContains(array('Fluid Content Area', ContentService::COLPOS_FLUXCONTENT, null), $parameters['items']);
    }
}
