<?php
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
    \Magento\Framework\App\ResourceConnection::class
);

$resource->getConnection()->truncateTable($resource->getTableName('inventory_reservation'));
$resource->getConnection()->truncateTable($resource->getTableName('inventory_source_reservation'));

$indexer = $objectManager->get(\Magento\InventoryIndexer\Indexer\Stock\StockIndexer::class);
$indexer->executeFull();

$indexer = $objectManager->get(\Magento\InventoryIndexer\Indexer\Stock\StockIndexer::class);
