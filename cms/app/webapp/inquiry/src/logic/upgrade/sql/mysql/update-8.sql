CREATE TABLE soyinquiry_ban_ip_address(
	ip_address VARCHAR(15) NOT NULL UNIQUE,
	log_date INTEGER
) ENGINE=InnoDB;
