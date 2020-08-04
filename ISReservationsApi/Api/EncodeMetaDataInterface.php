<?php

namespace ReachDigital\ISReservationsApi\Api;

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
