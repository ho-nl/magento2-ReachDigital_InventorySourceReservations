<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use ReachDigital\ISReservations\Model\ResourceModel\GetSourceItemIdsFromReservations;
use ReachDigital\ISReservations\Model\ResourceModel\SaveMultiple;
use ReachDigital\ISReservationsApi\Model\ReservationInterface;
use ReachDigital\ISReservationsApi\Model\AppendReservationsInterface;
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
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var GetSourceItemIdsFromReservations
     */
    private $getSourceItemIdsFromReservations;

    /**
     * @param SaveMultiple $saveMultiple
     * @param LoggerInterface $logger
     * @param SourceItemIndexer $sourceItemIndexer
     * @param GetSourceItemIdsFromReservations $getSourceItemIdsFromReservations
     */
    public function __construct(
        SaveMultiple $saveMultiple,
        LoggerInterface $logger,
        SourceItemIndexer $sourceItemIndexer,
        GetSourceItemIdsFromReservations $getSourceItemIdsFromReservations
    ) {
        $this->saveMultiple = $saveMultiple;
        $this->logger = $logger;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->getSourceItemIdsFromReservations = $getSourceItemIdsFromReservations;
    }

    /**
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
            // @todo should we check if indexer is async?
//            $sourceItemIds = $this->getSourceItemIdsFromReservations->execute($reservations);
//            $this->sourceItemIndexer->executeList($sourceItemIds);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not append Reservation'), $e);
        }
    }
}
