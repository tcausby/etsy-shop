=== Etsy Shop ===
Contributors: fsheedy
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9RPPQUY4M2AHL&lc=CA&item_name=Etsy%2dShop%20Wordpress%20Plugin&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: etsy, etsy listing, bracket, shortcode, shopping, shop, store, sell
Tested up to: 4.0
Requires at least: 3.4.2
Stable tag: 0.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin that allow you to insert Etsy Shop sections in pages or posts using the bracket/shortcode method.

== Description ==

Plugin that allow you to insert Etsy Shop sections in pages or posts using the bracket/shortcode method. This enable Etsy users to share their products through their blog!
[Feature plan](http://fsheedy.wordpress.com/etsy-shop-plugin/ "Feature plan")

== Installation ==

1. Upload the plugin to the '/wp-content/plugins/' directory;
2. Give read & write access to tmp folder;
3. Activate the plugin through the 'Plugins' menu in WordPress;
4. Get your own Etsy Developer API key (http://www.etsy.com/developers/register);
5. Enter your API key in the Etsy Shop Options page;
6. Place '[etsy-shop shop_name="*your-etsy-shop-name*" section_id="*your-etsy-shop-setion-id*"]' in your page or post;
7. Viewers will be able to click on your your items.

== Frequently Asked Questions ==

= How may I find the shop section id? =

Here is an example:

URL: http://www.etsy.com/shop/sushipot?section_id=11502395

So, in this example:
sushipot is **etsy-shop-name**
11502395 is **etsy-shop-section-id**

= I got Etsy Shop: empty arguments =

See below 'Etsy Shop: missing arguments'.

= I got Etsy Shop: missing arguments =

2 arguments are mandatory:

* etsy-shop-name
* etsy-shop-section-id

So, you should have someting like this: **[etsy-shop shop_name="Laplume" section_id="10088437"]**

= I got Etsy Shop: Your section ID is invalid =

Please use a valid section ID, to find your section ID, see [How to find section ID](http://fsheedy.wordpress.com/etsy-shop-plugin/how-to-find-section-id/ "How to find section ID")

= I got Etsy Shop: API reponse should be HTTP 200 =

Please open a new topic in Forum, with all details.

= I got Etsy Shop: Error on API Request =

Please make sure that your API Key is valid.

= How to integrate directly in template? =
Use '<?php echo do_shortcode( '[etsy-shop shop_name="*your-etsy-shop-name*" section_id="*your-etsy-shop-setion-id*"]' ); ?>'

== Screenshots ==

1. Options Page
2. Etsy listing rendering
3. Edit Post to include Etsy Shop

== Changelog ==

= 0.11 =
* Add the option to change time out value for requests to etsy servers
* Time out value by default is 10 seconds instead of 5

= 0.10 =
* Compatbile with WP 3.9.2
* Now use WP Shortcode API
* Allow maximum of 100 items per section instead of 25 items

= 0.9.5 =
* HTTPS for all Etsy requests, now mandatory for new Etsy Apps.
* Add detection for bad section ID.

= 0.9.4 =
* Centering items by default (sponsor Jsay Designs).
* Add opening in a new window link feature (sponsor Jsay Designs).
* Add filter for Shop ID and Section ID.
* Better filter for API Key.

= 0.9.3 =
* Corrections on Debug Mode.
* Automatically Remove spaces in API Key.

= 0.9.2 =
* Debug Mode more verbose.

= 0.9.1 =
* Debug Mode available.
* Options page compatible with PHP 5.2.X.

= 0.9.0 =
* Optimization of API request.
* Add error message if empty arguments.
* Now using Wordpress HTTP API, cURL is no more require.
* Update the Options Page.
* Code follow WordPress Coding Standards.

= 0.8.1 =
* Update installation steps.
* Translation of listing status.
* Correct listing table generation.

= 0.8 =
* First release.