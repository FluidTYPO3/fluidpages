INSERT INTO be_users (pid, tstamp, username, password, admin, usergroup, disable, starttime, endtime, lang, email) VALUES (0,1276860841,'_cli_lowlevel','5f4dcc3b5aa765d61d8327deb882cf99',0,'1',0,0,0,'','_cli_phpunit@example.com');
ALTER TABLE `pages` ADD `tx_fed_page_flexform` text NOT NULL;
ALTER TABLE `pages` ADD `tx_fed_page_flexform_sub` text NOT NULL;
ALTER TABLE `pages` ADD `tx_fed_page_controller_action` varchar(255) DEFAULT '' NOT NULL;
ALTER TABLE `pages` ADD `tx_fed_page_controller_action_sub` varchar(255) DEFAULT '' NOT NULL;