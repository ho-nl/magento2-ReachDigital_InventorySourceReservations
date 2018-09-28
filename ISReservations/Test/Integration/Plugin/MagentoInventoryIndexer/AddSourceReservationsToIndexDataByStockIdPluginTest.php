<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Test\Integration\Plugin\MagentoInventoryIndexer;

use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryIndexer\Test\Integration\Indexer\RemoveIndexData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendReservations;
use ReachDigital\ISReservations\Model\ReservationBuilder;
use ReachDigital\ISReservations\Model\ResourceModel\CleanupReservations;

class AddSourceReservationsToIndexDataByStockIdPluginTest extends TestCase
{

    /**
     * @var SourceIndexer
     */
    private $sourceIndexer;

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @var RemoveIndexData
     */
    private $removeIndexData;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    /** @var AppendReservations */
    private $appendReservations;

    /** @var CleanupReservations */
    private $cleanupReservations;

    protected function setUp()
    {
        $this->sourceIndexer = Bootstrap::getObjectManager()->get(SourceIndexer::class);
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);

        $this->removeIndexData = Bootstrap::getObjectManager()->get(RemoveIndexData::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservations::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservations::class);
        $this->removeIndexData->execute([10, 20, 30]);
    }

    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown()
    {
        $this->removeIndexData->execute([10, 20, 30]);
        $this->cleanupReservations->execute();
    }

    /**
     * Source 'eu-1' is assigned on EU-stock(id:10) and Global-stock(id:30)
     * Thus these stocks stocks be reindexed
     *
     * @covers             \ReachDigital\ISReservations\Plugin\MagentoInventoryIndexer\AddSourceReservationsToIndexDataByStockIdPlugin
     * @test
     *
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/_files/source_items.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     *
     * @param string     $sku
     * @param int        $stockId
     * @param float      $reservation
     * @param array|null $expectedData
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider dataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testAddSourceReservationToIndexDataByStockId(
        string $sku,
        int $stockId,
        float $reservation,
        ?array $expectedData
    ): void {
        $this->appendReservation('eu-1', $sku, $reservation, 'test');
        $this->sourceIndexer->executeRow('eu-1');
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);

        self::assertEquals($expectedData, $stockItemData);

        //cleanup
        $this->appendReservation('eu-1', $sku, $reservation *- 1, 'test');
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        return [
            ['SKU-1', 10, 5, [GetStockItemDataInterface::QUANTITY => 8.5+5, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-1', 30, 8, [GetStockItemDataInterface::QUANTITY => 8.5+8, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-2', 10, 9, null],
            ['SKU-2', 30, -5, [GetStockItemDataInterface::QUANTITY => 5-5, GetStockItemDataInterface::IS_SALABLE => 0]],
            ['SKU-3', 10, 3, [GetStockItemDataInterface::QUANTITY => 0+3, GetStockItemDataInterface::IS_SALABLE => 1]],
            ['SKU-3', 30, 1, [GetStockItemDataInterface::QUANTITY => 0+1, GetStockItemDataInterface::IS_SALABLE => 1]],
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
