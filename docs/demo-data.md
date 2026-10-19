# Test courier COD safely

The module does not seed carriers or orders. Use a staging shop and ordinary
PrestaShop test catalog data.

## Minimal test matrix

1. Create or identify one active courier carrier and one different delivery
   method.
2. Configure only the courier carrier in this module.
3. Add a low-value physical test product to a test customer's cart.
4. Select the courier carrier and confirm the dedicated payment option appears.
5. Place the order and record only the test order ID, module name, payment label
   and status.
6. Start a second checkout with the unselected carrier and confirm the dedicated
   option is absent; verify standard COD according to its own configuration.
7. Test a virtual-only cart and confirm courier COD is absent.

Never use a real customer's account, address or order for this verification.
Cancel or delete test orders according to the staging environment's data policy.

Resetting the module means selecting the intended carrier set and saving again.
There is no demo-data reset command.
