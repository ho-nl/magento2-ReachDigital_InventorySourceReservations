<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;

/**
 * Implementation of Reservation save multiple operation for specific db layer
 * Save Multiple used here for performance efficient purposes over single save operation
 */
class SaveMultiple
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param SourceReservationInterface[] $reservations
     *
     * @return void
     */
    public function execute(array $reservations)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_source_reservation');

        $columns = [
            SourceReservationInterface::SOURCE_CODE,
            SourceReservationInterface::SKU,
            SourceReservationInterface::QUANTITY,
            SourceReservationInterface::METADATA,
        ];

        $data = [];
        /** @var SourceReservationInterface $reservation */
        foreach ($reservations as $reservation) {
            $data[] = [
                $reservation->getSourceCode(),
                $reservation->getSku(),
                $reservation->getQuantity(),
                $reservation->getMetadata(),
            ];
        }
        $connection->insertArray($tableName, $columns, $data);
    }
}
