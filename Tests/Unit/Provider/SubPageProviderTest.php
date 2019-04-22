<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Development\AbstractTestCase;
use FluidTYPO3\Fluidpages\Controller\PageControllerInterface;
use FluidTYPO3\Fluidpages\Provider\SubPageProvider;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class SubPageProviderTest
 */
class SubPageProviderTest extends AbstractTestCase
{

    /**
     * @dataProvider getControllerActionFromRecordTestValues
     * @param array $record
     * @param string $fieldName
     * @param bool $expectsPageService
     * @param string $expected
     */
    public function testGetControllerActionFromRecord(array $record, $fieldName, $expectsPageService, $expected)
    {
        $instance = new SubPageProvider();
        $service = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\PageService')->setMethods(array('getPageTemplateConfiguration'))->getMock();
        $instance->injectPageService($service);
        if ($expectsPageService) {
            $service->expects($this->once())->method('getPageTemplateConfiguration')->willReturn($record);
        } else {
            $service->expects($this->never())->method('getPageTemplateConfiguration');
        }
        // make sure PageProvider is now using the right field name
        $instance->trigger($record, null, $fieldName);
        $result = $instance->getControllerActionFromRecord($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getControllerActionFromRecordTestValues()
    {
        return array(
            array(array('doktype' => 0, 'tx_fed_page_controller_action' => '', 'tx_fed_page_controller_action_sub' => ''), 'tx_fed_page_flexform_sub', true, 'default'),
            array(array('doktype' => 0, 'tx_fed_page_controller_action' => 'fluidpages->direct', 'tx_fed_page_controller_action_sub' => 'fluidpages->action'), 'tx_fed_page_flexform_sub', false, 'direct'),
            array(array('doktype' => 0, 'tx_fed_page_controller_action' => '', 'tx_fed_page_controller_action_sub' => 'fluidpages->parent'), 'tx_fed_page_flexform_sub', true, 'parent'),
        );
    }

    public function testGetTemplatePathAndFilename()
    {
        $expected = ExtensionManagementUtility::extPath('fluidpages', 'Tests/Fixtures/Templates/Page/Dummy.html');
        $dataFieldName = 'tx_fed_page_flexform_sub';
        $fieldName = 'tx_fed_page_controller_action_sub';
        /** @var PageService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\PageService')->setMethods(array('getPageTemplateConfiguration'))->getMock();
        $instance = new SubPageProvider();
        $instance->setTemplatePaths(array('templateRootPaths' => array('EXT:fluidpages/Tests/Fixtures/Templates/')));
        $instance->injectPageService($service);
        $record = array(
            $fieldName => 'Fluidpages->dummy',
        );
        $service->expects($this->any())->method('getPageTemplateConfiguration')->willReturn($record);
        $instance->trigger($record, null, $dataFieldName);
        $result = $instance->getTemplatePathAndFilename($record);
        $this->assertEquals($expected, $result);
    }
}
