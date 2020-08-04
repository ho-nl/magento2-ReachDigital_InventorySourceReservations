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
use ReachDigital\ISReservations\Model\SourceReservationBuilder;
use ReachDigital\ISReservations\Model\ResourceModel\CleanupSourceReservations;
use ReachDigital\ISReservations\Model\ResourceModel\GetSourceReservationsQuantity;

class GetReservationsQuantityTest extends TestCase
{
    /** @var AppendSourceReservations */
    private $appendReservations;

    /** @var CleanupSourceReservations */
    private $cleanupReservations;

    /** @var GetSourceReservationsQuantity */
    private $getReservationQuantity;

    /** @var SourceReservationBuilder */
    private $reservationBuilder;

    public function setUp()
    {
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(SourceReservationBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendSourceReservations::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupSourceReservations::class);
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
        return [['eu-1', 'SKU-1', 10], ['eu-2', 'SKU-2', 5], ['eu-2', 'SKU-2', -5]];
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    private function appendReservation(string $sourceCode, string $sku, float $quantity, string $metaData): void
    {
        $this->reservationBuilder->setSourceCode($sourceCode);
        $this->reservationBuilder->setQuantity($quantity);
        $this->reservationBuilder->setSku($sku);
        $this->reservationBuilder->setMetadata($metaData);
        $reservation = $this->reservationBuilder->build();
        $this->appendReservations->execute([$reservation]);
    }
}
