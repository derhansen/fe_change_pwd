#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
    change_password_code_hash varchar(255) DEFAULT '' NOT NULL,
    change_password_code_expiry_date int(11) DEFAULT '0' NOT NULL,
);
