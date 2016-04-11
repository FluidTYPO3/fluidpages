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
use FluidTYPO3\Flux\Utility\RecursiveArrayUtility;
use FluidTYPO3\Flux\View\TemplatePaths;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Page Configuration Provider
 *
 * Main Provider - triggers only on
 * records which have a selected action.
 * All other page records will be associated
 * with the SubPageProvider instead.
 */
class PageProvider extends AbstractProvider implements ProviderInterface {

	const FIELD_NAME_MAIN = 'tx_fed_page_flexform';
	const FIELD_NAME_SUB = 'tx_fed_page_flexform_sub';
	const FIELD_ACTION_MAIN = 'tx_fed_page_controller_action';
	const FIELD_ACTION_SUB = 'tx_fed_page_controller_action_sub';

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
	protected $fieldName = self::FIELD_NAME_MAIN;

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
	protected $pageConfigurationService;

	/**
	 * @var array
	 */
	private static $cache = array();

	/**
	 * CONSTRUCTOR
	 */
	public function __construct() {
		$this->flexformTool = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
	}

	/**
	 * Returns TRUE that this Provider should trigger if:
	 *
	 * - table matches 'pages'
	 * - field is NULL or matches self::FIELD_NAME
	 * - a selection was made in the "template for this page" field
	 *
	 * @param array $row
	 * @param string $table
	 * @param string $field
	 * @param string|NULL $extensionKey
	 * @return boolean
	 */
	public function trigger(array $row, $table, $field, $extensionKey = NULL) {
		$isRightTable = ($table === $this->tableName);
		$isRightField = (NULL === $field || $field === $this->fieldName);
		return (TRUE === $isRightTable && TRUE === $isRightField);
	}

	/**
	 * @param PageService $pageService
	 * @return void
	 */
	public function injectPageService(PageService $pageService) {
		$this->pageService = $pageService;
	}

