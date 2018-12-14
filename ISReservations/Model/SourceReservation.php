<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Model;

use ReachDigital\ISReservationsApi\Model\SourceReservationInterface;

/**
 * {@inheritdoc}
 *
 * @codeCoverageIgnore
 */
class SourceReservation implements SourceReservationInterface
{
    /**
     * @var int|null
     */
    private $reservationId;

    /**
     * @var int
     */
    private $sourceCode;

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
     * @param string $sourceCode
     * @param string $sku
     * @param float $quantity
     * @param null $metadata
     */
    public function __construct(
        $reservationId,
        string $sourceCode,
        string $sku,
        float $quantity,
        $metadata = null
    ) {
        $this->reservationId = $reservationId;
        $this->sourceCode = $sourceCode;
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
    public function getSourceCode(): string
    {
        return $this->sourceCode;
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
