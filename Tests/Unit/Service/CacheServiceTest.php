<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Service;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Service\Cache\CacheService;

/**
 * @package Pixelant\PxaPmImporter\Tests\Unit\Service
 */
class CacheServiceTest extends UnitTestCase
{
    /**
     * @var CacheService
     */
    protected $subject = null;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->subject = $this->getAccessibleMock(CacheService::class, null, [], '', false);
    }

    /**
     * @test
     */
    public function tagsToCacheManagerTagsConvertPageUidsToTags()
    {
        $tags = ['simpleTag', 12, '15'];
        $expect = ['simpleTag', 'pageId_12', 'pageId_15'];

        $this->assertEquals($expect, $this->subject->_call('tagsToCacheManagerTags', $tags));
    }
}
