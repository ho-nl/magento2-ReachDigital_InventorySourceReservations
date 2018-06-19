<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\InventorySourceReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\InventorySourceReservationsApi\Model\ReservationInterface;
use ReachDigital\InventorySourceReservationsApi\Model\CleanupReservationsInterface;

/**
 * @inheritdoc
 */
class CleanupReservations implements CleanupReservationsInterface
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
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_source_reservation');

        $select = $connection->select()
            ->from(
                $reservationTable,
                ['GROUP_CONCAT(' . ReservationInterface::RESERVATION_ID . ')']
            )
            ->group([ReservationInterface::SOURCE_ID, ReservationInterface::SKU])
            ->having('SUM(' . ReservationInterface::QUANTITY . ') = 0');
            $connection->query('SET group_concat_max_len = ' . $this->groupConcatMaxLen);
        $groupedReservationIds = implode(',', $connection->fetchCol($select));

        $condition = [ReservationInterface::RESERVATION_ID . ' IN (?)' => explode(',', $groupedReservationIds)];
        $connection->delete($reservationTable, $condition);
    }
}
