<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

/**
 * Class DefaultDataAdapter
 * @package Pixelant\PxaPmImporter\Adapter
 */
class DefaultDataAdapter extends AbstractDefaultAdapter
{
    /**
     * Just return
     *
     * @param array $data
     * @return array
     */
    protected function transformSourceData(array $data): array
    {
        return $data;
    }
}
