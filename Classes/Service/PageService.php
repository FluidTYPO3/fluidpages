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

use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\Utility\PathUtility;
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
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var WorkspacesAwareRecordService
	 */
	protected $workspacesAwareRecordService;

	/**
	 * @param ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param WorkspacesAwareRecordService $workspacesAwareRecordService
	 * @return void
	 */
	public function injectWorkspacesAwareRecordService(WorkspacesAwareRecordService $workspacesAwareRecordService) {
		$this->workspacesAwareRecordService = $workspacesAwareRecordService;
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
		$pageUid = (integer) $pageUid;
		if (1 > $pageUid) {
			return NULL;
		}
		$page = $this->workspacesAwareRecordService->getSingle('pages', '*', $pageUid);

		// Note: 't3ver_oid' is analysed in order to make versioned records inherit the original record's
		// configuration as an emulated first parent page.
		$resolvedMainTemplateIdentity = $page['tx_fed_page_controller_action'];
		$resolvedSubTemplateIdentity = NULL;
		do {
			if (NULL === $resolvedSubTemplateIdentity && FALSE !== strpos($page['tx_fed_page_controller_action_sub'], '->')) {
				$resolvedSubTemplateIdentity = $page['tx_fed_page_controller_action_sub'];
				break;
			}
			$resolveParentPageUid = (integer) (0 > $page['pid'] ? $page['t3ver_oid'] : $page['pid']);
			$page = $this->workspacesAwareRecordService->getSingle('pages', '*', $resolveParentPageUid);
		} while (NULL !== $page && NULL === $resolvedSubTemplateIdentity);
		if (TRUE === empty($resolvedMainTemplateIdentity) && NULL === $resolvedSubTemplateIdentity) {
			return NULL;
		}
		if (TRUE === empty($resolvedMainTemplateIdentity) && NULL !== $resolvedSubTemplateIdentity) {
			$resolvedMainTemplateIdentity = $resolvedSubTemplateIdentity;
		}
		return array(
			'tx_fed_page_controller_action' => 	$resolvedMainTemplateIdentity,
			'tx_fed_page_controller_action_sub' => 	$resolvedSubTemplateIdentity
		);

	}

	/**
	 * Get a usable page configuration flexform from rootline
	 *
	 * @param integer $pageUid
	 * @return string
	 * @api
	 */
	public function getPageFlexFormSource($pageUid) {
		$pageUid = (integer) $pageUid;
		if (1 > $pageUid) {
			return NULL;
		}
		$page = $this->workspacesAwareRecordService->getSingle('pages', '*', $pageUid);
		while (NULL !== $page && 0 !== (integer) $page['uid'] && TRUE === empty($page['tx_fed_page_flexform'])) {
			$resolveParentPageUid = (integer) (0 > $page['pid'] ? $page['t3ver_oid'] : $page['pid']);
			$page = $this->workspacesAwareRecordService->getSingle('pages', '*', $resolveParentPageUid);
		};
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
					$this->getPageTemplateLabel($extensionName, $path . $file);
					$output[$extensionName][] = $pathinfo['filename'];
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
