<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Controller\PageControllerInterface;
use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Records;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Xml;
use FluidTYPO3\Flux\Tests\Unit\AbstractTestCase;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class PageProviderTest
 */
class PageProviderTest extends AbstractTestCase {

	/**
	 * @return void
	 */
	public function testPerformsInjections() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidpages\\Provider\\PageProvider');
		$this->assertAttributeInstanceOf('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools', 'flexformTool', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
	}

	public function testGetExtensionKey() {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getControllerExtensionKeyFromRecord'));
		$instance->expects($this->once())->method('getControllerExtensionKeyFromRecord')->willReturn('fluidpages');
		$result = $instance->getExtensionKey(array());
		$this->assertEquals('fluidpages', $result);
	}

	public function testGetExtensionKeyWithoutSelection() {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getControllerExtensionKeyFromRecord'));
		$instance->expects($this->once())->method('getControllerExtensionKeyFromRecord')->willReturn(NULL);
		$result = $instance->getExtensionKey(array());
		$this->assertEquals('fluidpages', $result);
	}

	public function testGetTemplatePathAndFilename() {
		$expected = ExtensionManagementUtility::extPath('fluidpages', 'Tests/Fixtures/Templates/Page/Dummy.html');
		$fieldName = 'tx_fed_page_controller_action';
		$dataFieldName = 'tx_fed_page_flexform';
		$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', array('getPageTemplateConfiguration'));
		$instance = new PageProvider();
		$instance->setTemplatePaths(array('templateRootPath' => 'EXT:fluidpages/Tests/Fixtures/Templates/'));
		$instance->injectPageService($service);
		$record = array(
			$fieldName => 'Fluidpages->dummy',
		);
		$service->expects($this->any())->method('getPageTemplateConfiguration')->willReturn($record);
		$instance->trigger($record, NULL, $dataFieldName);
		$result = $instance->getTemplatePathAndFilename($record);
		$this->assertEquals($expected, $result);
	}

