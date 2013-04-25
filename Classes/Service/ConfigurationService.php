<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Claus Due <claus@wildside.dk>, Wildside A/S
 *
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
 * Configuration Service
 *
 * Provides methods to read various configuration related
 * to Fluid Content Elements.
 *
 * @author Claus Due, Wildside A/S
 * @package Fluidpages
 * @subpackage Service
 */
class Tx_Fluidpages_Service_ConfigurationService extends Tx_Flux_Service_FluxService implements t3lib_Singleton {

	/**
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Get definitions of paths for Page Templates defined in TypoScript
	 *
	 * @param string $extensionName
	 * @return array
	 * @api
	 */
	public function getPageConfiguration($extensionName = NULL) {
		$cacheKey = NULL === $extensionName ? 0 : $extensionName;
		$cacheKey = 'page_' . $cacheKey;
		if (TRUE === isset(self::$cache[$cacheKey])) {
			return self::$cache[$cacheKey];
		}
		if (TYPO3_MODE === 'BE') {
			// Hack. This is no fun matter, but the TYPO3 BackendConfigurationManager
			// is incapable of considering how to fetch a page UID from the "editconf"
			// array which is used when editing a particular page. The result is that
			// because Flux uses Extbase's resolve methods for TypoScript, the methods
			// will use the fallback behavior (only root TS templates). Forcibly setting
			// this GET['id'] should not have a negative effect on other scripts and will
			// fix the problem - because Extbase will instantly detect and use this ID.
			// New behaviour has one flaw: editing multiple pages will cause every page
			// to use the first page's TypoScript settings and therefore page templates -
			// which could be a problem when editing multiple pages each having own TS.
			if (TRUE === isset($GLOBALS['SOBE']->editconf['pages'])) {
				$_GET['id'] = key($GLOBALS['SOBE']->editconf['pages']);
			}
		}
		$newLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'collections', array(), 'fluidpages');
		$oldLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'page', array(), 'fed');
		$merged = t3lib_div::array_merge_recursive_overrule($oldLocation, $newLocation);
		$registeredExtensionKeys = Tx_Flux_Core::getRegisteredProviderExtensionKeys('Page');
		if (NULL === $extensionName) {
			foreach ($registeredExtensionKeys as $registeredExtensionKey) {
				$nativeViewLocation = $this->getPageConfiguration($registeredExtensionKey);
				if (NULL === $nativeViewLocation) {
					continue;
				}
				if (FALSE === isset($nativeViewLocation['extensionKey'])) {
					$nativeViewLocation['extensionKey'] = $registeredExtensionKey;
				}
				self::$cache[$registeredExtensionKey] = $nativeViewLocation;
				$merged[$registeredExtensionKey] = $nativeViewLocation;
			}
		} else {
			$nativeViewLocation = $this->getViewConfigurationForExtensionName($extensionName);
			if (NULL !== $nativeViewLocation) {
				if (FALSE === isset($nativeViewLocation['templateRootPath'])) {
					$this->message('The extension-native view configuration for extension "' . $extensionName . '" does not contain ' .
						'at least a templateRootPath. This indicates that the static TypoScript for the extension is not loaded or ' .
						'it uses constants which are either not defined, cleared or set to an empty value', t3lib_div::SYSLOG_SEVERITY_FATAL);
					return $merged;
				}
				if (FALSE === isset($nativeViewLocation['extensionKey'])) {
					$nativeViewLocation['extensionKey'] = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
				}
				$merged = t3lib_div::array_merge_recursive_overrule($merged, $nativeViewLocation);
			}
		}
		self::$cache[$cacheKey] = $merged;
		return $merged;
	}

}
