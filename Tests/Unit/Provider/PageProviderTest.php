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
use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Fluidpages\Tests\Fixtures\Provider\DummyPageProvider;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Service\RecordService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Records;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Xml;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageProviderTest
 */
class PageProviderTest extends AbstractTestCase
{
    const FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL = 'EXT:flux/Tests/Fixtures/Templates/Content/AbsolutelyMinimal.html';

    /**
     * @var array
     */
    public static $contentRecordWithoutParentAndWithoutChildren = array(
        'uid' => 123,
        'header' => 'Has no parent',
        'colPos' => 0,
        'tx_flux_parent' => 0,
        'tx_flux_column' => '',
    );

    /**
     * @return void
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->get('FluidTYPO3\\Fluidpages\\Provider\\PageProvider');
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
        $this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'pageConfigurationService', $instance);
    }

    public function testGetExtensionKey()
    {
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getControllerExtensionKeyFromRecord'))->getMock();
        $instance->expects($this->once())->method('getControllerExtensionKeyFromRecord')->willReturn('fluidpages');
        $result = $instance->getExtensionKey(array());
        $this->assertEquals('fluidpages', $result);
    }

    public function testGetExtensionKeyWithoutSelection()
    {
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getControllerExtensionKeyFromRecord'))->getMock();
        $instance->expects($this->once())->method('getControllerExtensionKeyFromRecord')->willReturn(null);
        $result = $instance->getExtensionKey(array());
        $this->assertEquals('fluidpages', $result);
    }

    public function testGetTemplatePathAndFilename()
    {
        $expected = ExtensionManagementUtility::extPath('fluidpages', 'Tests/Fixtures/Templates/Page/Dummy.html');
        $fieldName = 'tx_fed_page_controller_action';
        $dataFieldName = 'tx_fed_page_flexform';
        /** @var PageService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\PageService')->setMethods(array('getPageTemplateConfiguration'))->getMock();
        $instance = new PageProvider();
        $instance->setTemplatePaths(array('templateRootPaths' => array('EXT:fluidpages/Tests/Fixtures/Templates/')));
        $instance->injectPageService($service);

        /** @var LayoutSelect $layoutSelectMock */
        $layoutSelectMock = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\UserFunction\\LayoutSelect')->setMethods(array('getPageTemplateConfiguration'))->getMock();
        $instance->injectLayoutSelect($layoutSelectMock);

