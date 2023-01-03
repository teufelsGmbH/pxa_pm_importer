<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Page;

/**
 * Class SingleviewPageProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class SingleviewPageProcessor extends AbstractRelationFieldProcessor
{
    /**
     * @inheritDoc
     */
    protected function domainModel(): string
    {
        return Page::class;
    }
}
