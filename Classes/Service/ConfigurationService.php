<?php
namespace FluidTYPO3\Fluidpages\Service;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
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

use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration Service
 *
 * Provides methods to read various configuration related
 * to Fluid Content Elements.
 *
 * @author Claus Due
 * @package Fluidpages
 * @subpackage Service
 */
class ConfigurationService extends FluxService implements SingletonInterface {

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
		if (NULL !== $extensionName && TRUE === empty($extensionName)) {
			// Note: a NULL extensionName means "fetch ALL defined collections" whereas
			// an empty value that is not null indicates an incorrect caller. Instead
			// of returning ALL paths here, an empty array is the proper return value.
			// However, dispatch a debug message to inform integrators of the problem.
			$this->message('Template paths have been attempted fetched using an empty value that is NOT NULL in ' . get_class($this) .
				'. This indicates a potential problem with your TypoScript configuration - a value which is expected to be ' .
			    'an array may be defined as a string. This error is not fatal but may prevent the affected collection (which cannot ' .
				'be identified here) from showing up', GeneralUtility::SYSLOG_SEVERITY_NOTICE);
			return array();
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
		$newLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'collections', 'fluidpages');
		$oldLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'page', 'fed');
		$merged = (array) GeneralUtility::array_merge_recursive_overrule($oldLocation, $newLocation);
		if (NULL === $extensionName) {
			$registeredExtensionKeys = Core::getRegisteredProviderExtensionKeys('Page');
			foreach ($registeredExtensionKeys as $registeredExtensionKey) {
				$extensionViewPaths = $this->getPageConfiguration($registeredExtensionKey);
				if (FALSE === isset($extensionViewPaths['extensionKey'])) {
					$extensionViewPaths['extensionKey'] = ExtensionNamingUtility::getExtensionKey($registeredExtensionKey);
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
			$nativeViewLocation = $this->getViewConfigurationForExtensionName($extensionName);
			if (TRUE === is_array($nativeViewLocation)) {
				$merged = GeneralUtility::array_merge_recursive_overrule($nativeViewLocation, $merged);
			}
			if (FALSE === isset($nativeViewLocation['extensionKey'])) {
				$merged['extensionKey'] = ExtensionNamingUtility::getExtensionKey($extensionName);
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
			'it uses constants which are either not defined, cleared or set to an empty value', GeneralUtility::SYSLOG_SEVERITY_FATAL);
	}

}
