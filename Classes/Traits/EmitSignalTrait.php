<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Traits;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Trait EmitSignalTrait
 * @package Pixelant\PxaPmImporter\Traits
 */
trait EmitSignalTrait
{
    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher = null;

    /**
     * Emit signal
     *
     * @param string $className
     * @param string $signalName
     * @param array $signalArguments
     */
    protected function emitSignal(string $className, string $signalName, array $signalArguments): void
    {
        $this->getSignalSlotDispatcher()->dispatch(
            $className,
            $signalName,
            $signalArguments
        );
    }

    /**
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher(): Dispatcher
    {
        if ($this->signalSlotDispatcher === null) {
            $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        }

        return $this->signalSlotDispatcher;
    }
}
