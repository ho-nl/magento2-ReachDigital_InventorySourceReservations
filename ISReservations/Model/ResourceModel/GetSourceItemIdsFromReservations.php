<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use ReachDigital\ISReservations\Model\Reservation;
use ReachDigital\ISReservationsApi\Model\ReservationInterface;

class GetSourceItemIdsFromReservations
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
      ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Given one or more source reservations, find the relevant source items. The given reservations need not actually
     * be inserted (or have a reservation_id value); lookup is done by SKU and source code combination.
     *
     * @param array $reservations
     *
     * @return array
     * @throws \DomainException
     */
    public function execute(array $reservations): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceItemTable = $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);

        // Build condition based on sku/source_code, since we can't rely on having reservation IDs to do a join with
        $whereParts = [];
        /** @var Reservation $reservation */
        foreach ($reservations as $reservation) {
            $skuEquals = $connection->prepareSqlCondition('si.'.ReservationInterface::SKU, [ 'eq' => $reservation->getSku() ]);
            $sourceCodeEquals = $connection->prepareSqlCondition('si.'.ReservationInterface::SOURCE_CODE, [ 'eq' => $reservation->getSourceCode() ]);
            $whereParts[] = "($skuEquals AND $sourceCodeEquals)";
        }
        $whereCondition = '(' . implode(' OR ', $whereParts) . ')';

        $select = $connection->select()
            ->from([ 'si' => $sourceItemTable ], [ 'si.'.SourceItem::ID_FIELD_NAME ])
            ->where($whereCondition);

        return array_unique($connection->fetchCol($select));
    }
}