<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Repository;

use Pixelant\PxaPmImporter\Context\ImportContext;

/**
 * @package Pixelant\PxaPmImporter\Domain\Repository
 */
class AbstractImportRepository
{
    /**
     * @var ImportContext
     */
    protected $context = null;

    /**
     * @param ImportContext $context
     */
    public function __construct(ImportContext $context)
    {
        $this->context = $context;
    }
}
