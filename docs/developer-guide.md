# Extend CCI Courier Cash on Delivery

Choose the smallest supported extension surface for the goal.

| Goal | Supported surface | Read first |
| --- | --- | --- |
| Change additional checkout or confirmation markup | Theme Smarty template override | This page |
| Check carrier eligibility in compatible module code | `isCarrierAllowed()` | [PHP API](./api-reference) |
| Read the dedicated order-state ID | `getOrderStateId()` | [PHP API](./api-reference) |
| Understand when payment is exposed | `paymentOptions` workflow | [Hooks reference](./hooks-reference) |
| React after an order is created | A standard PrestaShop order hook in a separate module | [Checkout and orders](./checkout-orders) |

## Template overrides

Copy only the template that must change into the active theme:

```text
themes/<theme>/modules/cci_couriercashondelivery/views/templates/hook/payment_options.tpl
themes/<theme>/modules/cci_couriercashondelivery/views/templates/hook/order_confirmation.tpl
```

Preserve Smarty escaping and the assigned variable names. The checkout template
receives `cci_courier_cod_description`. The confirmation template receives
`cci_courier_cod_label` and `cci_courier_cod_contact_url`.

## Payment boundary

Do not post directly to the module's validation controller. It is an internal
checkout endpoint that requires a valid cart and customer context. Do not create
orders by calling `validateOrder()` from display code.

The public constants `PAYMENT_LABEL` and `PAYMENT_DESCRIPTION` describe version
1.0.0 output, but changing installed source files is not a supported
customization. Use translations or a maintained custom module release when
labels must become configurable.

Version 1.0.0 dispatches no custom hooks and publishes no REST routes or Symfony
services.
