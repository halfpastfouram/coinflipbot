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
 * Class Subreddit
 * @package Coinflipbot\Mapper
 */
class Subreddit implements SubredditInterface
{
	/**
	 * The database adapter used for reading from and writing to the database.
	 *
	 * @var Adapter
	 */
	private $dbAdapter;

	/**
	 * Subreddit constructor.
	 *
	 * @param \Zend\Db\Adapter\Adapter $dbAdapter
	 */
	public function __construct( Adapter $dbAdapter )
	{
		$this->dbAdapter	= $dbAdapter;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findIgnored( int $id ) : array
	{

	}

	/**
	 * @param string $subredditName
	 * @param int    $timestamp
	 *
	 * @return array
	 */
	public function findIgnoredByName( string $subredditName, int $timestamp = null ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$parameters	= [ ':subreddit_name' => strval( $subredditName ) ];
		$select		= $statement->select()->from( 'subreddits__ignored' )
			->where( 'subreddit_name = :subreddit_name' );

		if( $timestamp ) {
			$parameters[ ':timestamp' ] = $timestamp;
			$select->where( 'whitelist_timestamp <= :timestamp' );
		} else {
			$select->where( 'unban IS NULL' );
		}

		$result = $statement->prepareStatementForSqlObject( $select )->execute( $parameters );

		return $result->current() ?: [];
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\SubredditInterface
	 */
	public function saveIgnored( array $data ) : SubredditInterface
	{
		// TODO: Implement saveIgnored() method.
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function findWhitelisted( int $id ) : array
	{
		// TODO: Implement findWhitelisted() method.
	}

	/**
	 * @param string $subredditName
	 * @param int    $timestamp
	 *
	 * @return array
	 */
	public function findWhitelistedByName( string $subredditName, int $timestamp = null ) : array
	{
		$statement	= new Sql( $this->dbAdapter );
		$parameters	= [ ':subreddit_name' => strval( $subredditName ) ];
		$select		= $statement->select()->from( 'subreddits__whitelisted' )
			->where( 'subreddit_name = :subreddit_name' );

		if( $timestamp ) {
			$parameters[ ':timestamp' ] = $timestamp;
			$select->where( 'whitelist_timestamp <= :timestamp' );
		} else {
			$select->where( 'unwhitelist IS NULL' );
		}

		$result = $statement->prepareStatementForSqlObject( $select )->execute( $parameters );

		return $result->current() ?: [];
	}

	/**
	 * @param string $commentName
	 *
	 * @return array
	 */
	public function findWhitelistedByComment( string $commentName ) : array
	{
		// TODO: Implement findWhitelistedByComment() method.
	}

	/**
	 * @param array $data
	 *
	 * @return \Coinflipbot\Mapper\SubredditInterface
	 */
	public function saveWhitelisted( array $data ) : SubredditInterface
	{
		$subreddit	= $this->findWhitelistedByName( $data['subreddit'] );

		if( !$subreddit || !array_key_exists( 'unwhitelist', $data ) || !$data['unwhitelist'] ) {
			$sql          = new Sql( $this->dbAdapter );
			$insert       = $sql->insert( 'subreddits__whitelisted' )
				->values( [
					'subreddit_name'             => $data['subreddit'],
					'whitelist_comment_name'     => $data['name'],
					'whitelist_requested_by_mod' => $data['author'],
					'whitelist_timestamp'        => $data['whitelist_timestamp'],
					'display_public'             => $data['display_public'],
				] );
			$selectString = $sql->buildSqlString( $insert );
			$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );
		} else {
			$sql	= new Sql( $this->dbAdapter );
			$update	= $sql->update( 'subreddits__whitelisted' )
				->set( [
					'unwhitelist'                  => 1,
					'unwhitelist_comment_name'     => $data['name'],
					'unwhitelist_requested_by_mod' => $data['author'],
					'unwhitelist_timestamp'        => $data['unwhitelist_timestamp'],
				] )->where( [
					'subreddit' => $subreddit['subreddit_name'],
				] );
			$selectString	= $sql->buildSqlString( $update );
			$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );
		}

		return $this;
	}
}