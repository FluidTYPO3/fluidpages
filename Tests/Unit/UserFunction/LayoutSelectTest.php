<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\UserFunction;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\UserFunction\LayoutSelect;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class LayoutSelectTest extends AbstractTestCase
{
    /**
     * @return LayoutSelect
     */
    protected function getLayoutSelect()
    {
        return new LayoutSelect();
    }

    /**
     * @test
     */
    public function getPageTemplateConfigurationWithZeroUidReturnsNull()
    {
        $this->assertNull($this->getLayoutSelect()->getPageTemplateConfiguration(0));
    }

    /**
     * @dataProvider getPageTemplateConfigurationTestValues
     * @param array $records
     * @param array|NULL $expected
     */
    public function testGetPageTemplateConfiguration(array $records, $expected)
    {
        /** @var WorkspacesAwareRecordService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService')->setMethods(array('getSingle'))->getMock();
        foreach ($records as $index => $record) {
            //automatically set page uid
            $record['uid'] = $index + 1;
            $record['pid'] = $index + 2;
            $service->expects($this->at($index))->method('getSingle')->willReturn($record);
        }
        $instance = new LayoutSelect();
        $instance->setWorkspacesAwareRecordService($service);
        $result = $instance->getPageTemplateConfiguration(1);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getPageTemplateConfigurationTestValues()
    {
        $b  = 'backend_layout';
        $bs = 'backend_layout_next_level';
        $a  = 'tx_fed_page_controller_action';
        $as = 'tx_fed_page_controller_action_sub';
        $bfp = 'fluidpages__fluidpages';
        return [
            'no data at all' => [
                [[]],
                null
            ],
            'empty actions' => [
                [
                    [$a => '', $as => '', $b => $bfp, $bs => $bfp]
                ],
                null
            ],
            'controller action on page itself' => [
                [
                    [$a => 'test->self', $as => 'test->selfsub', $b => $bfp, $bs => $bfp]
                ],
                [$a => 'test->self', $as => 'test->selfsub']
            ],
            'sub controller action on parent page' => [
                [
                    //pages are listed in reverse order, root level last
                    [$a => '', $b => $bfp, $bs => $bfp],
                    [$as => 'test->selfsub', $b => $bfp, $bs => $bfp]
                ],
                [$a => 'test->selfsub', $as => 'test->selfsub']
            ],
            'no backend layout configured' => [
                [
                    [$a => 'test->self', $as => 'test->selfsub', $b => '', $bs => ''],
                ],
                null
            ],
            'backend layout configured only for parent page' => [
                [
                    [$a => 'test->self', $as => 'test->selfsub', $b => ''  , $bs => ''],
                    [$a => 'test->root', $as => 'test->rootsub', $b => $bfp, $bs => ''],
                ],
                null
            ],
            'backend layout configured on parent page' => [
                [
                    [$a => 'test->self', $as => 'test->selfsub', $b => '', $bs => ''],
                    [$a => 'test->root', $as => 'test->rootsub', $b => '', $bs => $bfp],
                ],
                [$a => 'test->self', $as => 'test->selfsub'],
            ],
            'backend layout configured on parent page #2' => [
                [
                    [$a => ''          , $as => ''            , $b => '', $bs => ''],
                    [$a => 'test->root', $as => 'test->rootsub', $b => '', $bs => $bfp],
                ],
                [$a => 'test->rootsub', $as => 'test->rootsub'],
            ],
            'different backend layout in between' => [
                [
                    [$a => ''          , $as => ''             , $b => '', $bs => ''],
                    [$a => ''          , $as => ''             , $b => '', $bs => 'templavoila'],
                    [$a => 'test->root', $as => 'test->rootsub', $b => '', $bs => $bfp],
                ],
                null
            ],
            'self backend layout, but different sub backend layout in between' => [
                [
                    [$a => ''          , $as => ''             , $b => $bfp, $bs => ''],
                    [$a => ''          , $as => ''             , $b => '', $bs => 'templavoila'],
                    [$a => 'test->root', $as => 'test->rootsub', $b => '', $bs => $bfp],
                ],
                [$a => 'test->rootsub', $as => null]
            ],
            'action and backend layout on different levels: action higher' => [
                [
                    [$a => ''          , $as => ''             , $b => '', $bs => ''],
                    [$a => ''          , $as => ''             , $b => '', $bs => $bfp],
                    [$a => 'test->root', $as => 'test->rootsub', $b => '', $bs => ''],
                ],
                [$a => 'test->rootsub', $as => 'test->rootsub']
            ],
            'action and backend layout on different levels: backend layout higher' => [
                [
                    [$a => ''         , $as => ''            , $b => '', $bs => ''],
                    [$a => 'test->mid', $as => 'test->midsub', $b => '', $bs => ''],
                    [$a => ''         , $as => ''            , $b => '', $bs => $bfp],
                ],
                [$a => 'test->midsub', $as => 'test->midsub']
            ],
            'only sub backend layout, but both actions, no parents' => [
                [
                    [$a => 'test->self', $as => 'test->selfsub', $b => '', $bs => $bfp],
                ],
                [$a => null, $as => 'test->selfsub']
            ],
            'only sub backend layout, but both actions, parents' => [
                [
                    [$a => 'test->self', $as => 'test->selfsub', $b => '', $bs => $bfp],
                    [$a => 'test->root', $as => 'test->rootsub', $b => '', $bs => ''],
                ],
                [$a => null, $as => 'test->selfsub']
            ],
        ];
    }
}
