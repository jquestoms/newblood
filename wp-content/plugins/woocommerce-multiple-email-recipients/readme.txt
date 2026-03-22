=== WooCommerce Multiple Email Recipients ===
Contributors: barn2media
Tags: woocommerce, email
Requires at least: 6.1
Tested up to: 6.7.1
Requires PHP: 7.4
Stable tag: 1.2.12
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Stores multiple email addresses for each customer.

== Description ==

Stores multiple email addresses for each customer.

== Installation ==

1. Go to Plugins -> Add New -> Upload and select the plugin ZIP file (see link in Purchase Confirmation Email).
2. Activate the plugin
3. Enter your license key under WooCommerce -> Settings -> Products -> Multiple Email Recipients
4. Set your login page and enter a password

== Changelog ==

= 1.2.12 =
Release date 12 December 2024

* Dev: Updated the internal libraries
* Dev: Tested up to WordPress 6.7.1 and WooCommerce 9.4.3
* Fix: Loaded the email services with the correct priority
* Fix: Loaded the assets only on the WMER pages

<!--more-->

= 1.2.11 =
Release date 30 July 2024

* Dev: Tested up to WordPress 6.6 and WooCommerce 9.1.4
* Dev: Updated the internal libraries
* Dev: Updated the classes to use barn2-lib 2
* Fix: Wrong number of emails when counting user email

= 1.2.10 =
Release date 27 September 2023

* Fix: Additional emails don't save for orders

= 1.2.9 =
Release date 06 September 2023

* Dev: Tested up to WordPress 6.3.1 and WooCommerce 8.0.3
* Dev: Added support for WooCommerce HPOS
* Dev: Upgrade to the composer version of barn2-lib
* Fix: Added syncing of order emails from customer

= 1.2.8 =
Release date 12 May 2023

* Tested up to WordPress 6.2.0 and WooCommerce 7.7.0
* Dev: Updated webpack configuration

= 1.2.7 =
Release date 18 October 2022

* Fix: Additional recipients not being displayed in admin emails
* Dev: Tested for compatibility with WooCommerce 7.0.0

= 1.2.6 =
Release date 10 October 2022

* Fix: Fatal error being triggered when rules include a product that was deleted
* Dev: Tested for compatibility with WordPress 6.0.2 and WooCommerce 6.9.4

= 1.2.5 =
Release date 21 March 2022

* Fix: Additional emails are not being sent when order is created from the administrative area
* Dev: Tested for compatibility with WordPress 5.9.2 and WooCommerce 6.3.1

= 1.2.4 =
Release date 25 February 2022

* Fix: Additional email not being sent when checkout options is disabled
* Fix: Additional emails stored with incorrect order ID in some situations

= 1.2.3 =
Release date 23 February 2022

* Fix: Some fields do not get stored properly when saving WooCommerce emails

= 1.2.2 =
Release date 23 February 2022

* Fix: Resolved a fatal error triggered by admin email events
* Tweak: Improved compatibility with plugins that add custom emails
* Dev: Tested for compatibility with WooCommerce 6.2

= 1.2.1 =
Release date 26 January 2022

* Fix: Resolved an issue with additional recipients not available on admin emails when disabling customer emails
* Dev: Tested for compatibility with WordPress 5.9 and WooCommerce 6.1.1

= 1.2 =
Release date 22 November 2021

* New: Additional recipients to the admin emails, with optional category and product filters.
* Dev: Tested for compatibility with WordPress 5.8.2 and WooCommerce 5.9.

= 1.1.3 =
Release date 20 July 2021

* Dev: Tested for compatibility with WordPress 5.8 and WooCommerce 5.5.

= 1.1.2 =
Release date 14 June 2021

* Fix: Resolved issue where deleting an email during checkout would still send to the email address on file.
* Fix: Resolved issue where 'New Account' notification emails were not being sent to additional email addresses specified during checkout.

= 1.1.1 =
Release date 3 February 2021

* Fix: Resolved problem with additional emails not being stored/sent during guest checkout.
* Fix: Resolved issue with the manual "Customer invoice / Order details" email not sending to additional emails.

= 1.1 =
Release date 21 January 2021

* New: Options to change the field label for each additional email address.
* Dev: Tested compatibility with WordPress 5.6 and Woocommerce 5.9.

= 1.0.2 =
Release date 8 November 2020

* Dev: Updated composer to fix conflict with WooCommerce Private Store plugin.

= 1.0.1 =
Release date 21 September 2020

* Tweak: Added missing term to .pot file for translations.

= 1.0 =
Release date 23 June 2020

* Initial release.
