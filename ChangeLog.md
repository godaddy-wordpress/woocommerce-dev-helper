
### 1.0.0 - 2018.08.15
 * Misc - Uses the SkyVerge Plugin Framework
 * Misc - Drop support for PHP 5.2 - PHP 5.3 is the minimum required version
 * Misc - Drop support for WooCommerce Subscriptions 1.5.x 

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
