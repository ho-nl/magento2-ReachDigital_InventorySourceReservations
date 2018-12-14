<?php
declare(strict_types=1);
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservations\Model\SourceReservation;
use ReachDigital\ISReservationsApi\Model\SourceReservationInterface;
use ReachDigital\ISReservationsApi\Model\ReservationInterfaceFactory;

class GetReservationsByMetadata
{

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ReservationInterfaceFactory
     */
    private $reservationFactory;

    public function __construct(
        ResourceConnection $resourceConnection,
        ReservationInterfaceFactory $reservationFactory
    ) {
        $this->resource = $resourceConnection;
        $this->reservationFactory = $reservationFactory;
    }

    /**
     * @param string $startsWith
     *
     * @return SourceReservation[]
     * @throws \DomainException
     */
    public function execute(string $startsWith) : array
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_source_reservation');

        $select = $connection->select()
            ->from($reservationTable, [
                SourceReservationInterface::RESERVATION_ID,
                SourceReservationInterface::SOURCE_CODE,
                SourceReservationInterface::SKU,
                SourceReservationInterface::QUANTITY,
                SourceReservationInterface::METADATA
            ])
            ->where(SourceReservationInterface::METADATA . ' LIKE ?', "{$startsWith}%");

        return array_map(function($row) : SourceReservationInterface {
            return $this->reservationFactory->create([
                'reservationId' => (int)$row[SourceReservationInterface::RESERVATION_ID],
                'sourceCode' => (string)$row[SourceReservationInterface::SOURCE_CODE],
                'sku' => (string)$row[SourceReservationInterface::SKU],
                'quantity' => (float)$row[SourceReservationInterface::QUANTITY],
                'metadata' => (string)$row[SourceReservationInterface::METADATA]
            ]);
        }, $connection->fetchAssoc($select));
    }
}
