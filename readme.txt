=== AI Auto Alt Text Generator ===
Contributors: connorbulmer
Tags: alt text, accessibility, seo, images, ai
Requires at least: 5.5
Tested up to: 6.8
Stable tag: 1.17
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Author URI: https://profiles.wordpress.org/connorbulmer

Automatically generates alt text and image titles for your WordPress media uploads with selectable OpenAI models (defaulting to GPT-4o mini), improving accessibility and SEO.

== Description ==

**AI Auto Alt Text Generator** is a lightweight, privacy-conscious plugin that uses OpenAI vision-capable models (defaulting to GPT-4o mini, with GPT 5 Mini/Nano options) to create meaningful, screen-reader-friendly alt text and SEO-friendly titles for your images. Whether you add a single image, bulk-process your library, or just want a one-click fix in the Media Library, the plugin handles it all – no manual effort required.

This solution is far cheaper than many alternatives because it cuts out the middle-person. You’ll just need to bring your own OpenAI API key.

### ✨ Key features

* **Automatic alt text on upload** – set-and-forget accessibility.
* **Optional automatic image titles** – improve SEO with descriptive titles.
* **One-click manual generation** – “Generate Alt Text & Title” button in the Media Library.
* **Bulk update tool** – batch-process existing images (five at a time) with a configurable pause between batches.
* **Prompt fine-tuning** – supply site-wide context and optionally include the image file name.
* **Model selection** – choose GPT-4o mini (default) or GPT 5 Mini/Nano (BETA).
* **Image size & detail control** – choose the resolution and level of visual detail sent.
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

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/ai-auto-alt-text-generator` **or** install via **Plugins → Add New**.  
2. Activate the plugin.  
3. Go to **Settings → Alt Text Generator**.  
4. Enter your OpenAI API key, configure your preferences, and save.

== Settings overview ==

* **OpenAI API Key** – Required to connect to GPT-4o.  
* **OpenAI Model** – GPT-4o mini (default), GPT 5 Mini (BETA), or GPT 5 Nano (BETA).  
* **Image Size to Send** – Thumbnail, Medium, Large, or Full.  
* **Image Detail Quality** – ‘Low’ or ‘High’.  
* **Site Context** – Optional free-form prompt guidance (brand voice, niche, etc.).  
* **Send Image File Name** – Include file name in the prompt for extra context.  
* **Automatically Generate Title** – Add descriptive titles alongside alt text.  
* **Use full context for image titles** – When enabled, title generation includes site context and file name (uses more tokens).  
* **Bulk optimiser delay (seconds)** – Pause between five-image batches during bulk runs.  
* **Output Language** – Default English (US). Choose English (UK) for British spellings or another popular language; outputs (alt text and titles) will be generated in the selected language.

== Frequently Asked Questions ==

= What data is sent to OpenAI? =  
The publicly accessible image URL, plus any optional context you enable: image file name, site-wide context, and the parent post/page title.

= Does OpenAI store my images or text? =  
No. The OpenAI API returns a response and does not retain your data. The plugin itself stores only the generated alt text and title in your WordPress database.

= Can I customise the prompt? =  
Yes – via **Settings → Alt Text Generator** you can add site context and choose whether to include the image’s file name or parent post title. You can also select an output language; English (US) remains the default.

= Which model do you use? =  
GPT-4o mini by default, with GPT 5 Mini and GPT 5 Nano (BETA) available in settings.

= Who can access the bulk tool? =  
By default, the bulk page requires the `manage_options` capability (typically Administrators). You can change this in code to `upload_files` if you want Editors with media permissions to run it.

== Changelog ==

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
