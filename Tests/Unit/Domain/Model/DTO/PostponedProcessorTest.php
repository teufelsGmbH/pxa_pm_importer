<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Model\DTO;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Model\DTO\PostponedProcessor;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;

/**
 * Class PostponedProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Model\DTO
 */
class PostponedProcessorTest extends UnitTestCase
{
    /**
     * @var PostponedProcessor
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function postponedProcessorCallProcessorTearDownOnInit()
    {
        $processor = $this->createMock(FieldProcessorInterface::class);
        $value = 'test';

        $processor
            ->expects($this->once())
            ->method('tearDown');

        new PostponedProcessor($processor, $value);
    }

    /**
     * @test
     */
    public function postponedProcessorReturnValueThatWasSet()
    {
        $processor = $this->createMock(FieldProcessorInterface::class);
        $value = 'test';

        $postponedProcessor = new PostponedProcessor($processor, $value);

        $this->assertEquals($value, $postponedProcessor->getValue());
    }

    /**
     * @test
     */
    public function postponedProcessorReturnProcessorThatWasSet()
    {
        $processor = $this->createMock(FieldProcessorInterface::class);
        $value = 'test';

        $postponedProcessor = new PostponedProcessor($processor, $value);

        $this->assertSame($postponedProcessor->getProcessor(), $processor);
    }
}
