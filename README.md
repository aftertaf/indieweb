# IndieWeb Content Plugin for Joomla 6  
Adds clean Microformats2 (h‑entry + h‑card + post‑type properties) to Joomla articles, fully compatible with Astroid Framework.

This plugin makes your Joomla site IndieWeb‑aware and ready for Webmention, Micropub, and Bridgy Fed.

---

## ✨ Features

- Wraps Joomla articles in **h-entry**  
- Injects **h-card** for the author  
- Adds **dt-published**, **dt-updated**, **u-url**  
- Supports IndieWeb post types via custom fields:
  - **article**
  - **note**
  - **reply**
  - **like**
  - **repost**
  - **photo**
- Adds **u-in-reply-to**, **u-like-of**, **u-repost-of**, **u-photo**, **u-category**, **u-syndication**
- Adds ActivityPub discovery link for **Bridgy Fed**
- Fully compatible with Astroid (no duplication, no layout breakage)
- Zero CSS/JS required — pure semantic HTML

---

## 📦 Installation

1. Create a ZIP containing:

```
plg_content_indieweb/
│
├── indieweb.php
└── indieweb.xml
```

2. Install via Joomla:

**System → Extensions → Install → Upload Package File**

3. Enable the plugin:

**System → Plugins → Content – Indieweb → Enabled**

No SQL tables or assets are required.

---

## 🧩 How It Works

The plugin hooks into Joomla’s content events:

- `onContentBeforeDisplay()` → opens the `<article class="h-entry">` wrapper  
- `onContentAfterDisplay()` → closes the wrapper and adds Bridgy Fed link  
- `onBeforeCompileHead()` → adds ActivityPub discovery `<link>`  

Astroid splits article rendering into multiple blocks.  
To avoid duplication, the plugin only wraps the **main article body** using:

```
if ($limit !== 0) return '';
```

This ensures microformats appear **once**, in the correct place.

---

## 🏷 Custom Fields (Required for Post Types)

Create the following **Custom Fields** in Joomla:

### Group: **IndieWeb**

| Field Name       | Type    | Description |
|------------------|---------|-------------|
| `post_type`      | List    | article, note, reply, like, repost, photo |
| `in_reply_to`    | Text    | URL you are replying to |
| `like_of`        | Text    | URL you liked |
| `repost_of`      | Text    | URL you reposted |
| `syndication`    | Text    | Comma-separated URLs (Mastodon, Bluesky, etc.) |
| `photo`          | Text    | Comma-separated image URLs |
| `mf_category`    | Text    | Comma-separated tags |
| `hcard_name`     | Text    | Override author name |
| `hcard_url`      | Text    | Override author URL |
| `hcard_photo`    | Text    | Override author photo |

All fields are optional.  
If a field is missing or empty, the plugin silently ignores it.

---

## 📝 Post Type Behavior

### **article** (default)  
Standard blog post. Title is visible.

### **note**  
Short-form post. Title is hidden:

```
<h1 class="p-name" style="display:none;">
```

### **reply**  
Adds:

```
<a class="u-in-reply-to" href="..."></a>
```

### **like**  
Adds:

```
<a class="u-like-of" href="..."></a>
```

### **repost**  
Adds:

```
<a class="u-repost-of" href="..."></a>
```

### **photo**  
Adds one or more:

```
<img class="u-photo" src="...">
```

---

## 🔗 Syndication Links

If you cross‑post to Mastodon, Bluesky, Pixelfed, etc., add their URLs to the `syndication` field:

```
https://mastodon.social/@aftertaf/12345,
https://bsky.app/profile/aftertaf.bsky.social/post/67890
```

The plugin outputs:

```
<a class="u-syndication" href="..."></a>
```

Bridgy Fed uses these to connect your site to ActivityPub.

---

## 🧭 ActivityPub Discovery

The plugin adds:

```
<link rel="alternate"
      type="application/activity+json"
      href="https://fed.brid.gy/r/<current-url>">
```

This enables ActivityPub federation via Bridgy Fed.

---

## 🧪 Debug Mode

Enable debug in plugin settings to log events to:

```
administrator/logs/indieweb.php
```

---

## 🛠 Compatibility

- Joomla 6.x  
- Astroid Framework  
- PHP 8+  
- Works with SEF and non‑SEF URLs  
- No template overrides required  

---

## 📚 Roadmap (Optional Enhancements)

This plugin is designed to work alongside:

- **Webmention Receiver** (Plugin A)  
- **Webmention Sender** (Plugin B)  
- **Micropub Endpoint** (Plugin C)  

All three can be installed independently.

---

## ❤️ Author

**aftertaf**  
Mont Gargan, Normandy  
IndieWeb‑powered Joomla site

---

If you want, I can also generate:

- a **CHANGELOG.md**  
- a **LICENSE file**  
- a **screenshot.png** for Joomla’s extension manager  
- a **GitHub‑ready README** with badges  

Just tell me.
