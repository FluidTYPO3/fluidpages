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
 * Page SubConfiguration Provider
 *
 * This Provider has a slightly lower priority
 * than the main PageProvider but will trigger
 * on any selection in the targeted field,
 * including when "parent decides" is selected.
 *
 * This lets the PageProvider act on records
 * that define a specific action to use and the
 * SubPageProvider act on all other page records.
 */
class SubPageProvider extends PageProvider implements ProviderInterface {

	/**
	 * @var string
	 */
	protected $fieldName = self::FIELD_NAME_SUB;

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		if (TRUE === empty($row[self::FIELD_ACTION_SUB])) {
			$row = $this->pageService->getPageTemplateConfiguration($row['uid']);
		}
		return $row[self::FIELD_ACTION_SUB];
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getFlexFormValuesSingle(array $row) {
		$fieldName = $this->getFieldName($row);
		$form = $this->getForm($row);
		$immediateConfiguration = $this->configurationService->convertFlexFormContentToArray($row[$fieldName], $form, NULL, NULL);
		return $immediateConfiguration;
	}

}
