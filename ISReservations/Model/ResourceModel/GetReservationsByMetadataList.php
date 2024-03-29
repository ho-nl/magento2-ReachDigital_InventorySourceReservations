<?php
declare(strict_types=1);
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservations\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use ReachDigital\ISReservations\Model\SourceReservation;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterfaceFactory;
use ReachDigital\ISReservationsApi\Api\GetReservationsByMetadataListInterface;

class GetReservationsByMetadataList implements GetReservationsByMetadataListInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var SourceReservationInterfaceFactory
     */
    private $sourceReservationInterfaceFactory;

    public function __construct(
        ResourceConnection $resourceConnection,
        SourceReservationInterfaceFactory $reservationFactory
    ) {
        $this->resource = $resourceConnection;
        $this->sourceReservationInterfaceFactory = $reservationFactory;
    }

    /**
     * @param string[] $matches
     * @return SourceReservation[]
     */
    public function execute(array $matches): array
    {
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_source_reservation');

        $select = $connection
            ->select()
            ->from($reservationTable, [
                SourceReservationInterface::RESERVATION_ID,
                SourceReservationInterface::SOURCE_CODE,
                SourceReservationInterface::SKU,
                SourceReservationInterface::QUANTITY,
                SourceReservationInterface::METADATA,
            ])
            ->where(SourceReservationInterface::METADATA . ' IN(?)', $matches);

        return array_map(function ($row): SourceReservationInterface {
            return $this->sourceReservationInterfaceFactory->create([
                'reservationId' => (int) $row[SourceReservationInterface::RESERVATION_ID],
                'sourceCode' => (string) $row[SourceReservationInterface::SOURCE_CODE],
                'sku' => (string) $row[SourceReservationInterface::SKU],
                'quantity' => (float) $row[SourceReservationInterface::QUANTITY],
                'metadata' => (string) $row[SourceReservationInterface::METADATA],
            ]);
        }, $connection->fetchAssoc($select));
    }
}
