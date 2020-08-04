<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Test\Integration\Model\ResourceModel;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendSourceReservations;
use ReachDigital\ISReservations\Model\MetaData\EncodeMetaData;
use ReachDigital\ISReservations\Model\ResourceModel\GetReservationsByMetadataList;
use ReachDigital\ISReservations\Model\SourceReservationBuilder;
use ReachDigital\ISReservations\Model\ResourceModel\CleanupSourceReservations;
use ReachDigital\ISReservations\Model\ResourceModel\GetSourceReservationsQuantity;

class GetReservationsByMetadataListTest extends TestCase
{
    /** @var AppendSourceReservations */
    private $appendReservations;

    /** @var SourceReservationBuilder */
    private $reservationBuilder;
    /**
     * @var EncodeMetaData
     */
    private $encodeMetaData;

    /**
     * @var GetReservationsByMetadataList
     */
    private $getReservationsByMetadataList;

    public function setUp()
    {
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(SourceReservationBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendSourceReservations::class);
        $this->encodeMetaData = Bootstrap::getObjectManager()->get(EncodeMetaData::class);
        $this->getReservationsByMetadataList = Bootstrap::getObjectManager()->get(GetReservationsByMetadataList::class);
    }

    /**
     * @test
     * @covers \ReachDigital\ISReservations\Model\ResourceModel\GetSourceReservationsQuantity
     *
     * @magentoDbIsolation disabled
     * @magentoDataFixture ../../../../vendor/reach-digital/magento2-inventory-source-reservations/ISReservations/Test/Integration/_files/clean_all_reservations.php
     */
    public function should_add_the_reservation_to_the_stock_amount()
    {
        $orderItem1 = $this->encodeMetaData->execute(['order' => 10, 'order_item' => 12]);
        $this->appendReservation('source', 'sku1', 10, $orderItem1);

        $orderItem2 = $this->encodeMetaData->execute(['order' => 10, 'order_item' => 13]);
        $this->appendReservation('source', 'sku1', 10, $orderItem2);

        $orderItem3 = $this->encodeMetaData->execute(['order' => 11, 'order_item' => 14]);
        $this->appendReservation('source', 'sku1', 10, $orderItem3);

        $orderItem4 = $this->encodeMetaData->execute(['order' => 11, 'order_item' => 15]);
        $this->appendReservation('source', 'sku1', 10, $orderItem4);

        $reservations = $this->getReservationsByMetadataList->execute([
            $orderItem1,
            $orderItem2,
            $orderItem3,
            $orderItem4,
        ]);

        self::assertCount(4, $reservations);
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    private function appendReservation(string $sourceCode, string $sku, int $quantity, string $metaData): void
    {
        $this->reservationBuilder->setSourceCode($sourceCode);
        $this->reservationBuilder->setQuantity($quantity);
        $this->reservationBuilder->setSku($sku);
        $this->reservationBuilder->setMetadata($metaData);
        $reservation = $this->reservationBuilder->build();
        $this->appendReservations->execute([$reservation]);
    }
}
