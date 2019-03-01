<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ValidatorFactory
 * @package Pixelant\PxaPmImporter\Domain\Validation\Validator
 */
final class ValidatorFactory
{
    /**
     * Validator factory
     *
     * @param string $validatorName
     * @return ProcessorFieldValueValidatorInterface
     */
    public static function factory(string $validatorName): ProcessorFieldValueValidatorInterface
    {
        if (class_exists($validatorName)) {
            $className = $validatorName;
        } else {
            $className = sprintf(
                'Pixelant\\PxaPmImporter\\Domain\\Validation\\Validator\\%sValidator',
                ucfirst($validatorName)
            );
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Validator '{$className}' doesn't exist.", 1550064722323);
        }

        $validatorInstance = GeneralUtility::makeInstance($className);
        if (!($validatorInstance instanceof ProcessorFieldValueValidatorInterface)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Validator "%s", should be instance of ProcessorFieldValueValidatorInterface',
                    $className
                ),
                1550064848419
            );
        }

        return $validatorInstance;
    }
}
