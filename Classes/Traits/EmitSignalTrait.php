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
    private $signalSlotDispatcher = null;

    /**
     * Emit signal
     *
     * @param string $name
     * @param array &$variables
     */
    protected function emitSignal($name, array &$variables)
    {
        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            $name,
            $variables
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
