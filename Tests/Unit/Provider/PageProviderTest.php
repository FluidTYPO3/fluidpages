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
use FluidTYPO3\Fluidpages\Service\PageService;
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
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getControllerExtensionKeyFromRecord']);
		$instance->expects($this->once())->method('getControllerExtensionKeyFromRecord')->willReturn('fluidpages');
		$result = $instance->getExtensionKey([]);
		$this->assertEquals('fluidpages', $result);
	}

	public function testGetExtensionKeyWithoutSelection() {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getControllerExtensionKeyFromRecord']);
		$instance->expects($this->once())->method('getControllerExtensionKeyFromRecord')->willReturn(NULL);
		$result = $instance->getExtensionKey([]);
		$this->assertEquals('fluidpages', $result);
	}

	public function testGetTemplatePathAndFilename() {
		$expected = ExtensionManagementUtility::extPath('fluidpages', 'Tests/Fixtures/Templates/Page/Dummy.html');
		$fieldName = 'tx_fed_page_controller_action';
		$dataFieldName = 'tx_fed_page_flexform';
		$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', ['getPageTemplateConfiguration']);
		$instance = new PageProvider();
		$instance->setTemplatePaths(['templateRootPath' => 'EXT:fluidpages/Tests/Fixtures/Templates/']);
		$instance->injectPageService($service);
		$record = [
			$fieldName => 'Fluidpages->dummy',
		];
		$service->expects($this->any())->method('getPageTemplateConfiguration')->willReturn($record);
		$instance->trigger($record, NULL, $dataFieldName);
		$result = $instance->getTemplatePathAndFilename($record);
		$this->assertEquals($expected, $result);
	}

	public function testGetFormCallsSetDefaultValuesInFieldsWithInheritedValues() {
		$form = Form::create();
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['setDefaultValuesInFieldsWithInheritedValues']);
		$instance->injectPageService(new PageService());
		$instance->expects($this->once())->method('setDefaultValuesInFieldsWithInheritedValues')->willReturn($form);
		$instance->setForm($form);
		$instance->getForm([]);
	}

	public function testGetControllerExtensionKeyFromRecordReturnsPresetKeyOnUnrecognisedAction() {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getControllerActionReferenceFromRecord']);
		$instance->expects($this->once())->method('getControllerActionReferenceFromRecord')->willReturn('invalid');
		$instance->setExtensionKey('fallback');
		$result = $instance->getControllerExtensionKeyFromRecord([]);
		$this->assertEquals('fallback', $result);
	}

	/**
	 * @dataProvider getInheritanceTreeTestValues
	 * @param array $input
	 * @param array $expected
	 */
	public function testGetInheritanceTree(array $input, array $expected) {
		$record = ['uid' => 1];
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['loadRecordTreeFromDatabase']);
		$instance->expects($this->once())->method('loadRecordTreeFromDatabase')->with($record)->willReturn($input);
		$result = $this->callInaccessibleMethod($instance, 'getInheritanceTree', $record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getInheritanceTreeTestValues() {
		return [
			[[], []],
			[
				[[PageProvider::FIELD_ACTION_SUB => 'testsub'], [PageProvider::FIELD_ACTION_MAIN => 'testmain']],
				[[PageProvider::FIELD_ACTION_MAIN => 'testmain']]
			],
			[
				[[PageProvider::FIELD_ACTION_SUB => 'testsub'], [PageProvider::FIELD_ACTION_MAIN => '']],
				[[PageProvider::FIELD_ACTION_SUB => 'testsub'], [PageProvider::FIELD_ACTION_MAIN => '']]
			],
		];
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
		if (PageControllerInterface::DOKTYPE_RAW !== $record['doktype'] && TRUE === empty($record[$fieldName])) {
			$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', ['getPageTemplateConfiguration']);
			$instance->injectPageService($service);
		}
		if (TRUE === $expectsMessage) {
			$configurationService = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', ['message']);
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
		return [
			[['doktype' => PageControllerInterface::DOKTYPE_RAW], '', FALSE, 'raw'],
			[['doktype' => 0, 'tx_fed_page_controller_action' => ''], 'tx_fed_page_flexform', TRUE, 'default'],
			[['doktype' => 0, 'tx_fed_page_controller_action' => 'fluidpages->action'], 'tx_fed_page_flexform', FALSE, 'action'],
		];
	}

	public function testGetFlexFormValuesReturnsCollectedDataWhenEncounteringNullForm() {
		$tree = [
			$this->getBasicRecord(),
			$this->getBasicRecord()
		];
		$form = Form::create();
		$form->createField('Input', 'foo');
		$record = $this->getBasicRecord();
		$dummyProvider1 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider2 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider1->setForm($form);
		$dummyProvider1->setFlexFormValues(['foo' => 'bar']);
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getInheritanceTree', 'unsetInheritedValues', 'getForm']);
		$mockConfigurationService = $this->getMock('FluidTYPO3\Fluidpages\Service\ConfigurationService', ['resolvePrimaryConfigurationProvider']);
		$mockConfigurationService->expects($this->at(0))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider1);
		$mockConfigurationService->expects($this->at(1))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider2);
		$provider->expects($this->once())->method('getInheritanceTree')->will($this->returnValue($tree));
		$provider->expects($this->any())->method('unsetInheritedValues');
		$provider->expects($this->any())->method('getForm')->willReturn(Form::create());
		$provider->setTemplatePathAndFilename($this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL));
		$provider->injectConfigurationService($mockConfigurationService);
		$values = $provider->getFlexformValues($record);
		$this->assertEquals($values, []);
	}

	/**
	 * @test
	 */
	public function canGetFlexformValuesUnderInheritanceConditions() {
		$tree = [
			$this->getBasicRecord(),
			$this->getBasicRecord()
		];
		$form = Form::create();
		$form->createField('Input', 'foo');
		$record = $this->getBasicRecord();
		$dummyProvider1 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider2 = $this->objectManager->get('FluidTYPO3\\Fluidpages\\Tests\\Fixtures\\Provider\\DummyPageProvider');
		$dummyProvider1->setForm($form);
		$dummyProvider1->setFlexFormValues(['foo' => 'bar']);
		$dummyProvider2->setForm(Form::create());
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getInheritanceTree', 'unsetInheritedValues', 'getForm']);
		$mockConfigurationService = $this->getMock('FluidTYPO3\Fluidpages\Service\ConfigurationService', ['resolvePrimaryConfigurationProvider']);
		$mockConfigurationService->expects($this->at(0))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider1);
		$mockConfigurationService->expects($this->at(1))->method('resolvePrimaryConfigurationProvider')->willReturn($dummyProvider2);
		$provider->expects($this->once())->method('getInheritanceTree')->will($this->returnValue($tree));
		$provider->expects($this->any())->method('unsetInheritedValues');
		$provider->expects($this->any())->method('getForm')->willReturn(Form::create());
		$provider->setTemplatePathAndFilename($this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_ABSOLUTELYMINIMAL));
		$provider->injectConfigurationService($mockConfigurationService);
		$values = $provider->getFlexformValues($record);
		$this->assertEquals($values, []);
	}

	/**
	 * @test
	 */
	public function canLoadRecordTreeFromDatabase() {
		$record = $this->getBasicRecord();
		$provider = $this->getMock(
			str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4)),
			['loadRecordFromDatabase', 'getParentFieldName', 'getParentFieldValue']
		);
		$provider->expects($this->exactly(2))->method('getParentFieldName')->will($this->returnValue('somefield'));
		$provider->expects($this->exactly(1))->method('getParentFieldValue')->will($this->returnValue(1));
		$provider->expects($this->exactly(1))->method('loadRecordFromDatabase')->will($this->returnValue($record));
		$output = $this->callInaccessibleMethod($provider, 'loadRecordTreeFromDatabase', $record);
		$expected = [$record];
		$this->assertEquals($expected, $output);
	}

	/**
	 * @test
	 */
	public function setsDefaultValueInFieldsBasedOnInheritedValue() {
		$row = [];
		$className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
		$instance = $this->getMock($className, ['getInheritedPropertyValueByDottedPath']);
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
		$field = Form\Field\Input::create(['type' => 'Input']);
		$field->setName('test');
		$field->setInherit($inherit);
		$field->setInheritEmpty($inheritEmpty);
		$values = ['foo' => 'bar', 'test' => $testValue];
		$result = $this->callInaccessibleMethod($instance, 'unsetInheritedValues', $field, $values);
		if (TRUE === $expectsOverride) {
			$this->assertEquals($values, $result);
		} else {
			$this->assertEquals(['foo' => 'bar'], $result);
		}
	}

	/**
	 * @return array
	 */
	public function getRemoveInheritedTestValues() {
		return [
			['test', TRUE, TRUE, TRUE],
			['', TRUE, FALSE, TRUE],
			['', TRUE, TRUE, FALSE],
		];
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
		$instance = $this->getMock($className, ['getParentFieldName', 'getTableName', 'loadRecordFromDatabase']);
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
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getInheritedConfiguration']);
		$provider->expects($this->once())->method('getInheritedConfiguration')->willReturn($input);
		$result = $this->callInaccessibleMethod($provider, 'getInheritedPropertyValueByDottedPath', [], $path);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getInheritedPropertyValueByDottedPathTestValues() {
		return [
			[[], '', NULL],
			[['foo' => 'bar'], 'foo', 'bar'],
			[['foo' => 'bar'], 'bar', NULL],
			[['foo' => ['bar' => 'baz']], 'foo.bar', 'baz'],
			[['foo' => ['bar' => 'baz']], 'foo.foo', NULL],
		];
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
		$provider = $this->getMock('FluidTYPO3\\Fluidpages\\Provider\\PageProvider', ['getForm', 'getInheritedPropertyValueByDottedPath']);
		$form = Form::create();
		$form->createField('Input', 'settings.input')->setInherit(TRUE);
		$record = $this->getBasicRecord();
		$fieldName = $provider->getFieldName($record);
		$tableName = $provider->getTableName($record);
		$record[$fieldName] = Xml::EXPECTING_FLUX_REMOVALS;
		$id = $record['uid'];
		$parentInstance = GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
		$parentInstance->datamap[$tableName][$id] = [
			$fieldName => [
				'data' => [
					'options' => [
						'lDEF' => [
							'settings.input' => [
								'vDEF' => 'test'
							],
							'settings.input_clear' => [
								'vDEF' => 1
							]
						]
					]
				]
			]
		];
		$provider->expects($this->any())->method('getForm')->willReturn($form);
		$provider->expects($this->once())->method('getInheritedPropertyValueByDottedPath')
			->with($parentInstance->datamap[$tableName][$id], 'settings.input')->willReturn('test');
		$recordService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', ['getSingle', 'update']);
		$recordService->expects($this->atLeastOnce())->method('getSingle')->willReturn($parentInstance->datamap[$tableName][$id]);
		$recordService->expects($this->once())->method('update');
		$configurationService = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', ['message']);
		$configurationService->expects($this->any())->method('message');
		$provider->injectRecordService($recordService);
		$provider->injectConfigurationService($configurationService);
		$provider->postProcessRecord('update', $id, $record, $parentInstance);
		$this->assertIsString($record[$fieldName]);
		$this->assertNotContains('settings.input', $record[$fieldName]);
	}

}
