=== AI Auto Alt Text Generator ===
Contributors: connorbulmer
Tags: alt text, accessibility, seo, images, ai, openai
Requires at least: 5.5
Tested up to: 6.8
Stable tag: 1.15
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Author URI: https://profiles.wordpress.org/connorbulmer

Automatically generates alt text and image titles for your WordPress media uploads with GPT‑4o mini, improving accessibility and SEO.

== Description ==

**AI Auto Alt Text Generator** sends each newly uploaded (or manually selected) image to OpenAI’s GPT‑4o vision model and stores the returned alt text in `_wp_attachment_image_alt`.  
Optional settings let you generate image titles, include the file name in the prompt, and bulk‑process your library.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/ai-auto-alt-text-generator` or install via *Plugins ▸ Add New*  
2. Activate the plugin  
3. Navigate to *Settings ▸ Alt Text Generator*  
4. Enter your OpenAI API key and save

== Frequently Asked Questions ==

= What does the plugin send to OpenAI? =  
Only the publicly accessible image URL, optional filename, optional site/context text and the parent post title (if any).

= Is any data stored by the plugin off‑site? =  
No. OpenAI receives the content described above, processes it, and returns a single text string. Nothing is persisted by OpenAI on your behalf.

== External services ==

This plugin calls the **OpenAI API** to generate descriptive alt text and (optionally) image titles.

* **Service:** chat completions endpoint at `https://api.openai.com/v1/chat/completions`  
* **When:**  
  * Automatically – when an image is uploaded (if enabled)  
  * Manually – when you click *Generate Alt Text & Title* in the Media Library  
  * Bulk – when using *Tools ▸ Bulk Alt Text Update*  
* **Data sent:**  
  * Public URL of the selected image  
  * (Optional) image filename  
  * (Optional) site‑wide context you supply in Settings  
  * (Optional) attached post/page title  
* **Terms of use:** <https://openai.com/policies/terms-of-use>  
* **Privacy policy:** <https://openai.com/policies/privacy-policy>
