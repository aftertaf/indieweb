CREATE TABLE IF NOT EXISTS `#__webmentions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(1024) NOT NULL,
  `target` VARCHAR(1024) NOT NULL,
  `author` VARCHAR(255) DEFAULT '',
  `content` TEXT,
  `published` VARCHAR(64) DEFAULT '',
  `type` VARCHAR(32) DEFAULT 'mention',
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;