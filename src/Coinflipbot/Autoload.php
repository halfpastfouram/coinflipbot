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

namespace Coinflipbot;

/**
 * Class Autoload
 * @package Coinflipbot
 */
class Autoload
{
	/**
	 * @var string
	 */
	private static $basePath;

	/**
	 * Register the autoloader
	 *
	 * @param string $p_sBasePath
	 */
	public static function register( $p_sBasePath = __DIR__ )
	{
		self::$basePath	= rtrim( $p_sBasePath, '/' ) . '/';
		spl_autoload_register( self::class . '::load' );
	}

	/**
	 * @param string $class
	 */
	public static function load( $class )
	{
		$parts		= explode( '\\', $class );
		$className	= array_pop( $parts );
		require self::$basePath . implode( '/', $parts ) . "/{$className}.php";
	}
}
