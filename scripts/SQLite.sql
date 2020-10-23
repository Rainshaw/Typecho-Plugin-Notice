CREATE TABLE IF NOT EXISTS `typecho_notice` (
  `id` INTEGER NOT NULL PRIMARY KEY,
  `coid` INTEGER NOT NULL,
  `type` text NOT NULL,
  `log` text NOT NULL
);