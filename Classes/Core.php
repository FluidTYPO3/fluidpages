<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@wildside.dk>, Wildside A/S
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * FLUIDPAGES CORE
 *
 * Quick-access API methods to easily integrate with Flux
 *
 * @author Claus Due, Wildside A/S
 * @package Fluidpages
 */
class Tx_Fluidpages_Core {

	/**
	 * Contains all extensions registered with Fluidpages
	 * @var array
	 */
	private static $extensions = array();

	/**
	 * @param string $extensionKey
	 * @return void
	 */
	public static function registerProviderExtensionKey($extensionKey) {
		if (FALSE === in_array($extensionKey, self::$extensions)) {
			array_push(self::$extensions, $extensionKey);
		}
	}

	/**
	 * @return array
	 */
	public static function getRegisteredProviderExtensionKeys() {
		return self::$extensions;
	}

}
