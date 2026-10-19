# Fix common courier cash-on-delivery problems

## The payment is missing

Check these points in order:

1. The cart contains at least one physical product.
2. The module is enabled.
3. The carrier is turned on in this module's settings.
4. The current checkout still uses that carrier. Start a new cart after changing delivery settings.
5. The payment is allowed for the cart's country, currency, customer group and shop.

Clear the PrestaShop cache and test again with a fresh cart.

## Both cash-on-delivery choices appear

Open this module's settings and save the carrier list again. Then check payment restrictions for this module and `ps_cashondelivery`. Do not turn both modules on manually for the same carrier.

## The settings will not save

Select at least one carrier. If saving still fails, check the PrestaShop log for the first database error. The module tries to restore the previous carrier list when synchronization fails.

## Checkout returns to delivery selection

Create a new cart and enter the customer and address data again. This usually means the cart is stale, virtual, missing an address, or uses a carrier that is no longer allowed.

## The order shows another payment name

Check when the order was created. The module writes its payment name only on new orders placed through `cci_couriercashondelivery`. It does not rename old orders created by `ps_cashondelivery` or another module.

## The order status is missing

Do not assign a random status to production orders. Restore the database backup or reproduce the installation on staging, then check the PrestaShop log for the error that prevented the status from being created.
