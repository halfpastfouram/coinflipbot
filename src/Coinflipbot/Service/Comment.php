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

use Coinflipbot\Mapper\CommentInterface;

/**
 * Class Comment
 * @package Coinflipbot\Service
 */
class Comment
{
	/**
	 * @var \Coinflipbot\Mapper\Comment
	 */
	private $mapper;

	/**
	 * Comment constructor.
	 *
	 * @param \Coinflipbot\Mapper\CommentInterface $mapper
	 */
	public function __construct( CommentInterface $mapper )
	{
		$this->mapper = $mapper;
	}

	/**
	 * @param int $id
	 * @param int $parseType
	 *
	 * @return array
	 */
	public function findParsed( int $id, int $parseType ) : array
	{
		return $this->mapper->findParsed( $id, $parseType );
	}

	/**
	 * @param string $name
	 * @param int    $parseType
	 *
	 * @return array
	 */
	public function findParsedByName( string $name, int $parseType ) : array
	{
		return $this->mapper->findParsedByName( $name, $parseType );
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Service\Comment
	 */
	public function saveParsed( array $data ) : Comment
	{
		$this->mapper->saveParsed( $data );

		return $this;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findReplied( int $id ) : array
	{
		return $this->mapper->findReplied( $id );
	}

	/**
	 * @param string $name
	 *
	 * @return array
	 */
	public function findRepliedByName( string $name ) : array
	{
		return $this->mapper->findRepliedByName( $name );
	}

	/**
	 * @param string $userName
	 *
	 * @return array
	 */
	public function findRepliedByUser( string $userName ) : array
	{
		return $this->mapper->findRepliedByUser( $userName );
	}

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findRepliedBySubreddit( string $subredditName ) : array
	{
		return $this->findRepliedBySubreddit( $subredditName );
	}

	/**
	 * @param string $postName
	 *
	 * @return array
	 */
	public function findRepliedByPost( string $postName ) : array
	{
		return $this->mapper->findRepliedByPost( $postName );
	}

	/**
	 * @param string $postTitle
	 *
	 * @return array
	 */
	public function findRepliedByPostTitle( string $postTitle ) : array
	{
		return $this->mapper->findRepliedByPostTitle( $postTitle );
	}

	/**
	 * @param string $url
	 *
	 * @return array
	 */
	public function findRepliedByUrl( string $url ) : array
	{
		return $this->mapper->findRepliedByUrl( $url );
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Service\Comment
	 */
	public function saveReplied( array $data ) : Comment
	{
		$this->mapper->saveReplied( $data );

		return $this;
	}
}