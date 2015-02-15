<?php
namespace FluidTYPO3\Fluidpages\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Controller\PageControllerInterface;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Provider\AbstractProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\Utility\RecursiveArrayUtility;
use FluidTYPO3\Flux\Utility\ResolveUtility;
use FluidTYPO3\Flux\View\TemplatePaths;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Page Configuration Provider
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
	 * @var FlexFormTools
	 */
	protected $flexformTool;

	/**
	 * @var PageService
	 */
	protected $pageService;

	/**
	 * @var ConfigurationService
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
	 * @param PageService $pageService
	 * @return void
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param ConfigurationService $configurationService
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
		return $this->extensionKey;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTemplatePathAndFilename(array $row) {
		$templatePathAndFilename = $this->templatePathAndFilename;
		$action = $this->getControllerActionReferenceFromRecord($row);
		if (FALSE === empty($action)) {
			$paths = $this->getTemplatePaths($row);
			$templatePaths = new TemplatePaths($paths);
			list (, $action) = explode('->', $action);
			$action = ucfirst($action);
			$templatePathAndFilename = $templatePaths->resolveTemplateFileForControllerAndActionAndFormat('Page', $action);
		}
		return $templatePathAndFilename;
	}

	/**
	 * @param array $row
	 * @return Form|NULL
	 */
	public function getForm(array $row) {
		$form = parent::getForm($row);
		if (NULL !== $form) {
			$form = $this->setDefaultValuesInFieldsWithInheritedValues($form, $row);
		}
		return $form;
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
		$records = $this->loadRecordTreeFromDatabase($row);
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
		return $this->extensionKey;
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
		 * @param Form $form
		 * @param array $row
		 * @return Form
		 */
	protected function setDefaultValuesInFieldsWithInheritedValues(Form $form, array $row) {
		foreach ($form->getFields() as $field) {
			$name = $field->getName();
			$inheritedValue = $this->getInheritedPropertyValueByDottedPath($row, $name);
			if (NULL !== $inheritedValue && TRUE === $field instanceof Form\FieldInterface) {
				$field->setDefault($inheritedValue);
			}
		}
		return $form;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getFlexFormValues(array $row) {
		$fieldName = $this->getFieldName($row);
 		$form = $this->getForm($row);
		$immediateConfiguration = $this->configurationService->convertFlexFormContentToArray($row[$fieldName], $form, NULL, NULL);
		$tree = $this->getInheritanceTree($row);
		if (0 === count($tree)) {
			return (array) $immediateConfiguration;
		}
		$inheritedConfiguration = $this->getMergedConfiguration($tree);
		if (0 === count($immediateConfiguration)) {
			return (array) $inheritedConfiguration;
		}
		$merged = RecursiveArrayUtility::merge($inheritedConfiguration, $immediateConfiguration);
		return $merged;
 	}

	/**
	 * @param array $row
	 * @param string $propertyPath
	 * @return mixed
	 */
	protected function getInheritedPropertyValueByDottedPath(array $row, $propertyPath) {
		$tree = $this->getInheritanceTree($row);
		$inheritedConfiguration = $this->getMergedConfiguration($tree);
		if (FALSE === strpos($propertyPath, '.')) {
			return TRUE === isset($inheritedConfiguration[$propertyPath]) ? ObjectAccess::getProperty($inheritedConfiguration, $propertyPath) : NULL;
		}
		return ObjectAccess::getPropertyPath($inheritedConfiguration, $propertyPath);
	}

	/**
	 * @param FormInterface $field
	 * @param array $values
	 * @return array
	 */
	protected function unsetInheritedValues(Form\FormInterface $field, $values) {
		$name = $field->getName();
		$inherit = (boolean) $field->getInherit();
		$inheritEmpty = (boolean) $field->getInheritEmpty();
		$empty = (TRUE === empty($values[$name]) && $values[$name] !== '0' && $values[$name] !== 0);
		if (FALSE === $inherit || (TRUE === $inheritEmpty && TRUE === $empty)) {
			unset($values[$name]);
		}
		return $values;
	}

	/**
	 * @param array $tree
	 * @param string $cacheKey Overrides the cache key
	 * @param boolean $mergeToCache Merges the configuration of $tree to the current $cacheKey
	 * @return array
	 */
	protected function getMergedConfiguration(array $tree, $cacheKey = NULL, $mergeToCache = FALSE) {
		if (FALSE === $this->isUsingSubFieldName()) {
			$branch = reset($tree);
			$hasMainAction = FALSE === empty($branch[$this->mainAction]);
			$hasSubAction = FALSE === empty($branch[$this->subAction]);
			$hasSubActionValue = FALSE === empty($branch[$this->subFieldName]);
			$mainAndSubActionsDiffer = $branch[$this->mainAction] !== $branch[$this->subAction];
			if (TRUE === $hasMainAction && TRUE === $hasSubAction && TRUE === $mainAndSubActionsDiffer && TRUE === $hasSubActionValue) {
				$branch = array_shift($tree);
				$this->currentFieldName = $this->subFieldName;
				$this->getMergedConfigurationInternal(array($branch), $cacheKey);
				$this->currentFieldName = $this->fieldName;
			}
		}
		return $this->getMergedConfigurationInternal($tree, $cacheKey);
	}

	/**
	 * @param array $tree
	 * @param string $cacheKey Overrides the cache key
	 * @return array
	 */
	protected function getMergedConfigurationInternal(array $tree, $cacheKey = NULL) {
		$data = array();
		foreach ($tree as $branch) {
			$form = $this->getForm($branch);
			if (NULL === $form) {
				return $data;
			}
			$fields = $form->getFields();
			$values = $this->getFlexFormValues($branch);
			foreach ($fields as $field) {
				$values = $this->unsetInheritedValues($field, $values);
			}
			$data = RecursiveArrayUtility::merge($data, $values);
		}
		return $data;
	}

	/**
	 * @param array $row
	 * @return mixed
	 */
	protected function getParentFieldValue(array $row) {
		$parentFieldName = $this->getParentFieldName($row);
		if (NULL !== $parentFieldName && FALSE === isset($row[$parentFieldName])) {
			$row = $this->loadRecordFromDatabase($row['uid']);
		}
		return $row[$parentFieldName];
	}

	/**
	 * @param array $record
	 * @return array
	 */
	protected function loadRecordTreeFromDatabase($record) {
		$parentFieldName = $this->getParentFieldName($record);
		if (FALSE === isset($record[$parentFieldName])) {
			$record[$parentFieldName] = $this->getParentFieldValue($record);
		}
		$records = array();
		while ($record[$parentFieldName] > 0) {
			$record = $this->loadRecordFromDatabase($record[$parentFieldName]);
			$parentFieldName = $this->getParentFieldName($record);
			array_push($records, $record);
		}
		$records = array_reverse($records);
		return $records;
	}

}
