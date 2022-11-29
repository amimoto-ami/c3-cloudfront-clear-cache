=== C3 Cloudfront Cache Controller ===
Contributors: amimotoami,hideokamoto,megumithemes,wokamoto,miyauchi,hnle,bartoszgadomski,jepser,johnbillion,pacifika
Donate link: http://wp-kyoto.net/
Tags: AWS,CDN,CloudFront
Requires at least: 4.9.0
Tested up to: 6.1.1
Stable tag: 6.1.4
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
2. Create IAM user to attach valid IAM Policy for AWS CloudFront
3. Configure the plugin settings from wp-admin

== AWS IAM Policy Example ==

`
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Action": [
                "cloudfront:GetDistribution",
                "cloudfront:ListInvalidations",
                "cloudfront:GetStreamingDistribution",
                "cloudfront:GetDistributionConfig",
                "cloudfront:GetInvalidation",
                "cloudfront:CreateInvalidation"
            ],
            "Effect": "Allow",
            "Resource": "*"
        }
    ]
}
`

## Adding your configuration through env vars

The plugin can be configured by defining the following variables:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `C3_DISTRIBUTION_ID`

You can put these variables like the code into the wp-config.php

`php
define( 'AWS_ACCESS_KEY_ID', '' );
define( 'AWS_SECRET_ACCESS_KEY', '' );
define( 'C3_DISTRIBUTION_ID', '' );
`

== Cookie ==
This plugin set a Cookie named `wordpress_loginuser_last_visit` to the user.
It prevents CDN caching when the user is previewing his posts or contents.
Once logging out from Dashboard, plugin removes this cookie from user.

== Frequently Asked Questions ==

= When it send invalidation request？ =

If post published,this plugin post invalidation request to CloudFront.

= Can we select AWS SDK version ? =

You can select AWS SDK version to add a plugin like ...

`
<?php
/*
 * Plugin Name: Should use AWS SDK v2
 * Version: 0.0.1
 * Plugin URI:https://github.com/amimoto-ami/c3-cloudfront-clear-cache
 * Description: To use aws sdk v2 for C3 Cloudfront Cache Controller
 * Author: hideokamoto
 * Author URI: https://wp-kyoto.net/
 */
add_filter( 'c3_select_aws_sdk', function() {
	return true;
} );
`

== Changelog ==

== 6.1.4 ==
* [Update] Support post_type_archive_link

== 6.1.3 ==
* [Fix] Lost filter c3_invalidation_items From v6.0.0

== 6.1.2 ==
* [Fix] php version compare issue

== 6.1.1 ==
* [Fix] Plugin deactivation issue

== 6.1.0 ==
* [Update] Flush cache by post_ids

== 6.0.0 ==
* [Breaking Change] Re-write entire code
* [Update] Add `c3_log_cron_invalidation_task` filter to show cron job Logs
* [Update] Add `c3_aws_sdk_path` filter to change or remove bundled AWS SDK path

== 5.5.1 ==
* [Update] Replace the top level menu with a Settings submenu

= 5.5.0 =
* [Update] Can use defined variables for AWS Credentials

= 5.4.2 =
* [Bug fix] Set cookie path in preview page

= 5.4.1 =
* [Bug fix] Undefined Paths index in invalidation query cron event

= 5.4.0 =
* [Update] Update preview fix plugin

= 5.3.4 =
* [Bug fix] Small bug fix

= 5.3.3 =
* [Change SDK] replace sdk

= 5.3.2 =
* [Bug fix] Use EC2 role if using as AMIMOTO Managed hosting

= 5.3.1 =
* [Add filter] Can select AWS SDK version by filter

= 5.3.0 =
* [Update SDK] Use AWS SDK v3 when you using php 5.6 or later

= 5.2.1 =
* [For AMIMOTO Managed] bug fix to run by WP-CLI

= 5.2.0 =
* [Add filter] We can disable to register wp-cron that retry request invalidation.
* [Readme] Update readme to see IAM Policy example.

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

== 6.1.0 ==
* [Fix] Plugin deactivation issue
