# CCI Courier Cash on Delivery support

## Choose the right support channel

We are happy to help if a step is unclear or something is not working as expected. You can describe the situation in your own words.

For a private question, order or licence issue, use [Services and support](https://coolcatideas.com/en/account/services/) in your Cool Cat Ideas account:

1. Sign in, or create an account if you do not have one.
2. Under **Contact the team**, choose **General question**. If your licence includes active technical support, you can instead choose **Technical support for a licence** and select the relevant **Licence**.
3. Enter a subject and describe the issue. A screenshot and the installed product version can help. Please leave out passwords, licence keys and customer data.
4. Click **Send message**. Your conversation opens after submission, and you can return to it under **Your conversations** on the same page.

You can send a general question without an active support licence. For an installation or technical intervention you already purchased, open the item under **Purchased services** and use its linked conversation.

For an unclear documentation step or a broken link, use **Report an issue** on the [online documentation page](https://coolcatideas.com/en/docs/cci-courier-cash-on-delivery/1.0.0/support/). It opens the documentation feedback form. To report a security vulnerability, please [email info@coolcatideas.com](mailto:info@coolcatideas.com) rather than posting the details publicly.

Never include passwords, cart keys, access tokens, customer data or production database exports in a public post.

Describe the payment option you expected and what appeared instead. Include
any of these details you know; you can ask for help without collecting them
all. If you repeat a payment test, use a staging copy and a test cart:

- module version (`1.0.0` for this documentation);
- exact PrestaShop and PHP versions;
- affected shop in a multistore installation;
- current cart carrier ID and its `id_reference`;
- configured courier carrier references;
- whether `ps_cashondelivery` is installed and enabled;
- expected and actual payment options;
- for a test order only: order ID, `Order::$module`, saved payment label and
  order-state ID;
- relevant PrestaShop log messages and exact reproduction steps.

Do not send customer names, addresses, cart secure keys, database dumps or
production credentials in an ordinary support message. Use a dedicated test
customer and temporary least-privilege access only through an approved secure
support process.

The module works without a license key. Bespoke carrier rules,
historical order migrations and checkout-theme adaptations are separate
development work.
