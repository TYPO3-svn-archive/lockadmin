

CREATE TABLE sys_lockedrecords (
	uid int(11) unsigned DEFAULT '0' NOT NULL,
	userid int(11) DEFAULT '0' NOT NULL,
	userid_nokey int(11) unsigned DEFAULT '0' NOT NULL,
	real_pid int(11) unsigned DEFAULT '0' NOT NULL,
	hash varchar(40) DEFAULT '' NOT NULL,
	PRIMARY KEY (userid,record_table,record_uid,hash)
);

CREATE TABLE tx_lockadmin_messages (
  uid int(11) unsigned NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  recipient int(11) unsigned DEFAULT '0' NOT NULL,
  sent int(3) unsigned DEFAULT '0' NOT NULL,
  urgent int(3) unsigned DEFAULT '0' NOT NULL,
  message mediumblob NOT NULL,
  PRIMARY KEY (uid)
);


