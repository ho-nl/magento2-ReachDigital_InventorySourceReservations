<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Test\Integration\Model\ResourceModel;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\InventorySourceReservations\Model\AppendReservations;
use ReachDigital\InventorySourceReservations\Model\ReservationBuilder;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\CleanupReservations;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\GetReservationsQuantity;

class GetReservationsQuantityTest extends TestCase
{
    /** @var AppendReservations */
    private $appendReservations;

    /** @var CleanupReservations */
    private $cleanupReservations;

    /** @var GetReservationsQuantity */
    private $getReservationQuantity;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    public function setUp()
    {
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservations::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservations::class);
        $this->getReservationQuantity = Bootstrap::getObjectManager()->get(GetReservationsQuantity::class);
    }

    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown()
    {
        $this->cleanupReservations->execute();
    }


    /**
     * @test
     * @covers \ReachDigital\InventorySourceReservations\Model\ResourceModel\GetReservationsQuantity
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     *
     * @param string $sourceCode
     * @param string $sku
     * @param float  $quantity
     *
     * @dataProvider       reindexRowDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function should_add_the_reservation_to_the_stock_amount(string $sourceCode, string $sku, float $quantity)
    {
        $this->appendReservation($sourceCode, $sku, $quantity, 'test');
        $this->assertEquals($quantity, $this->getReservationQuantity->execute($sku, $sourceCode));
        $this->appendReservation($sourceCode, $sku, $quantity * -1, 'test');
    }

    /**
     * @return array
     */
    public function reindexRowDataProvider(): array
    {
        return [
            ['eu-1', 'SKU-1', 10],
            ['eu-2', 'SKU-2', 5],
            ['eu-2', 'SKU-2', -5],
        ];
    }

    private function appendReservation($sourceCode, $sku, $quantity, $metaData): void
    {
        $this->reservationBuilder->setSourceCode($sourceCode);
        $this->reservationBuilder->setQuantity($quantity);
        $this->reservationBuilder->setSku($sku);
        $this->reservationBuilder->setMetadata($metaData);
        $reservation = $this->reservationBuilder->build();
        $this->appendReservations->execute([$reservation]);
    }
}