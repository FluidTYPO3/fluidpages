<?php
namespace FluidTYPO3\Fluidpages\Provider;
/*****************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 *****************************************************************/

use FluidTYPO3\Fluidpages\Controller\PageControllerInterface;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Provider\AbstractProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\ResolveUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page Configuration Provider
 *
 * @author Claus Due <claus@namelesscoder.net>
 * @package Fluidpages
 * @subpackage Provider
 */
class PageProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * @var string
	 */
	protected $tableName = 'pages';

	/**
	 * @var string
	 */
	protected $parentFieldName = 'pid';

	/**
	 * @var string
	 */
	protected $fieldName = 'tx_fed_page_flexform';

	/**
	 * @var string
	 */
	protected $subFieldName = 'tx_fed_page_flexform_sub';

	/**
	 * @var string
	 */
	protected $currentFieldName = NULL;

	/**
	 * @var string
	 */
	protected $extensionKey = 'fluidpages';

	/**
	 * @var string
	 */
	protected $configurationSectionName = 'Configuration';

	/**
	 * @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools
	 */
	protected $flexformTool;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\PageService
	 */
	protected $pageService;

	/**
	 * @var \FluidTYPO3\Fluidpages\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @var integer
	 */
	protected $priority = 100;

	/**
	 * @var string
	 */
	protected $mainAction = 'tx_fed_page_controller_action';

	/**
	 * @var string
	 */
	protected $subAction = 'tx_fed_page_controller_action_sub';

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$this->flexformTool = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
	}

	/**
	 * @param \FluidTYPO3\Fluidpages\Service\PageService $pageService
	 * @return void
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param \FluidTYPO3\Fluidpages\Service\ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getExtensionKey(array $row) {
		$controllerExtensionKey = $this->getControllerExtensionKeyFromRecord($row);
		if (FALSE === empty($controllerExtensionKey)) {
			return ExtensionNamingUtility::getExtensionKey($controllerExtensionKey);
		}
		return parent::getExtensionKey($row);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getTemplatePaths(array $row) {
		$extensionName = $this->getExtensionKey($row);
		$paths = $this->configurationService->getPageConfiguration($extensionName);
		if (TRUE === is_array($paths) && FALSE === empty($paths)) {
			$paths = PathUtility::translatePath($paths);
			return $paths;
		}

		return parent::getTemplatePaths($row);
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTemplatePathAndFilename(array $row) {
		$action = $this->getControllerActionReferenceFromRecord($row);
		$paths = $this->getTemplatePaths($row);
		if (FALSE === empty($action)) {
			list (, $action) = explode('->', $action);
			$templatePathAndFilename = ResolveUtility::resolveTemplatePathAndFilenameByPathAndControllerNameAndActionAndFormat($paths, 'Page', $action);
		}
		$templatePathAndFilename = GeneralUtility::getFileAbsFileName($templatePathAndFilename);
		return $templatePathAndFilename;
	}

	/**
	 * Gets an inheritance tree (ordered parent -> ... -> this record)
	 * of record arrays containing raw values.
	 *
	 * @param array $row
	 * @return array
	 */
	public function getInheritanceTree(array $row) {
		if (TRUE === $this->isUsingSubFieldName()) {
			return array();
		}
		$records = parent::getInheritanceTree($row);
		if (0 === count($records)) {
			return $records;
		}
		$template = $records[0][$this->subAction];
		foreach ($records as $index => $record) {
			if ((FALSE === empty($record[$this->mainAction]) && $template !== $record[$this->mainAction]) || (FALSE === empty($record[$this->subAction]) && $template !== $record[$this->subAction])) {
				return array_slice($records, $index);
			}
		}
		return $records;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerExtensionKeyFromRecord(array $row) {
		$action = $this->getControllerActionReferenceFromRecord($row);
		if (FALSE !== strpos($action, '->')) {
			$extensionName = array_shift(explode('->', $action));
			return $extensionName;
		}
		return parent::getControllerExtensionKeyFromRecord($row);
	}

	/**
	 * @param array $row
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getControllerActionFromRecord(array $row) {
		if (PageControllerInterface::DOKTYPE_RAW === intval($row['doktype'])) {
			return 'raw';
		}
		$action = $this->getControllerActionReferenceFromRecord($row);
		if (TRUE === empty($action)) {
			$this->configurationService->message('No page template selected and no template was inherited from parent page(s)');
			return 'default';
		}
		$controllerActionName = array_pop(explode('->', $action));
		$controllerActionName{0} = strtolower($controllerActionName{0});
		return $controllerActionName;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		if (TRUE === $this->isUsingSubFieldName()) {
			return $configuration[$this->subAction];
		}
		return $configuration[$this->mainAction];
	}

	/**
	 * @param array $row The record row which triggered processing
	 * @return string|NULL
	 */
	public function getFieldName(array $row) {
		if (TRUE === $this->isUsingSubFieldName()) {
			return $this->subFieldName;
		}
		return $this->fieldName;
	}

	/**
	 * @param array $row
	 * @param string $table
	 * @param string $field
	 * @param string $extensionKey
	 * @return boolean
	 */
	public function trigger(array $row, $table, $field, $extensionKey = NULL) {
		$this->currentFieldName = $field;
		return parent::trigger($row, $table, $field, $extensionKey);
	}

	/**
	 * @return boolean
	 */
	public function isUsingSubFieldName() {
		return $this->currentFieldName === $this->subFieldName;
	}

	/**
	 * @param array $tree
	 * @param string $cacheKey Overrides the cache key
	 * @param boolean $mergeToCache Merges the configuration of $tree to the current $cacheKey
	 * @return array
	 */
	protected function getMergedConfiguration(array $tree, $cacheKey = NULL, $mergeToCache = FALSE) {
		$cacheKey = $this->getCacheKeyForMergedConfiguration($tree);
		if (TRUE === $this->hasCacheForMergedConfiguration($cacheKey)) {
			return parent::getMergedConfiguration($tree, $cacheKey);
		}

		if (FALSE === $this->isUsingSubFieldName()) {
			$branch = reset($tree);
			$hasMainAction = FALSE === empty($branch[$this->mainAction]);
			$hasSubAction = FALSE === empty($branch[$this->subAction]);
			$hasSubActionValue = FALSE === empty($branch[$this->subFieldName]);
			$mainAndSubActionsDiffer = $branch[$this->mainAction] !== $branch[$this->subAction];
			if (TRUE === $hasMainAction && TRUE === $hasSubAction && TRUE === $mainAndSubActionsDiffer && TRUE === $hasSubActionValue) {
				$branch = array_shift($tree);
				$this->currentFieldName = $this->subFieldName;
				parent::getMergedConfiguration(array($branch), $cacheKey);
				$this->currentFieldName = $this->fieldName;
			}
		}
		return parent::getMergedConfiguration($tree, $cacheKey, TRUE);
	}

}
