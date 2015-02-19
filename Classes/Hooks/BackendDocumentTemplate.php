<?php
/**
 * User: robsonrobi
 * Date: 04.02.15
 * Time: 16:56
 */

namespace FluidTYPO3\Fluidpages\Hooks;


class BackendDocumentTemplate {

    /**
     * Hook after rendering a page in the backend to remove the section "Fluid Content Area" produced by the colpos 18181
     *
     * @param array $params
     * @param  \TYPO3\CMS\Backend\Template\DocumentTemplate $template
     * @return void
     */
    public function removeFluidContentAreaSection(array $params, \TYPO3\CMS\Backend\Template\DocumentTemplate &$template) {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
        $pageRenderer = $template->getPageRenderer();

        $jsCode = "Ext.onReady(function() {
             function contains(selector, text) {
                 var elements = document.querySelectorAll(selector);
                 return [].filter.call(elements, function(element){
                    return RegExp(text).test(element.textContent);
                 });
             }
             var childs = contains('div', /^Fluid\ Content\ Area$/);
             childs.forEach(function(element, index, array){
                var remove = element.parentNode.parentNode;
                var removeParent = remove.parentNode;
                removeParent.removeChild(remove);
             });
         });";

        $pageRenderer->addHeaderData('<script type="text/javascript">' . $jsCode . '</script>');
    }

} 