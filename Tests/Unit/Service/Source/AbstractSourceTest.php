<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Service\Source;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Service\Source\AbstractSource;

/**
 * Class AbstractSourceTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Service\Source
 */
class AbstractSourceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function countSourceRequireSetDataToCall()
    {
        $source = $this->getAccessibleMock(AbstractSource::class, ['initialize', 'setData', 'next', 'rewind', 'valid', 'key']);

        $source
            ->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function () use ($source) {
                $source->_set('data', []);
            });

        $source->count();
    }

    /**
     * @test
     */
    public function countSourceWillCountDataCall()
    {
        $source = $this->getAccessibleMock(AbstractSource::class, ['initialize', 'setData', 'next', 'rewind', 'valid', 'key']);

        $source
            ->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function () use ($source) {
                $source->_set('data', [1, 2, 3, 4, 5, 6, 7]);
            });

        $this->assertEquals(7, $source->count());
    }
}
