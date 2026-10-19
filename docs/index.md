# CCI Courier Cash on Delivery 1.0.0

Use this module when customers may pay the courier at the door. You decide which carriers offer that payment. Other delivery methods can keep using PrestaShop's standard payment-on-receipt option.

## Set up courier cash on delivery

1. Note which carriers currently use the standard `ps_cashondelivery` module.
2. Install `cci_couriercashondelivery-1.0.0.zip`.
3. Open **Cool Cat Ideas > CCI Courier Cash on Delivery**.
4. Turn on every courier that collects payment at delivery.
5. Save the settings.
6. Place one test order with a selected courier and one with an unselected delivery method.

For a selected courier, the customer should see the courier cash-on-delivery choice. The new order should keep that payment name and use the module's waiting-for-courier-payment status.

The module works without a license key. It changes new checkout choices. It does not rename payments saved on old orders.

## More help

- [Installation](./installation)
- [Choose the carriers](./configuration)
- [Checkout and orders](./checkout-orders)
- [Troubleshooting](./troubleshooting)
- [Support](./support)
- [Developer guide](./developer-guide)
- [PHP API](./api-reference)
- [Hooks reference](./hooks-reference)
- [Demo data](./demo-data)
