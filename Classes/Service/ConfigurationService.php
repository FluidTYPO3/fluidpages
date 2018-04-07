<?php
namespace FluidTYPO3\Fluidpages\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;

/**
 * Configuration Service
 *
 * Provides methods to read various configuration related
 * to Fluid Content Elements.
 */
class ConfigurationService extends FluxService implements SingletonInterface
{

    /**
     * @var WorkspacesAwareRecordService
     */
    protected $recordService;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @param WorkspacesAwareRecordService $recordService
     * @return void
     */
    public function injectRecordService(WorkspacesAwareRecordService $recordService)
    {
        $this->recordService = $recordService;
    }

    /**
     * @param ResourceFactory $resourceFactory
     * @return void
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param string $reference
     * @return string
     */
    public function convertFileReferenceToTemplatePathAndFilename($reference)
    {
        $filename = array_pop(explode(':', $reference));
        if (true === ctype_digit($filename)) {
            return $this->resourceFactory->getFileObjectFromCombinedIdentifier($reference);
        }
        $reference = GeneralUtility::getFileAbsFileName($reference);
        return $reference;
    }

    /**
     * @param string $reference
     * @return array
     */
    public function getViewConfigurationByFileReference($reference)
    {
        $extensionKey = 'fluidpages';
        if (0 === strpos($reference, 'EXT:')) {
            $extensionKey = substr($reference, 4, strpos($reference, '/') - 4);
        }
        return (new TemplatePaths(ExtensionNamingUtility::getExtensionKey($extensionKey)))->toArray();
    }

    /**
     * Get definitions of paths for Page Templates defined in TypoScript
     *
     * @param string $extensionName
     * @return array
     * @api
     */
    public function getPageConfiguration($extensionName = null)
    {
        if (null !== $extensionName && true === empty($extensionName)) {
            // Note: a NULL extensionName means "fetch ALL defined collections" whereas
            // an empty value that is not null indicates an incorrect caller. Instead
            // of returning ALL paths here, an empty array is the proper return value.
            // However, dispatch a debug message to inform integrators of the problem.
            GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->log(
                GeneralUtility::SYSLOG_SEVERITY_NOTICE,
                'Template paths have been attempted fetched using an empty value that is NOT NULL in ' .
                get_class($this) . '. This indicates a potential problem with your TypoScript configuration - a ' .
                'value which is expected to be an array may be defined as a string. This error is not fatal but may ' .
                'prevent the affected collection (which cannot be identified here) from showing up'
            );
            return [];
        }
        if (null !== $extensionName) {
            return (new TemplatePaths(ExtensionNamingUtility::getExtensionKey($extensionName)))->toArray();
        }
        $configurations = [];
        $registeredExtensionKeys = Core::getRegisteredProviderExtensionKeys('Page');
        foreach ($registeredExtensionKeys as $registeredExtensionKey) {
            $configurations[$registeredExtensionKey] = (new TemplatePaths(ExtensionNamingUtility::getExtensionKey($registeredExtensionKey)))->toArray();
        }
        return $configurations;
    }

    /**
     * Resolve fluidpages specific configuration provider. Always
     * returns the main PageProvider type which needs to be used
     * as primary PageProvider when processing a complete page
     * rather than just the "sub configuration" field value.
     *
     * @param array $row
     * @return ProviderInterface|NULL
     */
    public function resolvePageProvider($row)
    {
        $provider = $this->resolvePrimaryConfigurationProvider('pages', PageProvider::FIELD_NAME_MAIN, $row);
        return $provider;
    }
}
