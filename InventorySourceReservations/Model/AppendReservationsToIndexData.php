<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Model;

use Magento\InventoryIndexer\Indexer\IndexStructure;
use ReachDigital\InventorySourceReservationsApi\Model\ReservationInterface;

class AppendReservationsToIndexData
{
    public function execute(\Traversable $indexData, \Traversable $reservations): \ArrayIterator
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
     * @todo We should check if the is_in_stock value is 1 or 0.
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