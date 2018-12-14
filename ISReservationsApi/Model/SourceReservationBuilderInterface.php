<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservationsApi\Model;

use Magento\Framework\Validation\ValidationException;

/**
 * Used to build ReservationInterface objects
 *
 * @api
 * @see SourceReservationInterface
 */
interface SourceReservationBuilderInterface
{
    public function setSourceCode(string $sourceCode): self;

    public function setSku(string $sku): self;

    public function setQuantity(float $quantity): self;

    public function setMetadata(string $metadata = null): self;

    /**
     * @return SourceReservationInterface
     * @throws ValidationException
     */
    public function build(): SourceReservationInterface;
}
