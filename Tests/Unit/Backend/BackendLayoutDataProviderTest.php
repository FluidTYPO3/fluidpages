<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider;
use FluidTYPO3\Flux\Form\Container\Grid;
use FluidTYPO3\Flux\Provider\Provider;
use FluidTYPO3\Flux\Service\ContentService;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BackendLayoutDataProviderTest
 */
class BackendLayoutDataProviderTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testPerformsInjections() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidpages\\Backend\\BackendLayoutDataProvider');
		$this->assertAttributeInstanceOf('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', 'objectManager', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', 'recordService', $instance);
	}

	/**
	 * @dataProvider getBackendLayoutConfigurationTestValues
	 * @param Provider $provider
	 * @param mixed $record
	 * @param array $expected
	 */
	public function testGetBackendLayoutConfiguration(Provider $provider, $record, array $expected) {
		$GLOBALS['LANG'] = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService', ['sL']);
		$GLOBALS['LANG']->csConvObj = $this->getMock('TYPO3\CMS\Core\Charset\CharsetConverter', ['readLLfile']);
		$GLOBALS['LANG']->expects($this->any())->method('sL')->willReturn('translatedlabel');
		$GLOBALS['LANG']->csConvObj->expects($this->any())->method('readLLfile')->willReturn([]);
		$instance = new BackendLayoutDataProvider();
		$pageUid = 1;
		$backendLayout = [];
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			['resolvePageProvider', 'debug', 'message']
		);
		if (NULL !== $record) {
			$configurationService->expects($this->once())->method('resolvePageProvider')
				->with($record)->willReturn($provider);
		}
		$recordService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', ['getSingle']);
		$recordService->expects($this->once())->method('getSingle')->willReturn($record);
		$instance->injectConfigurationService($configurationService);
		$instance->injectWorkspacesAwareRecordService($recordService);
		$result = $this->callInaccessibleMethod($instance, 'getBackendLayoutConfiguration', $pageUid);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getBackendLayoutConfigurationTestValues() {
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
				],
				'2.' => [
					'columns.' => [
						'1.' => [
							'name' => 'Fluid Content Area',
							'colPos' => ContentService::COLPOS_FLUXCONTENT
						]
					]
				]
			]
		];
		return [
			[$standardProvider, NULL, []],
			[$standardProvider, [], []],
			[$actionLessProvider, [], []],
			[$emptyGridProvider, [], []],
			[$exceptionProvider, [], []],
			[$gridProvider, [], $gridArray],
		];
	}

	/**
	 * @dataProvider getEnsureDottedKeysTestValues
	 * @param array $input
	 * @param array $expected
	 */
	public function testEnsureDottedKeys(array $input, array $expected) {
		$instance = new BackendLayoutDataProvider();
		$result = $this->callInaccessibleMethod($instance, 'ensureDottedKeys', $input);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getEnsureDottedKeysTestValues() {
		return [
			[
				['foo' => ['bar' => 'bar']],
				['foo.' => ['bar' => 'bar']]
			],
			[
				['foo.' => ['bar' => 'bar']],
				['foo.' => ['bar' => 'bar']]
			]
		];
	}

	/**
	 * @dataProvider getEncodeTypoScriptArrayTestValues
	 * @param array $input
	 * @param $expected
	 */
	public function testEncodeTypoScriptArray(array $input, $expected) {
		$instance = new BackendLayoutDataProvider();
		$result = $this->callInaccessibleMethod($instance, 'encodeTypoScriptArray', $input);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getEncodeTypoScriptArrayTestValues() {
		return [
			[
				['foo' => ['bar' => 'bar']],
				'backend_layout.foo.bar = bar' . PHP_EOL
			],
			[
				['foo.' => ['bar' => 'bar']],
				'backend_layout.foo.bar = bar' . PHP_EOL
			]
		];
	}

	/**
	 * @return void
	 */
	public function testGetBackendLayout() {
		$instance = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Backend\\BackendLayoutDataProvider',
			['getBackendLayoutConfiguration', 'ensureDottedKeys', 'encodeTypoScriptArray']
		);
		$instance->expects($this->at(0))->method('getBackendLayoutConfiguration')->with(1)->willReturn(['conf']);
		$instance->expects($this->at(1))->method('ensureDottedKeys')->with(['conf'])->willReturn(['conf-converted']);
		$instance->expects($this->at(2))->method('encodeTypoScriptArray')->with(['conf-converted'])->willReturn('config');
		$result = $instance->getBackendLayout('identifier', 1);
		$this->assertInstanceOf('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', $result);
		$this->assertEquals('identifier', $result->getIdentifier());
		$this->assertEquals('config', $result->getConfiguration());
	}

	/**
	 * @return void
	 */
	public function testAddBackendLayouts() {
		$instance = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Backend\\BackendLayoutDataProvider',
			['getBackendLayoutConfiguration', 'encodeTypoScriptArray']
		);
		$instance->expects($this->once())->method('getBackendLayoutConfiguration')->with(1)->willReturn(['conf']);
		$instance->expects($this->once())->method('encodeTypoScriptArray')->with(['conf'])->willReturn('conf');
		$collection = new BackendLayoutCollection('collection');
		$context = new DataProviderContext();
		$context->setPageId(1);
		$instance->addBackendLayouts($context, $collection);
		$this->assertEquals('conf', reset($collection->getAll())->getConfiguration());
	}

}
