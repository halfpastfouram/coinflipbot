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
use Zend\Db\Sql\Sql;

/**
 * Class Comment
 * @package Coinflipbot\Mapper
 */
class Comment implements CommentInterface
{
	/**
	 * The database adapter used for reading from and writing to the database.
	 *
	 * @var Adapter
	 */
	private $dbAdapter;

	/**
	 * Comment constructor.
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
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__parsed" )
			->where( [
				'id'         => $id,
				'parse_type' => $parseType,
			] );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute();

		return $result->current() ?: [];
	}

	/**
	 * @param string $name
	 * @param int    $parseType
	 *
	 * @return array
	 */
	public function findParsedByName( string $name, int $parseType ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__parsed" )
			->where( 'comment_name = :name' )
			->where( 'parse_type = :parse_type' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':name'       => $name,
			':parse_type' => $parseType,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @return array
	 */
	public function findLastParsed() : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select	= $statement->select()->from( 'comments__parsed' )->order( 'id DESC' )->limit( 1 );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute();

		return $result->current() ?: [];
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\CommentInterface
	 */
	public function saveParsed( array $data ) : CommentInterface
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( "comments__parsed" )
			->values( [
				"comment_name" => @$data['comment_name'],
				'timestamp'    => @$data['timestamp'],
				'parse_type'   => @$data['parse_type'],
				'hit'          => @$data['hit'],
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findReplied( int $id ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'id = :id' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':id' => $id,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param string $name
	 *
	 * @return array
	 */
	public function findRepliedByName( string $name ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'name = :name' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':name' => $name,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param string $userName
	 *
	 * @return array
	 */
	public function findRepliedByUser( string $userName ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'user = :user' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':user' => $userName,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param string $subredditName
	 *
	 * @return array
	 */
	public function findRepliedBySubreddit( string $subredditName ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'subreddit_name = :subreddit_name' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':subreddit_name' => $subredditName,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param string $postName
	 *
	 * @return array
	 */
	public function findRepliedByPost( string $postName ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'post_name = :post_name' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':post_name' => $postName,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param string $postTitle
	 *
	 * @return array
	 */
	public function findRepliedByPostTitle( string $postTitle ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'post_title = :post_title' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':post_title' => $postTitle,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param string $url
	 *
	 * @return array
	 */
	public function findRepliedByUrl( string $url ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( "comments__replied" )
			->where( 'url = :url' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':url' => $url,
		] );

		return $result->current() ?: [];
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\CommentInterface
	 */
	public function saveReplied( array $data ) : CommentInterface
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( "comments__parsed" )
			->values( [
				"comment_name"   => @$data['comment_name'],
				'timestamp'      => @$data['timestamp'],
				'flip'           => @$data['flip'],
				'ban'            => @$data['ban'],
				'whitelist'      => @$data['whitelist'],
				'user'           => @$data['user'],
				'subreddit_name' => @$data['subreddit_name'],
				'post_name'      => @$data['post_name'],
				'post_title'     => @$data['post_title'],
				'url'            => @$data['url'],
				'reply'          => @$data['reply'],
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}
}