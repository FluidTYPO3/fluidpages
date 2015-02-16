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
 */
class SubPageProvider extends PageProvider implements ProviderInterface {

	/**
	 * @var string
	 */
	protected $fieldName = 'tx_fed_page_flexform_sub';

	/**
	 * Gets an inheritance tree (ordered parent -> ... -> this record)
	 * of record arrays containing raw values.
	 *
	 * @param array $row
	 * @return array
	 */
	public function getInheritanceTree(array $row) {
		return array();
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		$configuration = $this->pageService->getPageTemplateConfiguration($row['uid']);
		return $configuration[self::FIELD_ACTION_SUB];
	}

}
