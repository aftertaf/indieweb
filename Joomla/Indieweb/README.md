## Configuration

The plugin exposes several identity and discovery settings.  
Below are concrete examples showing how each field should be filled out.

### Site Identity Fields

These fields populate your global `.h-card` and author `.h-card`:

- **Full Name:**  
  Example: `David Taffanel`

- **Profile URL:**  
  Example: `https://aftertaf.com/`

- **Photo URL:**  
  Example: `https://aftertaf.com/images/avatar.jpg`

- **Email (optional):**  
  Example: `mailto:david@aftertaf.com`

- **Fediverse Handle (optional):**  
  Example: `@aftertaf@social.aftertaf.com`

These values appear in:

- homepage `.h-card`
- article `.p-author .h-card`
- WebFinger responses
- IndieAuth discovery

---

### WebFinger Username

This defines your identity for:

- Mastodon
- Bridgy Fed
- Fediverse discovery
- Bluesky DID linking

**Example:**
aftertaf@aftertaf.com


If your site is `https://aftertaf.com`:
the plugin will automatically serve: https://aftertaf.com/.well-known/webfinger with the correct JSON resource: acct:aftertaf@aftertaf.com


### IndieAuth Endpoints

These URLs tell clients where to authenticate and verify tokens.

Typical IndieAuth setup:

- **Authorization Endpoint:**  
  Example: `https://indieauth.com/auth`

- **Token Endpoint:**  
  Example: `https://tokens.indieauth.com/token`

If you self‑host IndieAuth:

- **Authorization Endpoint:**  
  Example: `https://aftertaf.com/indieauth/auth`

- **Token Endpoint:**  
  Example: `https://aftertaf.com/indieauth/token`

The plugin injects:
<link rel="authorization_endpoint" href="…">
<link rel="token_endpoint" href="…">

into your homepage and article pages.

### Micropub Endpoint

This is the URL of your Micropub server (`plg_system_micropub`).

**Example:**
https://aftertaf.com/micropub



The plugin injects: <link rel="micropub" href="https://aftertaf.com/micropub">


Micropub clients (Indigenous, Quill, Omnibear) use this to:

- create posts  
- update posts  
- delete posts  
- upload media (via `/micropub/media`)


### Webmention Endpoint

This is the URL of your Webmention receiver (`plg_system_webmentionreceiver`).

**Example:**
https://aftertaf.com/webmention


The plugin injects: <link rel="webmention" href="https://aftertaf.com/webmention">


This allows:

- Bridgy Fed → your site  
- Mastodon → your site  
- IndieWeb sites → your site  
- Likes, replies, reposts → your site

---

## Example Full Configuration

Here’s a realistic configuration for a site like `aftertaf.com`:
        Full Name: David Taffanel
        Profile URL: https://aftertaf.com/
        Photo URL: https://aftertaf.com/images/avatar.jpg
        Email: david@aftertaf.com
        Fediverse Handle: @aftertaf@social.aftertaf.com

        WebFinger Username: aftertaf@aftertaf.com

        Authorization Endpoint: https://indieauth.com/auth
        Token Endpoint: https://tokens.indieauth.com/token

        Micropub Endpoint: https://aftertaf.com/micropub
        Webmention Endpoint: https://aftertaf.com/webmention


This produces:

- full `.h-card` on homepage  
- full `.h-entry` on articles  
- correct WebFinger JSON  
- correct IndieAuth discovery  
- correct Micropub discovery  
- correct Webmention discovery  
- correct Bridgy Fed discovery  
- correct Bluesky DID linking  
- correct ActivityPub discovery  


🚀 Quick‑Start Guide for First‑Time IndieWeb Users
This guide walks a new user through the absolute minimum needed to get a Joomla site IndieWeb‑ready using your plugin stack.

1. Enable the Required Plugins
Enable these plugins in Extensions → Plugins:

Content – IndieWeb

System – Micropub

System – Webmention Receiver

Content – Webmention Sender

Task – Webmention Queue

Content – Webmention Display

2. Create Custom Fields for Articles
Go to Content → Fields → New and create:

in_reply_to (text)

like_of (text)

repost_of (text)

photo (text or media)

mf_category (text)

syndication (text)

post_type (list: note, article, reply, like, repost, photo)

These fields allow Micropub clients to create structured posts.

3. Configure the IndieWeb Plugin
Fill out:

Full Name: David Taffanel

Profile URL: https://aftertaf.com/

Photo URL: https://aftertaf.com/images/avatar.jpg

WebFinger Username: aftertaf@aftertaf.com

Authorization Endpoint: https://indieauth.com/auth

Token Endpoint: https://tokens.indieauth.com/token

Micropub Endpoint: https://aftertaf.com/micropub

Webmention Endpoint: https://aftertaf.com/webmention

4. Create a Scheduled Task
Go to System → Scheduled Tasks → New:

Task Type: Webmention Queue

Frequency: Every 5 minutes

This ensures outgoing Webmentions are delivered.

5. Test Your Setup
Use:

Indigenous (iOS/Android)

Quill (web)

Omnibear (browser extension)

Create a post, reply, like, or photo — verify:

The article is created

Custom fields are populated

mf2 renders correctly

Webmentions are queued

Queue task sends them

🧪 Validation Checklist
A complete checklist to confirm your IndieWeb stack is functioning correctly.

✔ Microformats2 (mf2)
Validate using:

mf2 validator

IndieWebify.me

Check:

.h-entry wraps the article

.p-name shows the title

.e-content contains the body

.dt-published is correct

.u-url matches the canonical URL

.p-author .h-card is present

Custom fields render as:

.u-in-reply-to

.u-like-of

.u-repost-of

.u-photo

.u-category

.u-syndication

✔ WebFinger
Visit:

Code
https://yourdomain.com/.well-known/webfinger?resource=acct:USERNAME@yourdomain.com
Check:

subject matches your acct: URI

aliases include your profile URL

links include:

rel="self"

rel="profile"

rel="webmention"

rel="micropub"

rel="authorization_endpoint"

rel="token_endpoint"

✔ IndieAuth
Check your homepage source contains:

Code
<link rel="authorization_endpoint" href="…">
<link rel="token_endpoint" href="…">
Test login using:

IndieAuth.com

Indigenous

Quill

✔ Micropub
Test with:

Indigenous

Quill

Micropub Rocks

Verify:

POST /micropub creates an article

Custom fields are populated

JSON and form‑encoded requests work

Media uploads work at /micropub/media

Update and delete actions work

✔ Webmention (Receiver)
Send a test Webmention using:

Webmention Rocks

Bridgy Fed

Verify:

/webmention returns 202 Accepted

Entry appears in #__webmention_received

Type detection works (reply, like, repost, follow, mention)

✔ Webmention (Sender + Queue)
Create an article linking to another site.

Verify:

Entry appears in #__webmention_queue

Scheduled Task processes it

Status changes to done

Response is logged

✔ Bridgy Fed
Visit:

Code
https://fed.brid.gy/
Enter your site URL.

Verify:

Bridgy Fed detects your WebFinger

Bridgy Fed detects your h‑card

Bridgy Fed detects your Webmention endpoint

Replies, likes, reposts federate correctly