CREATE TABLE IF NOT EXISTS /*_*/ratepage_vote (
  `rv_page_id` int(10) unsigned NOT NULL,
  `rv_user` varchar(255) NOT NULL,
  `rv_ip` varchar(255) default NULL,
  `rv_answer` int(3) NOT NULL,
  `rv_date` datetime NOT NULL,
  PRIMARY KEY  (`rv_page_id`,`rv_user`)
) /*$wgDBTableOptions*/;