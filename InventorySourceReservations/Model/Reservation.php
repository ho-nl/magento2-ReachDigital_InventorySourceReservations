<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\InventorySourceReservations\Model;

use ReachDigital\InventorySourceReservationsApi\Model\ReservationInterface;

/**
 * {@inheritdoc}
 *
 * @codeCoverageIgnore
 */
class Reservation implements ReservationInterface
{
    /**
     * @var int|null
     */
    private $reservationId;

    /**
     * @var int
     */
    private $sourceId;

    /**
     * @var string
     */
    private $sku;

    /**
     * @var float
     */
    private $quantity;

    /**
     * @var string|null
     */
    private $metadata;

    /**
     * @param int|null $reservationId
     * @param int $sourceId
     * @param string $sku
     * @param float $quantity
     * @param null $metadata
     */
    public function __construct(
        $reservationId,
        int $sourceId,
        string $sku,
        float $quantity,
        $metadata = null
    ) {
        $this->reservationId = $reservationId;
        $this->sourceId = $sourceId;
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->metadata = $metadata;
    }

    /**
     * @inheritdoc
     */
    public function getReservationId(): ?int
    {
        return $this->reservationId;
    }

    /**
     * @inheritdoc
     */
    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @inheritdoc
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): ?string
    {
        return $this->metadata;
    }
}
