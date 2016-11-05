# Create database
CREATE DATABASE IF NOT EXISTS coinflipbot
	CHARSET = UTF8;

# use database coinflipbot
USE coinflipbot;

# Create tables
CREATE TABLE comments__parsed
(
	id           BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	comment_name VARCHAR(255)    NOT NULL,
	timestamp    INT             NOT NULL,
	parse_type   INT             NOT NULL,
	hit          INT             NOT NULL
);
CREATE INDEX comments__parsed_comment_name_index ON comments__parsed (comment_name);
CREATE INDEX comments__parsed_timestamp_index ON comments__parsed (timestamp);
CREATE INDEX comments__parsed_parse_type_index ON comments__parsed (parse_type);
CREATE INDEX comments__parsed_hit_index ON comments__parsed (hit);

CREATE TABLE comments__replied
(
	id             INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	comment_name   VARCHAR(255),
	timestamp      INT             NOT NULL,
	flip           INT             NULL,
	ban            INT             NULL,
	whitelist      INT             NULL,
	`user`         VARCHAR(255)    NOT NULL,
	subreddit_name VARCHAR(255)    NOT NULL,
	post_name      VARCHAR(255)    NOT NULL,
	post_title     VARCHAR(255)    NOT NULL,
	url            VARCHAR(255)    NOT NULL,
	reply          TEXT            NOT NULL
);

CREATE INDEX comments__replied_comment_name_index ON comments__replied (comment_name);
CREATE INDEX comments__replied_timestamp_index ON comments__replied (timestamp);
CREATE INDEX comments__replied_flip_index ON comments__replied (flip);
CREATE INDEX comments__replied_ban_index ON comments__replied (ban);
CREATE INDEX comments__replied_whitelist_index ON comments__replied (whitelist);
CREATE INDEX comments__replied_user_index ON comments__replied (`user`);
CREATE INDEX comments__replied_subreddit_name_index ON comments__replied (subreddit_name);
CREATE INDEX comments__replied_post_name_index ON comments__replied (post_name);
CREATE INDEX comments__replied_post_title_index ON comments__replied (post_title);
CREATE INDEX comments__replied_url_index ON comments__replied (url);

CREATE TABLE messages__parsed
(
	id           BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	message_name VARCHAR(255)    NOT NULL,
	timestamp    INT             NOT NULL,
	parse_type   INT             NOT NULL,
	hit          INT             NOT NULL
);
CREATE INDEX messages__parsed_comment_name_index ON messages__parsed (message_name);
CREATE INDEX messages__parsed_timestamp_index ON messages__parsed (timestamp);
CREATE INDEX messages__parsed_parse_type_index ON messages__parsed (parse_type);
CREATE INDEX messages__parsed_hit_index ON messages__parsed (hit);

CREATE TABLE messages__replied
(
	id             INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	message_name   VARCHAR(255),
	timestamp      INT             NOT NULL,
	flip           INT             NULL,
	ban            INT             NULL,
	`user`         VARCHAR(255)    NOT NULL,
	reply          TEXT            NOT NULL
);

CREATE INDEX messages__replied_comment_name_index ON messages__replied (message_name);
CREATE INDEX messages__replied_timestamp_index ON messages__replied (timestamp);
CREATE INDEX messages__replied_flip_index ON messages__replied (flip);
CREATE INDEX messages__replied_ban_index ON messages__replied (ban);
CREATE INDEX messages__replied_user_index ON messages__replied (`user`);

CREATE TABLE `subreddits__ignored` (
	`id`                     INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	`subreddit_name`         VARCHAR(255)    		  DEFAULT NULL,
	`ban_thing_name`         VARCHAR(255)             DEFAULT NULL,
	`ban_requested_by_mod`   VARCHAR(255)             DEFAULT NULL,
	`ban_timestamp`          INT(11)                  DEFAULT NULL,
	`unban`                  INT(11)         NULL,
	`unban_thing_name`       VARCHAR(255)             DEFAULT NULL,
	`unban_requested_by_mod` VARCHAR(255)             DEFAULT NULL,
	`unban_timestamp`        INT(11)                  DEFAULT NULL,
	`display_public`         INT(11)         NOT NULL DEFAULT '1'
);

