# ROBSE ONE AI: data handling

When a site editor generates a page outline, the brief is sent from the WordPress server to the OpenAI Responses API. The result is returned to the editor and is saved as a WordPress page draft only after a separate explicit action.

- The editor must agree before sending. The REST endpoint checks consent too.
- The brief is the user-provided input sent for the generation request. The plugin does not intentionally send the site name or WordPress user profile.
- The API key remains server-side. The `ROBSE_ONE_AI_API_KEY` constant in `wp-config.php` takes precedence; otherwise the key is stored in a non-autoloaded WordPress option.
- The request sets `store: false`. Site operators should review the terms and data handling for the OpenAI account and service they use.
- API usage fees are charged to the owner of the API key. The plugin does not estimate or cap those fees.

## Suggested privacy policy text

Adapt this to the site's actual practices and policy. It is not legal advice:

> Site administrators may send the brief entered in ROBSE ONE AI to the OpenAI API to generate a page outline. Do not include personal, confidential, or third-party non-public information. The outline is saved as a draft on this site only after an administrator reviews and chooses to save it. The site operator manages API costs and external-service data handling.

If the AI feature is not used, the ROBSE ONE AI plugin does not need to be installed or activated.
