<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\PageLayoutSelector;
use FluidTYPO3\Flux\Form;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageLayoutSelectorTest
 */
class PageLayoutSelectorTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testPerformsInjections() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidpages\\Backend\\PageLayoutSelector');
		$this->assertAttributeInstanceOf('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager', 'configurationManager', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
	}

	/**
	 * @return void
	 */
	public function testRenderField() {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Backend\\PageLayoutSelector', ['renderInheritanceField', 'renderOptions']);
		$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', ['getAvailablePageTemplateFiles']);
		$service->expects($this->once())->method('getAvailablePageTemplateFiles')->willReturn(['foo' => ['bar']]);
		$instance->injectPageService($service);
		$parameters = [];
		$parent = '';
		$result = $instance->renderField($parameters, $parent);
		$this->assertStringStartsWith('<div>', $result);
		$this->assertStringEndsWith('</div>', $result);
	}

	/**
	 * @dataProvider getRenderInheritanceFieldTestValues
	 * @param array $parameters
	 * @param array $settings
	 * @param boolean $expectsEmpty
	 */
	public function testRenderInheritanceField(array $parameters, array $settings, $expectsEmpty) {
		$typoScript = ['plugin.' => ['tx_fluidpages.' => $settings]];
		$configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', ['getConfiguration']);
		$configurationManager->expects($this->any())->method('getConfiguration')->willReturn($typoScript);
		$instance = new PageLayoutSelector();
		$instance->injectConfigurationManager($configurationManager);
		$result = $this->callInaccessibleMethod($instance, 'renderInheritanceField', $parameters);
		if (TRUE === $expectsEmpty) {
			$this->assertEmpty($result);
		} else {
			$this->assertNotEmpty($result);
		}
	}

	/**
	 * @return array
	 */
	public function getRenderInheritanceFieldTestValues() {
		$normalPage = ['pid' => 123, 'is_siteroot' => 0];
		$rootPage = ['pid' => 1, 'is_siteroot' => 1];
		$pidZeroPage = ['pid' => 0, 'is_siteroot' => 1];
		return [
			[[], [], TRUE],
			// main template field conditioning
			[['field' => 'tx_fed_page_controller_action', 'row' => $normalPage], [], FALSE],
			[['field' => 'tx_fed_page_controller_action', 'row' => $rootPage], ['siteRootInheritance' => FALSE], TRUE],
			[['field' => 'tx_fed_page_controller_action', 'row' => $rootPage], ['siteRootInheritance' => TRUE], FALSE],
			// sub template field conditioning
			[['field' => 'tx_fed_page_controller_action_sub', 'row' => $normalPage], [], FALSE],
			[['field' => 'tx_fed_page_controller_action_sub', 'row' => $rootPage], ['siteRootInheritance' => FALSE], TRUE],
			[['field' => 'tx_fed_page_controller_action_sub', 'row' => $rootPage], ['siteRootInheritance' => TRUE], FALSE],
			// pid zero pages never show inheritance field regardless of other settings
			[['field' => 'tx_fed_page_controller_action', 'row' => $pidZeroPage], [], TRUE],
			[['field' => 'tx_fed_page_controller_action', 'row' => $pidZeroPage], ['siteRootInheritance' => FALSE], TRUE],
			[['field' => 'tx_fed_page_controller_action', 'row' => $pidZeroPage], ['siteRootInheritance' => TRUE], TRUE],
			[['field' => 'tx_fed_page_controller_action_sub', 'row' => $pidZeroPage], [], TRUE],
			[['field' => 'tx_fed_page_controller_action_sub', 'row' => $pidZeroPage], ['siteRootInheritance' => FALSE], TRUE],
			[['field' => 'tx_fed_page_controller_action_sub', 'row' => $pidZeroPage], ['siteRootInheritance' => TRUE], TRUE],
		];
	}

	/**
	 * @dataProvider getRenderOptionsTestValues
	 * @param string $extension
	 * @param string $expectedTitle
	 * @return void
	 */
	public function testRenderOptions($extension, $expectedTitle) {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Backend\\PageLayoutSelector', ['renderOption']);
		$instance->expects($this->any())->method('renderOption')->willReturn('');
		$result = $this->callInaccessibleMethod($instance, 'renderOptions', $extension, ['foo' => 'bar'], []);
		$this->assertContains($expectedTitle, $result);
	}

	/**
	 * @return array
	 */
	public function getRenderOptionsTestValues() {
		return [
			['fluidpages', 'Package: Fluid Pages Engine'],
			['fakeextensionkey', 'Package: Fakeextensionkey']
		];
	}

	/**
	 * @dataProvider getRenderOptionTestValues
	 * @param string $file
	 * @param Form|NULL $form
	 * @param $expectedMessageFunction
	 * @param $expectsEmptyOutput
	 */
	public function testRenderOption($file, $form, $expectedMessageFunction, $expectsEmptyOutput) {
		$instance = new PageLayoutSelector();
		$service = $this->getMock(
			'FluidTYPO3\\Fluidpages\\Service\\ConfigurationService',
			['getPageConfiguration', 'getFormFromTemplateFile', 'message', 'debug']
		);
		$service->expects($this->any())->method('getPageConfiguration')->willReturn([
			'templateRootPaths' => ['EXT:fluidpages/Tests/Fixtures/Templates/']
		]);
		$service->expects($this->any())->method('getFormFromTemplateFile')->willReturn($form);
		if (NULL !== $expectedMessageFunction) {
			$service->expects($this->once())->method($expectedMessageFunction);
		}
		$instance->injectConfigurationService($service);
		$result = $this->callInaccessibleMethod($instance, 'renderOption', 'fluidpages', $file, []);
		if (TRUE === $expectsEmptyOutput) {
			$this->assertEmpty($result);
		} else {
			$this->assertNotEmpty($result);
		}

	}

	/**
	 * @return array
	 */
	public function getRenderOptionTestValues() {
		$validFile = 'Dummy';
		$disabledForm = $this->getMock('FluidTYPO3\\Flux\\Form', ['getEnabled']);
		$disabledForm->expects($this->once())->method('getEnabled')->willReturn(FALSE);
		$exceptionForm = $this->getMock('FluidTYPO3\\Flux\\Form', ['getEnabled']);
		$exceptionForm->expects($this->once())->method('getEnabled')->willThrowException(new \RuntimeException('test'));
		$correctForm = $this->getMock('FluidTYPO3\\Flux\\Form', ['getEnabled', 'getLabel']);
		$correctForm->expects($this->once())->method('getEnabled')->willReturn(TRUE);
		$correctForm->expects($this->once())->method('getLabel')->willReturn('label');
		return [
			['/does/not/exist', NULL, 'message', TRUE],
			[$validFile, NULL, 'message', TRUE],
			[$validFile, $disabledForm, 'message', TRUE],
			[$validFile, $exceptionForm, 'debug', TRUE],
			[$validFile, $correctForm, NULL, FALSE]
		];
	}

}
