<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendSourceReservations;
use ReachDigital\ISReservations\Model\SourceReservationBuilder;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;

class GetSourceItemIdsFromReservationsTest extends TestCase
{
    /** @var SourceItemRepositoryInterface */
    private $sourceItemRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var AppendSourceReservations */
    private $appendReservations;

    /** @var SourceReservationBuilder */
    private $reservationBuilder;

    /** @var GetSourceItemIdsFromReservations */
    private $getSourceItemByReservations;

    protected function setUp()
    {
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendSourceReservations::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(SourceReservationBuilder::class);
        $this->getSourceItemByReservations = Bootstrap::getObjectManager()->get(
            GetSourceItemIdsFromReservations::class
        );
    }

    protected function tearDown()
    {
    }

    /**
     * @test
     * @covers \ReachDigital\ISReservations\Model\ResourceModel\GetSourceItemIdsFromReservations
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
    public function should_provide_correct_source_item_ids_from_reservations(): void
    {
        // Get some source items
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        $itemIds = [];
        $reservations = [];

        // Make reservations on it
        /** @var SourceItem $item */
        foreach ($sourceItems as $item) {
            $reservations[] = $this->appendReservation(
                $item->getSourceCode(),
                $item->getSku(),
                1,
                'test_source_item_ids_from_reservations'
            );
            $itemIds[] = $item->getId();
        }

        // Obtain source item IDs by newly appended reservations
        $testItemIds = $this->getSourceItemByReservations->execute($reservations);

        // Assert that obtained source item ID matches original source item ID
        self::assertSameSize($itemIds, $testItemIds);
        self::assertEquals([], array_diff($itemIds, $testItemIds));
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    private function appendReservation(
        string $sourceCode,
        string $sku,
        float $quantity,
        string $metaData
    ): SourceReservationInterface {
        $this->reservationBuilder->setSourceCode($sourceCode);
        $this->reservationBuilder->setQuantity($quantity);
        $this->reservationBuilder->setSku($sku);
        $this->reservationBuilder->setMetadata($metaData);
        $reservation = $this->reservationBuilder->build();
        $this->appendReservations->execute([$reservation]);
        return $reservation;
    }
}
