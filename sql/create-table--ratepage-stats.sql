CREATE TABLE IF NOT EXISTS /*_*/ratepage_stats (
  `rs_page_id` int(10) unsigned NOT NULL,
  `rs_day` int(8) NOT NULL,
  `rs_hits` int(10) NOT NULL default 0,
  PRIMARY KEY  (`rs_page_id`,`rs_day`)
) /*$wgDBTableOptions*/;