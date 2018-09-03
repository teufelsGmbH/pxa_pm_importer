<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Command\ImportCommandController;
use Pixelant\PxaPmImporter\Domain\Model\Category;
use Pixelant\PxaPmImporter\Domain\Repository\CategoryRepository;
use Pixelant\PxaPmImporter\Utility\MainUtility;

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
     * @var Category
     */
    protected $category = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
        $this->categoryRepository = MainUtility::getObjectManager()->get(CategoryRepository::class);
    }

    /**
     * Check if category exist and is valid
     *
     * @param $value
     * @param ImportCommandController $pObj
     * @param array $fieldConfiguration
     * @return bool
     */
    public function isValid($value, ImportCommandController $pObj, array $fieldConfiguration = []): bool
    {
        $isRequired = $this->isRequired($fieldConfiguration);

        if ($isRequired && empty($value)) {
            return false;
        }

        $category = $this->categoryRepository->findByPimUid($value);

        if ($category !== null) {
            $this->setCategory($category);

            return true;
        }

        $this->logger->error('Category with ID "' . $value . '" was not found.');

        return false;
    }

    /**
     * Assign categories
     *
     * @param array $data
     * @param array $product
     * @param string $field
     * @param $value
     * @param ImportCommandController $pObj
     */
    public function process(array &$data, array &$product, string $field, $value, ImportCommandController $pObj): void
    {
        if ($product[$field] !== $value) {
            $product['categories'] = $this->category->getUid();
            $product[$field] = $value;

            $this->markProductAsChanged($product, $field, 'categories');
        }
    }

    /**
     * @param Category $category
     */
    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }
}
