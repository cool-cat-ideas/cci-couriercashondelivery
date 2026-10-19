# Install CCI Courier Cash on Delivery

This module moves selected carriers from PrestaShop's standard cash-on-delivery payment to the courier payment. Start on a test shop or staging copy.

Download the module ZIP from the [Cool Cat Ideas product page](https://coolcatideas.com/products/cci-courier-cash-on-delivery/).

## Before you start

1. Make a database backup that you can restore.
2. Open the payment restrictions for `ps_cashondelivery`.
3. Note which carriers currently use it.

## Install and check the module

1. Open **Modules > Module Manager > Upload a module**.
2. Upload `cci_couriercashondelivery-1.0.0.zip`.
3. Open **Cool Cat Ideas > CCI Courier Cash on Delivery**.
4. Turn on at least one carrier and save.
5. Add a physical product to a new cart.
6. Choose the selected carrier and continue to payment.
7. Confirm that the courier payment appears.
8. Repeat the checkout with an unselected delivery method and confirm that the courier payment is hidden.

## If the payment does not appear

Check the carrier switch first. Then check the usual PrestaShop payment restrictions for country, customer group, currency and shop. Start a new cart after changing a carrier.

## Update or remove the module

After an update, open the settings, save the carrier list again and repeat both checkout tests.

During uninstall, the module returns its current carrier list to `ps_cashondelivery`. The order status stays in the database so old orders keep a valid history. Compare the restored carrier list with the note you made before installation.
