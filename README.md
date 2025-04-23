# Auto Alt Text Generator

Automatically generates alt text and image titles for uploaded images in WordPress using OpenAI’s GPT‑4o mini vision model. Improves accessibility and SEO with no manual effort.

---

**Plugin Name:** Auto Alt Text Generator  
**Author:** [Connor Bulmer](https://connorbulmer.co.uk)  
**Version:** 1.9  
**Tested up to:** WordPress 6.5  
**Requires at least:** WordPress 5.5  
**License:** GPL v2 or later  
**Tags:** alt text, accessibility, SEO, image optimisation, GPT-4o, media

---

## ✨ Features

- Automatically generates alt text on image upload
- Optional automatic image title generation
- One-click manual generation in the Media Library
- Bulk update tool for existing images
- Choose the image size and visual detail level to send
- Provide optional site-wide context to improve results
- **NEW**: Optionally include the image file name in the prompt
- Uses GPT-4o’s vision model (text + image input)
- Lightweight and privacy-conscious — no third-party servers involved except OpenAI

---

## 🧠 How It Works

This plugin uses the OpenAI API (GPT-4o) to generate meaningful, screen-reader-friendly alt text and titles for your media uploads. It sends the image **via public URL** to OpenAI along with context like:

- The parent page title (if attached)
- Site context (from plugin settings)
- (Optional) The original image file name — e.g. `woman-on-a-bridge.jpg`

The response is used to fill the image’s `alt` attribute and (optionally) its WordPress title.

---

## 🛠 Installation

1. Upload the plugin to `/wp-content/plugins/auto-alt-text-generator`  
   (or install via the Plugins screen in WordPress)
2. Activate it
3. Go to **Settings → Alt Text Generator**
4. Enter your OpenAI API key
5. Adjust your preferences and save

---

## ⚙️ Settings Overview

| Option                            | Description |
|----------------------------------|-------------|
| **OpenAI API Key**               | Required to connect to GPT-4o |
| **Image Size to Send**           | Thumbnail, Medium, Large, or Full |
| **Image Detail Quality**         | Send ‘low’ or ‘high’ image detail |
| **Site Context**                 | Optional prompt hint (e.g. your brand voice or industry) |
| **Send Image File Name**         | Includes file name (e.g. `products-summer.jpg`) in prompt |
| **Automatically Generate Title** | Create SEO-friendly titles for images |

---

## 🖱 Manual & Bulk Generation

- **Media Library:** Each image gains a “Generate Alt Text & Title” button
- **Bulk Tool:** Found under **Tools → Bulk Alt Text Update**  
  Processes images without alt text in batches of five, with a progress bar

---

## 📦 Changelog

### 1.9 – April 2025
- ✅ Added setting to pass image file name into the prompt
- ✅ Clarified prompt structure and site context behaviour

### 1.8 – March 2025
- 🎉 Initial release
- Alt text generation on upload
- Manual and bulk tools
- Optional image title generation

---

## ❓ FAQ

### Does this store anything externally?
No — images are passed to OpenAI by URL only, and never stored. Your data remains private.

### What model does this use?
GPT-4o mini vision model (`gpt-4o-mini`) as of April 2025. It accepts both text and image input.

### Can I customise the prompt?
You can guide results using:
- Site context (freeform)
- Parent page title (automatic)
- Image file name (optional setting)

Prompt text itself is optimised for clear, accessible alt descriptions.

---

## 📸 Screenshots (if provided)

1. Plugin settings page
2. Generate button in the Media Library
3. Bulk alt text tool with progress bar

---

## 📜 License

This plugin is licensed under the GNU General Public License v2.0 or later.  
See: [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

> Developed with ❤️ by [Connor Bulmer](https://connorbulmer.co.uk)  
> Accessibility, SEO, and automation — all in one plugin.
