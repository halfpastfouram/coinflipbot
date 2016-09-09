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
