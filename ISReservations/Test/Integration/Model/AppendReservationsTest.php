<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ISReservations\Test\Integration\Model;

use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryIndexer\Test\Integration\Indexer\RemoveIndexData;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendReservations;
use ReachDigital\ISReservations\Model\ReservationBuilder;
use ReachDigital\ISReservations\Model\ResourceModel\CleanupReservations;

class AppendReservationsTest extends TestCase
{
    /** @var GetStockItemData */
    private $getStockItemData;

    /** @var AppendReservations */
    private $appendReservations;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    /** @var CleanupReservations */
    private $cleanupReservations;

    protected function setUp()
    {
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservations::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilder::class);
        $this->removeIndexData = Bootstrap::getObjectManager()->get(RemoveIndexData::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservations::class);
    }

    protected function tearDown()
    {
    }

    /**
     * @test
     * @covers \ReachDigital\ISReservations\Model\AppendReservations
     *
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/products.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/sources.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stocks.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/source_items.php
     * @magentoDataFixture ../../../../vendor/magento/module-inventory-api/Test/_files/stock_source_links.php
     *
     * @magentoDbIsolation disabled
     *
     * @throws 
     */
    public function should_invoke_sourceitem_indexer_after_appending_source_reservation(): void
    {
        // Have some sources, stocks, skus setup.

        // Trigger initial reindex (else no stock index tables exist)
        /** @var SourceItemIndexer $indexer */
        $indexer = Bootstrap::getObjectManager()->get(SourceItemIndexer::class);
        $indexer->executeFull();

        // Get indexed quantity. Stock 30 has all sources
        $origStockData = $this->getStockItemData->execute('SKU-1', 30);

        // Append some reservations to different sources, indexed stock quantity is not affected by disabled sources
        $this->appendReservation('eu-1', 'SKU-1', -3, 'test_index_trigger');
        $this->appendReservation('eu-2', 'SKU-1', -3, 'test_index_trigger');
        $this->appendReservation('eu-disabled', 'SKU-1', -3, 'test_index_trigger');

        // Check indexed quantity. Must have decreased by 6.
        $newStockData = $this->getStockItemData->execute('SKU-1', 30);

        self::assertEquals($origStockData['quantity'] - 6, $newStockData['quantity'], 'Asserting that new indexed quantity has decreased');

        // Append some reservations to different sources, indexed stock quantity is not affected by disabled sources
        $this->appendReservation('eu-1', 'SKU-1', 3, 'test_index_trigger_rollback');
        $this->appendReservation('eu-2', 'SKU-1', 3, 'test_index_trigger_rollback');
        $this->appendReservation('eu-disabled', 'SKU-1', 3, 'test_index_trigger_rollback');
    }

    /**
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
        $this->appendReservations->execute([$reservation]);
    }
}