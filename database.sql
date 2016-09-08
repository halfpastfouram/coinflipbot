# Create database
CREATE DATABASE IF NOT EXISTS coinflipbot
	CHARSET = UTF8;

# Create tables
CREATE TABLE coinflipbot.comments__parsed
(
	id           INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	comment_name VARCHAR(255)    NOT NULL,
	timestamp    INT             NOT NULL,
	parse_type   INT             NOT NULL,
	hit          INT             NOT NULL
);
CREATE INDEX comments__parsed_comment_name_index ON coinflipbot.comments__parsed (comment_name);
CREATE INDEX comments__parsed_timestamp_index ON coinflipbot.comments__parsed (timestamp);
CREATE INDEX comments__parsed_parse_type_index ON coinflipbot.comments__parsed (parse_type);
CREATE INDEX comments__parsed_hit_index ON coinflipbot.comments__parsed (hit);

CREATE TABLE coinflipbot.comments__replied
(
	id             INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	comment_name   VARCHAR(255),
	timestamp      INT             NOT NULL,
	flip           INT             NULL,
	ban            INT             NULL,
	`user`         VARCHAR(255)    NOT NULL,
	subreddit_name VARCHAR(255)    NOT NULL,
	post_name      VARCHAR(255)    NOT NULL,
	post_title     VARCHAR(255)    NOT NULL,
	url            VARCHAR(255)    NOT NULL,
	reply          TEXT            NOT NULL
);
CREATE INDEX comments__replied_comment_name_index ON coinflipbot.comments__replied (comment_name);
CREATE INDEX comments__replied_timestamp_index ON coinflipbot.comments__replied (timestamp);
CREATE INDEX comments__replied_flip_index ON coinflipbot.comments__replied (flip);
CREATE INDEX comments__replied_ban_index ON coinflipbot.comments__replied (ban);
CREATE INDEX comments__replied_user_index ON coinflipbot.comments__replied (`user`);
CREATE INDEX comments__replied_subreddit_name_index ON coinflipbot.comments__replied (subreddit_name);
CREATE INDEX comments__replied_post_name_index ON coinflipbot.comments__replied (post_name);
CREATE INDEX comments__replied_post_title_index ON coinflipbot.comments__replied (post_title);
CREATE INDEX comments__replied_url_index ON coinflipbot.comments__replied (url);

CREATE TABLE `subreddits__ignored` (
	`id`                     INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	`subreddit_name`         VARCHAR(255)    NOT NULL,
	`ban_comment_name`       VARCHAR(255)             DEFAULT NULL,
	`ban_requested_by_mod`   VARCHAR(255)             DEFAULT NULL,
	`ban_timestamp`          INT(11)                  DEFAULT NULL,
	`unban`                  INT(11)         NULL,
	`unban_comment_name`     VARCHAR(255)             DEFAULT NULL,
	`unban_requested_by_mod` VARCHAR(255)             DEFAULT NULL,
	`unban_timestamp`        INT                      DEFAULT NULL,
	`display_public`         INT(11)         NOT NULL DEFAULT '1'
);

CREATE INDEX subreddits__ignored_subreddit_name_index ON coinflipbot.subreddits__ignored (subreddit_name);
CREATE INDEX subreddits__ignored_ban_comment_name_index ON coinflipbot.subreddits__ignored (ban_comment_name);
CREATE INDEX subreddits__ignored_ban_requested_by_mod_index ON coinflipbot.subreddits__ignored (ban_requested_by_mod);
CREATE INDEX subreddits__ignored_ban_timestamp_index ON coinflipbot.subreddits__ignored (ban_timestamp);
CREATE INDEX subreddits__ignored_unban_index ON coinflipbot.subreddits__ignored (unban);
CREATE INDEX subreddits__ignored_unban_comment_name_index ON coinflipbot.subreddits__ignored (unban_comment_name);
CREATE INDEX subreddits__ignored_unban_requested_by_mod_index ON coinflipbot.subreddits__ignored (unban_requested_by_mod);
CREATE INDEX subreddits__ignored_unban_timestamp_index ON coinflipbot.subreddits__ignored (unban_timestamp);
CREATE INDEX subreddits__ignored_display_public_index ON coinflipbot.subreddits__ignored (display_public);