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

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array $skuList
     * @param array $sourceCodes
     *
     * @return \Traversable
     */
    public function execute(array $skuList, array $sourceCodes = null): \Traversable
    {
        $connection = $this->resourceConnection->getConnection();
        $reservationTable = $this->resourceConnection->getTableName('inventory_source_reservation');

        $select = $connection->select()
            ->from($reservationTable, [
                ReservationInterface::SKU,
                ReservationInterface::QUANTITY => 'SUM(' . ReservationInterface::QUANTITY . ')'
            ])
            ->where(ReservationInterface::SKU . ' IN(?)', $skuList)
            ->group(ReservationInterface::SKU);

        if ($sourceCodes) {
            $select->where(ReservationInterface::SOURCE_CODE . ' IN(?)', $sourceCodes);
        }

        return new \ArrayIterator($connection->fetchAssoc($select) ?: []);
    }
}
