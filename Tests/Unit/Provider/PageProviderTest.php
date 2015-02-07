<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Tests\UnitTestCase;
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

}