CREATE INDEX subreddits__ignored_subreddit_name_index ON subreddits__ignored (subreddit_name);
CREATE INDEX subreddits__ignored_ban_thing_name_index ON subreddits__ignored (ban_thing_name);
CREATE INDEX subreddits__ignored_ban_requested_by_mod_index ON subreddits__ignored (ban_requested_by_mod);
CREATE INDEX subreddits__ignored_ban_timestamp_index ON subreddits__ignored (ban_timestamp);
CREATE INDEX subreddits__ignored_unban_index ON subreddits__ignored (unban);
CREATE INDEX subreddits__ignored_unban_thing_name_index ON subreddits__ignored (unban_thing_name);
CREATE INDEX subreddits__ignored_unban_requested_by_mod_index ON subreddits__ignored (unban_requested_by_mod);
CREATE INDEX subreddits__ignored_unban_timestamp_index ON subreddits__ignored (unban_timestamp);
CREATE INDEX subreddits__ignored_display_public_index ON subreddits__ignored (display_public);

CREATE TABLE `subreddits__whitelisted` (
	`id`                           INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	`subreddit_name`               VARCHAR(255)    NOT NULL,
	`whitelist_comment_name`       VARCHAR(255)             DEFAULT NULL,
	`whitelist_requested_by_mod`   VARCHAR(255)             DEFAULT NULL,
	`whitelist_timestamp`          INT(11)                  DEFAULT NULL,
	`unwhitelist`                  INT(11)         NULL,
	`unwhitelist_comment_name`     VARCHAR(255)             DEFAULT NULL,
	`unwhitelist_requested_by_mod` VARCHAR(255)             DEFAULT NULL,
	`unwhitelist_timestamp`        INT(11)                  DEFAULT NULL,
	`display_public`               INT(11)         NOT NULL DEFAULT '1'
);

CREATE INDEX subreddits__whitelisted_name_index ON subreddits__whitelisted (subreddit_name);
CREATE INDEX subreddits__whitelisted_whitelist_comment_name_index ON subreddits__whitelisted (whitelist_comment_name);
CREATE INDEX subreddits__whitelisted_whitelist_requested_by_mod_index ON subreddits__whitelisted (whitelist_requested_by_mod);
CREATE INDEX subreddits__whitelisted_whitelist_timestamp_index ON subreddits__whitelisted (whitelist_timestamp);
CREATE INDEX subreddits__whitelisted_unwhitelist_index ON subreddits__whitelisted (unwhitelist);
CREATE INDEX subreddits__whitelisted_unwhitelist_comment_name_index ON subreddits__whitelisted (unwhitelist_comment_name);
CREATE INDEX subreddits__whitelisted_unwhitelist_requested_by_mod_index ON subreddits__whitelisted (unwhitelist_requested_by_mod);
CREATE INDEX subreddits__whitelisted_unwhitelist_timestamp_index ON subreddits__whitelisted (unwhitelist_timestamp);
CREATE INDEX subreddits__whitelisted_display_public_index ON subreddits__whitelisted (display_public);

# Statistics views

CREATE OR REPLACE VIEW stats__flips_side AS
	SELECT
		COUNT( id )                             AS total,
		sum( (`comments__replied`.`flip` = 1) ) AS `heads`,
		sum( (`comments__replied`.`flip` = 0) ) AS `tails`
	FROM `comments__replied`
	WHERE flip IS NOT NULL;

CREATE OR REPLACE VIEW stats__flips_weekday AS
	SELECT
		WEEKDAY( FROM_UNIXTIME( `timestamp` ) ) AS weekday,
		COUNT( id )                             AS total,
		IFNULL( SUM( flip = 1 ), 0 )            AS heads,
		IFNULL( SUM( flip = 0 ), 0 )            AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
	GROUP BY weekday
	ORDER BY weekday;

CREATE OR REPLACE VIEW stats__flips_month AS
	SELECT
		MONTH( FROM_UNIXTIME( `timestamp` ) ) AS month,
		COUNT( id )                           AS total,
		IFNULL( SUM( flip = 1 ), 0 )          AS heads,
		IFNULL( SUM( flip = 0 ), 0 )          AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
	GROUP BY month
	ORDER BY month;

CREATE OR REPLACE VIEW stats__flips_today AS
	SELECT
		COUNT( id )                  AS total,
		IFNULL( SUM( flip = 1 ), 0 ) AS heads,
		IFNULL( SUM( flip = 0 ), 0 ) AS tails
	FROM comments__replied
	WHERE UNIX_TIMESTAMP( CURDATE( ) ) <= `timestamp`
		  AND UNIX_TIMESTAMP( CURDATE( ) + INTERVAL 1 DAY ) >= `timestamp`
		  AND flip IS NOT NULL;

CREATE OR REPLACE VIEW stats__flips_yesterday AS
	SELECT
		COUNT( id )                  AS total,
		IFNULL( SUM( flip = 1 ), 0 ) AS heads,
		IFNULL( SUM( flip = 0 ), 0 ) AS tails
	FROM comments__replied
	WHERE UNIX_TIMESTAMP( CURDATE( ) - INTERVAL 1 DAY ) <= `timestamp`
		  AND UNIX_TIMESTAMP( CURDATE( ) ) >= `timestamp`
		  AND flip IS NOT NULL;

