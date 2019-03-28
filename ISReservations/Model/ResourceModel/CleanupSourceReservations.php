<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;
use ReachDigital\ISReservationsApi\Model\CleanupSourceReservationsInterface;

/**
 * @inheritdoc
 */
class CleanupSourceReservations implements CleanupSourceReservationsInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var int
     */
    private $groupConcatMaxLen;

    /**
     * @param ResourceConnection $resource
     * @param int $groupConcatMaxLen
     */
    public function __construct(
        ResourceConnection $resource,
        int $groupConcatMaxLen
    ) {
        $this->resource = $resource;
        $this->groupConcatMaxLen = $groupConcatMaxLen;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        return;

//      The CleanupReservations method is currently disabled, because this class removes all rows that sum to
//      0. We can't do that because we are interesed in certain rows of the reservation table instead of only
//      the sum of the reservation of an item. We can consider making the group statement different and clean
//      up reservations that way; E.g. Purchase orders that were reserved and ultimately fulfilled.
//
//      For now we'll leave this method disabled as there aren't any performance issues yet, this table can
//      probably easily be millions of rows before it will become slow.

//        $connection = $this->resource->getConnection();
//        $reservationTable = $this->resource->getTableName('inventory_source_reservation');
//
//        $select = $connection->select()
//            ->from(
//                $reservationTable,
//                ['GROUP_CONCAT(' . ReservationInterface::RESERVATION_ID . ')']
//            )
//            ->group([ReservationInterface::SOURCE_CODE, ReservationInterface::SKU])
//            ->having('SUM(' . ReservationInterface::QUANTITY . ') = 0');
//            $connection->query('SET group_concat_max_len = ' . $this->groupConcatMaxLen);
//        $groupedReservationIds = implode(',', $connection->fetchCol($select));
//
//        $condition = [ReservationInterface::RESERVATION_ID . ' IN (?)' => explode(',', $groupedReservationIds)];
//        $connection->delete($reservationTable, $condition);
    }
}
