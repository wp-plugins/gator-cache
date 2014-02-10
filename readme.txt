=== Gator Cache ===
Contributors: GatorDog
Donate link: http://gatordev.com/gator-cache
Tags: cache, performance, bbpress, woocommerce
Requires at least: 3.6
Tested up to: 3.8.1
Stable tag: 1.41
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A better, stronger, faster page cache for Wordpress. Performance that's easy to manage.

== Description ==

Gator Cache is an easy to manage page cache for Wordpress. Once installed, it automatically updates new and updated content in your cache. This keeps your website fresh while adding the superior performance advantage of a cache. Key features are as follows:

*   Greatly increases site performance by adding a page cache
*   Automatic update of cache when content is published or updated
*   Automatic update of cache when comments are approved
*   Compatible with WooCommerce, will not cache mini-cart in page
*   Compatible with bbPress, updates when topics, replies, etc are added
*   Compatible with Wordpress HTTPS, will cache pages secured by the plugin when applicable
*   Posts can be cached for logged-in Wordpress users by role. You can cache pages for Subscribers, Customers or other roles while skipping the cache for Administrators.
*   Http caching supported with Apache and Nginx

== Screenshots ==

1. The Gator Cache management panel.

== Installation ==

1. Upload `gator-cache.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click on Settings or the GatorCache menu icon to run the automated setup
4. Follow the automated 2-step installation
5. Check the "enabled" box and update your general settings

== Changelog ==

= 1.41 =
* Added feature for custom refresh rules based on page or archive url
= 1.33 =
* Maintenance release
* Improved support for post comments and http caching
= 1.32 =
* Maintenance release for 1.31
* Replace php short tags which may cause fatal errors on some php configurations
= 1.31 =
* Adds support for caching SSL pages and the Wordpress HTTPS plugin
= 1.20 =
* Adds the ability to exclude custom directories and pages
= 1.11 =
* Maintenance release for 1.10
* Fixes issue with cache serving
= 1.1 =
* Added support for bbPress
* Enhanced content refresh
* Performance optimizations
= 1.0 =
* Initial Release.
