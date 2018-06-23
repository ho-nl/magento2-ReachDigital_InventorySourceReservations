<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Test\Integration\Plugin\MagentoInventoryIndexer;

use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryIndexer\Test\Integration\Indexer\RemoveIndexData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\InventorySourceReservations\Model\AppendReservations;
use ReachDigital\InventorySourceReservations\Model\ReservationBuilder;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\CleanupReservations;

class AppendSourceReservationsToStockReservationsTest extends TestCase
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
     * @covers \ReachDigital\InventorySourceReservations\Plugin\MagentoInventoryIndexer\AddSourceReservationsToIndexDataByStockIdPlugin
     * @test
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     *
     * @param string $sku
     * @param int $stockId
     * @param array|null $expectedData
     *
     * @dataProvider should_add_the_reservation_amount_to_the_indexed_amount_data_provider
     *
     * @magentoDbIsolation disabled
     */
    public function should_add_the_reservation_amount_to_the_indexed_amount(
        string $sku,
        int $stockId,
        float $reservation,
        array $expectedData
    ): void {
        $this->appendReservation('eu-1', $sku, $reservation, 'test');
        $this->sourceIndexer->executeRow('eu-1');
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);

        $this->appendReservation('eu-1', $sku, $reservation *- 1, 'test');
        self::assertEquals($expectedData, $stockItemData);
    }

    /**
     * @return array
     */
    public function should_add_the_reservation_amount_to_the_indexed_amount_data_provider(): array
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