        $record = array(
            'backend_layout' => 'fluidpages__fluidpages',
            $fieldName => 'Fluidpages->dummy',
        );
        $service->expects($this->any())->method('getPageTemplateConfiguration')->willReturn($record);
        $instance->trigger($record, null, $dataFieldName);
        $result = $instance->getTemplatePathAndFilename($record);
        $this->assertEquals($expected, $result);
    }

    public function testGetFormCallsSetDefaultValuesInFieldsWithInheritedValues()
    {
        /** @var Form $form */
        $form = Form::create();
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('setDefaultValuesInFieldsWithInheritedValues'))->getMock();
        $instance->injectPageService(new PageService());
        $instance->expects($this->once())->method('setDefaultValuesInFieldsWithInheritedValues')->willReturn($form);
        $instance->setForm($form);
        $instance->getForm(array());
    }

    public function testGetControllerExtensionKeyFromRecordReturnsPresetKeyOnUnrecognisedAction()
    {
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getControllerActionReferenceFromRecord'))->getMock();
        $instance->expects($this->once())->method('getControllerActionReferenceFromRecord')->willReturn('invalid');
        $instance->setExtensionKey('fallback');
        $result = $instance->getControllerExtensionKeyFromRecord(array());
        $this->assertEquals('fallback', $result);
    }

    /**
     * @dataProvider getInheritanceTreeTestValues
     * @param array $input
     * @param array $expected
     */
    public function testGetInheritanceTree(array $input, array $expected)
    {
        $record = array('uid' => 1);
        $instance = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('loadRecordTreeFromDatabase'))->getMock();
        $instance->expects($this->once())->method('loadRecordTreeFromDatabase')->with($record)->willReturn($input);
        $result = $this->callInaccessibleMethod($instance, 'getInheritanceTree', $record);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getInheritanceTreeTestValues()
    {
        return array(
            array(array(), array()),
            array(
                array(array(PageProvider::FIELD_ACTION_SUB => 'testsub'), array(PageProvider::FIELD_ACTION_MAIN => 'testmain')),
                array(array(PageProvider::FIELD_ACTION_MAIN => 'testmain'))
            ),
            array(
                array(array(PageProvider::FIELD_ACTION_SUB => 'testsub'), array(PageProvider::FIELD_ACTION_MAIN => '')),
                array(array(PageProvider::FIELD_ACTION_SUB => 'testsub'), array(PageProvider::FIELD_ACTION_MAIN => ''))
            ),
        );
    }

    /**
     * @dataProvider getControllerActionFromRecordTestValues
     * @param array $record
     * @param string $fieldName
     * @param boolean $expectsMessage
     * @param string $expected
     */
    public function testGetControllerActionFromRecord(array $record, $fieldName, $expectsMessage, $expected)
    {
        $instance = new PageProvider();
        if (PageControllerInterface::DOKTYPE_RAW !== $record['doktype'] && true === empty($record[$fieldName])) {
            /** @var PageService $service */
            $service = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\PageService')->setMethods(array('getPageTemplateConfiguration'))->getMock();
            $instance->injectPageService($service);

            /** @var LayoutSelect $layoutSelectMock */
            $layoutSelectMock = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\UserFunction\\LayoutSelect')->setMethods(array('getPageTemplateConfiguration'))->getMock();
            $instance->injectLayoutSelect($layoutSelectMock);
        }
        if (true === $expectsMessage) {
            /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $configurationService */
            $configurationService = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService')->getMock();
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
            array(array('doktype' => 0, 'backend_layout' => 'fluidpages__fluidpages', 'tx_fed_page_controller_action' => ''), 'tx_fed_page_flexform', true, 'default'),
            array(array('doktype' => 0, 'backend_layout' => 'fluidpages__fluidpages', 'tx_fed_page_controller_action' => 'fluidpages->action'), 'tx_fed_page_flexform', false, 'action'),
        );
    }

    /**
     * @dataProvider getControllerActionReferenceFromRecordTestValues
     *
     * @param array $record   Page row
     * @param mixed $calcRow  Row data calculated by pageService->getPageTemplateConfiguration
     * @param mixed $expected Expected return value
     */
    public function testGetControllerActionReferenceFromRecord(array $record, $calcRow, $expected)
    {
        $instance = new PageProvider();
        /** @var LayoutSelect $layoutSelectMock */
        $layoutSelectMock = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\UserFunction\\LayoutSelect')->setMethods(array('getPageTemplateConfiguration'))->getMock();
        $layoutSelectMock->expects($this->any())
                        ->method('getPageTemplateConfiguration')
                        ->will($this->returnValue($calcRow));
        $instance->injectLayoutSelect($layoutSelectMock);

        $result = $instance->getControllerActionReferenceFromRecord($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for testGetControllerActionReferenceFromRecord()
     *
     * @return array
     */
    public function getControllerActionReferenceFromRecordTestValues()
    {
        return [
            'no action' => [
                [
                    'tx_fed_page_controller_action' => '',
                ],
                null,
                null
            ],
            'normal action' => [
                [
                    'tx_fed_page_controller_action' => 'test1->test1',
                    'backend_layout'                => 'fluidpages__foo',
                ],
                [],
                'test1->test1'
            ],
            'no action, result from pageService' => [
                [
                    'tx_fed_page_controller_action' => '',
                    'backend_layout'                => 'fluidpages__bar',
                ],
                [
                    'tx_fed_page_controller_action' => 'test1->test1',
                ],
                'test1->test1'
            ],
            'no backend layout' => [
                [
                    'tx_fed_page_controller_action' => 'test1->test1',
                    'backend_layout'                => '',
                ],
                null,
                null
            ],
            'no backend layout, result from pageService' => [
                [
                    'tx_fed_page_controller_action' => 'test1->test1',
                    'backend_layout'                => '',
                ],
                [
                    'tx_fed_page_controller_action' => 'test2->test2'
                ],
                'test2->test2'
            ],
        ];
    }

    public function testGetFlexFormValuesReturnsCollectedDataWhenEncounteringNullForm()
    {
        $tree = array(
            $this->getBasicRecord(),
            $this->getBasicRecord()
        );
        /** @var Form $form */
        $form = Form::create();
        $form->createField('Input', 'foo');
        $record = $this->getBasicRecord();
        /** @var DummyPageProvider $dummyProvider1 */
        $dummyProvider1 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
        /** @var DummyPageProvider $dummyProvider2 */
        $dummyProvider2 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
        $dummyProvider1->setForm($form);
        $dummyProvider1->setFlexFormValues(array('foo' => 'bar'));
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getInheritanceTree', 'unsetInheritedValues', 'getForm'))->getMock();
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationService */
        $mockConfigurationService = $this->getMockBuilder('FluidTYPO3\Fluidpages\Service\ConfigurationService')->setMethods(array('resolvePrimaryConfigurationProvider'))->getMock();
        $mockConfigurationService->expects($this->at(0))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider1);
        $mockConfigurationService->expects($this->at(1))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider2);
        $provider->expects($this->once())->method('getInheritanceTree')->will($this->returnValue($tree));
        $provider->expects($this->any())->method('unsetInheritedValues');
        $provider->expects($this->any())->method('getForm')->willReturn(Form::create());
        $provider->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(self::FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL));
        $provider->injectPageConfigurationService($mockConfigurationService);
        $provider->injectConfigurationService($this->getMockBuilder(FluxService::class)->getMock());
        $values = $provider->getFlexformValues($record);
        $this->assertEquals($values, array());
    }

    /**
     * @test
     */
    public function canGetFlexformValuesUnderInheritanceConditions()
    {
        $tree = array(
            $this->getBasicRecord(),
            $this->getBasicRecord()
        );
        /** @var Form $form */
        $form = Form::create();
        $form->createField('Input', 'foo');
        $record = $this->getBasicRecord();
        // use a new uid to prevent caching issues
        $record['uid'] = $record['uid'] + 1;
        /** @var DummyPageProvider $dummyProvider1 */
        $dummyProvider1 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
        /** @var DummyPageProvider $dummyProvider2 */
        $dummyProvider2 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
        $dummyProvider1->setForm($form);
        $dummyProvider1->setFlexFormValues(array('foo' => 'bar'));
        /** @var Form $form2 */
        $form2 = Form::create();
        $dummyProvider2->setForm($form2);
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getInheritanceTree', 'unsetInheritedValues', 'getForm'))->getMock();
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationService */
        $mockConfigurationService = $this->getMockBuilder('FluidTYPO3\Fluidpages\Service\ConfigurationService')->setMethods(array('resolvePrimaryConfigurationProvider'))->getMock();
        $mockConfigurationService->expects($this->at(0))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider1);
        $mockConfigurationService->expects($this->at(1))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider2);
        $provider->expects($this->once())->method('getInheritanceTree')->will($this->returnValue($tree));
        $provider->expects($this->any())->method('unsetInheritedValues');
        $provider->expects($this->any())->method('getForm')->willReturn(Form::create());
        $provider->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(self::FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL));
        $provider->injectPageConfigurationService($mockConfigurationService);
        $provider->injectConfigurationService($this->getMockBuilder(FluxService::class)->getMock());
        $values = $provider->getFlexformValues($record);
        $this->assertEquals($values, array());
    }

    /**
     * @test
     */
    public function canLoadRecordTreeFromDatabase()
    {
        $record = $this->getBasicRecord();
        $provider = $this->getMockBuilder(
            str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4))
        )->setMethods(
            array('loadRecordFromDatabase', 'getParentFieldName', 'getParentFieldValue')
        )->getMock();
        $provider->expects($this->exactly(2))->method('getParentFieldName')->will($this->returnValue('somefield'));
        $provider->expects($this->exactly(1))->method('getParentFieldValue')->will($this->returnValue(1));
        $recordService = $this->getMockBuilder(WorkspacesAwareRecordService::class)->setMethods(['getSingle'])->getMock();
        $recordService->expects($this->exactly(1))->method('getSingle')->will($this->returnValue($record));
        $provider->injectRecordService($recordService);
        $output = $this->callInaccessibleMethod($provider, 'loadRecordTreeFromDatabase', $record);
        $expected = array($record);
        $this->assertEquals($expected, $output);
    }

    /**
     * @test
     */
    public function setsDefaultValueInFieldsBasedOnInheritedValue()
    {
        $row = array();
        $className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
        $instance = $this->getMockBuilder($className)->setMethods(array('getInheritedPropertyValueByDottedPath', 'getInheritedConfiguration'))->getMock();
        $instance->expects($this->once())->method('getInheritedPropertyValueByDottedPath')
            ->with(array(), 'input')->will($this->returnValue('default'));
        $instance->expects($this->once())->method('getInheritedConfiguration')
            ->with($row)->will($this->returnValue(array()));
        $form = Form::create();
        $field = $form->createField('Input', 'input');
        $returnedForm = $this->callInaccessibleMethod($instance, 'setDefaultValuesInFieldsWithInheritedValues', $form, $row);
        $this->assertSame($form, $returnedForm);
        $this->assertEquals('default', $field->getDefault());
    }

    /**
     * @test
     * @dataProvider getRemoveInheritedTestValues
     * @param mixed $testValue
     * @param boolean $inherit
     * @param boolean $inheritEmpty
     * @param boolean $expectsOverride
     */
    public function removesInheritedValuesFromFields($testValue, $inherit, $inheritEmpty, $expectsOverride)
    {
        $instance = $this->createInstance();
        $field = Form\Field\Input::create(array('type' => 'Input'));
        $field->setName('test');
        $field->setInherit($inherit);
        $field->setInheritEmpty($inheritEmpty);
        $values = array('foo' => 'bar', 'test' => $testValue);
        $result = $this->callInaccessibleMethod($instance, 'unsetInheritedValues', $field, $values);
        if (true === $expectsOverride) {
            $this->assertEquals($values, $result);
        } else {
            $this->assertEquals(array('foo' => 'bar'), $result);
        }
    }

    /**
     * @return array
     */
    public function getRemoveInheritedTestValues()
    {
        return array(
            array('test', true, true, true),
            array('', true, false, true),
            array('', true, true, false),
        );
    }

    /**
     * @test
     */
    public function getParentFieldValueLoadsRecordFromDatabaseIfRecordLacksParentFieldValue()
    {
        $row = static::$contentRecordWithoutParentAndWithoutChildren;
        $row['uid'] = 2;
        $rowWithPid = $row;
        $rowWithPid['pid'] = 1;
        $className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
        $instance = $this->getMockBuilder($className)->setMethods(array('getParentFieldName', 'getTableName'))->getMock();
        $recordService = $this->getMockBuilder(WorkspacesAwareRecordService::class)->setMethods(['getSingle'])->getMock();
        $recordService->expects($this->exactly(1))->method('getSingle')->will($this->returnValue($rowWithPid));
        $instance->injectRecordService($recordService);
        $instance->expects($this->once())->method('getParentFieldName')->with($row)->will($this->returnValue('pid'));
        $result = $this->callInaccessibleMethod($instance, 'getParentFieldValue', $row);
        $this->assertEquals($rowWithPid['pid'], $result);
    }

    /**
     * @dataProvider getInheritedPropertyValueByDottedPathTestValues
     * @param array $input
     * @param string $path
     * @param mixed $expected
     */
    public function testGetInheritedPropertyValueByDottedPath(array $input, $path, $expected)
    {
        $provider = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getInheritedConfiguration'))->getMock();
        $result = $this->callInaccessibleMethod($provider, 'getInheritedPropertyValueByDottedPath', $input, $path);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getInheritedPropertyValueByDottedPathTestValues()
    {
        return array(
            array(array(), '', null),
            array(array('foo' => 'bar'), 'foo', 'bar'),
            array(array('foo' => 'bar'), 'bar', null),
            array(array('foo' => array('bar' => 'baz')), 'foo.bar', 'baz'),
            array(array('foo' => array('bar' => 'baz')), 'foo.foo', null),
        );
    }

    /**
     * @return array
     */
    protected function getBasicRecord()
    {
        $record = static::$contentRecordWithoutParentAndWithoutChildren;
        $record['pi_flexform'] = '<T3FlexForms>
            <data>
                <sheet index="options">
                    <language index="lDEF">
                        <field index="settings.input">
                            <value index="vDEF">0</value>
                        </field>
                    </language>
                </sheet>
            </data>
        </T3FlexForms>';
        return $record;
    }

    /**
     * @test
     */
    public function canPostProcessRecord()
    {
        /** @var PageProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Provider\\PageProvider')->setMethods(array('getForm', 'getInheritedPropertyValueByDottedPath'))->getMock();
        $form = Form::create();
        $form->createField('Input', 'settings.input')->setInherit(true);
        $record = $this->getBasicRecord();
        $fieldName = $provider->getFieldName($record);
        $tableName = $provider->getTableName($record);
        $record[$fieldName] = '<T3FlexForms>
            <data>
                <sheet index="options">
                    <language index="lDEF">
                        <field index="settings.input">
                            <value index="vDEF">0</value>
                        </field>
                    </language>
                </sheet>
            </data>
        </T3FlexForms>';
        $id = $record['uid'];
        /** @var DataHandler $parentInstance */
        $parentInstance = GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
        $parentInstance->datamap[$tableName][$id] = array(
            'uid' => $record['uid'],
            $fieldName => array(
                'data' => array(
                    'options' => array(
                        'lDEF' => array(
                            'settings.input' => array(
                                'vDEF' => 'test'
                            ),
                            'settings.input_clear' => array(
                                'vDEF' => 1
                            )
                        )
                    )
                )
            )
        );
        $provider->expects($this->any())->method('getForm')->willReturn($form);
        $provider->expects($this->once())->method('getInheritedPropertyValueByDottedPath')
            ->with([], 'settings.input')->willReturn('test');
        /** @var WorkspacesAwareRecordService|\PHPUnit_Framework_MockObject_MockObject $recordService */
        $recordService = $this->getMockBuilder('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService')->setMethods(array('getSingle', 'update'))->getMock();
        $recordService->expects($this->atLeastOnce())->method('getSingle')->willReturn($parentInstance->datamap[$tableName][$id]);
        $recordService->expects($this->once())->method('update');
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $configurationService */
        $configurationService = $this->getMockBuilder('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService')->setMethods(array('message'))->getMock();
        $configurationService->expects($this->any())->method('message');
        $provider->injectRecordService($recordService);
        $provider->injectPageConfigurationService($configurationService);
        $provider->postProcessRecord('update', $id, $record, $parentInstance);
        $this->assertIsString($record[$fieldName]);
        $this->assertNotContains('settings.input', $record[$fieldName]);
    }

    /**
     * @param mixed $value
     * @return void
     */
    protected function assertIsString($value)
    {
        $isStringConstraint = new \PHPUnit_Framework_Constraint_IsType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->assertThat($value, $isStringConstraint);
    }
}
