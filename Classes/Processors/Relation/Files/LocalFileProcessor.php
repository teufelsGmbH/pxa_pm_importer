<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation\Files;

use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;
use Pixelant\PxaPmImporter\Processors\Traits\FilesResources;
use Pixelant\PxaPmImporter\Processors\Traits\ImportListValue;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class LocalFileProcessor
 * @package Pixelant\PxaPmImporter\Processors\File
 */
class LocalFileProcessor extends AbstractRelationFieldProcessor
{
    use FilesResources, ImportListValue;

    /**
     * Get files from value list and convert to extbase domain file reference
     *
     * @param string|array $value
     * @return array
     */
    protected function initEntities($value): array
    {
        $entities = [];
        $value = $this->convertListToArray($value);

        try {
            $folder = $this->getFolder();
        } catch (FolderDoesNotExistException $exception) {
            $this->logger->error($exception->getMessage());

            return [];
        }

        /**
         * Find all existing file references attached to product
         * in order to reuse it if it was already imported
         */
        $existingFiles = [];

        $propertyValue = ObjectAccess::getProperty($this->entity, $this->property);
        if ($propertyValue instanceof LazyLoadingProxy) {
            $propertyValue = $propertyValue->_loadRealInstance();
        }
        // If multiple files
        if ($propertyValue instanceof ObjectStorage) {
            /** @var FileReference $file */
            foreach ($propertyValue as $file) {
                $existingFiles[$this->getEntityUidForCompare($file)] = $file;
            }
        } elseif ($propertyValue !== null) {
            // If one file and not set yet
            $existingFiles[$this->getEntityUidForCompare($propertyValue)] = $propertyValue;
        }

        foreach ($this->collectFilesFromList($folder, $value, $this->logger) as $file) {
            // Create new file reference
            if (!array_key_exists($file->getUid(), $existingFiles)) {
                $entities[] = $this->createFileReference(
                    $file,
                    $this->entity->getUid(),
                    $this->context->getNewRecordsPid(),
                    $this->entity->_getProperty('_languageUid')
                );
            } else {
                // Use existing file reference
                $entities[] = $existingFiles[$file->getUid()];
            }
        }

        return $entities;
    }
}
