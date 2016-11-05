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
 * Interface MessageInterface
 * @package Coinflipbot\Mapper
 */
interface MessageInterface
{
	/**
	 * @param int $id
	 * @param int $parseType
	 *
	 * @return array
	 */
	public function findParsed( int $id, int $parseType ) : array;

	/**
	 * @param string $messageName
	 * @param int    $parseType
	 *
	 * @return array
	 */
	public function findParsedByName( string $messageName, int $parseType ) : array;

	/**
	 * @param array $data
	 *
	 * @return MessageInterface
	 */
	public function saveParsed( array $data ) : MessageInterface;

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findReplied( int $id ) :array;

	/**
	 * @param string $messageName
	 *
	 * @return mixed
	 */
	public function findRepliedByName( string $messageName );

	/**
	 * @param string $userName
	 *
	 * @return array
	 */
	public function findRepliedByUser( string $userName );

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\MessageInterface
	 */
	public function saveReplied( array $data ) : MessageInterface;
}