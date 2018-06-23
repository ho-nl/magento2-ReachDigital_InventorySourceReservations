<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Plugin\MagentoInventoryIndexer;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\GetSourceCodesForStockIds;
use ReachDigital\InventorySourceReservationsApi\Model\ReservationInterface;


class AddSourceReservationsToIndexDataByStockIdPlugin
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetSourceCodesForStockIds
     */
    private $getSourceCodesForStockIds;

    public function __construct(
        ResourceConnection $resourceConnection,
        GetSourceCodesForStockIds $getSourceCodesForStockIds
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getSourceCodesForStockIds = $getSourceCodesForStockIds;
    }


    public function aroundExecute(
        \Magento\InventoryIndexer\Indexer\Stock\IndexDataProviderByStockId $subject,
        \Closure $proceed,
        int $stockId
    ) : \ArrayIterator {
        return $this->appendReservationsToIndexData(
            $proceed($stockId),
            $this->getReservationsForSources($this->getSourceCodesForStockIds->execute($stockId))
        );
    }

    private function getReservationsForSources($sourceCodes): array
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

        return $connection->fetchAssoc($select) ?: [];
    }

    private function appendReservationsToIndexData(\ArrayIterator $indexData, array $reservations): \ArrayIterator
    {
        $indexDataArr = iterator_to_array($indexData);
        foreach ($indexDataArr as &$indexRow) {
            $reservation = $reservations[$indexRow[IndexStructure::SKU]] ?? false;
            if ($reservation) {
                $indexRow[IndexStructure::IS_SALABLE] = $this->isSalable($indexRow, $reservation);
                $indexRow[IndexStructure::QUANTITY] += (float) $reservation[ReservationInterface::QUANTITY];
            }
        }
        return new \ArrayIterator($indexDataArr);
    }

    /**
     * @todo We should take min_qty of the product in account, that can be negative.
     * @todo We should take the backorder status of the product in account, that can be enabled.
     * Should probably be completely replaced by some of the more abstract stuff and should be implemented in the query.
     *
     * @param array $indexRow
     * @param array $reservation
     * @return int
     */
    private function isSalable(array $indexRow, array $reservation): int
    {
        $newQty = $indexRow[IndexStructure::QUANTITY] + (float) $reservation[ReservationInterface::QUANTITY];

        //if currently is not salable and newQty is positive, make salable
        if ($newQty > 0 && !$indexRow[IndexStructure::IS_SALABLE]) {
            return 1;
        }

        //if currently salable and newQty is 0 or negative
        if ($newQty <= 0 && $indexRow[IndexStructure::IS_SALABLE]) {
            return 0;
        }

        //If currently is salable and newQty is positive, done
        //If currently is not salable and newQty is 0 or negative, done
        return $indexRow[IndexStructure::IS_SALABLE];
    }
}
