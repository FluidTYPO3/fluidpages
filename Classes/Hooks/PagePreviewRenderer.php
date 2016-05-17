<?php
namespace FluidTYPO3\Fluidpages\Hooks;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\PageProvider;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\View\PreviewView;

class PagePreviewRenderer {

    /**
     * @param array $params
     * @param PageLayoutController $pageLayoutController
     * @return string
     */
    public function render(array $params, PageLayoutController $pageLayoutController) {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var WorkspacesAwareRecordService $recordService */
        $recordService = $objectManager->get(WorkspacesAwareRecordService::class);

        /** @var PageProvider $pageProvider */
        $pageProvider = $objectManager->get(PageProvider::class);
		$previewContent = '';

		if ($pageProvider) {
			$row = $recordService->getSingle('pages', '*', $pageLayoutController->id);
			$form = $pageProvider->getForm($row);

			if ($form) {

				// Force the preview to *not* generate content column HTML in preview
				$form->setOption(PreviewView::OPTION_PREVIEW, array(
					PreviewView::OPTION_MODE => PreviewView::MODE_NONE
				));

				list($previewHeader, $previewContent, $continueDrawing) = $pageProvider->getPreview($row);
			}

		}

        return $previewContent;
    }

}
