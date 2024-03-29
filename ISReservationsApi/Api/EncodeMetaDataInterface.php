<?php

namespace ReachDigital\ISReservationsApi\Api;

use InvalidArgumentException;

interface EncodeMetaDataInterface
{
    /**
     * @param array $data
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function execute(array $data): string;
}
