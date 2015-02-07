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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageProviderTest
 */
class PageProviderTest extends UnitTestCase {

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

	/**
	 * @dataProvider getTemplatePathAndFilenameTestValues
	 * @param string $fieldName
	 * @param string $expected
	 */
	public function testGetTemplatePathAndFilename($fieldName, $expected) {
		$dataFieldName = str_replace('tx_fed_page_controller_action', 'tx_fed_page_flexform', $fieldName);
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

	/**
	 * @return array
	 */
	public function getTemplatePathAndFilenameTestValues() {
		$template = ExtensionManagementUtility::extPath('fluidpages', 'Tests/Fixtures/Templates/Page/Dummy.html');
		return array(
			array('tx_fed_page_controller_action', $template),
			array('tx_fed_page_controller_action_sub', $template)
		);
	}

	/**
	 * @return void
	 */
	public function testTriggerSetsFieldName() {
		$instance = new PageProvider();
		$this->assertAttributeEmpty('currentFieldName', $instance);
		$instance->trigger(array(), 'pages', 'tx_fed_page_flexform');
		$this->assertAttributeEquals('tx_fed_page_flexform', 'currentFieldName', $instance);
		$instance->trigger(array(), 'pages', 'tx_fed_page_flexform_sub');
		$this->assertAttributeEquals('tx_fed_page_flexform_sub', 'currentFieldName', $instance);
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
			array(array('doktype' => 0, 'tx_fed_page_controller_action_sub' => ''), 'tx_fed_page_flexform_sub', TRUE, 'default'),
			array(array('doktype' => 0, 'tx_fed_page_controller_action_sub' => 'fluidpages->action'), 'tx_fed_page_flexform_sub', FALSE, 'action'),
		);
	}

}
