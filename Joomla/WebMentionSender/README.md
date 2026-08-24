# Webmention Sender (Content Plugin)

This Joomla 6 content plugin automatically queues outgoing Webmentions whenever
an article is saved or updated.

## Features

- Extracts Webmention targets from:
  - Custom fields: `in_reply_to`, `like_of`, `repost_of`, `syndication`, `photo`, `mf_category`
  - Article body links (`introtext` + `fulltext`)
- Inserts outgoing mentions into `#__webmention_queue`
- Works with the Scheduled Task plugin to send Webmentions asynchronously
- Fully Joomla 6–compatible

## Queue Table

Created automatically:

sql
#__webmention_queue
