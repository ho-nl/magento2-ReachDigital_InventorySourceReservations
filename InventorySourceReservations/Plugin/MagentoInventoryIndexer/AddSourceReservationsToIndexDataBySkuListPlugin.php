<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\InventorySourceReservations\Plugin\MagentoInventoryIndexer;

class AddSourceReservationsToIndexDataBySkuListPlugin
{

    /**
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * @param \Magento\InventoryIndexer\Indexer\SourceItem\IndexDataBySkuListProvider $subject
     * @param \Closure                                                                $proceed
     * @param int                                                                     $stockId
     * @param array                                                                   $skuList
     *
     * @return \ArrayIterator
     */
    public function aroundExecute(
        \Magento\InventoryIndexer\Indexer\SourceItem\IndexDataBySkuListProvider $subject,
        \Closure $proceed,
        int $stockId,
        array $skuList
    ) : \ArrayIterator {

        return $proceed($stockId, $skuList);
    }
}
