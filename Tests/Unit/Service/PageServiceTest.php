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
}
