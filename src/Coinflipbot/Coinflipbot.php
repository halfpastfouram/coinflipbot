<?php
/**
 * Copyright (c) 2016. This file is part of halfpastfour/coinflipbot.
 *
 * halfpastfour/coinflipbot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * halfpastfour/coinflipbot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with halfpastfour/coinflipbot.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Coinflipbot;

use Halfpastfour\Reddit\Interfaces\Bot;
use Halfpastfour\Reddit\Reddit;
use LukeNZ\Reddit\TokenStorageMethod;
use Zend\Config\Config;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

/**
 * Class Coinflipbot
 * @package Coinflipbot
 */
class Coinflipbot implements Bot
{
	/**
	 * @var Config|null
	 */
	private $config;

	/**
	 * @var Adapter
	 */
	private $dbAdapter;

	/**
	 * @var Reddit
	 */
	private $reddit;

	/**
	 * @var array
	 */
	protected $replied = [ ];

	/**
	 * Overwrite the configuration values.
	 *
	 * @param Config $p_oConfig
	 *
	 * @return Bot
	 */
	public function setConfig( Config $p_oConfig )
	{
		$this->config = $p_oConfig;

		return $this;
	}

	/**
	 * Should take care of requirements before doing its job.
	 *
	 * @return Bot
	 */
	public function init()
	{
		print( "Initializing.\n" );
		// Set up db adapter
		$this->dbAdapter	= new Adapter( $this->config->db->toArray() );

		// Set up the reddit client
		$this->reddit = new Reddit(
			$this->config->reddit->account->username,
			$this->config->reddit->account->password,
			$this->config->reddit->client->id,
			$this->config->reddit->client->secret
		);
		$this->reddit->setTokenStorageMethod( TokenStorageMethod::File, 'phpreddit:token', 'reddit.token' );
		$this->reddit->setUserAgent( "{$this->config->info->description} {$this->config->info->version}"  );

		return $this;
	}

	/**
	 * Should execute the logic performing the bot's job.
	 *
	 * @return Bot
	 */
	public function run()
	{
		print( "Running.\n" );
		$subreddits	= $this->config->reddit->subreddit->toArray();
		$comments	= $this->reddit->getComments(
			$subreddits,
			$this->config->reddit->limit->max_comments
		);

		foreach( $this->searchComments( $comments, $this->config->reddit->trigger->toArray() ) as $comment ) {
			if( !$this->hasReplied( $comment ) ) {
				$this->replyWithFlip( $comment );
			}
		}

		return $this;
	}

	/**
	 * Should shut down all activity or open connections and should be the last method to be executed.
	 *
	 * @return Bot
	 */
	public function shutdown()
	{
		print( "Shutting down.\n" );

		return $this;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return bool
	 */
	protected function hasParsed( array $p_aComment )
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( 'comments__parsed' )->where( 'comment_name = :comment_name' );
		$result		= $statement->prepareStatementForSqlObject( $select )->execute( array(
			':comment_name'	=> strval( $p_aComment['data']['name'] )
		) );

		return $result->count() > 0;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return bool
	 */
	protected function hasReplied( array $p_aComment )
	{
		$statement	= new Sql( $this->dbAdapter );
		$select	= $statement->select()->from( 'comments__replied' )->where( 'comment_name = :comment_name' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( array(
			':comment_name'	=> strval( $p_aComment['data']['name'] )
		) );
		return $result->count() > 0;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return Coinflipbot
	 */
	public function replyWithFlip( array $p_aComment )
	{
		// Generate flip: 0 is tails, 1 is heads.
		$flip    = rand( 0, 1 );

		// Reply to the comment
		$result  = $flip ? 'heads' : 'tails';
		$message = "Hey there, /u/{$p_aComment['data']['author']}! I flipped a coin for you and the result was: "
			. "{$result}.";

		$success	= $this->reddit->comment( $p_aComment['data']['name'], $message );
		if( $success ) {
			// Save reply to database
			$this->saveFlipToDatabase( $p_aComment, $flip, $message );
		}

		return $this;
	}

	/**
	 * Search comment body for given needle and return only those that have the needle.
	 *
	 * @param array $p_aComments
	 * @param       $p_sNeedle
	 *
	 * @return array
	 */
	protected function searchComments( array $p_aComments, $p_mNeedle )
	{
		$needles	= !is_array( $p_mNeedle ) ? array( $p_mNeedle ) : $p_mNeedle;
		$hits = [ ];
		foreach( $p_aComments as $index => $commentData ) {
			$comment	= $commentData['data'];
			$hit		= 0;
			// Skip this comment if it has already been parsed before
			if( $this->hasParsed( $commentData ) ) continue;

			foreach( $needles as $needle ) {
				if( strpos( $comment['body'], strval( $needle ) ) !== false ) {
					$hits[] = $commentData;
					$hit    = 1;
					break;
				}
			}
			$this->saveParsedComment( $commentData, $hit );
		}

		return $hits;
	}

	/**
	 * @param array $p_aCommentData
	 * @param       $p_bHit
	 *
	 * @return Coinflipbot
	 */
	protected function saveParsedComment( array $p_aCommentData, $p_bHit )
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'comments__parsed' )
			->values( [
				'comment_name'	=> $p_aCommentData['data']['name'],
				'timestamp'		=> time(),
				'hit'			=> intval( $p_bHit )
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * @param array $p_aComment
	 * @param       $p_iFlip
	 * @param       $p_sMessage
	 *
	 * @return Coinflipbot
	 */
	protected function saveFlipToDatabase( array $p_aComment, $p_iFlip, $p_sMessage )
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'comments__replied' )
			->values( [
				'comment_name'		=> $p_aComment['data']['name'],
				'timestamp'			=> time(),
				'flip'				=> intval( $p_iFlip ),
				'user'				=> $p_aComment['data']['author'],
				'subreddit_name'	=> $p_aComment['data']['subreddit'],
				'post_name'			=> $p_aComment['data']['link_id'],
				'post_title'		=> $p_aComment['data']['link_title'],
				'url'				=> $p_aComment['data']['link_url'],
				'reply'				=> strval( $p_sMessage ),
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * @return string|null
	 */
	protected function getLastParsedCommentName()
	{
		$statement	= new Sql( $this->dbAdapter );
		$select	= $statement->select()->from( 'comments__parsed' )->order( 'id DESC' )->limit( 1 );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute();

		return $result->count() ? $result->current()['comment_name'] : null;
	}
}