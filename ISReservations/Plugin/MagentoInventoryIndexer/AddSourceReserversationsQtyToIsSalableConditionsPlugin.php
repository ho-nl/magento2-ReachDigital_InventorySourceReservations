<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ISReservations\Plugin\MagentoInventoryIndexer;

use Closure;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\GetIsStockItemSalableConditionInterface;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\Inventory\Model\ResourceModel\Source as SourceResourceModel;
use Magento\Inventory\Model\ResourceModel\StockSourceLink as StockSourceLinkResourceModel;
use Magento\Inventory\Model\StockSourceLink;
use Magento\InventoryApi\Api\Data\SourceInterface;

class AddSourceReserversationsQtyToIsSalableConditionsPlugin
{
    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var GetIsStockItemSalableConditionInterface */
    private $getIsStockItemSalableCondition;

    /** @var string */
    private $productTableName;

    public function __construct(
        ResourceConnection $resourceConnection,
        GetIsStockItemSalableConditionInterface $getIsStockItemSalableCondition,
        string $productTableName
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getIsStockItemSalableCondition = $getIsStockItemSalableCondition;
        $this->productTableName = $productTableName;
    }

    /**
     * @param SelectBuilder $subject
     * @param Closure       $proceed
     * @param int           $stockId
     *
     * @return Select
     */
    public function aroundExecute(SelectBuilder $subject, Closure $proceed, int $stockId): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceItemTable = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);

        $quantityExpression = (string)$this->resourceConnection->getConnection()->getCheckSql(
            'source_item.' . SourceItemInterface::STATUS . ' = ' . SourceItemInterface::STATUS_OUT_OF_STOCK,
            0,
            SourceItemInterface::QUANTITY
        );
        $sourceCodes = $this->getSourceCodes($stockId);

        $select = $connection->select();
        $select->joinLeft(
            ['product' => $this->resourceConnection->getTableName($this->productTableName)],
            'product.sku = source_item.' . SourceItemInterface::SKU,
            []
        )->joinLeft(
            ['legacy_stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
            'product.entity_id = legacy_stock_item.product_id',
            []
        );

        $select->from(
            ['source_item' => $sourceItemTable],
            [
                SourceItemInterface::SKU,
                IndexStructure::QUANTITY => 'SUM(' . $quantityExpression . ')',
                IndexStructure::IS_SALABLE => $this->getIsStockItemSalableCondition->execute($select),
            ]
        )
            ->where('source_item.' . SourceItemInterface::SOURCE_CODE . ' IN (?)', $sourceCodes)
            ->group([SourceItemInterface::SKU]);

        return $select;
    }

    /**
     * Get all enabled sources related to stock
     *
     * @param int $stockId
     * @return array
     */
    private function getSourceCodes(int $stockId): array
    {
        $connection           = $this->resourceConnection->getConnection();
        $sourceTable          = $this->resourceConnection->getTableName(SourceResourceModel::TABLE_NAME_SOURCE);
        $sourceStockLinkTable = $this->resourceConnection->getTableName(
            StockSourceLinkResourceModel::TABLE_NAME_STOCK_SOURCE_LINK
        );
        $select = $connection->select()
            ->from(['source' => $sourceTable], [SourceInterface::SOURCE_CODE])
            ->joinInner(
                ['stock_source_link' => $sourceStockLinkTable],
                'source.' . SourceItemInterface::SOURCE_CODE . ' = stock_source_link.' . StockSourceLink::SOURCE_CODE,
                []
            )
            ->where('stock_source_link.' . StockSourceLink::STOCK_ID . ' = ?', $stockId)
            ->where(SourceInterface::ENABLED . ' = ?', 1);
        $sourceCodes = $connection->fetchCol($select);
        return $sourceCodes;
    }
}