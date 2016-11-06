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

namespace Halfpastfour\Test\Service;
use Coinflipbot\Mapper\CommentInterface;
use Coinflipbot\ParseType;
use Coinflipbot\Service\Comment;
use PHPUnit\Framework\TestCase;
use Zend\Config\Factory;

/**
 * Class Coinflipbot
 * @package Halfpastfour\Test
 */
class CommentTest extends TestCase
{
	/**
	 * @var \Zend\Db\Adapter\Adapter
	 */
	private $dbAdapter;

	/**
	 * @var \Coinflipbot\Mapper\CommentInterface
	 */
	private $emptyMapper;

	/**
	 * @var \Coinflipbot\Mapper\CommentInterface
	 */
	private $commentMapper;

	/**
	 * @var array
	 */
	private $comments;

	/**
	 * @var array
	 */
	private $commentTypes;

	/**
	 *
	 */
	public function setUp()
	{
		// Get the configuration
		$this->config	= Factory::fromFile( __DIR__ . '/../config.ini', true );

		// Mock the database adapter
		$dbAdapter	= $this->getMockBuilder( 'Zend\Db\Adapter\Adapter' )
			->setConstructorArgs( [ $this->config->db->toArray() ] )
			->getMock();

		$this->dbAdapter	= $dbAdapter;

		// Mock the mapper for the service
		// This mapper won't return correct values
		$this->emptyMapper	= $this->getMockBuilder( 'Coinflipbot\Mapper\Comment' )
			->setConstructorArgs( [ $this->dbAdapter ] )
			->getMock();

		// This mapper will return correct values
		$commentMapper		= $this->getMockBuilder( 'Coinflipbot\Mapper\Comment' )
			->setConstructorArgs( [ $this->dbAdapter ] )
			->getMock();

		$commentMapper->expects( $this->any() )
			->method( 'findParsed' )
			->will( $this->returnCallback( array( $this, 'returnComment' ) ) );

		$this->commentMapper	= $commentMapper;

		$this->comments		= include __DIR__ . '/../data/subreddit/comments.php';
		$this->commentTypes	= include __DIR__ . '/../data/subreddit/commentTypes.php';
	}

	/**
	 *
	 */
	public function testMapperImplementation()
	{
		$this->assertInstanceOf(
			CommentInterface::class,
			$this->emptyMapper,
			'Comment service implements correct interface'
		);
	}

	/**
	 * @param int $id
	 * @param int $returnType
	 *
	 * @return array
	 */
	public function returnComment( int $id, int $returnType ) : array
	{
		$currentComment	= array_key_exists( $id, $this->comments ) ? $this->comments[ $id ] : [];
		if( array_key_exists( $id, $this->commentTypes ) && $returnType == $this->commentTypes[ $id ] ) {
			return $currentComment;
		}

		return [];
	}

	/**
	 *
	 */
	public function testEmptyFindMethod()
	{
		$service	= new Comment( $this->emptyMapper );
		$result		= $service->findParsed( 1, ParseType::FLIP );

		$this->assertTrue( is_array( $result ), 'Result is array' );
		$this->assertTrue( empty( $result ), 'Result is empty' );
	}

	/**
	 *
	 */
	public function testFindMethodWithData()
	{
		$service			= new Comment( $this->commentMapper );
		for( $i = 0; $i < 100; $i++ ) {
			$randomCommentId = rand( 0, count( $this->comments ) - 1 );
			$randomParseType = array_rand( [
				ParseType::FLIP,
				ParseType::BAN,
				ParseType::UNBAN,
				ParseType::WHITELIST,
				ParseType::UNWHITELIST,
				ParseType::WHITELISTED_FLIP,
			] );
			$parseType       = $this->commentTypes[ $randomCommentId ];
			$result          = $service->findParsed( $randomCommentId, $randomParseType );

			$this->assertTrue( is_array( $result ), 'Result is array' );

			if( $parseType !== $randomParseType ) {
				$this->assertTrue( empty( $result ), 'Empty result for parse type other than "flip"' );
			} else {
				$this->assertFalse( empty( $result ), 'Result set may not be empty' );
			}
		}
	}
}