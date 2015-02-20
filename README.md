# drinkbot

Copyright 2015 Brendan Kidwell < snarf@glump.net >

This software is distributed under the terms of the GNU General Public License, version 3.

Drinkbot is a bot for #linuxjournal on Freenode. I wrote it because no one will tell me why "lapsang souchong" is a catch phrase there. Read the code to see what it does.

## Installation

1. Download [PHP IRC](http://www.phpbots.org/) into `$project` .

2. Copy the `drinkbot` folder into `$project/modules` .

3. Add the line `include modules/drinkbot/drinkbot.conf` to `$project/function.conf` .

4. Edit `$project/bot.conf` to set your IRC connection parameters.

5. Run `php $project/bot.php $project/bot.conf` .
