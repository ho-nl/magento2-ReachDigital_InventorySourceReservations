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
use ReachDigital\ISReservations\Model\AppendReservations;
use ReachDigital\ISReservations\Model\ReservationBuilder;

class AddSourceReserversationsQtyToIsSalableConditionsPluginTest extends TestCase
{
    /** @var IndexDataBySkuListProvider */
    private $indexDataBySkuListProvider;

    /** @var GetStockItemConfiguration */
    private $getStockItemConfiguration;

    /** @var SaveStockItemConfiguration */
    private $saveStockItemConfiguration;

    /** @var AppendReservations */
    private $appendSourceReservation;

    /** @var SourceItemRepositoryInterface */
    private $sourceItemRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var SourceItemsSaveInterface */
    private $sourceItemSave;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    protected function setUp()
    {
        $this->indexDataBySkuListProvider = Bootstrap::getObjectManager()->get(IndexDataBySkuListProvider::class);
        $this->getStockItemConfiguration = Bootstrap::getObjectManager()->get(GetStockItemConfiguration::class);
        $this->saveStockItemConfiguration = Bootstrap::getObjectManager()->get(SaveStockItemConfiguration::class);
        $this->appendSourceReservation = Bootstrap::getObjectManager()->get(AppendReservations::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->sourceItemSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilder::class);
    }

    /**
     * Test that given various stock item configurations, reseveration qty, and actual source stock level,
     * IndexDataBySkuListProvider provides correct index data.
     *
     * @covers             \ReachDigital\ISReservations\Plugin\MagentoInventoryIndexer\AddSourceReserversationsQtyToIsSalableConditionsPlugin
     * @dataProvider       isSalableTestDataProvider
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture ../../../../vendor/reach-digital/magento2-order-source-reservations/IOSReservations/Test/Integration/_files/order_simple_product_with_custom_options_rollback.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-indexer/Test/_files/reindex_inventory_rollback.php
     * @magentoDataFixture ../../../../vendor/reach-digital/magento2-order-source-reservations/IOSReservations/Test/Integration/_files/product_simple_with_custom_options_rollback.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-sales-api/Test/_files/websites_with_stores_rollback.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links_rollback.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks_rollback.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources_rollback.php
     * @magentoDataFixture ../../../../vendor/reach-digital/magento2-inventory-source-reservations/ISReservations/Test/Integration/_files/clean_all_reservations.php
     *
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     *
     * @param float $sourceQty
     * @param float $reservedQty
     * @param float $minQty
     * @param bool  $backorders
     * @param bool  $managed
     * @param bool  $expectedIsSalable
     * @param int   $expectedSalableQty
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function testAddSourceReserversationsQtyToIsSalableConditions(
        float $sourceQty,
        float $reservedQty,
        float $minQty,
        bool  $backorders,
        bool  $managed,
        int   $expectedIsSalable,
        int   $expectedSalableQty
    ): void
    {
        // Set source qty. Clear qty for the other sources
        $this->setSourceQtyBySkuAndSourceCode($sourceQty, 'SKU-1', 'eu-1');
        $this->setSourceQtyBySkuAndSourceCode(0,          'SKU-1', 'eu-2');
        $this->setSourceQtyBySkuAndSourceCode(0,          'SKU-1', 'eu-3');

        // Append reservation
        $this->appendReservation('eu-1', 'SKU-1', $reservedQty, 'testAddSourceReserversationsQtyToIsSalableConditions');

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
        $this->assertEquals($expectedIsSalable, $indexData['is_salable']);
        $this->assertEquals($expectedSalableQty, $indexData['quantity']);

        // Revert reservation
        $this->appendReservation('eu-1', 'SKU-1', -$reservedQty, 'testAddSourceReserversationsQtyToIsSalableConditions');
    }

    public function isSalableTestDataProvider(): array
    {
        return [
            // source_qty, reserved_qty, min_qty, backorders, managed,  expected_is_salable, expected_salable_qty
            [          10,            0,       0,      false,    true,                 1,                   10 ],
            [          10,           -2,       0,      false,    true,                 1,                    8 ],
            [          10,           -5,       0,      false,    true,                 1,                    5 ],
            [          10,          -10,       0,      false,    true,                 0,                    0 ],
            // @todo add more cases to test
        ];
    }

    /**
     * @param float  $qty
     * @param string $sku
     * @param string $sourceCode
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function setSourceQtyBySkuAndSourceCode(float $qty, string $sku, string $sourceCode): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();

        $items = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        // Assuming SKU always exists
        $item = array_pop($items);

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