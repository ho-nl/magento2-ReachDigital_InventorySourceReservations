<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ISReservations\Plugin\MagentoInventorySales;

use Closure;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\DB\Select;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\BackordersCondition;

class AddSourceReservationsQtyToIsSalableBackordersConditionPlugin
{
    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    public function __construct(StockConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function aroundExecute(BackordersCondition $subject, Closure $proceed, Select $select): string
    {
        $globalBackorders = (int) $this->configuration->getBackorders();

        $condition =
            1 === $globalBackorders
                ? 'legacy_stock_item.use_config_backorders = 1'
                : 'legacy_stock_item.use_config_backorders = 0 AND legacy_stock_item.backorders = 1';
        // @todo: figure out what to do with legacy_stock_item.qty
        $condition .= ' AND (legacy_stock_item.min_qty >= 0 OR legacy_stock_item.qty > legacy_stock_item.min_qty)';

        return $condition;
    }
}
