# CCI Courier Cash on Delivery hooks reference

Version 1.0.0 consumes two standard PrestaShop hooks. They form one order flow:
`paymentOptions` presents and validates the payment choice, then
`displayOrderConfirmation` adds information after order creation.

## Checkout stage: `paymentOptions`

Handled by `hookPaymentOptions(array $params): array`.

- Required parameter: `$params['cart']`, a valid `Cart` instance.
- Returns: `list<PaymentOption>` containing one option for an allowed,
  non-virtual cart, otherwise an empty list.
- Side effects: assigns `cci_courier_cod_description` to Smarty and renders the
  additional-information template.
- Payment action: the module's HTTPS `validation` controller URL.

PrestaShop invokes this hook while collecting checkout payment choices:

```php
$options = Hook::exec('paymentOptions', ['cart' => $cart], null, true);
```

Normal checkout code should let PrestaShop perform this collection. A custom
checkout must pass the current cart and retain the returned `PaymentOption`
action rather than replacing it with a hand-built URL.

## Confirmation stage: `displayOrderConfirmation`

Handled by `hookDisplayOrderConfirmation(array $params): string`.

- Accepted order parameter: `$params['objOrder']` or `$params['order']`, an
  existing `Order` instance.
- Returns: the courier-COD confirmation HTML when `Order::$module` equals
  `cci_couriercashondelivery`; otherwise an empty string.
- Side effects: assigns the payment label and HTTPS contact-page URL to Smarty.

Themes normally render the standard hook through the checkout confirmation
page. Custom confirmation layouts must preserve that hook if they need the
module's message.

The module does not dispatch custom hooks in version 1.0.0.
