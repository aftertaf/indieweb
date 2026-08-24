# **CHANGELOG.md — IndieWeb Content Plugin**

## **1.2.0 — 2026‑08‑24**
### Major Feature Release  
This version introduces full IndieWeb identity integration, Micropub property mapping, and Bridgy Fed compatibility.

#### Added
- **Micropub → Joomla custom fields mapping**
  - Automatically stores:
    - `post_type`
    - `in-reply-to`
    - `like-of`
    - `repost-of`
    - `photo[]`
    - `category[]`
    - `mp-syndicate-to[]`
  - Enables correct mf2 rendering for Micropub‑created replies, likes, reposts, photos, categories, and syndication links.

- **Identity metadata injection**
  - Configurable `rel=me` URLs
  - Configurable homepage h‑card (name, URL, photo, note)
  - Automatic insertion of hidden global h‑card on all pages

- **IndieAuth discovery**
  - Configurable `authorization_endpoint`
  - Configurable `token_endpoint`

- **WebFinger LRDD hint**
  - Configurable LRDD URL
  - Injected automatically into `<head>`

- **Webmention + Micropub discovery links**
  - `<link rel="webmention" href="/webmention">`
  - `<link rel="micropub" href="/micropub">`

- **Bridgy Fed integration**
  - Automatic ActivityPub discovery link:
    - `<link rel="alternate" type="application/activity+json" href="https://fed.brid.gy/r/URL">`
  - Automatic `<a class="u-bridgy-fed">` injection before `</body>`

#### Improved
- Unified plugin architecture: identity, mf2 rendering, and Micropub processing now live in a single plugin.
- More robust mf2 output for:
  - `.h-entry`
  - `.p-name`
  - `.e-content`
  - `.dt-published`
  - `.dt-updated`
  - `.u-url`
  - `.p-author.h-card`
  - `.u-in-reply-to`
  - `.u-like-of`
  - `.u-repost-of`
  - `.u-photo`
  - `.u-category`
  - `.u-syndication`

#### Fixed
- Ensured homepage h‑card is always discoverable by IndieAuth and Bridgy Fed.
- Ensured mf2 markup is injected only on frontend article views.
- Corrected ordering of injected metadata in `<head>`.

---

## **1.1.0 — 2026‑08‑20**
### Identity & Bridgy Fed Prep

#### Added
- ActivityPub discovery link for Bridgy Fed.
- Basic h‑entry wrapper for Joomla articles.
- Basic author h‑card injection.
- Bridgy Fed trigger on article pages.

#### Improved
- More consistent mf2 markup for article metadata.

---

## **1.0.0 — 2026‑08‑15**
### Initial Release

#### Added
- Basic h‑entry wrapper.
- Basic h‑card for article authors.
- Basic mf2 markup for:
  - title
  - published date
  - updated date
  - canonical URL
