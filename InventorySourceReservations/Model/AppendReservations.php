<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\InventorySourceReservations\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use ReachDigital\InventorySourceReservations\Model\ResourceModel\SaveMultiple;
use ReachDigital\InventorySourceReservationsApi\Model\ReservationInterface;
use ReachDigital\InventorySourceReservationsApi\Model\AppendReservationsInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class AppendReservations implements AppendReservationsInterface
{
    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SaveMultiple $saveMultiple
     * @param LoggerInterface $logger
     */
    public function __construct(
        SaveMultiple $saveMultiple,
        LoggerInterface $logger
    ) {
        $this->saveMultiple = $saveMultiple;
        $this->logger = $logger;
    }

    /**
     * @todo Run indexer when a reservation has been made.
     * @inheritdoc
     */
    public function execute(array $reservations)
    {
        if (empty($reservations)) {
            throw new InputException(__('Input data is empty'));
        }

        /** @var ReservationInterface $reservation */
        foreach ($reservations as $reservation) {
            if (null !== $reservation->getReservationId()) {
                $message =  __(
                    'Cannot update Reservation %reservation',
                    ['reservation' => $reservation->getReservationId()]
                );
                $this->logger->error($message);
                throw new InputException($message);
            }
        }
        try {
            $this->saveMultiple->execute($reservations);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not append Reservation'), $e);
        }
    }
}
