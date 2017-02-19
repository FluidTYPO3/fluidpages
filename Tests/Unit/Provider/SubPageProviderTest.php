<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Controller\PageControllerInterface;
use FluidTYPO3\Fluidpages\Provider\SubPageProvider;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Tests\Unit\AbstractTestCase;
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
     * @param boolean $expectsMessage
     * @param string $expected
     */
    public function testGetControllerActionFromRecord(array $record, $fieldName, $expectsMessage, $expected)
    {
        $instance = new SubPageProvider();
        if (PageControllerInterface::DOKTYPE_RAW !== $record['doktype']) {
            /** @var PageService $service */
            $service = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\PageService')->setMethods(array('getPageTemplateConfiguration'))->getMock();
            $instance->injectPageService($service);
        }
        if (true === $expectsMessage) {
            /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $configurationService */
            $configurationService = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService')->setMethods(array('message'))->getMock();
            $configurationService->expects($this->once())->method('message');
            $instance->injectPageConfigurationService($configurationService);
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
            array(array('doktype' => PageControllerInterface::DOKTYPE_RAW), '', false, 'raw'),
            array(array('doktype' => 0, 'tx_fed_page_controller_action_sub' => ''), 'tx_fed_page_flexform_sub', true, 'default'),
            array(array('doktype' => 0, 'tx_fed_page_controller_action_sub' => 'fluidpages->action'), 'tx_fed_page_flexform_sub', false, 'action'),
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
