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

namespace Coinflipbot\Mapper;

/**
 * Interface SubredditInterface
 * @package Coinflipbot\Mapper
 */
interface SubredditInterface
{
	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findIgnored( int $id ) : array;

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findIgnoredByName( string $subredditName ) : array;

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\SubredditInterface
	 */
	public function saveIgnored( array $data ) : SubredditInterface;

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findWhitelisted( int $id ) : array;

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findWhitelistedByName( string $subredditName ) : array;

	/**
	 * @param string $commentName
	 *
	 * @return array
	 */
	public function findWhitelistedByComment( string $commentName ) : array;

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\SubredditInterface
	 */
	public function saveWhitelisted( array $data ) : SubredditInterface;
}