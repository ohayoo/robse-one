=== ROBSE ONE Blocks ===
Contributors: robsejp
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

CTA and contact-link blocks, a menu profile for non-administrators and a dashboard editing guide for ROBSE ONE sites.

== Description ==
Provides ROBSE CTA and ROBSE Contact links dynamic blocks, an administrator-controlled standard menu profile for non-administrators, public custom post type menu choices, and a compact dashboard editing guide.

All screen texts are translatable. A Japanese fallback translation is bundled; WordPress.org language packs take priority when available.

Contact links open a calling or email app. This plugin does not send forms or deliver messages. CMS mode hides menus only; it does not change permissions or lock content.

== Installation ==
1. Upload and activate the plugin.
2. Use Settings > ROBSE ONE CMS to configure the client menu profile.
3. Insert ROBSE CTA or ROBSE Contact links from the block inserter. Use your real phone/email details and link the CTA to the actual contact section.
4. On ROBSE ONE, enable page content mode in Appearance > ROBSE ONE setup to give editors a direct homepage editing link.

== Uninstall ==
Deleting the plugin removes its CMS menu settings. Existing posts and pages are preserved.

== Changelog ==
= 0.3.0 =
* Add contact links with validated tel and mailto destinations.
* Link the client dashboard to the selected homepage in page content mode.
* Explain the limits of CMS menu hiding and show no CTA button without a URL.
* Follow theme palette/radius tokens and bundle Japanese PHP/JavaScript translations.
* Include the translation improvements prepared in 0.2.2.

= 0.2.2 =
* Make all screen texts translatable: English source strings, and the CTA block editor now uses the translation functions.
* The CTA block default texts follow the site language. Blocks saved with earlier versions keep the same output on Japanese sites.
* Update the editing guide for the current editor button name ("Save").
* Fix the installation steps (this plugin does not add block patterns).

= 0.2.1 =
* Add CMS menu profile, public custom post type choices, and dashboard guide.
* Update the CTA block editor controls.
