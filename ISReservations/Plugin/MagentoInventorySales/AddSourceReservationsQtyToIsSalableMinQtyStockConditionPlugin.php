<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ISReservations\Plugin\MagentoInventorySales;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\MinQtyStockCondition;

class AddSourceReservationsQtyToIsSalableMinQtyStockConditionPlugin
{
    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param StockConfigurationInterface $configuration
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        StockConfigurationInterface $configuration,
        ResourceConnection $resourceConnection
    ) {
        $this->configuration = $configuration;
        $this->resourceConnection = $resourceConnection;
    }

    public function aroundExecute(MinQtyStockCondition $subject, \Closure $proceed, Select $select): string
    {
        $globalMinQty = (float)$this->configuration->getMinQty();

        $quantityExpression = (string)$this->resourceConnection->getConnection()->getCheckSql(
            'source_item.' . SourceItemInterface::STATUS . ' = ' . SourceItemInterface::STATUS_OUT_OF_STOCK,
            0,
            'source_item.'. SourceItemInterface::QUANTITY . ' + reservation.quantity'
        );
        $quantityExpression = 'SUM(' . $quantityExpression . ')';

        $condition =
            '(legacy_stock_item.use_config_min_qty = 1 AND ' . $quantityExpression . ' > ' . $globalMinQty . ')'
            . ' OR '
            . '(legacy_stock_item.use_config_min_qty = 0 AND ' . $quantityExpression . ' > legacy_stock_item.min_qty)';

        return $condition;
    }
}