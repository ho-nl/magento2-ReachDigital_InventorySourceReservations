<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservationsApi\Model;

/**
 * Responsible for retrieving Reservation Quantity (without stock data)
 *
 * @api
 */
interface GetReservationsQuantityInterface
{
    /**
     * Given a product sku and a stock id, return reservation quantity
     *
     * @param string $sku
     * @param int $sourceCode
     * @return float
     */
    public function execute(string $sku, string $sourceCode): float;
}
