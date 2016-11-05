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

namespace Coinflipbot\Service;

use Coinflipbot\Mapper\SubredditInterface;

/**
 * Class Subreddit
 * @package Coinflipbot\Service
 */
class Subreddit
{
	/**
	 * @var \Coinflipbot\Mapper\SubredditInterface
	 */
	private $mapper;

	/**
	 * Subreddit constructor.
	 *
	 * @param \Coinflipbot\Mapper\SubredditInterface $mapper
	 */
	public function __construct( SubredditInterface $mapper )
	{
		$this->mapper = $mapper;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findIgnored( int $id ) : array
	{
		return $this->mapper->findIgnored( $id );
	}

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findIgnoredByName( string $subredditName ) : array
	{
		return $this->mapper->findIgnoredByName( $subredditName );
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Service\Subreddit
	 */
	public function saveIgnored( array $data ) : Subreddit
	{
		$this->mapper->saveIgnored( $data );

		return $this;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findWhitelisted( int $id ) : array
	{
		return $this->mapper->findWhitelisted( $id );
	}

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findWhitelistedByName( string $subredditName ) : array
	{
		return $this->mapper->findWhitelistedByName( $subredditName );
	}

	/**
	 * @param string $commentName
	 *
	 * @return array
	 */
	public function findWhitelistedByComment( string $commentName ) : array
	{
		return $this->mapper->findWhitelistedByComment( $commentName );
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Service\Subreddit
	 */
	public function saveWhitelisted( array $data ) : Subreddit
	{
		$this->mapper->saveWhitelisted( $data );

		return $this;
	}
}