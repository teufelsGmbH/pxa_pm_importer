<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Exception;

/**
 * Class FailedInitEntityException
 * @package Pixelant\PxaPmImporter\Exception
 */
class FailedInitEntityException extends \Exception
{
    /**
     * Entity identifier
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }
}
