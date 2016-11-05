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

use Coinflipbot\Mapper\MessageInterface;

/**
 * Class Message
 * @package Coinflipbot\Service
 */
class Message
{
	/**
	 * @var \Coinflipbot\Mapper\MessageInterface
	 */
	private $mapper;

	/**
	 * Message constructor.
	 *
	 * @param \Coinflipbot\Mapper\MessageInterface $mapper
	 */
	public function __construct( MessageInterface $mapper )
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
	 * @param string $messageName
	 * @param int    $parseType
	 *
	 * @return array
	 */
	public function findParsedByName( string $messageName, int $parseType ) : array
	{
		return $this->mapper->findParsedByName( $messageName, $parseType );
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Service\Message
	 */
	public function saveParsed( array $data ) : Message
	{
		$this->mapper->saveParsed( $data );

		return $this;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findReplied( int $id ) :array
	{
		return $this->mapper->findReplied( $id );
	}

	/**
	 * @param string $messageName
	 *
	 * @return mixed
	 */
	public function findRepliedByName( string $messageName ) : array
	{
		return $this->mapper->findRepliedByName( $messageName );
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
	 * @param array $data
	 *
	 * @return \Coinflipbot\Service\Message
	 */
	public function saveReplied( array $data ) : Message
	{
		$this->mapper->saveReplied( $data );

		return $this;
	}
}