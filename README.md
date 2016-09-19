# /u/coinflipbot
Version alpha-1.11

This is [/u/coinflipbot](https://reddit.com/u/coinflipbot), a bot that flips coins on [reddit](https://reddit.com). For more information see [/r/coinflipbot](https://reddit.com/r/coinflipbot).

This bot uses the PHP reddit API wrapper [halfpastfour/phpreddit](https://github.com/halfpastfouram/phpreddit).

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
cd src && cp config.sample.ini config.ini
```
Don't forget to edit `config.ini`! The bot can't run with an empty configuration file. 

To run:
-
`php coinflipbot.php`

The actual /u/coinflipbot is ran by a cronjob on a VPS every 60 seconds.

Requirements:
-
- php >=7.0.0
- mysql >=5.6


Lisence:
-
![AGPL v3.0](https://www.gnu.org/graphics/agplv3-155x51.png "GNU AGPL v3.0")

[GNU AGPL v3.0](https://www.gnu.org/licenses/agpl-3.0.txt)
