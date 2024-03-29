<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);
namespace ReachDigital\ISReservations\Model\MetaData;

use ReachDigital\ISReservationsApi\Api\DecodeMetaDataInterface;

class DecodeMetaData implements DecodeMetaDataInterface
{
    public function execute(string $metaData): array
    {
        $lineItems = explode(',', $metaData);
        $values = [];
        foreach ($lineItems as $lineItem) {
            [$key, $value] = explode('(', $lineItem);
            $values[$key] = rtrim($value, ')');
        }
        return $values;
    }
}
