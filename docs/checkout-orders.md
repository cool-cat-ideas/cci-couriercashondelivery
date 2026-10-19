# Courier COD checkout and order behavior

## Payment availability

The dedicated option appears only when:

- checkout supplies a valid `Cart`;
- the cart is not virtual;
- the selected carrier loads correctly;
- the carrier's `id_reference` is in the module configuration;
- PrestaShop reports the payment module as available for the current checkout.

Changing the carrier causes checkout to recalculate payment options. A carrier
not assigned to this module receives no option from it.

## Validation

The module's secure validation controller rechecks the cart, customer, delivery
and invoice addresses, carrier assignment, module availability and dedicated
order-state ID. It then calls PrestaShop's standard `validateOrder()` flow.

The created order stores:

- module name `cci_couriercashondelivery`;
- payment value `Płatność za pobraniem - zapłać u kuriera`;
- the full cart total in the cart currency;
- the dedicated unpaid courier-COD order state.

After creation, the customer is redirected to PrestaShop's normal order
confirmation URL with the cart, module, order and customer secure-key values.

## Order status

Installation creates an unpaid, non-loggable, non-shipped status. Polish shop
languages receive `Oczekiwanie na płatność za pobraniem u kuriera`; other
languages receive `Awaiting courier cash on delivery`.

The status remains installed when the module is uninstalled to preserve
historical order integrity.

## Confirmation message

`displayOrderConfirmation` renders the extra courier-COD message only when the
confirmed order belongs to this module. It includes the payment label and a link
to the shop's standard contact page.
