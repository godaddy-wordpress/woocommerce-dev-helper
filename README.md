WooCommerce Dev Helper
======================

This is a simple and opinionated plugin for helping develop/debug WooCommerce & extensions.

### Use Forwarded URLs

Once activated, this plugin will make WordPress use HTTP_X_FORWARDED_HOST for all your links instead of your site URL. No configuration needed.

We like [ForwardHQ](https://fwd.wf) for this, along with a `ForwardFile` in your WordPress root.

### Subscriptions (v1.5.x and v2.x.x)

Adds a "renew" link to each subscription under WooCommerce > Subscriptions for easy renewal processing. This is particularly useful for testing
payment gateways that supports subscriptions.

Allows minutes and hours-long Subscription periods for quicker testing.

### Memberships

Allows minutes and hours-long Membership lengths for quicker testing.

### Global Functions

* `wp_debug_backtrace()` - helper for using the `debug_backtrace()` function with a bit more sanity
* `wp_var_dump()` - helper for `var_dump`, allowing you to return the output instead of printing
* `wp_var_log()` - helper for `error_log` that uses `print_r()` or optionally `wp_var_dump()`
* `wp_print_r()` - helper for `print_r` that wraps the output in `<pre>` HTML tags

### Misc

* Removes the WooThemes Updater notice
* Removes the strong password requirement for customer accounts
* Helper for logging actions/filters fired during a page load -- simply add `?wcdh_hooks=actions|filters|all`, reload the page, and your desired hooks will be printed to the error log, along with the fired count

## Installation

Download and install just like any other WordPress plugin. If you want to be really fancy, symlink it into your installs instead.

## Changelog

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
