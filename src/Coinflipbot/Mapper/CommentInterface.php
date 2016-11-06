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
 * Interface CommentInterface
 * @package Coinflipbot\Mapper
 */
interface CommentInterface
{
	/**
	 * @param int $id
	 * @param int $parseType
	 *
	 * @return array
	 */
	public function findParsed( int $id, int $parseType ) : array;

	/**
	 * @param string $name
	 * @param int    $parseType
	 *
	 * @return array
	 */
	public function findParsedByName( string $name, int $parseType ) : array;

	/**
	 * @return array
	 */
	public function findLastParsed() : array;

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\CommentInterface
	 */
	public function saveParsed( array $data ) : CommentInterface;

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findReplied( int $id ) : array;

	/**
	 * @param string $name
	 *
	 * @return array
	 */
	public function findRepliedByName( string $name ) : array;

	/**
	 * @param string $userName
	 *
	 * @return array
	 */
	public function findRepliedByUser( string $userName ) : array;

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findRepliedBySubreddit( string $subredditName ) : array;

	/**
	 * @param string $postName
	 *
	 * @return array
	 */
	public function findRepliedByPost( string $postName ) : array;

	/**
	 * @param string $postTitle
	 *
	 * @return array
	 */
	public function findRepliedByPostTitle( string $postTitle ) : array;

	/**
	 * @param string $url
	 *
	 * @return array
	 */
	public function findRepliedByUrl( string $url ) : array;

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\CommentInterface
	 */
	public function saveReplied( array $data ) : CommentInterface;
}