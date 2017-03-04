<?php
namespace FluidTYPO3\Fluidpages\Hooks;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\View\PreviewView;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PagePreviewRenderer
 */
class PagePreviewRenderer
{
    /**
     * @param array $params
     * @param PageLayoutController $pageLayoutController
     * @return string
     */
    public function render(array $params, PageLayoutController $pageLayoutController)
    {
        $pageProvider = $this->getPageProvider();
        $previewContent = '';

        $row = $this->getRecordService()->getSingle('pages', '*', $pageLayoutController->id);
        $form = $pageProvider->getForm($row);

        if ($form) {
            // Force the preview to *not* generate content column HTML in preview
            $form->setOption(PreviewView::OPTION_PREVIEW, [
                PreviewView::OPTION_MODE => PreviewView::MODE_NONE
            ]);

            list(, $previewContent, ) = $pageProvider->getPreview($row);
        }

        return $previewContent;
    }

    /**
     * @return WorkspacesAwareRecordService
     * @codeCoverageIgnore
     */
    protected function getRecordService()
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(WorkspacesAwareRecordService::class);
    }

    /**
     * @return PageProvider
     * @codeCoverageIgnore
     */
    protected function getPageProvider()
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(PageProvider::class);
    }
}
