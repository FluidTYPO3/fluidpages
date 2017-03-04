<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\PageLayoutDataProvider;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Form;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PageLayoutDataProviderTest
 */
class PageLayoutDataProviderTest extends AbstractTestCase
{

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance(ObjectManager::class)->get(PageLayoutDataProvider::class);
        $this->assertAttributeInstanceOf(PageService::class, 'pageService', $instance);
        $this->assertAttributeInstanceOf(ConfigurationService::class, 'configurationService', $instance);
        $this->assertAttributeInstanceOf(ConfigurationManagerInterface::class, 'configurationManager', $instance);
    }

    /**
     * @param array $parameters
     * @param array $items
     * @param array $expected
     * @test
     * @dataProvider getAddItemsTestValues
     */
    public function testAddItems(array $parameters, array $items, array $expected)
    {
        $parameters['items'] = &$items;
        $instance = new PageLayoutDataProvider();
        $form = Form::create();
        $pageService = $this->getMockBuilder(PageService::class)->setMethods(['getAvailablePageTemplateFiles'])->getMock();
        $pageService->expects($this->once())->method('getAvailablePageTemplateFiles')->willReturn(['fluidpages' => [$form]]);
        $instance->injectPageService($pageService);
        $instance->addItems($parameters);
        $this->assertSame($expected, $items);
    }

    /**
     * @return array
     */
    public function getAddItemsTestValues()
    {
        return [

            [
                [],
                [],
                [['Fluid Pages Engine', '--div--'], [null, '->', null]]
            ],
            [
                [],
                [['foo', 'bar', 'baz']],
                [['foo', 'bar', 'baz'], ['Fluid Pages Engine', '--div--'], [null, '->', null]]
            ],
            [
                ['field' => 'tx_fed_page_controller_action_sub', 'row' => ['pid' => 1]],
                [['foo', 'bar', 'baz']],
                [['foo', 'bar', 'baz'], ['Parent decides', '', 'actions-move-down'], ['Fluid Pages Engine', '--div--'], [null, '->', null]]
            ],
            [
                ['field' => 'tx_fed_page_controller_action_sub', 'row' => ['pid' => 1, 'is_siteroot' => false]],
                [['foo', 'bar', 'baz']],
                [['foo', 'bar', 'baz'], ['Parent decides', '', 'actions-move-down'], ['Fluid Pages Engine', '--div--'], [null, '->', null]]
            ],
            [
                ['field' => 'tx_fed_page_controller_action', 'row' => ['pid' => 0, 'is_siteroot' => true]],
                [['foo', 'bar', 'baz']],
                [['foo', 'bar', 'baz'], ['Fluid Pages Engine', '--div--'], [null, '->', null]]
            ],

        ];
    }
}