CREATE OR REPLACE VIEW stats__flips_current_month AS
	SELECT
		COUNT( id )                  AS total,
		IFNULL( SUM( flip = 1 ), 0 ) AS heads,
		IFNULL( SUM( flip = 0 ), 0 ) AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
		  AND YEAR( FROM_UNIXTIME( TIMESTAMP ) ) = YEAR( NOW( ) )
		  AND MONTH( FROM_UNIXTIME( TIMESTAMP ) ) >= MONTH( NOW( ) );

CREATE OR REPLACE VIEW stats__flips_current_year AS
	SELECT
		COUNT( id )                  AS total,
		IFNULL( SUM( flip = 1 ), 0 ) AS heads,
		IFNULL( SUM( flip = 0 ), 0 ) AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
		  AND YEAR( FROM_UNIXTIME( TIMESTAMP ) ) = YEAR( NOW( ) );

CREATE OR REPLACE VIEW stats__flips_subreddit AS
	SELECT
		subreddit_name,
		COUNT( id )     AS total,
		SUM( flip = 1 ) AS heads,
		SUM( flip = 0 ) AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
	GROUP BY subreddit_name
	ORDER BY total DESC;

CREATE OR REPLACE VIEW stats__flips_subreddit AS
	SELECT
		subreddit_name,
		COUNT( id )     AS total,
		SUM( flip = 1 ) AS heads,
		SUM( flip = 0 ) AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
	GROUP BY subreddit_name
	ORDER BY total DESC;

CREATE OR REPLACE VIEW stats__flips_hour AS
	SELECT
		HOUR( FROM_UNIXTIME( `timestamp` ) ) AS hour,
		COUNT( * )                           AS total,
		SUM( flip = 1 )                      AS heads,
		SUM( flip = 0 )                      AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
	GROUP BY hour
	ORDER BY hour;

CREATE OR REPLACE VIEW stats__flips_user AS
	SELECT
		`user`,
		COUNT( id )     AS total,
		SUM( flip = 1 ) AS heads,
		SUM( flip = 0 ) AS tails
	FROM comments__replied
	WHERE flip IS NOT NULL
	GROUP BY `user`
	ORDER BY total DESC;

CREATE OR REPLACE VIEW stats__flips_projection_current_month AS
	SELECT
		COUNT( id )                                                                      AS total,
		DAYOFMONTH( NOW( ) )                                                             AS today,
		DAYOFMONTH( LAST_DAY( NOW( ) ) )                                                 AS lastday,
		ROUND( (COUNT( id ) / DAYOFMONTH( NOW( ) )) * DAYOFMONTH( LAST_DAY( NOW( ) ) ) ) AS projection
	FROM comments__replied
	WHERE flip IS NOT NULL
		  AND YEAR( FROM_UNIXTIME( `timestamp` ) ) = YEAR( NOW( ) )
		  AND MONTH( FROM_UNIXTIME( `timestamp` ) ) = MONTH( NOW( ) );

CREATE OR REPLACE VIEW stats__flips_projection_current_week AS
	SELECT
		COUNT( id )                                    AS total,
		WEEKDAY( NOW( ) )                              AS today,
		7                                              AS lastday,
		ROUND( (COUNT( id ) / WEEKDAY( NOW( ) )) * 7 ) AS projection
	FROM comments__replied
	WHERE flip IS NOT NULL
		  AND YEAR( FROM_UNIXTIME( `timestamp` ) ) = YEAR( NOW( ) )
		  AND WEEK( FROM_UNIXTIME( `timestamp` ) ) = WEEK( NOW( ), 1 );

CREATE OR REPLACE VIEW stats__flips_projection_current_year AS
	SELECT
		COUNT( id )                                                                               AS total,
		DAYOFYEAR( NOW( ) )                                                                       AS today,
		DAYOFYEAR( LAST_DAY( DATE_ADD( NOW( ), INTERVAL 12 - MONTH( NOW( ) ) MONTH ) ) )          AS lastday,
		ROUND( (COUNT( id ) / DAYOFYEAR( NOW( ) )) *
			   DAYOFYEAR( LAST_DAY( DATE_ADD( NOW( ), INTERVAL 12 - MONTH( NOW( ) ) MONTH ) ) ) ) AS projection
	FROM comments__replied
	WHERE flip IS NOT NULL
		  AND YEAR( FROM_UNIXTIME( `timestamp` ) ) = YEAR( NOW( ) );