CREATE TABLE user_groups (
	id SMALLINT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
	name VARCHAR(255) NOT NULL,
	admin TINYINT UNSIGNED NOT NULL DEFAULT 0
) CHARACTER SET utf8 COLLATE utf8_unicode_ci;

INSERT INTO user_groups (name, admin) VALUES ("Admins", 1), ("Users", 0);

CREATE TABLE users (
	id INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
	group_id SMALLINT UNSIGNED NOT NULL REFERENCES user_groups (id),
	name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	password VARCHAR(255) NULL,
	active TINYINT UNSIGNED NOT NULL DEFAULT 1,
	created DATETIME NOT NULL,
	updated DATETIME NOT NULL,
	-- Facebook user ID is actually a string but this could change
	facebook_id VARCHAR(1024) NULL,
	twitter_id VARCHAR(1024) NULL
) CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE UNIQUE INDEX user_email ON users (email);

-- MySQL 5.5 doesn't have support for DATETIME DEFAULT CURRENT_TIMESTAMP,
-- so we need triggers!

DELIMITER ;;

CREATE TRIGGER users_insert_date BEFORE INSERT ON users FOR EACH ROW
BEGIN
    SET NEW.created = NOW();
END;;

CREATE TRIGGER users_update_date BEFORE UPDATE ON users FOR EACH ROW
BEGIN
    SET NEW.updated = NOW();
END;;


DELIMITER ;