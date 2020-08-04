<?php

namespace ReachDigital\ISReservationsApi\Api;

interface DecodeMetaDataInterface
{
    /**
     * @param string $metaData
     * @return string[]
     */
    public function execute(string $metaData): array;
}
