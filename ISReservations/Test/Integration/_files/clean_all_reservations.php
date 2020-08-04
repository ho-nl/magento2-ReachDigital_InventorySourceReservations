<?php
declare(strict_types=1);

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var ResourceConnection $resource */
$resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);

$resource->getConnection()->truncateTable($resource->getTableName('inventory_reservation'));
$resource->getConnection()->truncateTable($resource->getTableName('inventory_source_reservation'));

$indexer = $objectManager->get(StockIndexer::class);
$indexer->executeFull();

$indexer = $objectManager->get(StockIndexer::class);
