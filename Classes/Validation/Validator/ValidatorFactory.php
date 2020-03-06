<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Validation\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ValidatorFactory
 * @package Pixelant\PxaPmImporter\Domain\Validation\Validator
 */
class ValidatorFactory
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Validator factory
     *
     * @param string $validatorName
     * @return ValidatorInterface
     */
    public function create(string $validatorName): ValidatorInterface
    {
        if (class_exists($validatorName)) {
            $className = $validatorName;
        } else {
            $className = sprintf(
                'Pixelant\\PxaPmImporter\\Validation\\Validator\\%sValidator',
                ucfirst($validatorName)
            );
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Validator '{$className}' doesn't exist.", 1550064722323);
        }

        $validator = $this->objectManager->get($className);
        if (!($validator instanceof ValidatorInterface)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Validator "%s", should be instance of ProcessorFieldValueValidatorInterface',
                    $className
                ),
                1550064848419
            );
        }

        return $validator;
    }
}
