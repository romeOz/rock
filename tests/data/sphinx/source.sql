/**
 * This is the MySQL database schema for creation of the test Sphinx index sources.
 */

DROP TABLE IF EXISTS sphinx_article;
DROP TABLE IF EXISTS sphinx_category;
DROP TABLE IF EXISTS sphinx_tag;
DROP TABLE IF EXISTS sphinx_article_tag;

CREATE TABLE IF NOT EXISTS `sphinx_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category_id` int(11) unsigned NOT NULL,
  `author_id` int(11) NOT NULL,
  `create_date` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sphinx_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `price` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sphinx_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sphinx_article_tag` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`article_id`,`tag_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `sphinx_article` (`id`, `title`, `content`, `category_id`, `author_id`, `create_date`) VALUES
(1, 'About cats', 'This article is about cats', 1, 1, FROM_UNIXTIME(UNIX_TIMESTAMP('2012-11-23 00:00:00'))),
(2, 'About dogs', 'This article is about dogs', 2, 2, FROM_UNIXTIME(UNIX_TIMESTAMP('2012-12-15 00:00:00')));

INSERT INTO `sphinx_category` (`id`, `title`, `price`) VALUES
(1, 'Football', 2.5),
(2, 'Tennis', 100);

INSERT INTO `sphinx_tag` (`id`, `title`) VALUES
(1, 'tag1'),
(2, 'tag2'),
(3, 'tag3'),
(4, 'tag4');

INSERT INTO `sphinx_article_tag` (`article_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 3),
(2, 4);