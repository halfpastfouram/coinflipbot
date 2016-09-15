# /u/coinflipbot
Version alpha-1.9

This is [/u/coinflipbot](https://reddit.com/u/coinflipbot), a bot that flips coins on [reddit](https://reddit.com). For more information see [/r/coinflipbot](https://reddit.com/r/coinflipbot).

This bot uses the PHP reddit API wrapper [halfpastfour/phpreddit](https://github.com/halfpastfouram/phpreddit).

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
