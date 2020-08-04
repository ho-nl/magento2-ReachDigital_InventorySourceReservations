<?php

namespace ReachDigital\ISReservationsApi\Api;

use DomainException;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;

interface GetReservationsByMetadataInterface
{
    /**
     * @param string $startsWith
     *
     * @return SourceReservationInterface[]
     * @throws DomainException
     */
    public function execute(string $startsWith): array;
}
