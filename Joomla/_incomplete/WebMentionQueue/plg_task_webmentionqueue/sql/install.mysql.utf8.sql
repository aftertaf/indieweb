CREATE TABLE IF NOT EXISTS `#__webmention_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(1024) NOT NULL,
  `target` VARCHAR(1024) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `created` DATETIME NOT NULL,
  `last_attempt` DATETIME NULL,
  `response` TEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
