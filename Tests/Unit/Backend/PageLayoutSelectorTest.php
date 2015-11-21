<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\PageLayoutSelector;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Form;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

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
		/** @var PageLayoutSelector $instance */
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Backend\\PageLayoutSelector', array('renderInheritanceField', 'renderOptions'));
		/** @var PageService|\PHPUnit_Framework_MockObject_MockObject $service */
		$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\PageService', array('getAvailablePageTemplateFiles'));
		$service->expects($this->once())->method('getAvailablePageTemplateFiles')->willReturn(array('foo' => array('bar')));
		$instance->injectPageService($service);
		$parameters = array();
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
		$typoScript = array('plugin.' => array('tx_fluidpages.' => $settings));
		/** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
		$configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', array('getConfiguration'));
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
		$normalPage = array('pid' => 123, 'is_siteroot' => 0);
		$rootPage = array('pid' => 1, 'is_siteroot' => 1);
		$pidZeroPage = array('pid' => 0, 'is_siteroot' => 1);
		return array(
			array(array(), array(), TRUE),
			// main template field conditioning
			array(array('field' => 'tx_fed_page_controller_action', 'row' => $normalPage), array(), FALSE),
			array(array('field' => 'tx_fed_page_controller_action', 'row' => $rootPage), array('siteRootInheritance' => FALSE), TRUE),
			array(array('field' => 'tx_fed_page_controller_action', 'row' => $rootPage), array('siteRootInheritance' => TRUE), FALSE),
			// sub template field conditioning
			array(array('field' => 'tx_fed_page_controller_action_sub', 'row' => $normalPage), array(), FALSE),
			array(array('field' => 'tx_fed_page_controller_action_sub', 'row' => $rootPage), array('siteRootInheritance' => FALSE), TRUE),
			array(array('field' => 'tx_fed_page_controller_action_sub', 'row' => $rootPage), array('siteRootInheritance' => TRUE), FALSE),
			// pid zero pages never show inheritance field regardless of other settings
			array(array('field' => 'tx_fed_page_controller_action', 'row' => $pidZeroPage), array(), TRUE),
			array(array('field' => 'tx_fed_page_controller_action', 'row' => $pidZeroPage), array('siteRootInheritance' => FALSE), TRUE),
			array(array('field' => 'tx_fed_page_controller_action', 'row' => $pidZeroPage), array('siteRootInheritance' => TRUE), TRUE),
			array(array('field' => 'tx_fed_page_controller_action_sub', 'row' => $pidZeroPage), array(), TRUE),
			array(array('field' => 'tx_fed_page_controller_action_sub', 'row' => $pidZeroPage), array('siteRootInheritance' => FALSE), TRUE),
			array(array('field' => 'tx_fed_page_controller_action_sub', 'row' => $pidZeroPage), array('siteRootInheritance' => TRUE), TRUE),
		);
	}

	/**
	 * @dataProvider getRenderOptionsTestValues
	 * @param string $extension
	 * @param string $expectedTitle
	 * @return void
	 */
	public function testRenderOptions($extension, $expectedTitle) {
		$instance = $this->getMock('FluidTYPO3\\Fluidpages\\Backend\\PageLayoutSelector', array('renderOption'));
		$instance->expects($this->any())->method('renderOption')->willReturn('');
		$forms = array(
			Form::create(array('extensionName' => $extension))
		);
		$result = $this->callInaccessibleMethod($instance, 'renderOptions', $extension, $forms, array());
		$this->assertContains($expectedTitle, $result);
	}

	/**
	 * @return array
	 */
	public function getRenderOptionsTestValues() {
		return array(
			array('fluidpages', 'Package: Fluid Pages Engine'),
			array('fakeextensionkey', 'Package: Fakeextensionkey')
		);
	}

	/**
	 * @test
	 */
	public function testRenderOption() {
		$correctForm = $this->getMock('FluidTYPO3\\Flux\\Form', array('getLabel'));
		$correctForm->expects($this->once())->method('getLabel')->willReturn('label');
		$instance = new PageLayoutSelector();
		$result = $this->callInaccessibleMethod($instance, 'renderOption', $correctForm, array());
		$this->assertNotEmpty($result);

	}

}
