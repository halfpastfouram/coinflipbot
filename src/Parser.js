/*jshint esversion: 6 */
/*jshint sub:true*/
'use strict';

class Parser {
    constructor(config, parseType) {
        Parser.config    = config;
        Parser.parseType = parseType;
    }

    /**
     * Parse a thing. Runs the given callback after parsing. First provided callback argument should always be TRUE,
     * indicating that the parsing process has actually worked. The second argument should be TRUE when the parsing
     * process finds something inside the thing that should trigger one of the bot's actions.
     *
     * @param thing
     * @param callback
     */
    parse(thing, callback) {
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

        if (thing.author && thing.author.name === Parser.config.snoowrap.username) {
            console.log("Refusing to parse thing authored by myself.");
            callback.apply(this, [true, false]);
            return;
        }

        let parseMethod = Parser[Parser.parseType];
        if (parseMethod) {
            parseMethod(thing, callback);
        } else {
            throw new Error(`Invalid parse method ${Parser.parseType}`);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     *
     * @return bool
     */
    static flip(thing, callback) {
        let commands = Parser.config.commands[Parser.parseType];
        let success  = false;
        commands.forEach((command) => {
            console.log(command, thing.body.toLocaleLowerCase());
            if (thing.body.toLowerCase().indexOf(command) >= 0) {
                success = true;
                callback.apply(this, [true, true]);
            }
        });

        if (! success) {
            callback.apply(this, [true, false]);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static ban(thing, callback) {
        let commands = Parser.config.commands[Parser.parseType];
        let success  = false;
        commands.forEach((command) => {
            if (thing.body.toLowerCase().indexOf(command) >= 0) {
                success = true;
                callback.apply(this, [true, true]);
            }
        });

        if (! success) {
            callback.apply(this, [true, false]);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static unban(thing, callback) {
        let commands = Parser.config.commands[Parser.parseType];
        let success  = false;
        commands.forEach((command) => {
            if (thing.body.toLowerCase().indexOf(command) >= 0) {
                success = true;
                callback.apply(this, [true, true]);
            }
        });

        if (! success) {
            callback.apply(this, [true, false]);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static whitelist(thing, callback) {
        let commands = Parser.config.commands[Parser.parseType];
        let success  = false;
        commands.forEach((command) => {
            if (thing.body.toLowerCase().indexOf(command) >= 0) {
                success = true;
                callback.apply(this, [true, true]);
            }
        });

        if (! success) {
            callback.apply(this, [true, false]);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static unwhitelist(thing, callback) {
        let commands = Parser.config.commands[Parser.parseType];
        let success  = false;
        commands.forEach((command) => {
            if (thing.body.toLowerCase().indexOf(command) >= 0) {
                success = true;
                callback.apply(this, [true, true]);
            }
        });

        if (! success) {
            callback.apply(this, [true, false]);
        }
    }

    //noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param thing
     * @param callback
     */
    static whitelistFlip(thing, callback) {
        let commands = Parser.config.commands[Parser.parseType];
        let success  = false;
        commands.forEach((command) => {
            if (thing.body.toLowerCase().indexOf(command) >= 0) {
                success = true;
                callback.apply(this, [true, true]);
            }
        });

        if (! success) {
            callback.apply(this, [true, false]);
        }
    }
}

module.exports = Parser;