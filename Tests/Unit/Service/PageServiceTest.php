<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PageServiceTest
 * @package FluidTYPO3\Fluidpages\Tests\Unit\Service
 */
class PageServiceTest extends AbstractTestCase
{

    /**
     * @return PageService
     */
    protected function getPageService()
    {
        return new PageService();
    }

    /**
     * @test
     */
    public function getPageFlexFormSourceWithZeroUidReturnsNull()
    {
        $this->assertNull($this->getPageService()->getPageFlexFormSource(0));
    }

    /**
     * @test
     */
    public function getPageTemplateConfigurationWithZeroUidReturnsNull()
    {
        $this->assertNull($this->getPageService()->getPageTemplateConfiguration(0));
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
            $service->expects($this->at($index))->method('getSingle')->willReturn($record);
        }
        $instance = new PageService();
        $instance->injectWorkspacesAwareRecordService($service);
        $result = $instance->getPageTemplateConfiguration(2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getPageTemplateConfigurationTestValues()
    {
        $u = 'uid';
        $p = 'pid';
        $o = 't3ver_oid';
        $m = 'tx_fed_page_controller_action';
        $s = 'tx_fed_page_controller_action_sub';
        return array(
            array(array(null), null),
            array(array(array($u => 2, $p => 0, $o => 0, $m => '', $s => '')), null),
            array(array(array($u => 2, $p => 0, $o => 0, $m => 'test1->test1', $s => 'test2->test2')), array($m => 'test1->test1', $s => 'test2->test2')),
            array(array(array($u => 2, $p => 1, $o => 0, $m => ''), array($u => 1, $p => 0, $o => 0, $s => 'test2->test2')), array($m => 'test2->test2', $s => 'test2->test2')),
            array(array(array($u => 2, $p => -1, $o => 1, $m => ''), array($u => 1, $p => 0, $o => 0, $s => 'test2->test2')), array($m => 'test2->test2', $s => 'test2->test2'))
        );
    }

    /**
     * @return void
     */
    public function testGetPageFlexFormSource()
    {
        $record1 = array('pid' => 2, 'uid' => 1);
        $record2 = array('pid' => 0, 'uid' => 3, 'tx_fed_page_flexform' => 'test');
        /** @var WorkspacesAwareRecordService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService')->setMethods(array('getSingle'))->getMock();
        $service->expects($this->at(0))->method('getSingle')->with('pages', 'uid,pid,t3ver_oid,tx_fed_page_flexform', 1)->willReturn($record1);
        $service->expects($this->at(1))->method('getSingle')->with('pages', 'uid,pid,t3ver_oid,tx_fed_page_flexform', 2)->willReturn($record2);
        $instance = new PageService();
        $instance->injectWorkspacesAwareRecordService($service);
        $output = $instance->getPageFlexFormSource(1);
        $this->assertEquals('test', $output);
    }

    /**
     * @dataProvider getAvailablePageTemplateFilesTestValues
     * @param string|array $typoScript
     * @param mixed $expected
     */
    public function testGetAvailablePageTemplateFiles($typoScript, $expected)
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array('getPageConfiguration', 'message', 'getFormFromTemplateFile')
        )->getMock();
        $service->expects($this->any())->method('getFormFromTemplateFile')->willReturn(Form::create());
        $service->expects($this->once())->method('getPageConfiguration')->willReturn($typoScript);
        $instance = new PageService();
        $instance->injectConfigurationService($service);
        $instance->injectObjectManager(GeneralUtility::makeInstance(ObjectManager::class));
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'] = [
            'f' => [
                'TYPO3\\CMS\\Fluid\\ViewHelpers',
                'TYPO3Fluid\\Fluid\\ViewHelpers'
            ]
        ];
        $result = $instance->getAvailablePageTemplateFiles();
        if (null === $expected) {
            $this->assertEmpty($result);
        } else {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * @return array
     */
    public function getAvailablePageTemplateFilesTestValues()
    {
        return array(
            array(array(), null),
            array(array('test' => array('enable' => false)), null),
            array(
                array('fluidpages' => array('templateRootPaths' => array('Dummy'))),
                array('fluidpages' => array('Dummy'))
            ),
            array(
                array('fluidpages' => array('templateRootPaths' => array(ExtensionManagementUtility::extPath('fluidpages', 'Invalid')))),
                array('fluidpages' => null)
            ),
            array(
                array('fluidpages' => array('templateRootPaths' => array(ExtensionManagementUtility::extPath('fluidpages', 'Resources/Private/Templates/')))),
                array('fluidpages' => null)
            ),
        );
    }
}
