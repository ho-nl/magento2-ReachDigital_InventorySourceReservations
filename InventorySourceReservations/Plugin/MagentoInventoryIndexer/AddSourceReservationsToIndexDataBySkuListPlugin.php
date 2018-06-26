<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Plugin\MagentoInventoryIndexer;

use Magento\InventoryIndexer\Indexer\SourceItem\IndexDataBySkuListProvider;
use ReachDigital\InventorySourceReservations\Model\AppendReservationsToIndexData;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\GetReservationsQuantityList;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\GetSourceCodesForStockIds;

class AddSourceReservationsToIndexDataBySkuListPlugin
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

    /**
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * @param IndexDataBySkuListProvider $subject
     * @param \Closure                   $proceed
     * @param int                        $stockId
     * @param array                      $skuList
     *
     * @return \ArrayIterator
     */
    public function aroundExecute(
        IndexDataBySkuListProvider $subject,
        \Closure $proceed,
        int $stockId,
        array $skuList
    ) : \ArrayIterator {

        return $this->appendReservationsToIndexData->execute(
            $proceed($stockId, $skuList),
            $this->getReservationsQuantityList->execute(
                $this->getSourceCodesForStockIds->execute($stockId),
                $skuList
            )
        );
    }
}
