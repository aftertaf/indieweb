# Micropub System Plugin (Joomla 6)

A Joomla system plugin providing a full Micropub endpoint:

- IndieAuth bearer token validation
- Create / update / delete Micropub actions
- JSON and form-encoded Micropub requests
- Media endpoint for file uploads
- Compatible with IndieWeb tools (Indigenous, Quill, Omnibear)

## Endpoints

- `POST /micropub` — main Micropub endpoint
- `POST /micropub/media` — media upload endpoint
- `GET /micropub` — discovery (returns media endpoint URL)

## Features

- Token validation against `#__micropub_tokens`
- Scope-based access control (`create`, `update`, `delete`)
- Article creation via `com_content`
- Article update and delete via Micropub actions
- Media uploads stored under `images/micropub` (configurable)

## Installation

1. Zip the plugin folder:

   - `micropub.php`
   - `micropub.xml`
   - `sql/install.mysql.utf8.sql`

2. Install via Joomla Extension Manager.

3. Enable **System - Micropub** plugin.

4. Configure:

   - Default category ID
   - Media upload path

## Token Storage

Tokens are stored in:

### sql
#__micropub_tokens (token, scope, created)
