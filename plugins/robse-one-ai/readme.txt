=== ROBSE ONE AI ===
Contributors: robsejp
Plugin URI: https://robse.jp/robse-one/
Requires at least: 6.6
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Creates editable Japanese page outlines and saves reviewed content as WordPress drafts.

== Description ==
ROBSE ONE AI is an optional admin-side extension. It sends the editor's brief to the OpenAI Responses API only after the editor agrees. The outline is editable, and the plugin creates a page draft only after the editor chooses the separate save action. The API key stays on the WordPress server.

An OpenAI API key and API usage fees are required. Site operators should disclose this data flow to editors and review the included PRIVACY.md before enabling the feature.

== Installation ==
1. Upload and activate the plugin.
2. Set an OpenAI API key and available model under Settings > ROBSE ONE AI.
3. Review the privacy information and update the site's privacy policy before enabling this feature for editors.

== Privacy ==
The editor's brief is sent to OpenAI's API after consent. The request sets `store: false`. Generated content is saved as a WordPress draft only after an explicit action. Read PRIVACY.md for details and suggested site policy text.

== Uninstall ==
Deleting the plugin removes its settings and stored API key. Draft pages created by the plugin remain on the site.

== Changelog ==
= 0.3.0 =
* Add consent-based page outline generation and draft creation.
* Add server-side API key settings and REST permission checks.
