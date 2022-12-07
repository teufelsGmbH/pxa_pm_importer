<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Doctrine\DBAL\DBALException;
use Pixelant\PxaPmImporter\Processors\Relation\Updater\RelationPropertyUpdater;
use Pixelant\PxaProductManager\Domain\Model\ProductType;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use Pixelant\PxaProductManager\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\AbstractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Class ProductTypeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductTypeProcessor extends AbstractRelationFieldProcessor implements AbleCreateMissingEntities
{

    /**
     * @var RelationPropertyUpdater
     */
    protected $propertyUpdater = null;

    /**
     * @inheritDoc
     */
    public function process($value): void
    {
        $this->propertyUpdater->update($this->entity, $this->property, $this->initEntities($value));
    }

    /**
     * @inheritDoc
     */
    public function createMissingEntity(string $importId)
    {
        $fields = ['name' => $importId, $this->tcaHiddenField() => 1];
        $sysLanguageUid = 0;

        $this->repository->createEmpty(
            $importId,
            'tx_pxaproductmanager_domain_model_producttype',
            $sysLanguageUid,
            $this->newRecordFieldsWithPlaceHolder($fields)
        );
    }

    /**
     * @inheritDoc
     */
    protected function domainModel(): string
    {
        return ProductType::class;
    }
}
