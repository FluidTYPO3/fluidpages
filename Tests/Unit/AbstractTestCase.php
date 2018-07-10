<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Charset\CharsetConverter;

/**
 * AbstractTestCase
 */
abstract class AbstractTestCase extends \FluidTYPO3\Development\AbstractTestCase
{
    /**
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['LANG'] = (object) ['csConvObj' => new CharsetConverter()];
    }
}
