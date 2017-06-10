/*jshint esversion: 6 */
/*jshint sub:true*/
'use strict';

const Message = require('./Message.js');

class ActionDelegate {
    constructor(config, parseType) {
        ActionDelegate.config    = config;
        this.parseType = parseType;
    }

    /**
     * Handle the required action on a thing via the reddit api. Runs the given callback after handling. First provided
     * callback argument should always be TRUE, indicating that the handling process has actually worked. The second
     * argument should be TRUE when the handling process ran successfully.
     *
     * @param thing
     * @param callback
     */
    handle(thing, callback) {
        if (thing.removed) {
            console.log("Thing has been removed.");
            callback.apply(this, [true, false]);
            return;
        }

        if (thing.archived) {
            console.log("Thing has been archived.");
            callback.apply(this, [true, false]);
            return;
        }

        if (thing.author && thing.author.name === ActionDelegate.config.snoowrap.username) {
            console.log("Refusing to handle thing authored by myself.");
            callback.apply(this, [true, false]);
            return;
        }

        let actionMethod = ActionDelegate[this.parseType];
        if (actionMethod) {
            actionMethod(thing, callback);
        } else {
            throw new Error(`Invalid parse method ${this.parseType}`);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     * Reply to a coin flip request
     *
     * @param thing
     * @param callback
     */
    static flip(thing, callback) {
        let commands = ActionDelegate.config.commands['flip'];
        let response = ActionDelegate.config.responses['flip'];
        let responseFooter = ActionDelegate.config.responses['footer'];
        let min = 0;
        let max = 1;
        let result = Math.floor(Math.random() * (max - min + 1) + min);
        let resultString = result ? 'heads' : 'tails';

        for (let i = 0; i < commands.length; i++) {
            if (thing.body.indexOf(commands[i]) >= 0) {
                // Build a message
                let message = new Message();
                message.body = response.replace('{flip-result}', resultString).replace('{author}', thing.author.name);
                message.footer = responseFooter;

                // Reply the message to the thing
                thing.reply(message.toString());

                // Apply the callback and return
                callback.apply(this, [true, true]);
                return;
            }
        }
        callback.apply(ActionDelegate, [true, false]);
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static ban(thing, callback) {
        console.info(thing.name);
        callback.apply(ActionDelegate, [true, false]);
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static unban(thing, callback) {
        console.info(thing.name);
        callback.apply(ActionDelegate, [true, false]);
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static whitelist(thing, callback) {
        console.info(thing.name);
        callback.apply(ActionDelegate, [true, false]);
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static unwhitelist(thing, callback) {
        console.info(thing.name);
        callback.apply(ActionDelegate, [true, false]);
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static whitelistFlip(thing, callback) {
        console.info(thing.name);
        callback.apply(ActionDelegate, [true, false]);
    }
}

module.exports = ActionDelegate;