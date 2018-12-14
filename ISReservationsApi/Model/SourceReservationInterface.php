<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservationsApi\Model;

/**
 * The entity responsible for reservations, created to keep inventory amount (product quantity) up-to-date.
 * It is created to have a state between order creation and inventory deduction (deduction of specific SourceItems).
 *
 * Reservations are designed to be immutable entities.
 *
 * @api
 */
interface SourceReservationInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const RESERVATION_ID = 'reservation_id';
    public const SOURCE_CODE = 'source_code';
    public const SKU = 'sku';
    public const QUANTITY = 'quantity';
    public const METADATA = 'metadata';

    public function getReservationId(): ?int;

    public function getSourceCode(): string;

    public function getSku(): string;

    /**
     * Get Product Qty
     *
     * This value can be positive (>0) or negative (<0) depending on the Reservation semantic.
     *
     * For example, when an Order is assigned to a source, a Reservation with negative quantity is appended.
     * When that Order is processed and the SourceItems related to ordered products are updated, a Reservation with
     * positive quantity is appended to neglect the first one.
     *
     * @return float
     */
    public function getQuantity(): float;

    /**
     * Get Reservation Metadata
     *
     * Metadata is used to store serialized data that encapsulates the semantic of a Reservation.
     *
     * @return string|null
     */
    public function getMetadata(): ?string;
}
