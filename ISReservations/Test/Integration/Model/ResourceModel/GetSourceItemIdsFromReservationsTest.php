<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Setup\Exception;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReachDigital\ISReservations\Model\AppendReservations;
use ReachDigital\ISReservations\Model\ReservationBuilder;
use ReachDigital\ISReservationsApi\Model\ReservationInterface;

class GetSourceItemIdsFromReservationsTest extends TestCase
{
    /** @var GetSourceItemIdsFromReservations */
    private $getReservationQuantity;

    /** @var SourceItemRepositoryInterface */
    private $sourceItemRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var AppendReservations */
    private $appendReservations;

    /** @var ReservationBuilder */
    private $reservationBuilder;

    /** @var GetSourceItemIdsFromReservations */
    private $getSourceItemByReservations;

    protected function setUp()
    {
        $this->getReservationQuantity = Bootstrap::getObjectManager()->get(GetSourceItemIdsFromReservations::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservations::class);
        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilder::class);
        $this->getSourceItemByReservations = Bootstrap::getObjectManager()->get(GetSourceItemIdsFromReservations::class);
    }

    protected function tearDown()
    {
    }

    /**
     * @test
     * @covers \ReachDigital\ISReservations\Model\ResourceModel\GetSourceItemIdsFromReservations
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     *
     * @throws
     */
    public function should_provide_correct_source_item_ids_from_reservations(): void
    {
        // Get a source item
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        /** @var SourceItem $item */
        $item = array_pop($sourceItems);
        $itemId = $item->getId();

        // Make a reservation on it
        $reservation = $this->appendReservation($item->getSourceCode(), $item->getSku(), 4, 'test'); // @todo test multiple reservations case, maybe with dataprovider?

        // Obtain source item ID by reservation(s)
        $reseverationIds = $this->getSourceItemByReservations->execute([$reservation]);

        // Assert that obtained ID matches original source item ID
        $this->assertSameSize([$itemId], $reseverationIds);
        $this->assertEquals($itemId, array_pop($reseverationIds));
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    private function appendReservation(string $sourceCode, string $sku, float $quantity, string $metaData) : ReservationInterface
    {
        $this->reservationBuilder->setSourceCode($sourceCode);
        $this->reservationBuilder->setQuantity($quantity);
        $this->reservationBuilder->setSku($sku);
        $this->reservationBuilder->setMetadata($metaData);
        $reservation = $this->reservationBuilder->build();
        $this->appendReservations->execute([$reservation]);
        return $reservation;
    }
}