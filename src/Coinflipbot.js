/*jshint esversion: 6 */
/*jshint sub:true*/
'use strict';

const snoowrap = require('snoowrap');
const mapper = require('./Mapper/MysqlMapper.js');
const Parser = require('./Parser.js');
const ActionDelegate = require('./ActionDelegate.js');

class Coinflipbot {
    /**
     * @param {Config} config
     */
    constructor(config) {
        this.config = config;
        this.mapper = new mapper(this.config);
        this.connect();
    }

    /**
     * Parse types. The order of the list is important since it will be respected by the parsing process. Flipping a
     * coin first when the bot should be banned would be problematic. Trying to flip when the bot should be unbanned
     * first would be worse.
     *
     * @returns {{
     *  flip: number, ban: number, unban: number, whitelist: number, unwhitelist: number, whitelistFlip: number
     * }}
     */
    static get parseTypes() {
        return {
            ban: 2,
            unban: 4,
            whitelist: 8,
            unwhitelist: 16,
            flip: 1,
            whitelistFlip: 32,
        };
    }

    /**
     * Start a new instance of snoowrap and connect to the reddit API.
     */
    connect() {
        this.snoowrapInstance = new snoowrap(this.config.snoowrap);
    }

    /**
     * Parse a comment.
     *
     * @param {object} comment
     * @param {string} parseType
     * @param {function} callback
     */
    parseComment(comment, parseType, callback) {
        if (!parseType) {
            throw new Error('No parse type provided!');
        }

        this.mapper.hasProcessedComment(comment, Coinflipbot.parseTypes[parseType], (hasProcessed) => {
            if (hasProcessed) {
                // Apply the callback, tell them that success = false, because comment was already processed.
                callback.apply(this, [false, false]);
            } else {
                // Delegate parsing to the actual parser object.
                let parser = new Parser(this.config, parseType);
                parser.parse(comment, callback);
            }
        });
    }

    /**
     * Perform an action with the reddit API.
     *
     * @param {string} parseType
     * @param {object} thing
     * @param {function} callback
     */
    performActionForParseType(parseType, thing, callback) {
        if (!parseType) {
            throw new Error('No parse type provided');
        }

        let actionDelegate = new ActionDelegate(this.config, parseType);
        actionDelegate.handle(thing, callback);
    }

    /**
     * Parse the latest comments
     */
    parseComments(callback) {
        let subreddit     = this.snoowrapInstance.getSubreddit(this.config.subreddit);

        console.info(`Requesting ${this.config.commentParser.limit} comments from ${this.config.subreddit}`);
        subreddit.getNewComments(this.config.commentParser).then((listing) => {

            if (listing.length) {
                let listingLength = listing.length;
                let processedComments = 0;
                console.info(`Parsing ${listing.length} comments`);

                listing.forEach((comment) => {
                    // Try and parse the comment for each given parse type
                    Object.keys(Coinflipbot.parseTypes).forEach((parseType) => {
                        // Parse the comment for the current parse type
                        this.parseComment(comment, parseType, (success, result) => {
                            if (success) {
                                // Mark comment as processed
                                console.info(`Marking comment ${comment.id} as processed for parse type ${parseType}.`);
                                this.mapper.markCommentProcessed(
                                    comment,
                                    Coinflipbot.parseTypes[parseType],
                                    result ? 1 : 0
                                );

                                if (result) {
                                    this.performActionForParseType(parseType, comment, (success, result) => {
                                        if (success && result) {
                                            console.log(`Successfully handled ${comment.name}`);
                                        } else if (!success) {
                                            console.log(`Unable to handle ${comment.name}`);
                                        } else {
                                            console.log(`Something went wrong handling ${comment.name}`);
                                        }
                                    });
                                }
                            }

                            // Keep track of the amount of parsed comments
                            processedComments++;

                            // If the amount of parsed comments equals the listing's length then apply the callback,
                            // letting the callee know the parsing process has been completed.
                            if (processedComments === listingLength && callback) {
                                callback.apply();
                            }
                        });
                    });
                });
            } else {
                // Let the callee know the parsing process has been completed.
                if (callback) {
                    callback.apply();
                }
            }

        });
    }
}

module.exports = Coinflipbot;