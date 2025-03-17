# Wordpress AesirX Consent Management Platform

Take full control of your users' consent with a truly first-party approach. 
Comply with EU ePrivacy Directive and UK PECR by avoiding risky third-party consent services, all while promoting transparency, trust, and privacy-first technology.


## For local setup

To install this you will need to clone this repo locally with command:

`git clone https://github.com/aesirxio/wordpress-analytics-plugin.git`

## PHP set up

After that you can run the next commands.

`yarn install` - initialize libraries

`yarn build` - for building Joomla zip installer (PHP 7.2 or higher)

`yarn watch` - for watching changes in the JS when developing

## Docker set up

### Linux

Alternatively can be used docker-compose with npm and php included, see available commands in `Makefile`:
_Before build docker container please make sure you set correct USER_ID and GROUP_ID in .env file_

`make init` - initialize libraries

`make build` - for building Joomla zip installer (PHP 7.2 or higher)

`make watch` - for watching changes in the JS when developing

### Windows

If you don't have Makefile set uo on Windows you can use direct docker commands.

`docker-compose run php-npm yarn install` - initialize libraries

`docker-compose run php-npm yarn build` - for building Joomla zip installer (PHP 7.2 or higher)

`docker-compose run php-npm yarn watch` - for watching changes in the JS when developing

## Installing and Set up

After running the build the install package will be created in the `dist` folder.
