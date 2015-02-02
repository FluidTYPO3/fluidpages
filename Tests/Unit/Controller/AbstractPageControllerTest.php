<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractPageControllerTest
 */
class AbstractPageControllerTest extends UnitTestCase {

	/**
	 * @return void
	 */
	public function testGetRecordDelegatesToRecordService() {
		$subject = $this->getMockForAbstractClass('FluidTYPO3\\Fluidpages\\Controller\\AbstractPageController');
		$mockService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', array('getSingle'));
		$mockService->expects($this->once())->method('getSingle');
		$subject->injectWorkspacesAwareRecordService($mockService);
		$subject->getRecord();
	}

}
