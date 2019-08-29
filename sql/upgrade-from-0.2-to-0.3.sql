ALTER TABLE ratepage_vote ADD COLUMN `rv_contest` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE ratepage_vote DROP PRIMARY KEY, ADD PRIMARY KEY (`rv_page_id`, `rv_contest`, `rv_user`);