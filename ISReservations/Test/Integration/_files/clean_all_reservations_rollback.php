<?php
declare(strict_types=1);

/** @var ResourceConnection $resource */

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;

$resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);

$resource->getConnection()->truncateTable($resource->getTableName('inventory_reservation'));
$resource->getConnection()->truncateTable($resource->getTableName('inventory_source_reservation'));
