CREATE TABLE soyinquiry_form (
  id INTEGER primary key AUTO_INCREMENT,
  form_id VARCHAR(128) unique,
  name VARCHAR(255),
  config LONGTEXT
)ENGINE = InnoDB;

CREATE TABLE soyinquiry_column(
  id INTEGER primary key AUTO_INCREMENT,
  form_id VARCHAR(255),
  column_id VARCHAR(255),
  label VARCHAR(255),
  column_type VARCHAR(255),
  config TEXT,
  is_require TINYINT default 0,
  display_order INTEGER default 0
)ENGINE = InnoDB;

CREATE TABLE soyinquiry_inquiry (
  id INTEGER primary key AUTO_INCREMENT,
  tracking_number VARCHAR(255),
  form_id VARCHAR(255),
  content TEXT,
  data TEXT,
  flag TINYINT default 1,
  create_date INTEGER,
  form_url VARCHAR(255)
)ENGINE = InnoDB;

CREATE INDEX soyinquiry_tracking_number_idx on soyinquiry_inquiry(tracking_number);

CREATE TABLE soyinquiry_serverconfig(
	config LONGTEXT
)ENGINE = InnoDB;

CREATE TABLE soyinquiry_comment (
	id INTEGER primary key AUTO_INCREMENT,
	inquiry_id INTEGER not null,
	title VARCHAR(255),
	author VARCHAR(255),
	content TEXT,
	create_date INTEGER
)ENGINE = InnoDB;