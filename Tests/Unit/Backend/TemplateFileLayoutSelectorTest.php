<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\TemplateFileLayoutSelector;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TemplateFileLayoutSelectorTest
 */
class TemplateFileLayoutSelectorTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testPerformsInjections() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidpages\\Backend\\TemplateFileLayoutSelector');
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\PageService', 'pageService', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', 'configurationService', $instance);
	}

	/**
	 * @return void
	 */
	public function testAddLayoutOptions() {
		$layoutRootPath = 'EXT:fluidpages/Tests/Fixtures/Templates/Page/';
		$parameters = array('items' => array());
		$instance = new TemplateFileLayoutSelector();
		$parent = '';
		$service = $this->getMock('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService', array('getViewConfigurationByFileReference'));
		$service->expects($this->once())->method('getViewConfigurationByFileReference')->willReturn(array(
			'layoutRootPaths' => array($layoutRootPath)
		));
		$instance->injectConfigurationService($service);
		$instance->addLayoutOptions($parameters, $parent);
		$this->assertEquals(array('Dummy', 'Dummy'), $parameters['items'][0]);
	}

}
