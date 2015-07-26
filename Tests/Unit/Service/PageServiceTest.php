<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Service\PageService;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class PageServiceTest
 * @package FluidTYPO3\Fluidpages\Tests\Unit\Service
 */
class PageServiceTest extends UnitTestCase {

	/**
	 * @return PageService
	 */
	protected function getPageService() {
		return new PageService();
	}

	/**
	 * @test
	 */
	public function getPageFlexFormSourceWithZeroUidReturnsNull() {
		$this->assertNull($this->getPageService()->getPageFlexFormSource(0));
	}

	/**
	 * @test
	 */
	public function getPageTemplateConfigurationWithZeroUidReturnsNull() {
		$this->assertNull($this->getPageService()->getPageTemplateConfiguration(0));
	}

	/**
	 * @dataProvider getPageTemplateConfigurationTestValues
	 * @param array $records
	 * @param array|NULL $expected
	 */
	public function testGetPageTemplateConfiguration(array $records, $expected) {
		$service = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', ['getSingle']);
		foreach ($records as $index => $record) {
			$service->expects($this->at($index))->method('getSingle')->willReturn($record);
		}
		$instance = new PageService();
		$instance->injectWorkspacesAwareRecordService($service);
		$result = $instance->getPageTemplateConfiguration(1);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getPageTemplateConfigurationTestValues() {
		$m = 'tx_fed_page_controller_action';
		$s = 'tx_fed_page_controller_action_sub';
		return [
			[[[]], NULL],
			[[[$m => '', $s => '']], NULL],
			[[[$m => 'test1->test1', $s => 'test2->test2']], [$m => 'test1->test1', $s => 'test2->test2']],
			[[[$m => ''], [$s => 'test2->test2']], [$m => 'test2->test2', $s => 'test2->test2']]
		];
	}

	/**
	 * @return void
	 */
	public function testGetPageFlexFormSource() {
		$record1 = ['pid' => 2, 'uid' => 1];
		$record2 = ['pid' => 0, 'uid' => 3, 'tx_fed_page_flexform' => 'test'];
		$service = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', ['getSingle']);
		$service->expects($this->at(0))->method('getSingle')->with('pages', '*', 1)->willReturn($record1);
		$service->expects($this->at(1))->method('getSingle')->with('pages', '*', 2)->willReturn($record2);
		$instance = new PageService();
		$instance->injectWorkspacesAwareRecordService($service);
		$output = $instance->getPageFlexFormSource(1);
		$this->assertEquals('test', $output);
	}

	/**
	 * @dataProvider getAvailablePageTemplateFilesTestValues
	 * @param string|array $typoScript
	 * @param array $expected
	 */
	public function testGetAvailablePageTemplateFiles($typoScript, array $expected) {
		$service = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			['getPageConfiguration', 'message']
		);
		$service->expects($this->once())->method('getPageConfiguration')->willReturn($typoScript);
		$service->expects($this->any())->method('message');
		$instance = new PageService();
		$instance->injectConfigurationService($service);
		$result = $instance->getAvailablePageTemplateFiles();
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getAvailablePageTemplateFilesTestValues() {
		return [
			[NULL, []],
			[['test' => ['enable' => FALSE]], []],
			[
				['fluidpages' => ['templateRootPath' => 'EXT:fluidpages/Tests/Fixtures/Templates/']],
				['fluidpages' => ['Dummy' => 'Dummy']]
			],
			[
				['fluidpages' => ['templateRootPath' => 'EXT:fluidpages/Invalid']],
				['fluidpages' => []]
			]
		];
	}

}
