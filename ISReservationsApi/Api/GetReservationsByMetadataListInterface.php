<?php

namespace ReachDigital\ISReservationsApi\Api;

use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;

interface GetReservationsByMetadataListInterface
{
    /**
     * @param string[] $metadataList
     * @return SourceReservationInterface[]
     */
    public function execute(array $metadataList): array;
}
