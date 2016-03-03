#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_fluidpages_templatefile varchar(255) DEFAULT NULL,
	tx_fluidpages_layout varchar(64) DEFAULT NULL,
	tx_fed_page_flexform text,
	tx_fed_page_flexform_sub text,
	tx_fed_page_controller_action varchar(255) DEFAULT '' NOT NULL,
	tx_fed_page_controller_action_sub varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'pages_language_overlay'
#
CREATE TABLE pages_language_overlay (
	tx_fed_page_flexform text,
	tx_fed_page_flexform_sub text
);
