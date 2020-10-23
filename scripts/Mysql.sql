CREATE TABLE IF NOT EXISTS `typecho_notice` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `coid` int(11) unsigned NOT NULL,
  `type` text NOT NULL,
  `log` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MYISAM DEFAULT CHARSET=%charset%;