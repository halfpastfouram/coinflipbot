/*jshint esversion: 6 */
'use strict';

const Coinflipbot = require('./Coinflipbot.js');
const Config = require('./config.js');

let config = new Config();

//
//noinspection JSUnresolvedVariable
process.argv.forEach(function (val, index, array) {
    if (val === "--verbose" || val === '-v') {
        config.verbose = true;
    }
});

const bot = new Coinflipbot(config);

console.log(`Coinflipbot booted up.`);

let interval = null;

// Start parsing
console.log('Parsing comments...');
bot.parseComments(() => {
    // Create an interval to parse comments
    console.log(`Starting comment parse loop in ${config.commentParser.timeout} ms`);
    interval = setInterval(() => {
        bot.parseComments();
    }, config.commentParser.timeout);
});