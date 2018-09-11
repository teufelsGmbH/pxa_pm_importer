<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Adapter;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Adapter\DefaultDataAdapter;

/**
 * Class DefaultDataAdapterTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Adapter
 */
class DefaultDataAdapterTest extends UnitTestCase
{
    /**
     * @var DefaultDataAdapter|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            DefaultDataAdapter::class,
            ['dummy']
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function adaptSourceDataWillSetDataAccordingToMapping()
    {
        $configuration = [
            'mapping' => [
                'id' => 4,
                'languages' => [
                    0 => [
                        'title' => 0,
                        'description' => 1
                    ],
                    1 => [
                        'title' => 2,
                        'description' => 3
                    ],
                ]
            ]
        ];

        $data = [
            [
                0 => 'English title 1',
                1 => 'English desc 1',
                2 => 'Ukrainian title 1',
                3 => 'Ukrainian desc 1',
                4 => 'id1'
            ],
            [
                0 => 'English title 2',
                1 => 'English desc 2',
                2 => 'Ukrainian title 2',
                3 => 'Ukrainian desc 2',
                4 => 'id2'
            ],
        ];

        $expect = [
            0 => [
                [
                    'title' => 'English title 1',
                    'description' => 'English desc 1',
                    'id' => 'id1'
                ],
                [
                    'title' => 'English title 2',
                    'description' => 'English desc 2',
                    'id' => 'id2'
                ]
            ],
            1 => [
                [
                    'title' => 'Ukrainian title 1',
                    'description' => 'Ukrainian desc 1',
                    'id' => 'id1'
                ],
                [
                    'title' => 'Ukrainian title 2',
                    'description' => 'Ukrainian desc 2',
                    'id' => 'id2'
                ]
            ],
        ];

        $this->subject->_call('initialize', $configuration);
        $this->assertEquals($expect, $this->subject->_call('adaptSourceData', $data));
    }
}
