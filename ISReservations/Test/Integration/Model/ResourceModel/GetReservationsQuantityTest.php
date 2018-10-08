<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Test\Integration\Model\ResourceModel;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendReservations;
use ReachDigital\ISReservations\Model\ReservationBuilder;
use ReachDigital\ISReservations\Model\ResourceModel\CleanupReservations;
use ReachDigital\ISReservations\Model\ResourceModel\GetSourceReservationsQuantity;

class GetReservationsQuantityTest extends TestCase
{
    /** @var AppendReservations */
    private $appendReservations;

    /** @var CleanupReservations */
    private $cleanupReservations;

    /** @var GetSourceReservationsQuantity */
    private $getReservationQuantity;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    public function setUp()
    {
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservations::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservations::class);
        $this->getReservationQuantity = Bootstrap::getObjectManager()->get(GetSourceReservationsQuantity::class);
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
     * @covers \ReachDigital\ISReservations\Model\ResourceModel\GetSourceReservationsQuantity
     *
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
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
        $initialQty = $this->getReservationQuantity->execute($sku, $sourceCode);
        $this->appendReservation($sourceCode, $sku, $quantity, 'test');
        $this->assertEquals($initialQty + $quantity, $this->getReservationQuantity->execute($sku, $sourceCode));
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