<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;
use ReachDigital\ISReservationsApi\Model\GetSourceReservationsQuantityInterface;

class GetSourceReservationsQuantity implements GetSourceReservationsQuantityInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function execute(string $sku, string $sourceCode): float
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_source_reservation');

        $select = $connection
            ->select()
            ->from($reservationTable, [
                SourceReservationInterface::QUANTITY => 'SUM(' . SourceReservationInterface::QUANTITY . ')',
            ])
            ->where(SourceReservationInterface::SKU . ' = ?', $sku)
            ->where(SourceReservationInterface::SOURCE_CODE . ' = ?', $sourceCode)
            ->limit(1);

        return (float) $connection->fetchOne($select) ?: (float) 0;
    }
}
