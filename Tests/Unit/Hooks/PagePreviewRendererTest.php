<?php
namespace FluidTYPO3\Fluidpages\Tests\Unit\Hooks;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Controller\PageController;
use FluidTYPO3\Fluidpages\Hooks\PagePreviewRenderer;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Fluidpages\Tests\Fixtures\Controller\DummyPageController;
use FluidTYPO3\Fluidpages\Tests\Unit\AbstractTestCase;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Provider\Provider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\View\ExposedTemplateView;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PageControllerTest
 */
class PagePreviewRendererTest extends AbstractTestCase
{

    /**
     * @param ProviderInterface $provider
     * @param string $expected
     * @test
     * @dataProvider getRenderTestValues
     */
    public function testRender(ProviderInterface $provider, $expected)
    {
        $recordService = $this->getMockBuilder(WorkspacesAwareRecordService::class)->setMethods(['getSingle'])->getMock();
        $recordService->expects($this->once())->method('getSingle')->with('pages', '*', 123)->willReturn([]);
        $subject = $this->getMockBuilder(PagePreviewRenderer::class)->setMethods(['getPageProvider', 'getRecordService'])->getMock();
        $subject->expects($this->once())->method('getPageProvider')->willReturn($provider);
        $subject->expects($this->once())->method('getRecordService')->willReturn($recordService);
        $pageLayoutController = $this->getMockBuilder(PageLayoutController::class)->getMock();
        $pageLayoutController->id = 123;
        $result = $subject->render([], $pageLayoutController);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getRenderTestValues()
    {
        $withForm = new Provider();
        $withForm->setForm(Form::create());
        $withDisabledForm = new Provider();
        $withDisabledForm->setForm(Form::create(['enabled' => false]));
        $withPreview = $this->getMockBuilder(Provider::class)->setMethods(['getForm', 'getPreview'])->getMock();
        $withPreview->expects($this->once())->method('getPreview')->willReturn([null, 'preview', true]);
        $withPreview->expects($this->once())->method('getForm')->willReturn(Form::create());

        return [
            [$withForm, ''],
            [$withDisabledForm, ''],
            [$withPreview, 'preview'],
        ];
    }

}
