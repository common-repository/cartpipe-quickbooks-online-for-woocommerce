=== Plugin Name ===
Contributors: Cartpipe
Donate Link: 
Tags: woocommerce, quickbooks, xero, freshbooks, accounting, ecommerce, integrations
Requires at least: 3.0.1
Tested up to: 4.4
Stable tag: 1.1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cartpipe QuickBooks Online Integration for WooCommerce

== Description ==

Cartpipe QuickBooks Online Integration for WooCommerce is the easy-to-use, easy-to-configure QuickBooks Integration you've been looking for. 
Once installed, the plugin will:

*	Send WooCommerce customers to QuickBooks
*	Sync website prices with the values from QuickBooks
*	Sync website on-hand qty with QuickBooks (requires Standard or Premier)
*	Send orders from the website to QuickBooks as sales receipts or invoices (requires Standard or Premier)
*	Import items from QuickBooks into WooCommerce (requires Premier)
*	Export items from QuickBooks into WooCommerce (requires Premier)

Once setup, you can schedule the plugin to run on a schedule or manually. In order to connect WooCommerce with QuickBooks Online, you need a [Cartpipe account](https://www.cartpipe.com/services/quickbooks-online-integration/ "QuickBooks Online Integration for WooCommerce"). Cartpipe Basic is free and if you need extended functionality, you can sign-up for either our Standard or Premium Services


== Installation ==

Installation / configuration is straightforward. 

1. Install Cartpipe either via the plugin installing in your wordpress admin, or by uploading the files to your server and extracting.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Cartpipe > QuickBooks Online Settings
1. Enter your Cartpipe Consumer Key
1. Enter your Cartpipe Consumer Secret
1. Enter your Cartpipe License Key
1. Click the Activate button
1. From within your [Cartpipe.com account](https://www.cartpipe.com/account/ "Cartpipe Account"), click the Connect to QuickBooks button. This connects the plugin to your QuickBooks Online account

Once you've gone through those steps, the connection's been made. For product info syncing to occur, you just need to make sure that the WooCommerce sku matches the Item Name in QuickBooks. 
Customer's auto-map to QuickBooks and sending receipts just requires that you specify a few accounts to use in the pluging, otherwise, that's it. 

Do you need a free account or are interested in upgrading to Standard or Premier? Click [here](https://www.cartpipe.com/services/quickbooks-online-integration/ "QuickBooks Online Integration for WooCommerce") to be taken to Cartpipe.com 

== Frequently Asked Questions ==

= Do I need an account on Cartpipe.com? =

Yes. You need to have an account to get your Consumer Key, Consumer Secret and License Key. This info allows us to authenticate you and communicate with QuickBooks. Don't worry as a Cartpipe Basic account is free and we offer a 14-day free trial on the Standard account. 

= Where can I signup for Cartpipe? =

You can signup for a Free Cartpipe Account [here](https://www.cartpipe.com/services/quickbooks-online-integration/ "QuickBooks Online Integration for WooCommerce")

= Do I need an QuickBooks Online account? =

Yes. You need to have an account with Intuit for QuickBooks Online. Using Cartpipe does not create a QuickBooks Online account for you.

= Does this work with desktop versions of QuickBooks? =
No. It only works with QuickBooks Online



== Screenshots ==

1. Cartpipe settings
2. Product sync settings 
3. Order transfer settings

== Changelog ==
= 1.1.7 =
*Add explicit enqueue of WP heartbeat js 

= 1.1.6 =
* Update for product meta-box js and order metabox js. Only enqueue on respective pages. Was causing "reset password" from disappearing on edit user screen in admin 

= 1.1.5 =
* Fix for variations not transferring on sales receipts and not syncing 

= 1.1.4 =
* Fix - add missing space to _e() function on setup wizard 


= 1.1.3 =
* Fix - wc_product lookup id during sync 

= 1.1.2 =
* Fix - wc_product instant on inv sync 


= 1.1.1 =
* Change * 
Seperate product sync requests into individual requests

= 1.1.0 =
* Improvement * 
Setup Wizard
Cartpipe Account Creation
QuickBooks Online Connection

* Enhancement *
Order PDFs

= 1.0.26 =
* Fix * 
0 qty stock updates


= 1.0.25 =
* Fix * 
No Priv heartbeat enqueuing

= 1.0.24 =
* Updates * 
1. Rework Item Imports into WooCommerce from QuickBooks
2. Import QuickBooks Images into WooCommerce
3. Rework Item Exports from WooCommerce into QuickBooks
4. Change location of Import / Export buttons on the Cartpipe Settins pages 

= 1.0.23 =
* Fix * Restore license checks 

= 1.0.22 =
* Update * add settings for Import / Export options 

= 1.0.20 =
* Fix * send subtotal through on invoice line items instead of total 

= 1.0.19 =
* Restore Discount functionality 

= 1.0.14 =
* Include support for refunds in WooCommerce 

= 1.0.13 =
* Instantiate API Client in the wizard

= 1.0.12 =
* Include Setup Wizard

= 1.0.11 =
* Extend QB CA support to Invoices

= 1.0.10 =
* Support QuickBooks CA

= 1.0.9 =
* Formatting

= 1.0.8 =
* First release

== Upgrade Notice ==
Initial Release