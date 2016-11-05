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

use Zend\Db\Adapter\Adapter;

/**
 * Class Message
 * @package Coinflipbot\Mapper
 */
class Message implements MessageInterface
{
	/**
	 * The database adapter used for reading from and writing to the database.
	 *
	 * @var Adapter
	 */
	private $dbAdapter;

	/**
	 * Message constructor.
	 *
	 * @param \Zend\Db\Adapter\Adapter $dbAdapter
	 */
	public function __construct( Adapter $dbAdapter )
	{
		$this->dbAdapter	= $dbAdapter;
	}

	/**
	 * @param int $id
	 * @param int $parseType
	 *
	 * @return array
	 */
	public function findParsed( int $id, int $parseType ) : array
	{
		// TODO: Implement findParsed() method.
	}

	/**
	 * @param string $messageName
	 * @param int    $parseType
	 *
	 * @return array
	 */
	public function findParsedByName( string $messageName, int $parseType ) : array
	{
		// TODO: Implement findParsedByName() method.
	}

	/**
	 * @param array $data
	 *
	 * @return MessageInterface
	 */
	public function saveParsed( array $data ) : MessageInterface
	{
		// TODO: Implement saveParsed() method.
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findReplied( int $id ) :array
	{
		// TODO: Implement findReplied() method.
	}

	/**
	 * @param string $messageName
	 *
	 * @return mixed
	 */
	public function findRepliedByName( string $messageName ) : array
	{
		// TODO: Implement findRepliedByName() method.
	}

	/**
	 * @param string $userName
	 *
	 * @return MessageInterface
	 */
	public function findRepliedByUser( string $userName ) : array
	{
		// TODO: Implement findRepliedByUser() method.
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\MessageInterface
	 */
	public function saveReplied( array $data ) : MessageInterface
	{
		// TODO: Implement saveReplied() method.
	}
}