	public function testGetFormCallsSetDefaultValuesInFieldsWithInheritedValues() {
		$form = Form::create();
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('setDefaultValuesInFieldsWithInheritedValues'));
		$instance->expects($this->once())->method('setDefaultValuesInFieldsWithInheritedValues')->willReturn($form);
		$instance->setForm($form);
		$instance->getForm(array());
	}

	public function testGetControllerExtensionKeyFromRecordReturnsPresetKeyOnUnrecognisedAction() {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getControllerActionReferenceFromRecord'));
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
	public function testGetInheritanceTree(array $input, array $expected) {
		$record = array('uid' => 1);
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('loadRecordTreeFromDatabase'));
		$instance->expects($this->once())->method('loadRecordTreeFromDatabase')->with($record)->willReturn($input);
		$result = $this->callInaccessibleMethod($instance, 'getInheritanceTree', $record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getInheritanceTreeTestValues() {
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
	public function testGetControllerActionFromRecord(array $record, $fieldName, $expectsMessage, $expected) {
		$instance = new PageProvider();
		if (PageControllerInterface::DOKTYPE_RAW !== $record['doktype']) {
			$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', array('getPageTemplateConfiguration'));
			$service->expects($this->once())->method('getPageTemplateConfiguration')->willReturn($record);
			$instance->injectPageService($service);
		}
		if (TRUE === $expectsMessage) {
			$configurationService = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', array('message'));
			$configurationService->expects($this->once())->method('message');
			$instance->injectConfigurationService($configurationService);
		}
		// make sure PageProvider is now using the right field name
		$instance->trigger($record, NULL, $fieldName);
		$result = $instance->getControllerActionFromRecord($record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getControllerActionFromRecordTestValues() {
		return array(
			array(array('doktype' => PageControllerInterface::DOKTYPE_RAW), '', FALSE, 'raw'),
			array(array('doktype' => 0, 'tx_fed_page_controller_action' => ''), 'tx_fed_page_flexform', TRUE, 'default'),
			array(array('doktype' => 0, 'tx_fed_page_controller_action' => 'fluidpages->action'), 'tx_fed_page_flexform', FALSE, 'action'),
		);
	}

	public function testGetFlexFormValuesReturnsCollectedDataWhenEncounteringNullForm() {
		$tree = array(
			$this->getBasicRecord(),
			$this->getBasicRecord()
		);
		$form = Form::create();
		$form->createField('Input', 'foo');
		$record = $this->getBasicRecord();
		$dummyProvider1 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider2 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider1->setForm($form);
		$dummyProvider1->setFlexFormValues(array('foo' => 'bar'));
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getInheritanceTree', 'unsetInheritedValues', 'getForm'));
		$mockConfigurationService = $this->getMock('FluidTYPO3\Fluidpages\Service\ConfigurationService', array('resolvePrimaryConfigurationProvider'));
		$mockConfigurationService->expects($this->at(0))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider1);
		$mockConfigurationService->expects($this->at(1))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider2);
		$provider->expects($this->once())->method('getInheritanceTree')->will($this->returnValue($tree));
		$provider->expects($this->any())->method('unsetInheritedValues');
		$provider->expects($this->any())->method('getForm')->willReturn(Form::create());
		$provider->setTemplatePathAndFilename($this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL));
		$provider->injectConfigurationService($mockConfigurationService);
		$values = $provider->getFlexformValues($record);
		$this->assertEquals($values, array());
	}

	/**
	 * @test
	 */
	public function canGetFlexformValuesUnderInheritanceConditions() {
		$tree = array(
			$this->getBasicRecord(),
			$this->getBasicRecord()
		);
		$form = Form::create();
		$form->createField('Input', 'foo');
		$record = $this->getBasicRecord();
		$dummyProvider1 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider2 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider1->setForm($form);
		$dummyProvider1->setFlexFormValues(array('foo' => 'bar'));
		$dummyProvider2->setForm(Form::create());
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getInheritanceTree', 'unsetInheritedValues', 'getForm'));
		$mockConfigurationService = $this->getMock('FluidTYPO3\Fluidpages\Service\ConfigurationService', array('resolvePrimaryConfigurationProvider'));
		$mockConfigurationService->expects($this->at(0))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider1);
		$mockConfigurationService->expects($this->at(1))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider2);
		$provider->expects($this->once())->method('getInheritanceTree')->will($this->returnValue($tree));
		$provider->expects($this->any())->method('unsetInheritedValues');
		$provider->expects($this->any())->method('getForm')->willReturn(Form::create());
		$provider->setTemplatePathAndFilename($this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL));
		$provider->injectConfigurationService($mockConfigurationService);
		$values = $provider->getFlexformValues($record);
		$this->assertEquals($values, array());
	}

	/**
	 * @test
	 */
	public function canLoadRecordTreeFromDatabase() {
		$record = $this->getBasicRecord();
		$provider = $this->getMock(
			str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4)),
			array('loadRecordFromDatabase', 'getParentFieldName', 'getParentFieldValue')
		);
		$provider->expects($this->exactly(2))->method('getParentFieldName')->will($this->returnValue('somefield'));
		$provider->expects($this->exactly(1))->method('getParentFieldValue')->will($this->returnValue(1));
		$provider->expects($this->exactly(1))->method('loadRecordFromDatabase')->will($this->returnValue($record));
		$output = $this->callInaccessibleMethod($provider, 'loadRecordTreeFromDatabase', $record);
		$expected = array($record);
		$this->assertEquals($expected, $output);
	}

	/**
	 * @test
	 */
	public function setsDefaultValueInFieldsBasedOnInheritedValue() {
		$row = array();
		$className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
		$instance = $this->getMock($className, array('getInheritedPropertyValueByDottedPath'));
		$instance->expects($this->once())->method('getInheritedPropertyValueByDottedPath')
			->with($row, 'input')->will($this->returnValue('default'));
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
	public function removesInheritedValuesFromFields($testValue, $inherit, $inheritEmpty, $expectsOverride) {
		$instance = $this->createInstance();
		$field = Form\Field\Input::create(array('type' => 'Input'));
		$field->setName('test');
		$field->setInherit($inherit);
		$field->setInheritEmpty($inheritEmpty);
		$values = array('foo' => 'bar', 'test' => $testValue);
		$result = $this->callInaccessibleMethod($instance, 'unsetInheritedValues', $field, $values);
		if (TRUE === $expectsOverride) {
			$this->assertEquals($values, $result);
		} else {
			$this->assertEquals(array('foo' => 'bar'), $result);
		}
	}

	/**
	 * @return array
	 */
	public function getRemoveInheritedTestValues() {
		return array(
			array('test', TRUE, TRUE, TRUE),
			array('', TRUE, FALSE, TRUE),
			array('', TRUE, TRUE, FALSE),
		);
	}

	/**
	 * @test
	 */
	public function getParentFieldValueLoadsRecordFromDatabaseIfRecordLacksParentFieldValue() {
		$row = Records::$contentRecordWithoutParentAndWithoutChildren;
		$row['uid'] = 2;
		$rowWithPid = $row;
		$rowWithPid['pid'] = 1;
		$className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
		$instance = $this->getMock($className, array('getParentFieldName', 'getTableName', 'loadRecordFromDatabase'));
		$instance->expects($this->once())->method('loadRecordFromDatabase')->with($row['uid'])->will($this->returnValue($rowWithPid));
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
	public function testGetInheritedPropertyValueByDottedPath(array $input, $path, $expected) {
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getInheritedConfiguration'));
		$provider->expects($this->once())->method('getInheritedConfiguration')->willReturn($input);
		$result = $this->callInaccessibleMethod($provider, 'getInheritedPropertyValueByDottedPath', array(), $path);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getInheritedPropertyValueByDottedPathTestValues() {
		return array(
			array(array(), '', NULL),
			array(array('foo' => 'bar'), 'foo', 'bar'),
			array(array('foo' => 'bar'), 'bar', NULL),
			array(array('foo' => array('bar' => 'baz')), 'foo.bar', 'baz'),
			array(array('foo' => array('bar' => 'baz')), 'foo.foo', NULL),
		);
	}

	/**
	 * @return array
	 */
	protected function getBasicRecord() {
		$record = Records::$contentRecordWithoutParentAndWithoutChildren;
		$record['pi_flexform'] = Xml::SIMPLE_FLEXFORM_SOURCE_DEFAULT_SHEET_ONE_FIELD;
		return $record;
	}

	/**
	 * @test
	 */
	public function canPostProcessRecord() {
		$form = Form::create();
		$form->createField('Input', 'settings.input')->setInherit(TRUE);
		$record = $this->getBasicRecord();
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', array('getForm', 'getInheritedPropertyValueByDottedPath'));
		$provider->expects($this->any())->method('getForm')->willReturn($form);
		$provider->expects($this->once())->method('getInheritedPropertyValueByDottedPath')->with($record, 'settings.input')->willReturn('test');
		$recordService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', array('getSingle', 'update'));
		$recordService->expects($this->atLeastOnce())->method('getSingle')->willReturn($record);
		$recordService->expects($this->once())->method('update');
		$configurationService = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', array('message'));
		$configurationService->expects($this->any())->method('message');
		$provider->injectRecordService($recordService);
		$provider->injectConfigurationService($configurationService);
		$parentInstance = GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
		$record['test'] = 'test';
		$id = $record['uid'];
		$tableName = $provider->getTableName($record);
		if (TRUE === empty($tableName)) {
			$tableName = 'pages';
			$provider->setTableName($tableName);
		}
		$fieldName = $provider->getFieldName($record);
		$record[$fieldName] = array(
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
		);
		$parentInstance->datamap[$tableName][$id] = $record;
		$record[$fieldName] = Xml::EXPECTING_FLUX_REMOVALS;
		$provider->postProcessRecord('update', $id, $record, $parentInstance);
		$this->assertIsString($record[$fieldName]);
		$this->assertNotContains('settings.input', $record[$fieldName]);
	}

}
