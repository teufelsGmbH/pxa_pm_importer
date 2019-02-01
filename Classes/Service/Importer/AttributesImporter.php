<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;

/**
 * Doesn't support options
 *
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class AttributesImporter extends AbstractImporter
{
    /**
     * @var AttributeRepository
     */
    protected $repository = null;

    /**
     * Default fields for new record
     *
     * @var array
     */
    protected $defaultNewRecordFields = [

    ];

    /**
     * Set table name
     */
    protected function initDbTableName(): void
    {
        $this->dbTable = 'tx_pxaproductmanager_domain_model_attribute';
    }

    /**
     * Init repository
     */
    protected function initRepository(): void
    {
        $this->repository = $this->objectManager->get(AttributeRepository::class);
    }

    /**
     * Category mode name
     */
    protected function initModelName(): void
    {
        $this->modelName = Attribute::class;
    }
}
