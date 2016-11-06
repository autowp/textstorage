USE autowp_test;

CREATE TABLE IF NOT EXISTS `textstorage_revision` (
  `text_id` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `text` text NOT NULL,
  `timestamp` timestamp NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`text_id`,`revision`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `textstorage_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `last_updated` timestamp NOT NULL,
  `revision` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31128 DEFAULT CHARSET=utf8;

ALTER TABLE `textstorage_revision`
  ADD CONSTRAINT `textstorage_revision_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `textstorage_text` (`id`);
