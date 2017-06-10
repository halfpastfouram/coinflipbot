[![Test Coverage](https://codeclimate.com/github/codeclimate/codeclimate/badges/coverage.svg)](https://codeclimate.com/github/codeclimate/codeclimate/coverage)

[![Code Climate](https://codeclimate.com/github/halfpastfouram/coinflipbot/badges/gpa.svg)](https://codeclimate.com/github/halfpastfouram/coinflipbot)

[![Issue Count](https://codeclimate.com/github/codeclimate/codeclimate/badges/issue_count.svg)](https://codeclimate.com/github/codeclimate/codeclimate)

# /u/coinflipbot
Version alpha-2.0

This is [/u/coinflipbot](https://reddit.com/u/coinflipbot), a bot that flips coins on [reddit](https://reddit.com). For more information see [/r/coinflipbot](https://reddit.com/r/coinflipbot).

This bot uses the reddit API wrapper [snoowrap](https://not-an-aardvark.github.io/snoowrap/index.html).

----

# Features

- __Flip coins__
Users can ask the bot to flip a coin (`+/u/coinflipbot` by default) and it will reply with the result.
- __Ban and unban by moderator request__
Moderators can tell the bot to ban itself from a subreddit with a comment. The bot will no longer post any automated replies in the subreddit until unbanned again.
This way a moderator doesn't have to ban the account from the subreddit.
- __Whitelist and unwhitelist by moderator request__
The bot listens to moderators telling it in a comment to whitelist or unwhitelist itself from their subreddit. When whitelisted the bot can respond to alternate flip commands.
- __Configurable triggers and responses__
Every trigger string is configurable. If the string is present in a comment, an action will be performed.
Any response from the botis configurable as well as the footer it appends to its replies.


----

# Installation, configuration and running the bot
A quick guide to installing and running the bot.

To install:
-
```bash
git clone https://github.com/halfpastfouram/coinflipbot.git
cd coinflipbot && composer install
cat database.sql | mysql -p
cd src && cp config.js.dist config.js
```
Don't forget to edit `config.js`! The bot can't run with an empty configuration file.

To run:
-
`node bot.js`

The actual /u/coinflipbot is ran by a cronjob on a VPS every 60 seconds.

Requirements:
-
- node js
- mysql >=5.6


Lisence:
-
![AGPL v3.0](https://www.gnu.org/graphics/agplv3-155x51.png "GNU AGPL v3.0")

[GNU AGPL v3.0](https://www.gnu.org/licenses/agpl-3.0.txt)
