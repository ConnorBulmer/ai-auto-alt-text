# AI Auto Alt Text Generator

Automatically generates alt text and image titles for uploaded images in WordPress using OpenAI’s GPT‑4o mini vision model. Improves accessibility and SEO with no manual effort.

---

**Plugin Name:** AI Auto Alt Text Generator   
**Author:** [Connor Bulmer](https://connorbulmer.co.uk)   
**Version:** 1.12   
**Stable tag:** 1.12   
**Tested up to:** WordPress 6.8   
**Requires at least:** WordPress 5.5   
**License:** GPL v3 or later   
**Tags:** alt text, accessibility, SEO, image optimisation, GPT-4o, media, AI alt text   

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
| **Site Context**                 | Optional (but recommended)  prompt hint (e.g. about your company/website, your brand voice or industry) |
| **Send Image File Name**         | Includes file name (e.g. `products-summer.jpg`) in prompt |
| **Automatically Generate Title** | Create SEO-friendly titles for images |

---

## 🖱 Manual & Bulk Generation

- **Media Library:** Each image gains a “Generate Alt Text & Title” button
- **Bulk Tool:** Found under **Tools → Bulk Alt Text Update**  
  Processes images without alt text in batches of five, with a progress bar

---

## 📦 Changelog

### 1.11 – 2025-04-23
- ✅ Added checkbox for full site context for image title generations

### 1.10 – 2025-04-23
- 🛠️ Fixed: bulk update tool was prematurely ending after the first batch  
- ✅ `post_status = inherit` now included in remaining-image query  
- ✅ Improved meta-query to detect alt text that is empty **or** whitespace  
- 🧮 Remaining counter is now accurate across all batches  
- 🖱 Bulk update button now disables while the process is running and re-enables on completion

### 1.9 – 2025-04-21
- ✅ Added setting to include the image file name in the prompt
- ✅ Updated prompts to include filename context when enabled
- ✅ Improved plugin description and settings clarity

### 1.8 – 2025-04-21
- 🔐 Security hardening and internal code review
- ✅ Sanitised all option inputs with `sanitize_text_field()`
- ✅ Added nonce verification to all AJAX handlers
- ✅ Escaped all dynamic content in admin HTML output
- ⚙️ Refactored settings page for cleaner registration
- 🛡️ All options now use WordPress Settings API with proper defaults

### 1.7 – 2025-04-20
- 🆕 Real-time bulk update UI with progress bar and debug text
- ✅ AJAX response now includes per-image alt text preview
- 🔄 Bulk update now runs in batches of 5 with 5 5-second delay
- 🧠 Title prompt refined: “Output ONLY the title without extra labels”

### 1.6 – 2025-04-18
- ✅ Full-scan mode: finds all images with missing alt text
- ⚙️ Bulk processor now uses `sleep()` between chunks
- 🕒 Added `set_time_limit(0)` for longer runs
- 💡 Cleaned up AJAX handler logic

### 1.5 – 2025-04-14
- 🆕 Added “Automatically Generate Image Title” option (on by default)
- ✨ New `aatg_generate_image_title()` function
- 🔄 Title generation now integrated into upload and manual triggers

### 1.4 – 2025-04-10
- 📝 Added “Site Context” field (used in both alt and title prompts)
- ⚙️ Major settings refactor: API key, image size, detail level, context

### 1.3 – 2025-04-07
- 🔧 Default image size set to `large`
- 🖼 Added “Image Detail Quality” dropdown: `high` / `low`
- 🧩 Parent post title now included as contextual hint

### 1.2 – 2025-04-03
- 🖱 Added manual “Generate Alt Text” button to Media Library
- ⚙️ AJAX handler for on-demand generation

### 1.1 – 2025-03-28
- ⚙️ Settings page created under Settings → Alt Text Generator
- ✅ OpenAI API key and image size options added

### 1.0 – 2025-03-25
- 🎉 Initial release
- 🧠 Auto-generates alt text on upload using GPT-4o mini
- ✅ Stores output in `_wp_attachment_image_alt`

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

The prompt text itself is optimised for clear, accessible alt descriptions.

---

## 📜 License

This plugin is licensed under the GNU General Public License v2.0 or later.  
See: [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

> Developed with ❤️ by [Connor Bulmer](https://connorbulmer.co.uk)  
> Accessibility, SEO, and automation — all in one plugin.
