# CCI Courier Cash on Delivery PHP API

Use these methods only after loading the installed and active module through
PrestaShop. Configuration persistence and carrier synchronization helpers are
private implementation details.

## `isCarrierAllowed()`

```php
public function isCarrierAllowed(int $idCarrier): bool
```

Checks whether a concrete carrier row belongs to a configured carrier reference.

- `$idCarrier`: `int`; current PrestaShop carrier ID, not `id_reference`.
- Returns: `bool`; `true` only when the carrier loads and its reference is in
  the module configuration.
- Side effects: loads the carrier; it does not change configuration.

```php
$module = Module::getInstanceByName('cci_couriercashondelivery');
$allowed = $module instanceof Cci_Couriercashondelivery
    && $module->active
    && $module->isCarrierAllowed((int) $cart->id_carrier);
```

## `getOrderStateId()`

```php
public function getOrderStateId(): int
```

Reads the dedicated courier-COD order-state ID.

- Parameters: none.
- Returns: `int`; a positive `id_order_state` after a valid installation, or
  `0` when configuration is missing.
- Side effects: none.

```php
$module = Module::getInstanceByName('cci_couriercashondelivery');
$stateId = $module instanceof Cci_Couriercashondelivery
    ? $module->getOrderStateId()
    : 0;

if ($stateId > 0) {
    $state = new OrderState($stateId, (int) Context::getContext()->language->id);
}
```

## Public constants

| Constant | Type | Version 1.0.0 value |
| --- | --- | --- |
| `Cci_Couriercashondelivery::PAYMENT_LABEL` | `string` | `Płatność za pobraniem - zapłać u kuriera` |
| `Cci_Couriercashondelivery::PAYMENT_DESCRIPTION` | `string` | `Zapłać gotówką przy odbiorze` |

Hook handlers are documented by workflow in [Hooks reference](./hooks-reference).
They are invoked by PrestaShop and should not be called as general-purpose
service methods.
