=== Layer Maps ===
Contributors: ecommnet, gavin.williams, scottsalisbury
Tags: maps, quick maps, layer maps, marker maps, kml layer, mapping, geocode
Requires at least: 3.0.1
Tested up to: 4.3.1
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple maps plugin to create layers, add pins and display geocoded mapping data.

== Description ==

This plugin provides easy to use functionality to create maps, layers and pins. Pins can be given an address or post code which is geocoded and plotted on a Google Map. Pins can be grouped into layers and each map can be assigned multiple layers. 

You can toggle each layer on or off within the map interface.

Pin colours can be individually configured or globally overridden by the layer colour. 

KML layers can also be attached to each map.

= Configurable options =
* Optional Google API key
* Toggle map clustering on or off. 

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip package contents
1. Upload the "`layer-maps`" directory to the "`/wp-content/plugins/`" directory
1. Activate the plugin through the "`Plugins`" menu in WordPress
1. Configure the plugin by going to "`Layer Maps > Settings`". Note: the plugin will work out of the box with default settings
1. Create you map, layers and pins and assign them accordingly
1. Copy the map shortcode from the map edit screen and paste into your page of choice
1. Test and enjoy :)

== Frequently Asked Questions ==

= No pins are appearing on the map =

If your maps are looking a bit empty, make sure you've added pins, associated them with a layer, then associated your map with the layers you want to see. If it's still not working, there may be a problem displaying the pin icons. Make sure PHP's GD library is installed on your server.

= I'm having some problems =

This is a very early release of this plugin which started out as a tool for internal use. If you have any suggestions or bug reports, we'd love to hear them so we can continue to improve the plugin. Drop us a line on the support forum. 

== Changelog ==

= 0.1 =
* First version of the plugin