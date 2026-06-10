=== AI Auto Alt Text Generator ===
Contributors: connorbulmer
Tags: alt text, accessibility, seo, images, ai
Requires at least: 5.5
Tested up to: 7.0
Stable tag: 1.21
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Author URI: https://profiles.wordpress.org/connorbulmer

Automatically generates alt text and image titles for your WordPress media uploads with selectable OpenAI models (defaulting to GPT-5.4 nano), improving accessibility and SEO.

== Description ==

**AI Auto Alt Text Generator** is a lightweight, privacy-conscious plugin that uses OpenAI vision-capable models (defaulting to GPT-5.4 nano, with GPT-5.4 mini and legacy GPT-4o mini options) to create meaningful, screen-reader-friendly alt text and SEO-friendly titles for your images. Whether you add a single image, bulk-process your library, or just want a one-click fix in the Media Library, the plugin handles it all – no manual effort required.

This solution is far cheaper than many alternatives because it cuts out the middle-person. You’ll just need to bring your own OpenAI API key.

### ✨ Key features

* **Automatic alt text on upload** – set-and-forget accessibility.
* **Optional automatic image titles** – improve SEO with descriptive titles.
* **One-click manual generation** – “Generate Alt Text & Title” button in the Media Library.
* **Bulk update tool** – batch-process existing images, including those that only have filename-based alt text, with a configurable pause between batches.
* **Prompt fine-tuning** – supply site-wide context and optionally include the image file name.
* **Model selection** – default to GPT-5.4 nano (fastest & cheapest) or switch to GPT-5.4 mini for higher quality; legacy GPT-4o mini and GPT-5 mini/nano remain available.
* **Image size & detail control** – choose the resolution and level of visual detail sent.
* **Developer-friendly** – filters and actions to customise prompts, models and output, plus optional outgoing webhooks.
* **WordPress Abilities API** – registers a `generate-alt-text` ability (WordPress 6.9+/7.0) so core AI, agents and automation tools can generate alt text.
* **No extra servers** – data flows only between your site and OpenAI; nothing is stored off-site.
* **Multilingual output** – choose English (UK), English (US) or other popular languages. (English US is the default.)

### 🧭 Where to find the bulk tool

* **Tools → Bulk Alt Text Update**
* **Media → Bulk Alt Text Update** (shortcut that redirects to the Tools page)
* **Settings → Alt Text Generator** includes a button linking straight to the bulk page

### 🧠 How it works

1. When an image is uploaded (or manually chosen), the plugin creates a temporary public URL for that image.
2. It sends the image – plus optional context such as the parent post title, your custom site context and the file name – to your selected OpenAI vision model.
3. OpenAI returns a concise description.
   * Alt text is stored in WordPress’ native `_wp_attachment_image_alt` field.
   * (Optional) The returned title is stored as the attachment post title.
4. Nothing is cached or stored on OpenAI’s side; only the final strings live in your database.

== Screenshots ==

