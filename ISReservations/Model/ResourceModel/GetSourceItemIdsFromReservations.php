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
     * @param array $reservations
     *
     * @return array
     * @throws \DomainException
     */
    public function execute(array $reservations): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceItemTable = $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);
        $sourceReservationTable = $this->resourceConnection->getTableName('inventory_source_reservation'); // @fixme no resourcemodel?

        $reservationIds = [];
        /** @var Reservation $reservation */
        foreach ($reservations as $reservation) {
            $reservationIds[] = $reservation->getReservationId();
        }

        $select = $connection->select()
            ->from([ 'sr' => $sourceReservationTable ], [])
            ->joinInner(
                [ 'si' => $sourceItemTable ],
                'si.'.SourceItemInterface::SOURCE_CODE.' = sr.'.ReservationInterface::SOURCE_CODE.' and si.'.SourceItemInterface::SKU.' = sr.'.ReservationInterface::SKU,
                [ 'si.'.SourceItem::ID_FIELD_NAME ]
            )
            ->where('sr.'.ReservationInterface::RESERVATION_ID.' IN(?)', $reservationIds);
        return array_unique($connection->fetchCol($select));
    }
}