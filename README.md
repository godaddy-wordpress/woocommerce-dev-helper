WooCommerce Dev Helper
======================

This is a simple and opinionated plugin for helping develop/debug mainly WooCommerce & extensions, but can be used in any WordPress project. 

**DO NOT USE ON A PRODUCTION SERVER.**

### Use Forwarded URLs

Once activated, this plugin will make WordPress use `HTTP_X_FORWARDED_HOST` for all your links instead of your site URL. No configuration needed.

We like [ForwardHQ](https://fwd.wf) for this, along with a `ForwardFile` in your WordPress root.

### WooCommerce Subscriptions

* Adds a "renew" link to each subscription in the WooCommerce > Subscriptions admin screen for easy renewal processing. This is particularly useful for testing payment gateways that supports subscriptions.

* Allows for minutes and hours-long Subscription periods for quicker testing.

### WooCommerce Memberships

* Allows for minutes and hours-long Membership lengths for quicker testing.

### Bogus Gateway

* Adds a testing payment gateway that will call the `$order->payment_complete()` method to simulate a credit card payment. Can also be used for Subscriptions automatic renewals.

### Global Functions

#### PHP

* `wp_debug_backtrace()` - helper for using the `debug_backtrace()` function with a bit more sanity
* `wp_var_dump()` - helper for `var_dump`, allowing you to return the output instead of printing
* `wp_var_log()` - helper for `error_log` that uses `print_r()` or optionally `wp_var_dump()`
* `wp_print_r()` - helper for `print_r` that wraps the output in `<pre>` HTML tags

#### JavaScript

* `wc_dev_session` - fetches and logs all the current session data in console

### Misc

* Removes the WooCommerce Updater notice.
* Removes the strong password requirement for customer accounts.
* Adds support for an HTTP helper for logging actions/filters fired during a page load -- simply add `?wcdh_hooks=actions|filters|all`, reload the page, and your desired hooks will be printed to the error log, along with the fired count.

### Installation

Download and install just like any other WordPress plugin. If you want to be really fancy, symlink it into your installs instead.
