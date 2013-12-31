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

use FluidTYPO3\Flux\Utility\PathUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Page Service
 *
 * Service for interacting with Pages - gets content elements and page configuration
 * options.
 *
 * @package Fluidpages
 * @subpackage Service
 */
class PageService implements SingletonInterface {

	/**
	 * @var array
	 */
	private static $cache = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \FluidTYPO3\Fluidpages\Service\ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
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
	 * @return array|boolean
	 */
	public function getPage($pageUid) {
		$table = 'pages';
		$wsId = intval($GLOBALS['BE_USER']->workspace);
		$pageUid = intval($pageUid);
		if (1 > $pageUid) {
			return FALSE;
		}
		// check if active workspace is available
		$page = BackendUtility::getWorkspaceVersionOfRecord($wsId, $table, $pageUid);
		if (FALSE === $page) {
			// no workspace available ... use original one
			$page = BackendUtility::getRecord($table, $pageUid, '*');
		}
		return $page;
	}

	/**
	 * Return parent page array
	 *
	 * @param array $page
	 * @return array|boolean
	 */
	protected function getPageParent($page) {
		// try to get the original page
		$live = BackendUtility::getLiveVersionIdOfRecord('pages', intval($page['uid']));
		$live = NULL === $live ? $page : $live;
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
		$page = BackendUtility::getRecord('pages', $page['pid']);
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
		if (TRUE === is_array($page) && 0 < count($page)) {
			$wsid = $GLOBALS['BE_USER']->workspace ? : 0;
			$wsid = intval($wsid);
			if (0 !== $wsid && intval($page['t3ver_wsid']) !== $wsid) {
				$workspacePage = BackendUtility::getRecordRaw('pages', $where = sprintf('t3ver_oid=%d AND t3ver_wsid=%d', $page['uid'], $wsid), $fields = '*');
				if (NULL !== $workspacePage) {
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
		if (-1 !== intval($page['pid'])) {
			// original, dont do anything
			return $page;
		} elseif (0 === intval($page['t3ver_state'])) {
			// page has changed, but not moved
			$page = BackendUtility::getRecord('pages', $page['t3ver_oid']);
		} elseif (4 === intval($page['t3ver_state'])) {
			// page has moved. get placeholder for new position
			$page = BackendUtility::getRecordRaw('pages', $where = sprintf('t3ver_move_id=%d AND t3ver_state=3', $page['t3ver_oid']), $fields = '*');
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
		$form = $this->configurationService->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', array(), $extensionName);
		return (FALSE === empty($form) ? $form->getLabel() : $templateFile . '.html');
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
		$form = $this->configurationService->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', array(), $extensionName);
		return $form->getEnabled();
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
		if (FALSE === is_array($typoScript)) {
			return $output;
		}
		foreach ($typoScript as $extensionName=>$group) {
			if (TRUE === isset($group['enable']) && 1 > $group['enable']) {
				continue;
			}
			if (FALSE === isset($group['templateRootPath'])) {
				$this->configurationService->message('The template group "' . $extensionName . '" does not define a set of template containing at least a templateRootPath' .
					'paths. This indicates a problem with your TypoScript configuration - most likely a static template is not loaded', GeneralUtility::SYSLOG_SEVERITY_WARNING);
				continue;
			}
			$configuredPath = rtrim($group['templateRootPath'], '/') . '/Page/';
			$path = GeneralUtility::getFileAbsFileName($configuredPath);
			if (FALSE === is_dir($path)) {
				$this->configurationService->message('The template group "' . $extensionName . '" has been configured to use the templateRootPath "' .
					$configuredPath . '" but this directory does not exist.', GeneralUtility::SYSLOG_SEVERITY_FATAL);
				continue;
			}
			$files = scandir($path);
			$output[$extensionName] = array();
			foreach ($files as $key => $file) {
				$pathinfo = pathinfo($path . $file);
				$extension = $pathinfo['extension'];
				if ('.' === substr($file, 0, 1)) {
					unset($files[$key]);
				} else if (strtolower($extension) != strtolower($format)) {
					unset($files[$key]);
				} else {
					try {
						$this->getPageTemplateLabel($extensionName, $path . $file);
						$output[$extensionName][] = $pathinfo['filename'];
					} catch (\Exception $error) {
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
			if (TRUE === is_array($paths) && FALSE === empty($paths)) {
				$paths = PathUtility::translatePath($paths);
			}
			$templatePathAndFilename = rtrim($paths['templateRootPath'], '/') . '/Page/' . $template . '.html';
		}
		return $templatePathAndFilename;
	}

}
