/*jshint esversion: 6 */
'use strict';

const mysql = require("mysql");

class MysqlMapper {
    constructor(config) {
        this.config = config;
        this.connection = mysql.createConnection(this.config.mysql);
        this.connection.connect(function (error) {
            if (error) {
                throw new Error('Error connecting to MySQL database');
            }
        });
    }

    /**
     * Check if a comment has been processed
     *
     * @param {object} comment
     * @param {number} parseType
     * @param {function} callback
     */
    hasProcessedComment(comment, parseType, callback) {
        this.connection.query(
            "SELECT * FROM comments__parsed WHERE comment_name = ? AND parse_type = ?",
            [comment.name, parseType],
            (error, result) => {
                if (error) {
                    console.error(error);
                }

                let hasProcessed = false;
                // If a row was found, the comment was processed before
                if (result.length) {
                    hasProcessed = true;
                }

                callback.apply(this, [hasProcessed]);
            }
        );
    }

    /**
     * Mark a comment as processed.
     *
     * @param comment
     * @param parseType
     * @param hit
     */
    markCommentProcessed(comment, parseType, hit) {
        this.connection.query(
            "INSERT INTO comments__parsed SET ?",
            {
                comment_name: comment.name,
                timestamp: Math.floor(Date.now() / 1000),
                parse_type: parseType,
                hit: hit
            },
            (error) => {
                if (error) {
                    console.error(error);
                }
            }
        );
    }
}

module.exports = MysqlMapper;