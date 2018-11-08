<?php
declare(strict_types=1);
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservations\Model\Reservation;
use ReachDigital\ISReservationsApi\Model\ReservationInterface;
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
     * @return Reservation[]
     * @throws \DomainException
     */
    public function execute(string $startsWith) : array
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_source_reservation');

        $select = $connection->select()
            ->from($reservationTable, [
                ReservationInterface::RESERVATION_ID,
                ReservationInterface::SOURCE_CODE,
                ReservationInterface::SKU,
                ReservationInterface::QUANTITY,
                ReservationInterface::METADATA
            ])
            ->where(ReservationInterface::METADATA . ' LIKE ?', "{$startsWith}%");

        return array_map(function($row) : ReservationInterface {
            return $this->reservationFactory->create([
                'reservationId' => (int) $row[ReservationInterface::RESERVATION_ID],
                'sourceCode' => (string) $row[ReservationInterface::SOURCE_CODE],
                'sku' => (string) $row[ReservationInterface::SKU],
                'quantity' => (float) $row[ReservationInterface::QUANTITY],
                'metadata' => (string) $row[ReservationInterface::METADATA]
            ]);
        }, $connection->fetchAssoc($select));
    }
}
