/*jshint esversion: 6 */
'use strict';

const Coinflipbot = require('./Coinflipbot.js');
const Config = require('./config.js');

let config = new Config();
const bot = new Coinflipbot(config);
console.log("Coinflipbot booted up. Starting comment parse loop.");

let interval = setInterval(() => {
    bot.parseComments();
}, config.commentParser.timeout);