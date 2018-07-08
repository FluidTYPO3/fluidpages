<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Form\Container\Grid;
use FluidTYPO3\Flux\Provider\Provider;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BackendLayoutDataProviderTest
 */
class BackendLayoutDataProviderTest extends AbstractTestCase
{

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->get('FluidTYPO3\\Fluidpages\\Backend\\BackendLayoutDataProvider');
        $this->assertAttributeInstanceOf('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', 'objectManager', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', 'recordService', $instance);
    }

    /**
     * @return void
     */
    public function testGetBackendLayout()
    {
        $this->markTestSkipped('Skipped until Flux colPos feature is merged');
        /** @var BackendLayoutDataProvider|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(BackendLayoutDataProvider::class)->setMethods(['resolveProvider'])->getMock();
        $provider = new Provider();
        $provider->setGrid(Grid::create([]));
        $recordService = $this->getMockBuilder(WorkspacesAwareRecordService::class)->setMethods(['getSingle'])->getMock();
        $recordService->expects($this->once())->method('getSingle')->willReturn(['uid' => 123]);
        $instance->expects($this->once())->method('resolveProvider')->with(['uid' => 123])->willReturn($provider);
        $instance->injectWorkspacesAwareRecordService($recordService);
        $result = $instance->getBackendLayout('identifier', 1);
        $this->assertInstanceOf('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', $result);
        $this->assertEquals('empty', $result->getIdentifier());
    }

    /**
     * @return void
     */
    public function testAddBackendLayouts()
    {
        /** @var BackendLayoutDataProvider|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(BackendLayoutDataProvider::class)->setMethods(['getBackendLayout'])->getMock();
        $instance->expects($this->once())->method('getBackendLayout')->willReturn(new BackendLayout('test', 'test', ''));
        $collection = new BackendLayoutCollection('collection');
        $context = new DataProviderContext();
        $context->setPageId(1);
        $instance->addBackendLayouts($context, $collection);
        $this->assertNotEmpty($collection->getAll());
    }
}
