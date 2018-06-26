<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Plugin\MagentoInventoryIndexer;

use ReachDigital\InventorySourceReservations\Model\AppendReservationsToIndexData;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\GetReservationsQuantityList;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\GetSourceCodesForStockIds;

class AddSourceReservationsToIndexDataByStockIdPlugin
{
    /**
     * @var GetSourceCodesForStockIds
     */
    private $getSourceCodesForStockIds;

    /**
     * @var AppendReservationsToIndexData
     */
    private $appendReservationsToIndexData;

    /** @var GetReservationsQuantityList */
    private $getReservationsQuantityList;

    public function __construct(
        GetSourceCodesForStockIds $getSourceCodesForStockIds,
        GetReservationsQuantityList $getReservationsQuantityList,
        AppendReservationsToIndexData $appendReservationsToIndexData
    ) {
        $this->getSourceCodesForStockIds = $getSourceCodesForStockIds;
        $this->getReservationsQuantityList = $getReservationsQuantityList;
        $this->appendReservationsToIndexData = $appendReservationsToIndexData;
    }


    public function aroundExecute(
        \Magento\InventoryIndexer\Indexer\Stock\IndexDataProviderByStockId $subject,
        \Closure $proceed,
        int $stockId
    ) : \ArrayIterator {
        return $this->appendReservationsToIndexData->execute(
            $proceed($stockId),
            $this->getReservationsQuantityList->execute($this->getSourceCodesForStockIds->execute($stockId))
        );
    }
}
