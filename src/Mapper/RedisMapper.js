/*jshint esversion: 6 */
'use strict';

const redis = require("redis");

class RedisMapper {
    constructor() {
        this.redis = redis.createClient();
        this.redis.on('error', (message) => {
            this.error(message);
        });
    }

    error(message) {
        console.error('An error occurred: ', message);
    }

    hasProcessed(comment, callback) {
        this.redis.hget('processed_comment', comment.id, (result) => {
            callback.apply(this, [result !== null]);
        });
    }

    markProcessed(comment) {
        this.redis.hset('processed_comment', 'comment', comment.id);
    }

    getParsedComments() {
        this.redis.hgetall('process_comment', (result) => {
            console.log(result);
        });
        this.redis.hvals('processed_comment', (result) => {
           console.log(result);
        });
    }
}

module.exports = RedisMapper;