WooCommerce Dev Helper
======================

This is a simple and opinionated plugin for helping develop/debug WooCommerce & extensions.

### Use Forwarded URLs

Once activated, this plugin will make WordPress use HTTP_X_FORWARDED_HOST for all your links instead of your site URL. No configuration needed.

We like [ForwardHQ](https://fwd.wf) for this, along with a `ForwardFile` in your WordPress root.

### Subscriptions (v1.5.x)

Adds a "renew" link to each subscription under WooCommerce > Subscriptions for easy renewal processing. This is particularly useful for testing
payment gateways that supports subscriptions.

### Global Functions

* `wp_debug_backtrace()` - helper for using the `debug_backtrace()` function with a bit more sanity
* `wp_var_dump()` - helper for `var_dump`, allowing you to return the output instead of printing
* `wp_var_log()` - helper for `error_log` that uses `print_r()` or optionally `wp_var_dump()`

### Misc

* Removes the WooThemes Updater notice
* Helper for logging actions/filters fired during a page load -- simply add `?wcdh_hooks=actions|filters|all`, reload the page, and your desired hooks will be printed to the error log, along with the fired count

## Installation

Download and install just like any other WordPress plugin. If you want to be really fancy, symlink it into your installs instead.

## Changelog

### 0.1.0 - 2015.07.25
 * Initial Release
