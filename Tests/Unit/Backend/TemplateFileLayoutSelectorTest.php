<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\TemplateFileLayoutSelector;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TemplateFileLayoutSelectorTest
 */
class TemplateFileLayoutSelectorTest extends AbstractTestCase
{

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->get('FluidTYPO3\\Fluidpages\\Backend\\TemplateFileLayoutSelector');
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
    }

    /**
     * @return void
     */
    public function testAddLayoutOptions()
    {
        $layoutRootPath = 'EXT:fluidpages/Tests/Fixtures/Templates/Page/';
        $parameters = array('items' => array());
        $instance = new TemplateFileLayoutSelector();
        $parent = '';
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService')->setMethods(array('getViewConfigurationByFileReference'))->getMock();
        $service->expects($this->once())->method('getViewConfigurationByFileReference')->willReturn(array(
            'layoutRootPaths' => array($layoutRootPath)
        ));
        $instance->injectConfigurationService($service);
        $instance->addLayoutOptions($parameters, $parent);
        $this->assertEquals(array('Dummy', 'Dummy'), $parameters['items'][0]);
    }
}
