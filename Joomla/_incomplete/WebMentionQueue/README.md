# Webmention Queue Processor (Scheduled Task Plugin)

This Joomla 6 task plugin processes outgoing Webmentions stored in
`#__webmention_queue`.

## Features

- Runs via Joomla Scheduled Tasks (no server cron required)
- Discovers Webmention endpoints from target URLs
- Sends Webmentions using Joomla HTTP client
- Updates queue status:
  - `pending`
  - `done`
  - `failed`
- Logs endpoint responses
- Fully Joomla 6–compatible

## Queue Table

Used by both sender + task:

sql
#__webmention_queue
