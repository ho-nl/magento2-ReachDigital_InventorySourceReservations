<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservationsApi\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use ReachDigital\ISReservationsApi\Api\Data\SourceReservationInterface;

/**
 * Domain service used to append Source Reservations to keep track of quantity increments or deductions on the source
 * before they are actually delivered..
 *
 * Some use cases are:
 *
 * - an Order is assigned to a Source but not yet shipped.
 * - a Transfer Order is created to schedule new inventory
 *
 * @api
 */
interface AppendSourceReservationsInterface
{
    /**
     * Append reservations
     *
     * @param SourceReservationInterface[] $reservations
     * @return void
     * @throws InputException
     * @throws CouldNotSaveException
     */
    public function execute(array $reservations);
}
