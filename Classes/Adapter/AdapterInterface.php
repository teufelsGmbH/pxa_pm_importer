<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

/**
 * Interface AdapterInterface
 * @package Pixelant\PxaPmImporter\Adapter
 */
interface AdapterInterface
{
    /**
     * Yaml field name
     */
    const SETTINGS_FIELD = '__adapterSettings';

    /**
     * Adapt raw data from source
     *
     * @param array $data Raw data from source
     * @param array $configuration Adapter configuration
     * @return array
     */
    public function adapt(array $data, array $configuration): void;

    /**
     * Get default data to import after adapt
     * Could be data language layer if language field is forced to set, but in this case
     * record won't have parent
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Language layer data
     *
     * @param int $languageUid
     * @return array
     */
    public function getLocalizationData(int $languageUid): array;
}
