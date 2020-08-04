<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);
namespace ReachDigital\ISReservations\Model\MetaData;

use InvalidArgumentException;
use ReachDigital\ISReservationsApi\Api\EncodeMetaDataInterface;
use function in_array;

class EncodeMetaData implements EncodeMetaDataInterface
{
    /**
     * @param array $data
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function execute(array $data): string
    {
        $pieces = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                $pieces[] = $key;
            } else {
                if (!in_array(gettype($value), ['boolean', 'integer', 'double', 'string'])) {
                    throw new InvalidArgumentException('Only strings and scalar types supported');
                }
                $pieces[] = "$key($value)";
            }
        }
        return implode(',', $pieces);
    }
}
