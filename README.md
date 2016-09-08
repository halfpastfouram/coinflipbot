# /u/coinflipbot
Version alpha-1.2

This is [/u/coinflipbot](https://reddit.com/u/coinflipbot), a bot that flips coins on [reddit](https://reddit.com). For more information see [/r/coinflipbot](https://reddit.com/r/coinflipbot).

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
Edit config.ini

To run:
-
`php coinflipbot.php`

Requirements:
-
- php >=7.0.0
- mysql >=5.6


Lisence:
-
![GPL v3.0](https://www.gnu.org/graphics/gplv3-127x51.png "GNU GPL v3.0")

[GNU GPL v3.0](https://www.gnu.org/licenses/gpl-3.0.txt)
