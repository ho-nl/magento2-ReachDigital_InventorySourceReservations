<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfiguration\Model\GetStockItemConfiguration;
use Magento\InventoryConfiguration\Model\SaveStockItemConfiguration;
use Magento\InventoryIndexer\Indexer\SourceItem\IndexDataBySkuListProvider;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendSourceReservations;
use ReachDigital\ISReservations\Model\SourceReservationBuilder;

class AddSourceReservationsQtyToIsSalableConditionsPluginTest extends TestCase
{
    /** @var IndexDataBySkuListProvider */
    private $indexDataBySkuListProvider;

    /** @var GetStockItemConfiguration */
    private $getStockItemConfiguration;

    /** @var SaveStockItemConfiguration */
    private $saveStockItemConfiguration;

    /** @var AppendSourceReservations */
    private $appendSourceReservation;

    /** @var SourceItemRepositoryInterface */
    private $sourceItemRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var SourceItemsSaveInterface */
    private $sourceItemSave;

    /** @var SourceReservationBuilder */
    private $reservationBuilder;

    protected function setUp()
    {
        $this->indexDataBySkuListProvider = Bootstrap::getObjectManager()->get(IndexDataBySkuListProvider::class);
        $this->getStockItemConfiguration = Bootstrap::getObjectManager()->get(GetStockItemConfiguration::class);
        $this->saveStockItemConfiguration = Bootstrap::getObjectManager()->get(SaveStockItemConfiguration::class);
        $this->appendSourceReservation = Bootstrap::getObjectManager()->get(AppendSourceReservations::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->sourceItemSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(SourceReservationBuilder::class);
    }

    /**
     * Test that given various stock item configurations, reseveration qty, and actual source stock level,
     * IndexDataBySkuListProvider provides correct index data.
     *
     * @covers             \ReachDigital\ISReservations\Plugin\MagentoInventoryIndexer\AddSourceReservationsQtyToIsSalableConditionsPlugin
     * @dataProvider       isSalableTestDataProvider
     * @magentoDbIsolation disabled
     *
     * @-magentoDataFixture ../../../../vendor/reach-digital/magento2-order-source-reservations/IOSReservations/Test/Integration/_files/order_simple_product_with_custom_options_rollback.php
     * @-magentoDataFixture ../../../../vendor/magento/module-inventory-indexer/Test/_files/reindex_inventory_rollback.php
     * @-magentoDataFixture ../../../../vendor/reach-digital/magento2-order-source-reservations/IOSReservations/Test/Integration/_files/product_simple_with_custom_options_rollback.php
     * @-magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links_rollback.php
     * @-magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks_rollback.php
     * @-magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources_rollback.php
     * @magentoDataFixture ../../../../vendor/reach-digital/magento2-inventory-source-reservations/ISReservations/Test/Integration/_files/clean_all_reservations.php
     *
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     *
     * @param float $sourceQty
     * @param int   $sourceStatus
     * @param float $reservedQty
     * @param float $minQty
     * @param bool  $backorders
     * @param bool  $managed
     * @param int   $expectedIsSalable
     * @param int   $expectedSalableQty
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function testAddSourceReservationsQtyToIsSalableConditions(
        float $sourceQty,
        int $sourceStatus,
        float $reservedQty,
        float $minQty,
        bool $backorders,
        bool $managed,
        int $expectedIsSalable,
        int $expectedSalableQty
    ): void {
        // Set source qty for eu-1 that we are testing with. Clear qty/status for the other sources
        $this->setSourceQtyBySkuAndSourceCode($sourceQty, $sourceStatus, 'SKU-1', 'eu-1');
        $this->setSourceQtyBySkuAndSourceCode(0, 0, 'SKU-1', 'eu-2');
        $this->setSourceQtyBySkuAndSourceCode(0, 0, 'SKU-1', 'eu-3');

        // Append reservation
        $this->appendReservation('eu-1', 'SKU-1', $reservedQty, 'testAddSourceReservationsQtyToIsSalableConditions');

        // Set stock item config
        $stockItemConfiguration = $this->getStockItemConfiguration->execute('SKU-1', 30);
        $stockItemConfiguration->setUseConfigMinQty(false);
        $stockItemConfiguration->setUseConfigBackorders(false);
        $stockItemConfiguration->setMinQty($minQty);
        $stockItemConfiguration->setBackorders($backorders);
        $stockItemConfiguration->setManageStock($managed);
        $this->saveStockItemConfiguration->execute('SKU-1', 30, $stockItemConfiguration);

        // Obtain and check indexed data
        $indexData = $this->indexDataBySkuListProvider->execute(30, ['SKU-1'])[0];
        $this->assertEquals($expectedIsSalable, $indexData['is_salable'], 'Product is salable');
        $this->assertEquals($expectedSalableQty, $indexData['quantity'], 'Product salable qty is correct');

        // Revert reservation
        $this->appendReservation('eu-1', 'SKU-1', -$reservedQty, 'testAddSourceReservationsQtyToIsSalableConditions');
    }

    public function isSalableTestDataProvider(): array
    {
        return [
            // source_qty, source_status, reserved_qty, min_qty, backorders, managed,  expected_is_salable, expected_salable_qty
            [10, 1, 0, 0, false, true, 1, 10],
            [10, 1, -2, 0, false, true, 1, 8],
            [10, 1, -5, 0, false, true, 1, 5],
            [10, 1, -10, 0, false, true, 0, 0],
            [10, 1, -10, -1, true, true, 1, 0],
        ];
    }

    /**
     * @param float  $qty
     * @param int    $status
     * @param string $sku
     * @param string $sourceCode
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function setSourceQtyBySkuAndSourceCode(float $qty, int $status, string $sku, string $sourceCode): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();

        $items = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        // Assuming SKU always exists
        $item = array_pop($items);

        $item->setStatus($status);
        $item->setQuantity($qty);
        $this->sourceItemSave->execute([$item]);
    }

    /**
     * @param $sourceCode
     * @param $sku
     * @param $quantity
     * @param $metaData
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    private function appendReservation(string $sourceCode, string $sku, float $quantity, string $metaData): void
    {
        $this->reservationBuilder->setSourceCode($sourceCode);
        $this->reservationBuilder->setQuantity($quantity);
        $this->reservationBuilder->setSku($sku);
        $this->reservationBuilder->setMetadata($metaData);
        $reservation = $this->reservationBuilder->build();
        $this->appendSourceReservation->execute([$reservation]);
    }
}
