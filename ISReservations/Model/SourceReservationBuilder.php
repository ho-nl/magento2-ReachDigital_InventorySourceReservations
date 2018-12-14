<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ReachDigital\ISReservations\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use ReachDigital\ISReservationsApi\Model\SourceReservationInterface;
use ReachDigital\ISReservationsApi\Model\SourceReservationBuilderInterface;

/**
 * @inheritdoc
 */
class SourceReservationBuilder implements SourceReservationBuilderInterface
{
    /**
     * @var int
     */
    private $source;

    /**
     * @var string
     */
    private $sku;

    /**
     * @var float
     */
    private $quantity;

    /**
     * @var string
     */
    private $metadata;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SnakeToCamelCaseConverter
     */
    private $snakeToCamelCaseConverter;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param SnakeToCamelCaseConverter $snakeToCamelCaseConverter
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        SnakeToCamelCaseConverter $snakeToCamelCaseConverter,
        ValidationResultFactory $validationResultFactory
    ) {
        $this->objectManager = $objectManager;
        $this->snakeToCamelCaseConverter = $snakeToCamelCaseConverter;
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function setSourceCode(string $sourceCode): SourceReservationBuilderInterface
    {
        $this->source = $sourceCode;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku): SourceReservationBuilderInterface
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setQuantity(float $quantity): SourceReservationBuilderInterface
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setMetadata(string $metadata = null): SourceReservationBuilderInterface
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(): SourceReservationInterface
    {
        /** @var ValidationResult $validationResult */
        $validationResult = $this->validate();
        if (!$validationResult->isValid()) {
            throw new ValidationException(__('Validation error'), null, 0, $validationResult);
        }

        $data = [
            SourceReservationInterface::RESERVATION_ID => null,
            SourceReservationInterface::SOURCE_CODE => $this->source,
            SourceReservationInterface::SKU => $this->sku,
            SourceReservationInterface::QUANTITY => $this->quantity,
            SourceReservationInterface::METADATA => $this->metadata,
        ];

        $arguments = $this->convertArrayKeysFromSnakeToCamelCase($data);
        $reservation = $this->objectManager->create(SourceReservationInterface::class, $arguments);

        $this->reset();

        return $reservation;
    }

    private function validate(): ValidationResult
    {
        $errors = [];

        if (null === $this->source) {
            $errors[] = __('"%field" is expected to be a number.', ['field' => SourceReservationInterface::SOURCE_CODE]);
        }

        if (null === $this->sku || '' === trim($this->sku)) {
            $errors[] = __('"%field" can not be empty.', ['field' => SourceReservationInterface::SKU]);
        }

        if (null === $this->quantity) {
            $errors[] = __('"%field" can not be null.', ['field' => SourceReservationInterface::QUANTITY]);
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Used to clean state after object creation
     * @return void
     */
    private function reset()
    {
        $this->source = null;
        $this->sku = null;
        $this->quantity = null;
        $this->metadata = null;
    }

    /**
     * Used to convert database field names (that use snake case) into constructor parameter names (that use camel case)
     * to avoid to define them twice in domain model interface.
     *
     * @param array $array
     * @return array
     */
    private function convertArrayKeysFromSnakeToCamelCase(array $array): array
    {
        $convertedArrayKeys = $this->snakeToCamelCaseConverter->convert(array_keys($array));
        return array_combine($convertedArrayKeys, array_values($array));
    }
}
