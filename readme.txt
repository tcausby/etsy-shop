=== Etsy Shop ===
Contributors: fsheedy
Tags: etsy, etsy listing, bracket, shortcode, shopping, shop, store, sell
Tested up to: 3.4.2
Requires at least: 3.4.2
Stable tag: 0.9.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows users to insert Etsy Shop sections in pages or posts using the bracket/shortcode method.

== Description ==

This plugin allows users to inserts Etsy products in pages or posts using the bracket/shortcode method. This enable Etsy users to share their products through their blog!  

== Installation ==

1. Upload the plugin to the '/wp-content/plugins/' directory;
2. Give read & write access to tmp folder;
3. Activate the plugin through the 'Plugins' menu in WordPress;
4. Get your own Etsy Developer API key (http://www.etsy.com/developers/register);
5. Enter your API key in the Etsy Shop Options page;
6. Place '[etsy-include=*your-etsy-shop-name*;*your-etsy-shop-setion-id*]' in your page or post;
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

So, you should have someting like this: **[etsy-include=Laplume;10088437]**

= I got Etsy Shop: API reponse should be HTTP 200 =

Please open a new topic in Forum, with all details.
This error should not appear.

= I got Etsy Shop: Error on API Request =

Please make sure that your API Key is valid.

== Screenshots ==

1. Options Page
2. Etsy listing rendering
3. Edit Post to include Etsy Shop

== Changelog ==

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