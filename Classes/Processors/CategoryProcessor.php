<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CategoryProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class CategoryProcessor extends AbstractFieldProcessor
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository = null;

    /**
     * @var Category[]
     */
    protected $categories = [];

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->categoryRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(CategoryRepository::class);
    }

    /**
     * Check if category exist
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        $this->categories = []; // Reset categories
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $categoryIdentifier) {
            $record = $this->getCategoryRecord($categoryIdentifier); // Default language record
            if ($record !== null) {
                $category = MainUtility::convertRecordArrayToModel($record, Category::class);
                $this->categories[] = $category;
            } else {
                // @codingStandardsIgnoreStart
                throw new PostponeProcessorException('Category with id "' . $categoryIdentifier . '" not found.', 1536148407513);
                // @codingStandardsIgnoreEnd
            }
        }
    }

    /**
     * @param $value
     */
    public function process($value): void
    {
        $this->updateRelationProperty($this->categories);
    }

    /**
     * Fetch category record
     *
     * @param string $identifier
     * @return array|null
     */
    protected function getCategoryRecord(string $identifier): ?array
    {
        $hash = MainUtility::getImportIdHash($identifier);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                    $queryBuilder->createNamedParameter($hash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->importer->getPid(), Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        return is_array($row) ? $row : null;
    }
}
