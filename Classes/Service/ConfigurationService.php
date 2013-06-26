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
		$cacheKey = NULL === $extensionName ? 'pages_global' : 'pages_' . $extensionName;
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
		$merged = array();
		if (NULL === $extensionName) {
			$registeredExtensionKeys = Tx_Flux_Core::getRegisteredProviderExtensionKeys('Page');
			foreach ($registeredExtensionKeys as $registeredExtensionKey) {
				$extensionViewPaths = $this->getPageConfiguration($registeredExtensionKey);
				if (FALSE === isset($nativeViewLocation['extensionKey'])) {
					$extensionViewPaths['extensionKey'] = $registeredExtensionKey;
				}
				// preemptive caching; once read here, the cached value is returned when asking for specific extensions later
				if (FALSE === isset($extensionViewPaths['templateRootPath'])) {
					$this->sendWarningAboutMissingTemplatePath($registeredExtensionKey);
					continue;
				}
				self::$cache[$registeredExtensionKey] = $extensionViewPaths;
				$merged[$registeredExtensionKey] = $extensionViewPaths;
			}
		} else {
			$newLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'collections', 'fluidpages');
			$oldLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'page', 'fed');
			$merged = (array) t3lib_div::array_merge_recursive_overrule($oldLocation, $newLocation);
			$nativeViewLocation = $this->getViewConfigurationForExtensionName($extensionName);
			if (TRUE === is_array($nativeViewLocation)) {
				$merged = t3lib_div::array_merge_recursive_overrule($nativeViewLocation, $merged);
			}
			if (FALSE === isset($nativeViewLocation['extensionKey'])) {
				$merged['extensionKey'] = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
			}
			if (FALSE === isset($merged['templateRootPath'])) {
				$this->sendWarningAboutMissingTemplatePath($extensionName);
			}
		}
		self::$cache[$cacheKey] = $merged;
		return $merged;
	}

	/**
	 * @param string $extensionName
	 * @return void
	 */
	protected function sendWarningAboutMissingTemplatePath($extensionName) {
		$this->message('The configuration for extension "' . $extensionName . '" does not contain ' .
			'at least a templateRootPath. This indicates that the static TypoScript for the extension is not loaded or ' .
			'it uses constants which are either not defined, cleared or set to an empty value', t3lib_div::SYSLOG_SEVERITY_FATAL);
	}

}
