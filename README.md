WooCommerce Dev Helper
======================

This is a simple and opinionated plugin for helping develop/debug WooCommerce & extensions. DO NOT USE ON A PRODUCTION SERVER.

### Use Forwarded URLs

Once activated, this plugin will make WordPress use `HTTP_X_FORWARDED_HOST` or `HTTP_X_ORIGINAL_HOST` for all your links instead of your site URL. No configuration needed.

We like to use [NGrok](http://ngrok.com/) for this.

### Subscriptions (v1.5.x and v2.x.x)

Adds a "renew" link to each subscription under WooCommerce > Subscriptions for easy renewal processing. This is particularly useful for testing
payment gateways that supports subscriptions.

Allows for minutes and hours-long Subscription periods for quicker testing.

### Memberships

Allows for minutes and hours-long Membership lengths for quicker testing.

### Bogus Gateway

Adds a testing payment gateway that will call the `$order->payment_complete()` method to simulate a credit card payment. Can also be used for Subscriptions automatic renewals.

### Global Functions

* `wp_debug_backtrace()` - helper for using the `debug_backtrace()` function with a bit more sanity
* `wp_var_dump()` - helper for `var_dump`, allowing you to return the output instead of printing
* `wp_var_log()` - helper for `error_log` that uses `print_r()` or optionally `wp_var_dump()`
* `wp_print_r()` - helper for `print_r` that wraps the output in `<pre>` HTML tags
* `wc_dev_session` - JS helper to get all the current session data in console

### Misc

* Removes the WooCommmerce Updater notice
* Removes the strong password requirement for customer accounts
* Helper for logging actions/filters fired during a page load -- simply add `?wcdh_hooks=actions|filters|all`, reload the page, and your desired hooks will be printed to the error log, along with the fired count

## Installation

Download and install just like any other WordPress plugin. If you want to be really fancy, symlink it into your installs instead.

## Changelog

### 1.0.0 - 2018-11-06
 * Refactor - Use namespaces and rename classes
 * Tweak - Add support for ngrok
 * Fix - Ensure images load for products when using an https tunnel
 * Misc - Require PHP 5.3+

### 0.8.1 - 2017.12.13
 * Fix - Remove WC 3.3+ "Connect to WooCommerce" notice when official plugins are active

### 0.8.0 - 2017.07.22
 * Tweak - Remove dependency on WooCommerce
 * Tweak - Add support for domain forwarding as early as possible

### 0.7.0 - 2017.04.12
 * Feature - Use the Bogus gateway for Subscriptions automatic renewals
 * Fix - Subscriptions integration throwing a warning in WooCommerce 3.0+
 
### 0.6.0 - 2017.02.18
 * Feature - Adds a bogus gateway that calls `$order->payment_complete()` when used

### 0.5.0 - 2017.01.19
 * Feature - Dump the current session in AJAX to display in browser console

### 0.4.2 - 2016.10.21
 * Tweak - Filter the human access length for membership plans that have a length in minutes or hours set via this helper plugin (Memberships 1.7.2+)

### 0.4.1 - 2016.10.18
 * Fix - Minutes and hours-long periods in membership plans did not work properly or when creating a user membership in admin and setting the length from the membership plan default

### 0.4.0 - 2016.06.06
 * Feature - Added minutes and hours Subscription periods for quicker Subscriptions testing
 * Feature - Added Memberships support with minutes and hours Memberships periods for quicker access and dripping testing
 * Feature - Added `wp_print_r()` helper function

### 0.3.0 - 2015.12.28
 * Feature - Removes WooCommerce 2.5+ strong password requirement for customer registration

### 0.2.0 - 2015.09.04
 * Feature - Subscriptions 2.0 Compatibility
 * Fix - Fix is_ssl() when using Forward

### 0.1.0 - 2015.07.25
 * Initial Release
