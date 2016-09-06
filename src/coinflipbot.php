<?php
/**
 * Copyright (c) 2016. This file is part of halfpastfour/coinflipbot.
 *
 * halfpastfour/coinflipbot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * halfpastfour/coinflipbot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with halfpastfour/coinflipbot.  If not, see <http://www.gnu.org/licenses/>.
 */ 

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

// Require and register the autoloader
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Halfpastfour/Autoload.php';
Halfpastfour\Autoload::register( __DIR__ );

$bot = new \Coinflipbot\Coinflipbot;
$bot->setConfig( \Zend\Config\Factory::fromFile( __DIR__ . '/config.ini', true ) )
	->init()
	->run();