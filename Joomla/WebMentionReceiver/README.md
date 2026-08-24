# Webmention Receiver (System Plugin)

This Joomla 6 system plugin provides a Webmention endpoint at: /webmention


## Features

- Accepts incoming Webmentions via POST
- Fetches the source URL
- Detects Webmention type:
  - `reply`
  - `like`
  - `repost`
  - `follow`
  - `mention`
- Stores incoming mentions in `#__webmention_received`
- Fully Joomla 6–compatible

## Incoming Table

Created automatically:

sql
#__webmention_received
