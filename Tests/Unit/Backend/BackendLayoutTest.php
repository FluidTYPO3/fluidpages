<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayout;
use FluidTYPO3\Flux\Form\Container\Grid;
use FluidTYPO3\Flux\Provider\Provider;
use FluidTYPO3\Flux\Service\ContentService;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class BackendLayoutTest
 */
class BackendLayoutTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testPerformsInjections() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidpages\\Backend\\BackendLayout');
		$this->assertAttributeInstanceOf('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', 'objectManager', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', 'workspacesAwareRecordService', $instance);
	}

	/**
	 * @dataProvider getPostProcessBackendLayoutTestValues
	 * @param Provider $provider
	 * @param mixed $record
	 * @param string $messageFunction
	 * @param integer $messageCount
	 * @param array $expected
	 */
	public function testPostProcessBackendLayout(Provider $provider, $record, $messageFunction, $messageCount, array $expected) {
		$GLOBALS['LANG'] = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService', ['sL']);
		$GLOBALS['LANG']->expects($this->any())->method('sL')->willReturn('translatedlabel');
		$instance = new BackendLayout();
		$pageUid = 1;
		$backendLayout = [];
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			['resolvePrimaryConfigurationProvider', 'debug', 'message']
		);
		$configurationService->expects($this->exactly($messageCount))->method($messageFunction);
		if (NULL !== $record) {
			$configurationService->expects($this->once())->method('resolvePrimaryConfigurationProvider')
				->with('pages', 'tx_fed_page_flexform', $record)->willReturn($provider);
		}
		$recordService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', ['getSingle']);
		$recordService->expects($this->once())->method('getSingle')->willReturn($record);
		$instance->injectConfigurationService($configurationService);
		$instance->injectWorkspacesAwareRecordService($recordService);
		$instance->postProcessBackendLayout($pageUid, $backendLayout);
		$this->assertEquals($expected, $backendLayout);
	}

	/**
	 * @return array
	 */
	public function getPostProcessBackendLayoutTestValues() {
		$standardProvider = $this->getMock(
			'FluidTYPO3\\Flux\\Provider\\Provider',
			['getControllerActionFromRecord']
		);
		$standardProvider->setTemplatePaths([]);
		$actionLessProvider = clone $standardProvider;
		$exceptionProvider = clone $standardProvider;
		$emptyGridProvider = clone $standardProvider;
		$gridProvider = clone $standardProvider;
		$actionLessProvider->expects($this->any())->method('getControllerActionFromRecord')->willReturn(NULL);
		$exceptionProvider->expects($this->any())->method('getControllerActionFromRecord')->willThrowException(new \RuntimeException());
		$emptyGridProvider->setGrid(Grid::create());
		$emptyGridProvider->expects($this->any())->method('getControllerActionFromRecord')->willReturn('default');
		$grid = Grid::create([]);
		$grid->createContainer('Row', 'row')->createContainer('Column', 'column')->setColSpan(3)->setRowSpan(3)->setColumnPosition(2);
		$gridProvider->setGrid($grid);
		$gridProvider->expects($this->any())->method('getControllerActionFromRecord')->willReturn('default');
		$gridArray = [
			'__config' => [
				'backend_layout.' => [
					'colCount' => 3,
					'rowCount' => 1,
					'rows.' => [
						'1.' => [
							'columns.' => [
								'1.' => [
									'name' => 'translatedlabel',
									'colPos' => 2,
									'colspan' => 3,
									'rowspan' => 3
								]
							]
						]
					]
				]
			],
			'__colPosList' => [2],
			'__items' => [
				['translatedlabel', 2, NULL]
			]
		];
		return [
			[$standardProvider, NULL, 'message', 0, []],
			[$standardProvider, [], 'message', 1, []],
			[$actionLessProvider, [], 'message', 1, []],
			[$emptyGridProvider, [], 'message', 1, []],
			[$exceptionProvider, [], 'debug', 1, []],
			[$gridProvider, [], 'message', 0, $gridArray],
		];
	}

	/**
	 * @return void
	 */
	public function testPreProcessBackendLayoutPageUidPerformsNoOperation() {
		$id = 1;
		$instance = new BackendLayout();
		$instance->preProcessBackendLayoutPageUid($id);
		$this->assertEquals(1, $id);
	}

	/**
	 * @return void
	 */
	public function testPostProcessColPosListItemsParsedPerformsNoOperation() {
		$id = 1;
		$tca = ['foo' => 'bar'];
		$mock = $this->getMock('TYPO3\CMS\Backend\Form\FormEngine', ['fake'], [], '', FALSE);
		$instance = new BackendLayout();
		$instance->postProcessColPosListItemsParsed($id, $tca, $mock);
		$this->assertEquals(1, $id);
		$this->assertEquals(['foo' => 'bar'], $tca);
	}

	/**
	 * @return void
	 */
	public function testPostProcessColPosProcFuncItemsAppendsFluidContentArea() {
		$instance = new BackendLayout();
		$parameters = [
			'items' => []
		];
		$instance->postProcessColPosProcFuncItems($parameters);
		$this->assertContains(['Fluid Content Area', ContentService::COLPOS_FLUXCONTENT, NULL], $parameters['items']);
	}

}
