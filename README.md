# Magento 2 Inventory Source Reservations

Ability to register reservations on sources. Allows us to defer the actual
source deduction further by allowing us to reserve qty's on the source.

This allows us to create other functionalities on top:

- [https://github.com/ho-nl/magento2-ReachDigital_InventoryOrderSourceReservations](ReachDigital_InventoryOrderSourceReservations) Introduce source reservation for all orders placed.
- [https://github.com/ho-nl/magento2-ReachDigital_ShConnector](ReachDigital_ShConnector) Specific warehouse integration.
- [https://github.com/ho-nl/magento2-ReachDigital-TransferOrdersES] Reserve incoming inventory

## API

- [AppendReservations](https://github.com/ho-nl/magento2-ReachDigital_InventorySourceReservations/blob/master/ISReservationsApi/Model/AppendReservationsInterface.php):
  Allows us to append new reservations for a given SKU.
- [GetSourceReservationsQuantity](https://github.com/ho-nl/magento2-ReachDigital_InventorySourceReservations/blob/master/ISReservationsApi/Model/GetSourceReservationsQuantityInterface.php)
  Allows us to get the reservations for a certain source.

## Indexer

First we create an inventory*source_reservation table where source reservations
can be appended to. When the `inventory_stock*\*` is indexed it will also add
the source reservation sums. This updates the aggregated stock ('salable qty')
with all the source reservations.

We now have an accurate stock index with all the source reservations included.
There are other parts that don't use the indexed amount, and we need to append the
source reservation to the stock reservation:
[GetSourceReservationsQuantityInterface](https://github.com/ho-nl/magento2-ReachDigital_InventorySourceReservations/blob/master/ISReservationsApi/Model/GetSourceReservationsQuantityInterface.php)

## Commits

A commit is validated with https://github.com/conventional-changelog/commitlint

Gittower: Gittower doesn't properly read your PATH variable and thus commit
validation doesn't work. Use `gittower .` to open this repo.
