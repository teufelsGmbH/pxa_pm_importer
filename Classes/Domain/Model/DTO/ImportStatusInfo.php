<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Model\DTO;

use Pixelant\PxaPmImporter\Domain\Model\Import;

/**
 * Keep information about running import
 *
 * @package Pixelant\PxaPmImporter\Domain\Model\DTO
 */
class ImportStatusInfo
{
    /**
     * Determinate if info is available
     *
     * @var bool
     */
    protected $isAvailable = true;

    /**
     * @var Import
     */
    protected $import = null;

    /**
     * @var \DateTime
     */
    protected $startDate = null;

    /**
     * @var int
     */
    protected $progress = null;

    /**
     * Initialize
     *
     * @param Import $import
     * @param int $startDate
     * @param int $progress
     */
    public function __construct(Import $import, int $startDate = null, int $progress = null)
    {
        $this->import = $import;
        $this->startDate = new \DateTime();
        if ($this->startDate !== null) {
            $this->startDate->setTimestamp($startDate);
        }

        $this->progress = $progress ?? 0;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    /**
     * @param bool $isAvailable
     * @return ImportStatusInfo
     */
    public function setIsAvailable(bool $isAvailable): ImportStatusInfo
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'import' => $this->import->getUid(),
            'start' => $this->startDate->getTimestamp(),
            'progress' => $this->progress
        ];
    }
}
