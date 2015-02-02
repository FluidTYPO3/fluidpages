<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayout;
use FluidTYPO3\Flux\Service\ContentService;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
		$tca = array('foo' => 'bar');
		$mock = $this->getMock('TYPO3\CMS\Backend\Form\FormEngine', array('fake'), array(), '', FALSE);
		$instance = new BackendLayout();
		$instance->postProcessColPosListItemsParsed($id, $tca, $mock);
		$this->assertEquals(1, $id);
		$this->assertEquals(array('foo' => 'bar'), $tca);
	}

	/**
	 * @return void
	 */
	public function testPostProcessColPosProcFuncItemsAppendsFluidContentArea() {
		$instance = new BackendLayout();
		$parameters = array(
			'items' => array()
		);
		$instance->postProcessColPosProcFuncItems($parameters);
		$this->assertContains(array('Fluid Content Area', ContentService::COLPOS_FLUXCONTENT, NULL), $parameters['items']);
	}

}
