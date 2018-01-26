=== C3 Cloudfront Cache Controller ===
Contributors: amimotoami,hideokamoto,megumithemes,wokamoto,miyauchi,hnle
Donate link: http://wp-kyoto.net/
Tags: AWS,CDN,CloudFront
Requires at least: 4.9.0
Tested up to: 4.9.2
Stable tag: 5.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This is simple plugin that clear all cloudfront cache if you publish posts.

== Description ==

This is simple plugin that clear all cloudfront cache if you publish posts.
You can easy use CloudFront in front of WordPress.

== Fixtures ==

* wp_is_mobile()
Check device viewer type by CloudFront header.
Now supports `HTTP_CLOUDFRONT_IS_MOBILE_VIEWER` and `HTTP_CLOUDFRONT_IS_TABLET_VIEWER`.

* preview url
In preview page, plugin add `post_date` query to avoid CloudFront cache.
And set `wordpress_loginuser_last_visit` cookie for avoid CloudFront cache too.

The `wordpress_loginuser_last_visit` cookie will be removed if user sign out.

= Invalidation(Clear Cache) Page URL =
This plugin send following page url to CloudFront Invalidation API.

- TOP page URL
- Published Post Page URL
- Category Archive Page URL

== Installation ==

1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= When it send invalidation request？ =

If post published,this plugin post invalidation request to CloudFront.

== Changelog ==

= 5.1.0 =
* [Update preview fixture] Set cookie to avoid CloudFront cache if user sign in
* [Update preview fixture] Unset cookie for avoide CloudFront cache if user sign out

= 5.0.0 =
* Support CloudFront viewer params on `wp_is_mobile()`.(4.9.0 or later)

= 4.4.0 =
* Fix small bug
* Add some feature for AMIMOTO Managed hosting user

= 4.3.1 =
* Fix cron interval
* add filters `'c3_invalidation_interval`, `c3_invalidation_cron_interval`, `c3_invalidation_item_limits`.
* update default invalidation interval & items.

= 4.3.0 =
* Schedule cron event if you published many post at the same time

= 4.2.1 =
* Fix 'c3_credential' filter position

= 4.2.0 =
* Support AMIMOT Dashboard

= 4.1.0 =
* Show Invalidation Logs

= 4.0.3 =
* Auto Deploy by Travis

= 4.0.2 =
* Add WP-CLI param check ( wp c3 flush)
* change transient_key

= 4.0.1 =
* Bug fix ( conflict Nephila Clavata )

= 4.0.0 =
* Support AWS SDK Version3
* Remove action hook -> 'c3_add_setting_before'
  use 'c3_after_title' filter hook instead.
* Remove action hook -> 'c3_add_setting_after'
  use 'c3_after_auth_form' filter hook instead.

= 3.0.0 =
* Include CF Preview Fix plugin
* Rename

= 2.4.3 =
* Fix Catch Exception BUG

= 2.4.2 =
* Fix CLI BUG

= 2.4.1 =
* Fix CLI BUG

= 2.4.0 =
* Add WP-CLI Command ( Update Settings )

= 2.3.0 =
* Add WP-CLI Command

= 2.2.2 =
* Force Invalidation

= 2.2.1 =
* Fix Typo

= 2.2.0 =
* Add Filter for using EC2 Instance Role.
* Translationable in t.w.org

= 2.1.1 =
* Fix too many invalidation url error.

= 2.1.0 =
* Add hook to customize invalidation URL
* support new invalidation url, terms.
* Manualy invalidation button added

= 2.0.2 =
* Change AWS SDK
* code refactoring by wokamoto
* Invalidation URL

= 2.0.1 =
* Change AWS SDK
* code refactoring by wokamoto
* Invalidation URL

= 1.0 =
* Initial released.

== Upgrade Notice ==

= 5.1.0 =
* [Update preview fixture] Set cookie to avoid CloudFront cache if user sign in
* [Update preview fixture] Unset cookie for avoide CloudFront cache if user sign out
