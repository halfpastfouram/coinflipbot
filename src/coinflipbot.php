<?php
/**
 * /u/coinflipbot by halfpastfour.am. A reddit bot that flips coins for people by request.
 *
 * Copyright (c) 2016 halfpastfour.am. This file is part of halfpastfour/coinflipbot.
 * Contact the developer at coinflipbot@halfpastfour.am.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Halfpastfour\Reddit\Reddit;
use Halfpastfour\Reddit\TokenStorageMethod;
use Zend\Config;
use Zend\Db\Adapter\Adapter;

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

// Require and register the autoloader
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Coinflipbot/Autoload.php';
Coinflipbot\Autoload::register( __DIR__ );

// Get the configuration
$config	= Config\Factory::fromFile( __DIR__ . '/config.ini', true );

// Set up the reddit client
$reddit = new Reddit(
	$config->reddit->account->username,
	$config->reddit->account->password,
	$config->reddit->client->id,
	$config->reddit->client->secret
);
$reddit->setTokenStorageMethod( TokenStorageMethod::FILE, 'phpreddit:token', 'reddit.token' );
$reddit->setUserAgent( "{$config->info->description} {$config->info->version}"  );

// Set up the database adapter
$dbAdapter	= new Adapter( $config->db->toArray() );
$dbAdapter->query( 'SET TIME_ZONE = "Etc/UTC";' );

// Start up the bot
$bot	= new \Coinflipbot\Coinflipbot(
	$reddit,
	$config,
	new Coinflipbot\Service\Comment( new Coinflipbot\Mapper\Comment( $dbAdapter ) ),
	new Coinflipbot\Service\Message( new Coinflipbot\Mapper\Message( $dbAdapter ) ),
	new Coinflipbot\Service\Subreddit( new Coinflipbot\Mapper\Subreddit( $dbAdapter ) )
);

// Finally, run!
$bot->run()
	->shutdown();