<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Claus Due <claus@wildside.dk>, Wildside A/S
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
 * Page Service
 *
 * Service for interacting with Pages - gets content elements and page configuration
 * options.
 *
 * @package Fluidpages
 * @subpackage Service
 */
class Tx_Fluidpages_Service_PageService implements t3lib_Singleton {

	/**
	 * @var array
	 */
	private static $cache = array();

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Fluidpages_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param Tx_Fluidpages_Service_ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(Tx_Fluidpages_Service_ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * Process RootLine to find first usable, configured Fluid Page Template.
	 * WARNING: do NOT use the output of this feature to overwrite $row - the
	 * record returned may or may not be the same recod as defined in $id.
	 *
	 * @param integer $pageUid
	 * @return array|NULL
	 * @api
	 */
	public function getPageTemplateConfiguration($pageUid) {
		$pageUid = intval($pageUid);
		$workspaceId = intval($GLOBALS['BE_USER']->workspace);
		$cacheKey = 'page_uid' . $pageUid . '_wsid' . $workspaceId;
		if (1 > $pageUid) {
			return NULL;
		}
		if (TRUE === isset(self::$cache[$cacheKey])) {
			return self::$cache[$cacheKey];
		}
		$page = $this->getPage($pageUid);
		// if page has a controller action
		if (strpos($page['tx_fed_page_controller_action'], '->')) {
			return $page;
		}
		// if no controller action was found loop through rootline
		do {
			$page = $this->getPageParent($page);
		} while (FALSE !== $page && FALSE === strpos($page['tx_fed_page_controller_action_sub'], '->'));
		if (FALSE === $page) {
			self::$cache[$cacheKey] = NULL;
			return NULL;
		}
		$page['tx_fed_page_controller_action'] = $page['tx_fed_page_controller_action_sub'];
		if (TRUE === empty($page['tx_fed_page_controller_action'])) {
			$page = NULL;
		}
		self::$cache[$cacheKey] = $page;
		return $page;

	}

	/**
	 * Return the original or workspace page depending on workspace-mode
	 *
	 * @param integer $pageUid
	 * @return array|bool
	 */
	protected function getPage($pageUid) {
		$table = 'pages';
		$wsId = intval($GLOBALS['BE_USER']->workspace);
		$pageUid = intval($pageUid);
		if (1 > $pageUid) {
			return FALSE;
		}
		// check if active workspace is available
		$page = t3lib_BEfunc::getWorkspaceVersionOfRecord($wsId, $table, $pageUid);
		if (FALSE === $page) {
			// no workspace available ... use original one
			$page = t3lib_BEfunc::getRecord($table, $pageUid, '*');
		}
		return $page;
	}

	/**
	 * Return parent page array
	 *
	 * @param array $page
	 * @return array|bool
	 */
	protected function getPageParent($page) {
		// try to get the original page
		$live = t3lib_BEfunc::getLiveVersionIdOfRecord('pages', intval($page['uid']));
		$live = ($live === NULL ? $page : $live);
		return $this->getPage($live['pid']);
	}

	/**
	 * Gets the workspace parent for a given page
	 *
	 * @param array $page
	 * @return array
	 */
	protected function getWorkspaceParentPage($page) {
		$page = $this->getPositionPlaceholder($page);
		$page = t3lib_BEfunc::getRecord('pages', $page['pid']);
		$page = $this->getPositionPlaceholder($page);
		return $page;
	}

	/**
	 * Gets the workspace version of a given page
	 *
	 * @param array $page
	 * @return array
	 */
	protected function getWorkspacePage($page) {
		if ($page) {
			$wsid = $GLOBALS['BE_USER']->workspace ?: 0;
			if ($wsid != 0 && $page['t3ver_wsid'] != $wsid) {
				$workspacePage = t3lib_BEfunc::getRecordRaw('pages', $where = sprintf('t3ver_oid=%d AND t3ver_wsid=%d', $page['uid'], $wsid), $fields = '*');
				if ($workspacePage !== NULL) {
					$page = $workspacePage;
				}
			}
		}
		return $page;
	}

	/**
	 * Gets a placeholder for a given page
	 *
	 * @param array $page
	 * @return array
	 */
	protected function getPositionPlaceholder($page) {
		if ($page['pid'] != -1) {
			// original, dont do anything
			return $page;
		} elseif ($page['t3ver_state'] == 0) {
			// page has changed, but not moved
			$page = t3lib_BEfunc::getRecord('pages', $page['t3ver_oid']);
		} elseif ($page['t3ver_state'] == 4) {
			// page has moved. get placeholder for new position
			$page = t3lib_BEfunc::getRecordRaw('pages', $where = sprintf('t3ver_move_id=%d AND t3ver_state=3', $page['t3ver_oid']), $fields = '*');
		}
		return $page;
	}

	/**
	 * Get a usable page configuration flexform from rootline
	 *
	 * @param integer $pageUid
	 * @return string
	 * @api
	 */
	public function getPageFlexFormSource($pageUid) {
		$pageUid = intval($pageUid);
		if (1 > $pageUid) {
			return NULL;
		}
		$workspaceId = intval($GLOBALS['BE_USER']->workspace);
		$cacheKey = 'flexform_uid' . $pageUid . '_wsid' . $workspaceId;
		if (TRUE === isset(self::$cache[$cacheKey])) {
			return self::$cache[$cacheKey];
		}
		$page = $this->getPage($pageUid);
		while (0 !== intval($page['uid']) && TRUE === empty($page['tx_fed_page_flexform'])) {
			$page = $this->getPageParent($page);
		};
		if (empty($page['tx_fed_page_flexform'])) {
			self::$cache[$cacheKey] = NULL;
			return NULL;
		}
		self::$cache[$cacheKey] = $page['tx_fed_page_flexform'];
		return $page['tx_fed_page_flexform'];

	}

	/**
	 * Gets a human-readable label from a Fluid Page template file
	 *
	 * @param string $extensionName
	 * @param string $templateFile
	 * @return string
	 * @api
	 */
	public function getPageTemplateLabel($extensionName, $templateFile) {
		$config = $this->configurationService->getPageConfiguration($extensionName);
		$templatePathAndFilename = $this->expandPathsAndTemplateFileToTemplatePathAndFilename($config, $templateFile);
		$page = $this->configurationService->getStoredVariable($templatePathAndFilename, 'storage', 'Configuration', $config, $extensionName);
		return $page['label'] ? $page['label'] : $templateFile . '.html';
	}

	/**
	 * Returns TRUE if the template is enabled
	 *
	 * @param string $extensionName
	 * @param string $templateFile
	 * @return string
	 * @api
	 */
	public function getPageTemplateEnabled($extensionName, $templateFile) {
		$config = $this->configurationService->getPageConfiguration($extensionName);
		$templatePathAndFilename = $this->expandPathsAndTemplateFileToTemplatePathAndFilename($config, $templateFile);
		$page = $this->configurationService->getStoredVariable($templatePathAndFilename, 'storage', 'Configuration', $config, $extensionName);
		return (TRUE === (boolean) $page['enabled']);
	}

	/**
	 * Gets a list of usable Page Templates from defined page template TypoScript
	 *
	 * @param string $format
	 * @return array
	 * @api
	 */
	public function getAvailablePageTemplateFiles($format = 'html') {
		$typoScript = $this->configurationService->getPageConfiguration();
		$output = array();
		if (is_array($typoScript) === FALSE) {
			return $output;
		}
		foreach ($typoScript as $extensionName=>$group) {
			if (isset($group['enable']) === TRUE && $group['enable'] < 1) {
				continue;
			}
			if (FALSE === isset($group['templateRootPath'])) {
				$this->configurationService->message('The template group "' . $extensionName . '" does not define a set of template containing at least a templateRootPath' .
					'paths. This indicates a problem with your TypoScript configuration - most likely a static template is not loaded', t3lib_div::SYSLOG_SEVERITY_WARNING);
				continue;
			}
			$configuredPath = $group['templateRootPath'] . 'Page' . '/';
			$path = t3lib_div::getFileAbsFileName($configuredPath);
			if (FALSE === is_dir($path)) {
				$this->configurationService->message('The template group "' . $extensionName . '" has been configured to use the templateRootPath "' .
					$configuredPath . '" but this directory does not exist.', t3lib_div::SYSLOG_SEVERITY_FATAL);
				continue;
			}
			$files = scandir($path);
			$output[$extensionName] = array();
			foreach ($files as $k=>$file) {
				$pathinfo = pathinfo($path . $file);
				$extension = $pathinfo['extension'];
				if (substr($file, 0, 1) === '.') {
					unset($files[$k]);
				} else if (strtolower($extension) != strtolower($format)) {
					unset($files[$k]);
				} else {
					try {
						$this->getPageTemplateLabel($extensionName, $path . $file);
						$output[$extensionName][] = $pathinfo['filename'];
					} catch (Exception $error) {
						$this->configurationService->debug($error);
						continue;
					}
				}
			}
		}
		return $output;
	}

	/**
	 * @param array $paths
	 * @param string $template
	 * @return string
	 */
	public function expandPathsAndTemplateFileToTemplatePathAndFilename($paths, $template) {
		if (TRUE === file_exists($template)) {
			$templatePathAndFilename = $template;
		} else {
			$templatePathAndFilename = $paths['templateRootPath'] . 'Page/' . $template . '.html';
		}
		return $templatePathAndFilename;
	}

}

