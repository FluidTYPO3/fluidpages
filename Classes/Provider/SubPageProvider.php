<?php
namespace FluidTYPO3\Fluidpages\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Provider\ProviderInterface;

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
class SubPageProvider extends PageProvider implements ProviderInterface
{

    /**
     * @var string
     */
    protected $fieldName = self::FIELD_NAME_SUB;

    /**
     * @param array $row
     * @return string
     */
    public function getControllerActionReferenceFromRecord(array $row)
    {
        if (true === empty($row[self::FIELD_ACTION_SUB])) {
            $row = $this->pageService->getPageTemplateConfiguration($row['uid']);
        }
        return $row[self::FIELD_ACTION_SUB];
    }
}
