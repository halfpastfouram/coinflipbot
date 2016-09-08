# Create database
CREATE DATABASE IF NOT EXISTS coinflipbot
	CHARSET = UTF8;

# Create tables
CREATE TABLE coinflipbot.comments__parsed
(
	id           INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	comment_name VARCHAR(255)    NOT NULL,
	timestamp    INT             NOT NULL,
	hit          INT             NOT NULL
);
CREATE UNIQUE INDEX comments__parsed_comment_name_uindex ON coinflipbot.comments__parsed (comment_name);
CREATE INDEX comments__parsed_timestamp_index ON coinflipbot.comments__parsed (timestamp);
CREATE INDEX comments__parsed_hit_index ON coinflipbot.comments__parsed (hit);

CREATE TABLE coinflipbot.comments__replied
(
	id             INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	comment_name   VARCHAR(255),
	timestamp      INT             NOT NULL,
	flip           INT             NOT NULL,
	`user`         VARCHAR(255)    NOT NULL,
	subreddit_name VARCHAR(255)    NOT NULL,
	post_name      VARCHAR(255)    NOT NULL,
	post_title     VARCHAR(255)    NOT NULL,
	url            VARCHAR(255)    NOT NULL,
	reply          TEXT            NOT NULL
);
CREATE UNIQUE INDEX comments__replied_comment_name_uindex ON coinflipbot.comments__replied (comment_name);
CREATE INDEX comments__replied_timestamp_index ON coinflipbot.comments__replied (timestamp);
CREATE INDEX comments__replied_flip_index ON coinflipbot.comments__replied (flip);
CREATE INDEX comments__replied_user_index ON coinflipbot.comments__replied (`user`);
CREATE INDEX comments__replied_subreddit_name_index ON coinflipbot.comments__replied (subreddit_name);
CREATE INDEX comments__replied_post_name_index ON coinflipbot.comments__replied (post_name);
CREATE INDEX comments__replied_post_title_index ON coinflipbot.comments__replied (post_title);
CREATE INDEX comments__replied_url_index ON coinflipbot.comments__replied (url);