<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProductAttributeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductAttributeProcessor extends AbstractFieldProcessor
{
    /**
     * @var Attribute
     */
    protected $attribute = null;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository = null;

    /**
     * @var Product
     */
    protected $entity = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->attributeRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(AttributeRepository::class);
    }

    /**
     * Prepare for process
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (empty($this->configuration['attributeUid'])) {
            // @codingStandardsIgnoreStart
            throw new InvalidProcessorConfigurationException('Missing "attributeUid" of processor configuration. Name - "' . $this->property . '"', 1536325707731);
            // @codingStandardsIgnoreEnd
        }

        if (isset($this->configuration['treatAttributeUidAsImportUid'])
            && (bool)$this->configuration['treatAttributeUidAsImportUid'] === true
        ) {
            $record = $this->getRecordByImportIdentifier(
                $this->configuration['attributeUid'],
                'tx_pxaproductmanager_domain_model_attribute'
            );

            if ($record !== null) {
                $this->attribute = MainUtility::convertRecordArrayToModel($record, Attribute::class);
            }
        } else {
            $this->attribute = $this->attributeRepository->findByUid((int)$this->configuration['attributeUid']);
        }

        if ($this->attribute === null) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Could not find attribute with UID "' . $this->configuration['attributeUid'] . '"', 1536325896431);
            // @codingStandardsIgnoreEnd
        }

        parent::preProcess($value);
    }

    /**
     * Additional validation
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->attribute->getType() === Attribute::ATTRIBUTE_TYPE_DATETIME && !empty($value)) {
            try {
                $date = $this->parseDateTime($value);
            } catch (\Exception $exception) {
                $date = null;
            }

            if ($date === null) {
                $this->addError('Could not parse date from "' . $value . '" for "' . $this->property . '"');
                return false;
            }
        }

        return parent::isValid($value);
    }

    /**
     * Process attribute value
     *
     * @param $value
     */
    public function process($value): void
    {
        $attributeValues = unserialize($this->entity->getSerializedAttributesValues()) ?: [];
        $currentValue = $attributeValues[$this->attribute->getUid()] ?? '';
        if ($currentValue === $value) {
            return;
        }

        switch ($this->attribute->getType()) {
            case Attribute::ATTRIBUTE_TYPE_DROPDOWN:
            case Attribute::ATTRIBUTE_TYPE_MULTISELECT:
                $options = $this->getOptions((string)$value);
                $attributeValues[$this->attribute->getUid()] = implode(',', $options);
                break;
            case Attribute::ATTRIBUTE_TYPE_CHECKBOX:
                $attributeValues[$this->attribute->getUid()] = ((bool)$value) ? 1 : 0;
                break;
            case Attribute::ATTRIBUTE_TYPE_INPUT:
            case Attribute::ATTRIBUTE_TYPE_LABEL:
            case Attribute::ATTRIBUTE_TYPE_TEXT:
            case Attribute::ATTRIBUTE_TYPE_LINK:
                $attributeValues[$this->attribute->getUid()] = (string)$value;
                break;
            case Attribute::ATTRIBUTE_TYPE_DATETIME:
                $value = empty($value)
                    ? ''
                    : $this->parseDateTime($value);
                $attributeValues[$this->attribute->getUid()] = $value->format('Y-m-d\TH:i:s\Z');
                break;
            default:
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Attribute import with type "' . $this->attribute->getType() . '" is not supported', 1536566015842);
            // @codingStandardsIgnoreEnd
        }

        $this->entity->setSerializedAttributesValues(serialize($attributeValues));
        $this->updateAttributeValue($attributeValues[$this->attribute->getUid()]);
    }

    /**
     * Update attribute value record
     *
     * @param $value
     */
    protected function updateAttributeValue($value): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            'tx_pxaproductmanager_domain_model_attributevalue'
        );

        $attributeValueRow = $queryBuilder
            ->select('uid')
            ->from('tx_pxaproductmanager_domain_model_attributevalue')
            ->where(
                $queryBuilder->expr()->eq(
                    'product',
                    $queryBuilder->createNamedParameter(
                        $this->entity->getUid(),
                        Connection::PARAM_INT
                    )
                ),
                $queryBuilder->expr()->eq(
                    'attribute',
                    $queryBuilder->createNamedParameter(
                        $this->attribute->getUid(),
                        Connection::PARAM_INT
                    )
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(
                        $this->dbRow['sys_language_uid'],
                        Connection::PARAM_INT
                    )
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (is_array($attributeValueRow)) {
            // Update value
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                'tx_pxaproductmanager_domain_model_attributevalue'
            );

            $queryBuilder
                ->update('tx_pxaproductmanager_domain_model_attributevalue')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($attributeValueRow['uid'])
                    )
                )
                ->set('value', $value)
                ->execute();
        } else {
            $time = time();
            // Create attribute value record
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_pxaproductmanager_domain_model_attributevalue')
                ->insert(
                    'tx_pxaproductmanager_domain_model_attributevalue',
                    [
                        'attribute' => $this->attribute->getUid(),
                        'product' => $this->entity->getUid(),
                        'tstamp' => $time,
                        'crdate' => $time,
                        'pid' => $this->importer->getPid(),
                        't3_origuid' => 0,
                        'l10n_parent' => 0,
                        'sys_language_uid' => intval($this->dbRow['sys_language_uid']),
                        'value' => $value,
                    ]
                );
        }
    }

    /**
     * Parse datetime from value
     *
     * @param string $value
     * @return \DateTime|null
     */
    protected function parseDateTime(string $value)
    {
        if (!empty($this->configuration['dateFormat'])) {
            $date = \DateTime::createFromFormat($this->configuration['dateFormat'], $value);
        } else {
            $date = new \DateTime($value);
        }

        return $date;
    }

    /**
     * Fetch options uids
     *
     * @param string $value
     * @return array
     */
    protected function getOptions(string $value): array
    {
        $values = GeneralUtility::trimExplode(',', $value, true);
        unset($value);
        $hashes = [];
        foreach ($values as $value) {
            $hashes[] = MainUtility::getImportIdHash($value);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_pxaproductmanager_domain_model_option');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select('uid')
            ->from('tx_pxaproductmanager_domain_model_option')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->in(
                        'value',
                        $queryBuilder->createNamedParameter($values, Connection::PARAM_STR_ARRAY)
                    ),
                    $queryBuilder->expr()->in(
                        ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                        $queryBuilder->createNamedParameter($hashes, Connection::PARAM_STR_ARRAY)
                    )
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->importer->getPid(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'attribute',
                    $queryBuilder->createNamedParameter($this->attribute->getUid(), Connection::PARAM_INT)
                )
            )
            ->execute();
        $uids = [];
        while ($row = $statement->fetch()) {
            $uids[] = $row['uid'];
        }

        return $uids;
    }
}
