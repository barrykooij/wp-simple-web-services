=== WP Simple Web Services ===
Contributors: barrykooij
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Q7QDZTLCRKSMG
Tags: webservice, web service, JSON, REST
Requires at least: 3.1
Tested up to: 3.7.1
Stable tag: 1.1.0
License: GPL v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Simple WordPress Rest Web Services. Add JSON REST web services to your WordPress website with a few clicks.

== Description ==

Simple WordPress Rest Web Services. Add JSON REST web services to your WordPress website with a few clicks.

Please report issues and bugs at <a href='https://github.com/barrykooij/wp-simple-web-services/issues'>Git Hub</a>.

== Installation ==

1. Upload `wp-simple-web-services` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently asked questions ==

= Why do I get a 404 error when I try to call the web service URL? =

For WP Simple Web Service to work you have to have your permalinks structure set to <b>/%postname%/</b>, also make sure you .htaccess file is writable.

= What kind of web service call does this plugin provide? =

At this moment the WP Simple Web Service plugin only provides REST JSON GET calls out of the box.

= Does WP Simple Web Service support custom post types? =

Yes, it does.

= Does WP Simple Web Service supports (custom) meta fields? =

Yes, it does.

= Can I modify the output of the 'get_posts' web service? =

Yes, you can. WP Simple Web Service accepts all query vars you can pass to WP_Query, you can add them to the web service request by adding them in the qv GET variable. For example, limiting the result set to 10 results can be done by adding `&qv[posts_per_page]=10` to the request URL.

= I am a developer, can I add my own web service calls to WP Simple Web Service ? =

Yes, you can. Simply add an action to wpsws_webservice_YOUR-WEB-SERVICE and replace 'YOUR-WEB-SERVICE' with your own web service name. This webservice will now be called when you visit URL/webservice/YOUR-WEB-SERVICE/

= Can I also add my own settings to the WP Simple Web Service settings screen ? =

Of course! Simply hook into 'wpsws_general_settings' and display your custom settings.

== Changelog ==

= 1.1.0 =
* Added default WP_Query argument support to get_posts web service
* Removed JS console log

= 1.0.1 =
* Added an install function that flushed rewrite rules
* Changed the way the plugin is loaded, removed anonymous function

= 1.0.0 =
* Initial release