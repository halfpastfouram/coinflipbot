<?php
/**
 * /u/coinflipbot by halfpastfour.am. A reddit bot that flips coins for people by request.
 *
 * Copyright (c) 2016 halfpastfour.am. This file is part of halfpastfour/coinflipbot.
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

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

// Require and register the autoloader
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Coinflipbot/Autoload.php';
Coinflipbot\Autoload::register( __DIR__ );

$bot	= new \Coinflipbot\Coinflipbot;
$bot->setConfig( \Zend\Config\Factory::fromFile( __DIR__ . '/config.ini', true ) )
	->init()
	->run()
	->shutdown();