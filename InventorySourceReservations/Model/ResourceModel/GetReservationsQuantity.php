<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\InventorySourceReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\InventorySourceReservationsApi\Model\ReservationInterface;
use ReachDigital\InventorySourceReservationsApi\Model\GetReservationsQuantityInterface;

/**
 * @inheritdoc
 */
class GetReservationsQuantity implements GetReservationsQuantityInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $sourceId): float
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_source_reservation');

        $select = $connection->select()
            ->from($reservationTable, [ReservationInterface::QUANTITY => 'SUM(' . ReservationInterface::QUANTITY . ')'])
            ->where(ReservationInterface::SKU . ' = ?', $sku)
            ->where(ReservationInterface::SOURCE_ID . ' = ?', $sourceId)
            ->limit(1);

        return (float) $connection->fetchOne($select) ?: (float) 0;
    }
}