1. Plugin settings page for configuring automatic image alt text generation, OpenAI model selection, and accessibility-focused image options.
2. Bulk Alt Text Updater processing existing Media Library images in batches with live progress and rate-limit controls.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/ai-auto-alt-text-generator` **or** install via **Plugins → Add New**.
2. Activate the plugin.
3. Go to **Settings → Alt Text Generator**.
4. Enter your OpenAI API key, configure your preferences, and save.

== Settings overview ==

* **OpenAI API Key** – Required to connect to OpenAI.
* **OpenAI Model** – GPT-5.4 nano (default), GPT-5.4 mini, or legacy GPT-4o mini / GPT-5 mini / GPT-5 nano.
* **Image Size to Send** – Thumbnail, Medium, Large, or Full.
* **Image Detail Quality** – ‘Low’ or ‘High’ (Low uses fewer tokens).
* **Bulk batch size** – Number of images per batch (lower values reduce rate-limit risk).
* **Site Context** – Optional free-form prompt guidance (brand voice, niche, etc.).
* **Send Image File Name** – Include file name in the prompt for extra context.
* **Automatically Generate Title** – Add descriptive titles alongside alt text.
* **Use full context for image titles** – When enabled, title generation includes site context and file name (uses more tokens).
* **Bulk optimiser delay (seconds)** – Pause between batches during bulk runs.
* **OpenAI request timeout (seconds)** – Max wait time for OpenAI responses (10–120s) to reduce timeout failures on slower hosts.
* **Output Language** – Default English (US). Choose English (UK) for British spellings or another popular language; outputs (alt text and titles) will be generated in the selected language.
* **Webhook URL & signing secret** – Optionally POST generated alt text/titles to an external endpoint (with an optional HMAC-SHA256 signature) for automation and logging.

== Developers ==

The plugin is extensible via standard WordPress hooks, an outgoing webhook, and the WordPress Abilities API.

**Filters**

* `aatg_alt_text_prompt` ( $prompt, $post_ID, $context ) – customise the alt-text prompt.
* `aatg_image_title_prompt` ( $prompt, $post_ID, $context ) – customise the title prompt.
* `aatg_openai_request_payload` ( $payload, $context, $messages ) – customise the full OpenAI request (model, messages, reasoning effort).
* `aatg_generated_alt_text` ( $alt_text, $post_ID ) – filter alt text before it is saved.
* `aatg_generated_title` ( $title, $post_ID ) – filter the title before it is saved.
* `aatg_is_low_quality_alt` ( $is_low, $alt, $post_ID ) – control which existing alt text the bulk tool regenerates.
* `aatg_webhook_payload` ( $payload, $post_ID, $result ) – customise the outgoing webhook body.

**Actions**

* `aatg_after_alt_text_generated` ( $post_ID, $alt_text )
* `aatg_after_title_generated` ( $post_ID, $title )
* `aatg_after_generation` ( $post_ID, $result )

**Outgoing webhook**

Set a Webhook URL under **Settings → Integrations & Webhooks** to receive a non-blocking JSON POST after each generation. If a signing secret is set, requests include an `X-AATG-Signature: sha256=…` header (HMAC-SHA256 of the body) so your endpoint can verify authenticity.

**WordPress Abilities API**

On WordPress 6.9+ the plugin registers the ability `ai-auto-alt-text/generate-alt-text` (input: `attachment_id`) so WordPress core AI, agents, MCP servers and automation tools can generate alt text programmatically. Execute it with `wp_get_ability( 'ai-auto-alt-text/generate-alt-text' )->execute( array( 'attachment_id' => 123 ) )`.

== Frequently Asked Questions ==

= What data is sent to OpenAI? =
The publicly accessible image URL, plus any optional context you enable: image file name, site-wide context, and the parent post/page title.

= What if I see a 429 rate limit error? =
Try lowering the bulk batch size, increasing the bulk delay, switching Image Detail Quality to **Low**, and shortening site context if it’s very long.

= Does OpenAI store my images or text? =
No. The OpenAI API returns a response and does not retain your data. The plugin itself stores only the generated alt text and title in your WordPress database.

= Can I customise the prompt? =
Yes – via **Settings → Alt Text Generator** you can add site context and choose whether to include the image’s file name or parent post title. You can also select an output language; English (US) remains the default. Developers can filter the prompts and output directly via the `aatg_alt_text_prompt`, `aatg_image_title_prompt` and `aatg_generated_alt_text` hooks.

= Which model do you use? =
GPT-5.4 nano by default (cheapest, vision-capable), with GPT-5.4 mini for higher quality. Legacy GPT-4o mini and GPT-5 mini/nano remain selectable. Existing installs keep whatever model they previously selected.

= Will the bulk tool overwrite alt text I already wrote? =
No. The bulk tool regenerates alt text that is missing or clearly filename-based (e.g. `IMG_1234`, or alt text equal to the file name). Genuine human- or AI-written descriptions are left untouched. You can fine-tune the detection with the `aatg_is_low_quality_alt` filter.

= Who can access the bulk tool? =
By default, the bulk page requires the `manage_options` capability (typically Administrators). You can change this in code to `upload_files` if you want Editors with media permissions to run it.

== Changelog ==

= 1.21 = 2026-06-09
* **New:** Default model is now **GPT-5.4 nano** (fastest & cheapest, vision-capable), with **GPT-5.4 mini** for higher quality. Existing installs keep their current model; legacy GPT-4o mini and GPT-5 mini/nano remain available.
* **New:** **WordPress 7.0 compatibility** (tested up to 7.0).
* **New:** **WordPress Abilities API** integration – registers an `ai-auto-alt-text/generate-alt-text` ability (WordPress 6.9+) so core AI, agents and automation tools can generate alt text.
* **New:** **Outgoing webhooks** – optionally POST generated alt text/titles to your own endpoint, with an optional HMAC-SHA256 signature.
* **New:** **Developer hooks** – filters (`aatg_alt_text_prompt`, `aatg_image_title_prompt`, `aatg_openai_request_payload`, `aatg_generated_alt_text`, `aatg_generated_title`, `aatg_webhook_payload`, `aatg_is_low_quality_alt`) and actions (`aatg_after_alt_text_generated`, `aatg_after_title_generated`, `aatg_after_generation`).
* **Improved:** Bulk updater now also **regenerates filename-based / low-quality alt text**, while never overwriting genuine descriptions.
* **Improved:** Prompts rewritten for stronger accessibility (WCAG) **and** SEO; alt text tightened to ~125 characters and now transcribes meaningful in-image text.
* **Improved:** GPT-5-family models run with low reasoning effort to keep cost and latency down.
* **Improved:** The Media Library “Generate Alt Text & Title” button now updates the Alt Text and Title fields instantly — no page refresh required.

= 1.20 = 2026-03-25
* **Fixed:** Resolved an uncommon issue where generated alt text could include the parent page/post title; prompt now explicitly instructs the model to omit it.

= 1.19 = 2026-02-02
* **New:** Added configurable OpenAI request timeout setting (10–120 seconds, default 30).
* **Improved:** OpenAI calls now retry once after timeout with a longer wait window.
* **Improved:** Better resilience to transient `cURL error 28` timeout failures during alt text/title generation.

= 1.18 = 2026-01-28
* **New:** Branded tabbed dashboard with Settings, Bulk Updater, and Integrations.
* **New:** Bulk updater log for per-image warnings/errors in the UI.
* **Improved:** Clearer OpenAI error handling surfaced to users.
* **Improved:** Rate-limit controls (batch size + delay) and low detail default.
* **Fixed:** Bulk counter no longer double-counts missing/blank alt text.
* **Fixed:** Trim stray leading quotes from generated alt text and titles.

= 1.17 =
* **New:** OpenAI model selector with GPT-4o mini default and GPT 5 Mini/Nano (BETA) options.

= 1.16 =
* **New:** Output Language selector – generate alt text and titles in English (UK) or other popular languages; defaults to English (US).
* **Improved discoverability:** Added **Media → Bulk Alt Text Update** submenu (redirects to Tools page).
* **Improved workflow:** Added a **“Go to Bulk Alt Text Update”** button on the settings page.
* **Quality of life:** Added **Settings** and **Bulk Update** quick links on the Plugins screen.
* **Reliability:** Bulk submenu now redirects via the page load-hook for consistent behaviour across environments.
* No breaking changes.

= 1.15 =
* **New options:** Send image file name in the prompt; optional “full context” for titles (includes site context and file name).
* **Bulk runs:** Added delay control between five-image batches.
* **Diagnostics:** Lightweight file logger for bulk runs.
* General polish and copy tweaks.

== External services ==

This plugin connects to the **OpenAI API** to generate alt text and (optionally) image titles.

* **Endpoint:** `https://api.openai.com/v1/chat/completions`
* **When called:**
  * On image upload (automatic)
  * Via “Generate Alt Text & Title” button in Media Library (manual)
  * Via **Tools → Bulk Alt Text Update** (bulk)
* **Data sent:** image URL, optional file name, optional site context, optional parent post title
* **Terms:** <https://openai.com/policies/terms-of-use>
* **Privacy:** <https://openai.com/policies/privacy-policy>

If you configure a Webhook URL, generated alt text/titles (and the image URL) are also sent to that endpoint you control.
