<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Core;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationServiceTest
 */
class ConfigurationServiceTest extends AbstractTestCase
{

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->get('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService');
        $this->assertAttributeInstanceOf('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', 'resourceFactory', $instance);
    }

    /**
     * @dataProvider getConvertFileReferenceToTemplatePathAndFilenameTestValues
     * @param string $reference
     * @param string|NULL $resourceFactoryOutput
     * @param string $expected
     * @return void
     */
    public function testConvertFileReferenceToTemplatePathAndFilename($reference, $resourceFactoryOutput, $expected)
    {
        $instance = new ConfigurationService();
        if (null !== $resourceFactoryOutput) {
            /** @var ResourceFactory|\PHPUnit_Framework_MockObject_MockObject $resourceFactory */
            $resourceFactory = $this->getMockBuilder(
                'TYPO3\\CMS\\Core\\Resource\\ResourceFactory'
            )->setMethods(
                array('getFileObjectFromCombinedIdentifier')
            )->getMock();
            $resourceFactory->expects($this->once())->method('getFileObjectFromCombinedIdentifier')
                ->with($reference)->willReturn($resourceFactoryOutput);
            $instance->injectResourceFactory($resourceFactory);
        }
        $result = $instance->convertFileReferenceToTemplatePathAndFilename($reference);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getConvertFileReferenceToTemplatePathAndFilenameTestValues()
    {
        $relativeReference = 'Tests/Fixtures/Templates/Page/Dummy.html';
        return array(
            array($relativeReference, null, GeneralUtility::getFileAbsFileName($relativeReference)),
            array('1', $relativeReference, $relativeReference),
        );
    }

    /**
     * @dataProvider getViewConfigurationByFileReferenceTestValues
     * @param string $reference
     * @param string $expectedParameter
     * @return void
     */
    public function testGetViewConfigurationByFileReference($reference, $expectedParameter)
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array('getViewConfigurationForExtensionName')
        )->getMock();
        $instance->expects($this->once())->method('getViewConfigurationForExtensionName')
            ->with($expectedParameter)->willReturn($expectedParameter);
        $result = $instance->getViewConfigurationByFileReference($reference);
        $this->assertEquals($expectedParameter, $result);
    }

    /**
     * @return array
     */
    public function getViewConfigurationByFileReferenceTestValues()
    {
        return array(
            array('some/file', 'fluidpages'),
            array('EXT:fluidpages/some/file', 'fluidpages'),
            array('EXT:other/some/file', 'other')
        );
    }

    /**
     * @dataProvider getPageConfigurationInvalidTestValues
     * @param mixed $input
     * @return void
     */
    public function testGetPageConfigurationReturnsEmptyArrayAndDispatchesMessageOnInvalidInput($input)
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array('message')
        )->getMock();
        $instance->expects($this->once())->method('message');
        $result = $instance->getPageConfiguration($input);
        $this->assertEquals(array(), $result);
    }

    /**
     * @return array
     */
    public function getPageConfigurationInvalidTestValues()
    {
        return array(
            array(''),
            array(0),
            array(array()),
        );
    }

    /**
     * @return void
     */
    public function testGetPageConfigurationCallsGetViewConfigurationForExtensionName()
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array('getViewConfigurationForExtensionName')
        )->getMock();
        $instance->expects($this->once())->method('getViewConfigurationForExtensionName')->with('foobar')->willReturn(array());
        $result = $instance->getPageConfiguration('foobar');
        $this->assertEquals(array(), $result);
    }

    /**
     * @return void
     */
    public function testGetPageConfigurationWithoutExtensionNameReadsRegisteredProviders()
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(
            'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService'
        )->setMethods(
            array('getViewConfigurationForExtensionName')
        )->getMock();
        Core::registerProviderExtensionKey('foo', 'Page');
        Core::registerProviderExtensionKey('bar', 'Page');
        $instance->expects($this->exactly(2))->method('getViewConfigurationForExtensionName');
        $result = $instance->getPageConfiguration();
        $this->assertCount(2, $result);
    }
}
