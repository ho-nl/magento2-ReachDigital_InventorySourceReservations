# Magento 2 Inventory Source Reservations

Ability to register reservations on sources. Allows us to defer the actual source deduction further by allowing us to
reserve qty's on the source.

Module structure:
- ISReservationsApi: Reservation Interfaces to CRUD reservations on top of sources.
- ISReservations: Implementation of the above interfaces.



- InventorySourceShipmentReservationApi
- InventorySourceShipmentReservation

- InventoryTransferApi
- InventoryTransfer

## Plan

### Read side
First we create a inventory_source_reservation table where source reservations can be appended to. When the
`inventory_stock_*` is indexed it will also add the reservation sums. This updates the aggregated stock with all the
source reservations.

We now have an acurate stock index with all the source reservations included. There are other parts that dont use the
indexed amount and we need to append the source reservation to the stock reservation: `GetReservationsQuantityInterface`

### Pending Shipments

When a shipment is created we need to deduct the qty from the reservation instead of the actual source.
SourceDeductionServiceInterface

`Magento\InventoryShipping\Observer\VirtualSourceDeductionProcessor` uses `SourceDeductionServiceInterface`
`Magento\InventoryShipping\Observer\SourceDeductionProcessor` uses `SourceDeductionServiceInterface`

We'll create a new implementation of the SourceDeductionServiceInterface to store the SourceDeductions.
How to handle? `ProcessBackItemQtyToSource` because it currently has a custom implementation.




### `GetReservationsQuantityInterface`

All places that use the GetReservationsQuantityInterface it can also add all the SourceReservations?

- `\Magento\InventorySales\Model\GetProductSalableQty`
- `\Magento\InventorySales\Model\IsProductSalableCondition\IsSalableWithReservationsCondition`
- `\Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsCorrectQtyCondition`
- `\Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsSalableWithReservationsCondition`

### `AppendReservationsInterface`

- `\Magento\InventorySales\Model\PlaceReservationsForSalesEvent` adapt to `PlaceSourceReservationsForShipmentEvent`
- `\Magento\InventorySales\Plugin\CatalogInventory\StockManagement\ProcessBackItemQtyPlugin` adapt to `Process`
- `\Magento\InventorySales\Plugin\CatalogInventory\StockManagement\ProcessRevertProductsSalePlugin`
- `\Magento\InventorySales\Plugin\InventoryReservationsApi\PreventAppendReservationOnNotManageItemsInStockPlugin`
- `\Magento\InventorySales\Model\ReturnProcessor\ProcessRefundItems`

