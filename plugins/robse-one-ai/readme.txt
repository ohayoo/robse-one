=== ROBSE ONE AI ===
Contributors: robsejp
Tags: ai, openai, page builder, draft, content
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Creates editable page outlines with the OpenAI API after the editor's consent, and saves reviewed content as WordPress page drafts.

== Description ==

ROBSE ONE AI is an optional admin-side extension for the ROBSE ONE theme. It also works with other themes.

1. An editor describes the page they want (for example, the business and its services).
2. After the editor checks the consent box, the brief is sent to the OpenAI API, and a page outline (title and sections) comes back.
3. The editor reviews and edits the outline on screen.
4. Only when the editor clicks "Save as draft" is a page draft created. Nothing is published automatically.

The outline is written in the language of the site (for example, Japanese on a Japanese site).

* The API key is stored on the server and never sent to the browser. It can also be set with the `ROBSE_ONE_AI_API_KEY` constant in wp-config.php.
* Only users who can edit pages can use it, and only administrators can change the settings.
* Requests are rate limited per user.

An OpenAI account, an API key and API usage fees are required. The plugin supports WordPress 6.6; it calls the API directly because the core AI Client is only available in WordPress 7.0 and later.

== External services ==

This plugin connects to the OpenAI API (https://api.openai.com/v1/responses) to generate page outlines.

* **When:** only when an editor clicks "Create outline" on the ROBSE ONE AI screen after checking the consent box. Nothing is sent in the background.
* **What is sent:** the brief the editor typed, an instruction for the outline format, the site's locale code and the model name, together with the site's API key. The request sets `store: false`.
* **What is received:** a title and sections, which are shown for review and saved only if the editor chooses to save them as a draft.

The service is provided by OpenAI: [Terms of use](https://openai.com/policies/terms-of-use/), [Privacy policy](https://openai.com/policies/privacy-policy/).

Do not include personal, confidential or third-party non-public information in the brief. Site operators should review their OpenAI account's data handling and update the site's privacy policy before enabling the feature for editors. Suggested text: "Site administrators may send the brief entered in ROBSE ONE AI to the OpenAI API to generate a page outline. The result is saved as a draft on this site only after review and an explicit save action."

== Installation ==

1. Install and activate the plugin.
2. Open **ROBSE ONE AI > AI settings** and enter an OpenAI API key and an available model name.
3. Review the External services section above and update the site's privacy policy.
4. Open **ROBSE ONE AI**, describe the page, agree to sending it, and create an outline.

== Frequently Asked Questions ==

= Is anything published automatically? =

No. The plugin only creates page drafts, and only after an explicit save action.

= What happens when I delete the plugin? =

Its settings and the stored API key are removed. Draft pages created by the plugin remain on the site.

== Changelog ==
= 0.3.3 =
* Fix serialized group wrappers and add editable wide sections with spacing.
* Disable generation/save controls while a request is in progress and prevent repeated saves after success.
* Bundle a Japanese fallback translation.


= 0.3.2 =
* Raise the output token limit and use low reasoning effort on reasoning models, so the outline is not cut off.
* Show a clear message when the AI stops before finishing the outline.

= 0.3.1 =
* Write the interface in English and make all text translatable.
* Write the outline in the site's language instead of always in Japanese.
* Document the external service in the readme.

= 0.3.0 =
* Add consent-based page outline generation and draft creation.
* Add server-side API key settings and REST permission checks.
