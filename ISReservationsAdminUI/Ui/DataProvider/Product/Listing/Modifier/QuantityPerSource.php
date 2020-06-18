<?php
declare(strict_types=1);
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */
namespace ReachDigital\ISReservationsAdminUI\Ui\DataProvider\Product\Listing\Modifier;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use ReachDigital\ISReservations\Model\ResourceModel\GetSourceReservationsQuantity;

/**
 * Quantity Per Source modifier on CatalogInventory Product Grid
 */
class QuantityPerSource extends
    \Magento\InventoryCatalogAdminUi\Ui\DataProvider\Product\Listing\Modifier\QuantityPerSource
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var GetSourceReservationsQuantity
     */
    private $getSourceReservationsQuantity;

    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        $isSourceItemManagementAllowedForProductType,
        SourceRepositoryInterface $sourceRepository,
        $getSourceItemsBySku,
        GetSourceReservationsQuantity $getSourceReservationsQuantity
    ) {
        parent::__construct(
            $isSingleSourceMode,
            $isSourceItemManagementAllowedForProductType,
            $sourceRepository,
            $getSourceItemsBySku
        );
        $objectManager = ObjectManager::getInstance();
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->isSourceItemManagementAllowedForProductType =
            $isSourceItemManagementAllowedForProductType ?:
            $objectManager->get(IsSourceItemManagementAllowedForProductTypeInterface::class);
        $this->sourceRepository = $sourceRepository;
        $this->getSourceItemsBySku = $getSourceItemsBySku ?: $objectManager->get(GetSourceItemsBySkuInterface::class);
        $this->getSourceReservationsQuantity = $getSourceReservationsQuantity;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        if (0 === $data['totalRecords'] || true === $this->isSingleSourceMode->execute()) {
            return $data;
        }

        foreach ($data['items'] as &$item) {
            $item['quantity_per_source'] =
                $this->isSourceItemManagementAllowedForProductType->execute($item['type_id']) === true
                    ? $this->getSourceItemsData($item['sku'])
                    : [];
        }
        unset($item);

        return $data;
    }

    /**
     * @param string $sku
     * @return array
     * @throws NoSuchEntityException
     */
    private function getSourceItemsData(string $sku): array
    {
        $sourceItems = $this->getSourceItemsBySku->execute($sku);

        $sourceItemsData = [];
        foreach ($sourceItems as $sourceItem) {
            $source = $this->sourceRepository->get($sourceItem->getSourceCode());
            $qty = (float) $sourceItem->getQuantity();

            $reservation = $this->getSourceReservationsQuantity->execute(
                $sourceItem->getSku(),
                $sourceItem->getSourceCode()
            );

            $sourceItemsData[] = [
                'source_name' => $source->getName(),
                'qty' => $qty . ($reservation ? " ($reservation)" : ''),
            ];
        }
        return $sourceItemsData;
    }
}