	/**
	 * @param ConfigurationService $pageConfigurationService
	 * @return void
	 */
	public function injectPageConfigurationService(ConfigurationService $pageConfigurationService) {
		$this->pageConfigurationService = $pageConfigurationService;
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
		if (PageControllerInterface::DOKTYPE_RAW === (integer) $row['doktype']) {
			return 'raw';
		}
		$action = $this->getControllerActionReferenceFromRecord($row);
		if (TRUE === empty($action)) {
			$this->pageConfigurationService->message('No page template selected and no template was inherited from parent page(s)');
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
		if (TRUE === empty($row[self::FIELD_ACTION_MAIN])) {
			$row = $this->pageService->getPageTemplateConfiguration($row['uid']);
		}
		return $row[self::FIELD_ACTION_MAIN];
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getFlexFormValues(array $row) {
		$fieldName = $this->getFieldName($row);
		$form = $this->getForm($row);
		$immediateConfiguration = $this->getFlexFormValuesSingle($row);
		$inheritedConfiguration = $this->getInheritedConfiguration($row);
		$merged = RecursiveArrayUtility::merge($inheritedConfiguration, $immediateConfiguration);
		return $merged;
	}

	/**
	 * @param array $row source record row
	 * @param array $configuration to be overlayed
	 */
	public function overlayFlexFormValues($row, $configuration, $form) {
		if ($GLOBALS['TSFE']->sys_language_uid > 0) {
			$overlays = $this->recordService->get(
				'pages_language_overlay',
				'*',
				'hidden = 0 AND deleted = 0 AND sys_language_uid = ' . $GLOBALS['TSFE']->sys_language_uid . ' AND pid = ' . $row['uid']
			);
			$fieldName = $this->getFieldName($row);
			if (count($overlays) > 0) {
				foreach ($overlays as $overlay) {
					if (isset($overlay[$fieldName])) {
						// Note about condition: overlays may not consistently contain a workable value; skip those that don't
						$overlayConfiguration = $this->pageConfigurationService->convertFlexFormContentToArray($overlay[$fieldName], $form, NULL, NULL);
						$configuration = RecursiveArrayUtility::merge($configuration, $overlayConfiguration);
					}
				}
			}
		}
		return $configuration;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getFlexFormValuesSingle(array $row) {
		$fieldName = $this->getFieldName($row);
		$form = $this->getForm($row);

		// legacy language handling, this was deprecated in TYPO3 7.6 (Deprecation: #70138 - Flex form language handling)
		// this should stay here for a little while and be removed at some point in the future
		$languageRef = NULL;
		if ($GLOBALS['TSFE']->sys_language_uid > 0) {
			$languageRef = 'l' . $GLOBALS['TSFE']->config['config']['language'];
		}
		$immediateConfiguration = $this->pageConfigurationService->convertFlexFormContentToArray($row[$fieldName], $form, $languageRef, NULL);

		// replacement for the deprecated language handling (Deprecation: #70138 - Flex form language handling)
		$immediateConfiguration = $this->overlayFlexFormValues($row, $immediateConfiguration, $form);
		return $immediateConfiguration;
	}

	/**
	 * @param string $operation
	 * @param integer $id
	 * @param array $row
	 * @param DataHandler $reference
	 * @param array $removals Allows overridden methods to pass an additional array of field names to remove from the stored Flux value
	 */
	public function postProcessRecord($operation, $id, array &$row, DataHandler $reference, array $removals = array()) {
		if ('update' === $operation) {
			$record = $this->loadRecordFromDatabase($id);
			$record = RecursiveArrayUtility::mergeRecursiveOverrule($record, $reference->datamap[$this->tableName][$id]);
			$form = $this->getForm($record);
			if (NULL !== $form) {
				$tableFieldName = $this->getFieldName($record);
				foreach ($form->getFields() as $field) {
					$fieldName = $field->getName();
					$sheetName = $field->getParent()->getName();
					$inherit = (boolean) $field->getInherit();
					$inheritEmpty = (boolean) $field->getInheritEmpty();
					if (TRUE === isset($record[$tableFieldName]['data']) && TRUE === is_array($record[$tableFieldName]['data'])) {
						$value = $record[$tableFieldName]['data'][$sheetName]['lDEF'][$fieldName]['vDEF'];
						$inheritedValue = $this->getInheritedPropertyValueByDottedPath($record, $fieldName);
						$empty = (TRUE === empty($value) && $value !== '0' && $value !== 0);
						$same = ($inheritedValue == $value);
						if (TRUE === $same && TRUE === $inherit || (TRUE === $inheritEmpty && TRUE === $empty)) {
							$removals[] = $fieldName;
						}
					}
				}
			}
		}
		parent::postProcessRecord($operation, $id, $row, $reference, $removals);
	}

	/**
	 * Gets an inheritance tree (ordered parent -> ... -> this record)
	 * of record arrays containing raw values.
	 *
	 * @param array $row
	 * @return array
	 */
	protected function getInheritanceTree(array $row) {
		$records = $this->loadRecordTreeFromDatabase($row);
		if (0 === count($records)) {
			return $records;
		}
		$template = $records[0][self::FIELD_ACTION_SUB];
		foreach ($records as $index => $record) {
			$hasMainAction = FALSE === empty($record[self::FIELD_ACTION_MAIN]);
			$hasSubAction = FALSE === empty($record[self::FIELD_ACTION_SUB]);
			$shouldUseMainTemplate = $template !== $record[self::FIELD_ACTION_SUB];
			$shouldUseSubTemplate = $template !== $record[self::FIELD_ACTION_MAIN];
			if (($hasMainAction && $shouldUseSubTemplate) || ($hasSubAction && $shouldUseMainTemplate)) {
				return array_slice($records, $index);
			}
		}
		return $records;
	}

	/**
	 * @param Form $form
	 * @param array $row
	 * @return Form
	 */
	protected function setDefaultValuesInFieldsWithInheritedValues(Form $form, array $row) {
		$inheritedConfiguration = $this->getInheritedConfiguration($row);
		foreach ($form->getFields() as $field) {
			$name = $field->getName();
			$inheritedValue = $this->getInheritedPropertyValueByDottedPath($inheritedConfiguration, $name);
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
	protected function getInheritedConfiguration(array $row) {
		$tableName = $this->getTableName($row);
		$tableFieldName = $this->getFieldName($row);
		$cacheKey = $tableName . $tableFieldName . $row['uid'];
		if (FALSE === isset(self::$cache[$cacheKey])) {
			$tree = $this->getInheritanceTree($row);
			$data = array();
			foreach ($tree as $branch) {
				/** @var SubPageProvider $provider */
				$provider = $this->pageConfigurationService->resolvePrimaryConfigurationProvider($this->tableName, self::FIELD_NAME_SUB, $branch);
				$form = $provider->getForm($branch);
				if (NULL === $form) {
					continue;
				}
				$fields = $form->getFields();
				$values = $provider->getFlexFormValuesSingle($branch);
				foreach ($fields as $field) {
					$values = $this->unsetInheritedValues($field, $values);
				}
				$data = RecursiveArrayUtility::merge($data, $values);
			}
			self::$cache[$cacheKey] = $data;
		}
		return self::$cache[$cacheKey];
	}

	/**
	 * @param array $inheritedConfiguration
	 * @param string $propertyPath
	 * @return mixed
	 */
	protected function getInheritedPropertyValueByDottedPath($inheritedConfiguration, $propertyPath) {
		if (TRUE === empty($propertyPath)) {
			return NULL;
		} elseif (FALSE === strpos($propertyPath, '.')) {
			return TRUE === isset($inheritedConfiguration[$propertyPath]) ? ObjectAccess::getProperty($inheritedConfiguration, $propertyPath) : NULL;
		}
		return ObjectAccess::getPropertyPath($inheritedConfiguration, $propertyPath);
	}

	/**
	 * @param Form\FormInterface $field
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
		while (0 < $record[$parentFieldName]) {
			$record = $this->loadRecordFromDatabase($record[$parentFieldName]);
			$parentFieldName = $this->getParentFieldName($record);
			array_push($records, $record);
		}
		$records = array_reverse($records);
		return $records;
	}

}
