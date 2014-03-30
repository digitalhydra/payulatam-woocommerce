=== Plugin Name ===
Contributors: digitalhydra
Donate link: http://www.thecodeisintheair.com/wordpress-plugins/
Tags: WooCommerce, Payment Gateway, PayU Latam, PayU Latinoamerica, Pagos en linea Colombia, Pagos en linea Latinoamerica
Requires at least: 3.7
Tested up to: 3.8.1
Stable tag: 1.1.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

PayU Latam Payment Gateway for WooCommerce. Recibe pagos en internet en latinoaméica desde cualquier parte del mundo.

== Description ==

WooCommerce is a powerful, extendable eCommerce plugin that helps you sell anything. Beautifully.

PayU Latam - la plataforma de procesamiento de pagos en linea de América Latina.

Both are now one of the best choices to start an eCommerece site in latinoamerica, fast and easy.
*   "WooCommerce" is an open source application
*   "PayU Latam" is offering payment collection with no setup cost.

Update:
Since PayU is updating their testing platform all testing transaccions would be rejected unless the user use the followings parameters in PayU payment form:
Credit card: VISA 
Credit card Number: 4111111111111111 
Client Name: "APPROVED"

Visit [www.thecodeisintheair.com](http://thecodeisintheair.com/wordpress-plugins/woocommerce-payu-latam-gateway-plugin/ " Code is in the Air : Woocommerce PayU Latam Gateway Plugin") for more info about this plugin.

== Installation ==

1. Ensure you have latest version of WooCommerce plugin installed (WooCommerce 2.0+)
2. Unzip and upload contents of the plugin to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Configuration ==

1. Visit the WooCommerce settings page, and click on the Payment Gateways tab.
2. Click on *PayU Latam* to edit the settings. If you do not see *PayU Latam* in the list at the top of the screen make sure you have activated the plugin in the WordPress Plugin Manager.
3. Enable the Payment Method, name it as you want (this will show up on the payment page your customer sees), add in your Merchant id, Account Id and ApiKey and select the redirect url(URL you want to redirect after payment). Click Save.

== Screenshots ==

1. WooCommerce > Payment Gateway > PayU Latam - setting page
2. Checkout Page - Option of Payment by *PayU Latam*
3. PayU Latam - ApiKey and Merchant Id
4. PayU Latam - Account Id
5. PayU Latam - Payment Platform
6. PayU Latam - Client Information Page
7. PayU Latam - Payment Process page


== Frequently Asked Questions ==

= Do I Need a activated account on payU latam to use this plugin?=

Yes, if you want to receive payments on the account, if you want to test the gateway there is no need for an account.

= What is the cost for transaction for PayU Latam? =

It may vary on each country, check payulatam.com to find out.

= Transaccions always rejected issue =
Since PayU is updating their testing platform all testing transaccions would be rejected unless the user use the followings parameters in PayU payment form.
Credit card: VISA 
Credit card Number: 4111111111111111 
Client Name: "APPROVED"

== Changelog ==

= 1.0 =
* First Public Release.

= 1.0.1 =
* Added some fallbacks for response codes.

= 1.0.2 =
* Added all currencys supported by the PayU Latam Platform.

= 1.0.3 =
* Set all currencys supported by the PayU Latam Platform by default and fix problem with currencys not showing in front end.

= 1.1 =
* Add spanish translation, and mo-po files for future translations.

= 1.1.1 =
* Add option to empty shopping cart after transaction completed.

= 1.1.2 =
* Add payment confirmation support for new accounts.

== Upgrade Notice ==

= 1.0.1 =
We added some fallbacks for response codes sended by payu gateway.

= 1.0.2 =
* Added all currencys supported by the PayU Latam Platform.

= 1.0.3 =
* Fix problem with currencys not showing in front end.

= 1.1 =
* Add spanish translation, and mo-po files for future translations.

= 1.1.1 =
* Add option to empty shopping cart after transaction completed.

= 1.1.2 =
* Add payment confirmation support for new accounts.