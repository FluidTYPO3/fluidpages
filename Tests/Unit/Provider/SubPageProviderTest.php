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
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Records;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Xml;
use FluidTYPO3\Flux\Tests\Unit\AbstractTestCase;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class SubPageProviderTest
 */
class SubPageProviderTest extends AbstractTestCase {

	/**
	 * @dataProvider getControllerActionFromRecordTestValues
	 * @param array $record
	 * @param string $fieldName
	 * @param boolean $expectsMessage
	 * @param string $expected
	 */
	public function testGetControllerActionFromRecord(array $record, $fieldName, $expectsMessage, $expected) {
		$instance = new SubPageProvider();
		if (PageControllerInterface::DOKTYPE_RAW !== $record['doktype']) {
			$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', array('getPageTemplateConfiguration'));
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
			array(array('doktype' => 0, 'tx_fed_page_controller_action_sub' => ''), 'tx_fed_page_flexform_sub', TRUE, 'default'),
			array(array('doktype' => 0, 'tx_fed_page_controller_action_sub' => 'fluidpages->action'), 'tx_fed_page_flexform_sub', FALSE, 'action'),
		);
	}

	public function testGetTemplatePathAndFilename() {
		$expected = ExtensionManagementUtility::extPath('fluidpages', 'Tests/Fixtures/Templates/Page/Dummy.html');
		$dataFieldName = 'tx_fed_page_flexform_sub';
		$fieldName = 'tx_fed_page_controller_action_sub';
		$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', array('getPageTemplateConfiguration'));
		$instance = new SubPageProvider();
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

}
