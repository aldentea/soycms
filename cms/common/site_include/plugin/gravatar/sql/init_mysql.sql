CREATE TABLE GravatarAccount(
	id INTEGER PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(128),
  mail_address VARCHAR(512) UNIQUE
) ENGINE=InnoDB;
