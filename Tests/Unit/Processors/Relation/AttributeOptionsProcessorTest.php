<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Processors\Relation\AttributeOptionsProcessor;

class AttributeOptionsProcessorTest extends UnitTestCase
{
    /**
     * @var AttributeOptionsProcessor|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            AttributeOptionsProcessor::class,
            ['dummy']
        );
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
    public function isValidReturnFalseIfOptionsFailed()
    {
        $this->subject->_set('failedCreateOptions', true);

        $this->assertFalse($this->subject->isValid(''));
    }
}
