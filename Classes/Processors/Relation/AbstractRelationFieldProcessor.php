<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Handle relation like 1:1, 1:n, n:m
 * Work with object storage
 *
 * @package Pixelant\PxaPmImporter\Processors\Relation
 */
abstract class AbstractRelationFieldProcessor extends AbstractFieldProcessor
{
    use UpdateRelationProperty;

    /**
     * @var AbstractEntity[]
     */
    protected $entities = [];

    /**
     * Flag if init entities failed and validation result should be false
     *
     * @var bool
     */
    protected $failedInit = false;

    /**
     * Call init entities method
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        parent::preProcess($value);

        $this->entities = $this->initEntities($value);

        /** @var AbstractEntity $entity */
        foreach ($this->entities as $entity) {
            if (!is_object($entity) || !($entity instanceof AbstractEntity)) {
                throw new \UnexpectedValueException(
                    'All entities should be instance of AbstractEntity',
                    1547129113393
                );
            }
        }
    }

    /**
     * Validation
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->failedInit) {
            return false;
        }

        return parent::isValid($value);
    }

    /**
     * Process update
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->updateRelationProperty($this->entity, $this->property, $this->entities);
    }

    /**
     * Tears down
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->entities = [];
    }

    /**
     * This method should prepare entities for later call in process
     *
     * @param $value
     * @return AbstractEntity[] Entities
     */
    abstract protected function initEntities($value): array;
}
