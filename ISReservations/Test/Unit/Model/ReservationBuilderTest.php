<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Test\Unit\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use ReachDigital\ISReservations\Model\SourceReservationBuilder;
use ReachDigital\ISReservations\Model\SnakeToCamelCaseConverter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;
use PHPUnit\Framework\TestCase;

class ReservationBuilderTest extends TestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    /**
     * @var SourceReservationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reservation;

    /**
     * @var ValidationResult|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validationResult;

    /**
     * @var SnakeToCamelCaseConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $snakeToCamelCaseConverter;

    /** @var  ValidationResultFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $validationResultFactory;

    /**
     * @var SourceReservationBuilder
     */
    private $reservationBuilder;

    protected function setUp()
    {
        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->snakeToCamelCaseConverter = $this->getMockBuilder(SnakeToCamelCaseConverter::class)->getMock();
        $this->reservation = $this->getMockBuilder(SourceReservationInterface::class)->getMock();
        $this->validationResult = $this->getMockBuilder(ValidationResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validationResultFactory = $this->getMockBuilder(ValidationResultFactory::class)
            ->setConstructorArgs([
                'objectManager' => $this->createMock(\Magento\Framework\ObjectManagerInterface::class),
            ])->getMock();

        $this->reservationBuilder = (new ObjectManager($this))->getObject(
            SourceReservationBuilder::class,
            [
                'objectManager' => $this->objectManager,
                'snakeToCamelCaseConverter' => $this->snakeToCamelCaseConverter,
                'validationResultFactory' => $this->validationResultFactory,
            ]
        );
    }

    public function testBuild()
    {
        $reservationData = [
            SourceReservationInterface::RESERVATION_ID => null,
            SourceReservationInterface::SOURCE_CODE => '1',
            SourceReservationInterface::SKU => 'somesku',
            SourceReservationInterface::QUANTITY => 11,
            SourceReservationInterface::METADATA => 'some meta data',
        ];

        $reservationMappedData = [
            'reservationId' => null,
            'sourceCode' => 1,
            'sku' => 'somesku',
            'quantity' => 11,
            'metadata' => 'some meta data',
        ];

        $this->snakeToCamelCaseConverter
            ->expects($this->once())
            ->method('convert')
            ->with(array_keys($reservationData))
            ->willReturn(array_keys($reservationMappedData));

        $this->objectManager
            ->expects($this->once())
            ->method('create')
            ->with(SourceReservationInterface::class, $reservationMappedData)
            ->willReturn($this->reservation);

        $this->validationResultFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->validationResult);

        $this->validationResult
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->reservationBuilder->setSourceCode($reservationData[SourceReservationInterface::SOURCE_CODE]);
        $this->reservationBuilder->setSku($reservationData[SourceReservationInterface::SKU]);
        $this->reservationBuilder->setQuantity($reservationData[SourceReservationInterface::QUANTITY]);
        $this->reservationBuilder->setMetadata($reservationData[SourceReservationInterface::METADATA]);

        self::assertEquals($this->reservation, $this->reservationBuilder->build());
    }

    /**
     * @param array $firstSetter
     * @param array $secondSetter
     * @dataProvider getSettersAndValues
     * @expectedException \Magento\Framework\Validation\ValidationException
     * @expectedExceptionMessage  Validation error
     */
    public function testThrowValidationException(array $firstSetter, array $secondSetter)
    {
        $this->validationResultFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->validationResult);

        $this->validationResult
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $method = $firstSetter['method'];
        $argument = $firstSetter['argument'];
        $this->reservationBuilder->$method($argument);

        $method = $secondSetter['method'];
        $argument = $secondSetter['argument'];
        $this->reservationBuilder->$method($argument);

        $this->reservationBuilder->build();
    }

    /**
     * @return array
     */
    public function getSettersAndValues(): array
    {
        return [
            'with_missing_source_code' => [
                ['method' => 'setSku', 'argument' => 'somesku'],
                ['method' => 'setQuantity', 'argument' => 11]
            ],
            'with_missing_sku' => [
                ['method' => 'setSourceCode', 'argument' => '1'],
                ['method' => 'setQuantity', 'argument' => 11],
            ],
            'with_missing_qty' => [
                ['method' => 'setSourceCode', 'argument' => '1'],
                ['method' => 'setSku', 'argument' => 'somesku'],
            ],
        ];
    }
}
