<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Cache;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Flush cache actions
 */
class CacheService
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Flush by tags
     *
     * @param array $tags
     */
    public function flushByTags(array $tags): void
    {
        $tags = $this->tagsToCacheManagerTags($tags);
        if (!empty($tags)) {
            $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
        }
    }

    /**
     * Go thought all tags and check if it's a page or tag
     *
     * @param array $tags
     * @return array
     */
    protected function tagsToCacheManagerTags(array $tags): array
    {
        return array_map(function ($tag) {
            return MathUtility::canBeInterpretedAsInteger($tag)
                ? sprintf('pageId_%d', $tag)
                : $tag;
        }, $tags);
    }
}
