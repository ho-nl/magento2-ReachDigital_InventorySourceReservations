<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservationsApi\Model\ReservationInterface;

class GetReservationsQuantityList
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

    public function execute($sourceCodes, array $skuList = []): \Traversable
    {
        $connection = $this->resourceConnection->getConnection();
        $reservationTable = $this->resourceConnection->getTableName('inventory_source_reservation');

        $select = $connection->select()
            ->from($reservationTable, [
                ReservationInterface::SKU,
                ReservationInterface::QUANTITY => 'SUM(' . ReservationInterface::QUANTITY . ')'
            ])
            ->where(ReservationInterface::SOURCE_CODE . ' IN(?)', $sourceCodes)
            ->group(ReservationInterface::SKU);

        if ($skuList) {
            $select->where(ReservationInterface::SKU . ' IN(?)', $skuList);
        }

        return new \ArrayIterator($connection->fetchAssoc($select) ?: []);
    }
}
