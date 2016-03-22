=== C3 Cloudfront Clear Cache ===
Contributors: hideokamoto,megumithemes,wokamoto,miyauchi,hnle
Donate link: http://wp-kyoto.net/
Tags: AWS,CDN,CloudFront
Requires at least: 4.3.1
Tested up to: 4.4.0
Stable tag: 2.4.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This is simple plugin that clear all cloudfront cache if you publish posts.

== Description ==

This is simple plugin that clear all cloudfront cache if you publish posts.
You can easy use CloudFront in front of WordPress.

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

= 2.4.2 =
* Fix CLI BUG
