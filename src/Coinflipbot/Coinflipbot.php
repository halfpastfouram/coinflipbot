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
	const PARSE_FLIP	= 1;
	const PARSE_BAN		= 2;
	const PARSE_UNBAN	= 4;

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
		$trigger	= $this->config->reddit->trigger;
		$comments	= $this->reddit->getComments(
			$subreddits,
			$this->config->reddit->limit->max_comments
		);

		// Look for ban triggers. It's important to do this first to avoid parsing comments in a banned subreddit.
		print( "Looking for ban triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->ban->toArray(), self::PARSE_BAN ) as $comment ) {
			if( !$this->hasReplied( $comment ) && $this->commentIsFromSubredditMod( $comment )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Banning self from /r/{$comment['data']['subreddit']}\n" );
				$this->banAndReply( $comment );
			}
		}

		// Look for unban triggers
		print( "Looking for unban triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->unban->toArray(), self::PARSE_UNBAN ) as $comment ) {
			if( !$this->hasReplied( $comment ) && $this->commentIsFromSubredditMod( $comment )
				&& $this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Unbanning self from /r/{$comment['data']['subreddit']}\n" );
				$this->unbanAndReply( $comment );
			}
		}

		// Look for flip triggers
		print( "Looking for flip triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->flip->toArray(), self::PARSE_FLIP ) as $comment ) {
			if( !$this->hasReplied( $comment ) && !$this->isBannedFromSubreddit( $comment['data']['subreddit'] ) ) {
				print( "Replying to {$comment['data']['name']}\n" );
				$this->flipAndReply( $comment );
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
	 * @return string|null
	 */
	protected function getLastParsedCommentName()
	{
		$statement	= new Sql( $this->dbAdapter );
		$select	= $statement->select()->from( 'comments__parsed' )->order( 'id DESC' )->limit( 1 );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute();

		return $result->count() ? $result->current()['comment_name'] : null;
	}

	/**
	 * @param array $p_aComment
	 * @param int   $p_iParseType
	 *
	 * @return bool
	 */
	protected function hasParsed( array $p_aComment, $p_iParseType )
	{
		$statement	= new Sql( $this->dbAdapter );
		$select		= $statement->select()->from( 'comments__parsed' )
			->where( 'comment_name = :comment_name' )
			->where( 'parse_type = :parse_type' );
		$result		= $statement->prepareStatementForSqlObject( $select )->execute( array(
			':comment_name'	=> strval( $p_aComment['data']['name'] ),
			':parse_type'	=> intval( $p_iParseType ),
		) );

		return $result->count() > 0;
	}

	/**
	 * @param string $p_sSubreddit
	 *
	 * @return bool
	 */
	protected function isBannedFromSubreddit( $p_sSubreddit )
	{
		$statement	= new Sql( $this->dbAdapter );
		$select	= $statement->select()->from( 'subreddits__ignored' )
			->where( 'subreddit_name = :subreddit_name' )
			->where( 'unban IS NULL' );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute( [
			':subreddit_name'	=> strval( $p_sSubreddit ),
		] );

		return $result->count() > 0;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return bool
	 */
	protected function commentIsFromSubredditMod( array $p_aComment )
	{
		if( $mods = $this->reddit->getModsFromSubreddit( $p_aComment['data']['subreddit'] ) ) {
			return in_array( $p_aComment['data']['author'], $mods );
		}
		return false;
	}

	/**
	 * @param array $p_aCommentData
	 * @param bool  $p_bHit
	 * @param int   $p_iParseType
	 *
	 * @return \Coinflipbot\Coinflipbot
	 */
	protected function saveParsedComment( array $p_aCommentData, $p_bHit, $p_iParseType )
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'comments__parsed' )
			->values( [
				'comment_name'	=> strval( $p_aCommentData['data']['name'] ),
				'timestamp'		=> time(),
				'parse_type'	=> intval( $p_iParseType ),
				'hit'			=> intval( $p_bHit )
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * Search comment body for given needle and return only those that have the needle.
	 *
	 * @param array $p_aComments
	 * @param mixed $p_mNeedle
	 * @param int   $p_iParseType
	 *
	 * @return array
	 */
	protected function searchComments( array $p_aComments, $p_mNeedle, $p_iParseType )
	{
		$needles	= !is_array( $p_mNeedle ) ? array( $p_mNeedle ) : $p_mNeedle;
		$hits		= [ ];
		foreach( $p_aComments as $index => $commentData ) {
			$comment	= $commentData['data'];
			$hit		= 0;
			// Skip this comment if it has already been parsed before
			if( $this->hasParsed( $commentData, $p_iParseType ) ) {
				print( "Comment {$comment['name']} has been parsed before for type {$p_iParseType}. Skipping it.\n" );
				continue;
			}

			foreach( $needles as $needle ) {
				if( strpos( $comment['body'], strval( $needle ) ) !== false ) {
					$hits[] = $commentData;
					$hit    = 1;
					break;
				}
			}
			$this->saveParsedComment( $commentData, $hit, $p_iParseType );
		}

		return $hits;
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
	 * @param array  $p_aComment
	 * @param int    $p_iFlip
	 * @param bool   $p_bBan
	 * @param string $p_sMessage
	 *
	 * @return \Coinflipbot\Coinflipbot
	 */
	protected function saveReply( array $p_aComment, $p_iFlip = null, $p_bBan = null, $p_sMessage )
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'comments__replied' )
			->values( [
				'comment_name'		=> $p_aComment['data']['name'],
				'timestamp'			=> time(),
				'flip'				=> !is_null( $p_iFlip ) ? intval( $p_iFlip ) : null,
				'ban'				=> !is_null( $p_bBan ) ? intval( $p_bBan ) : null,
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
	 * @param array $p_aComment
	 *
	 * @return Coinflipbot
	 */
	protected function flipAndReply( array $p_aComment )
	{
		// Generate flip: 0 is tails, 1 is heads.
		$flip    = rand( 0, 1 );

		// Reply to the comment
		$message = str_replace( [
				'{author}',
				'{flip-result}'
			], [
				$p_aComment['data']['author'],
				$flip ? 'heads' : 'tails'
			], $this->config->reddit->response->flip
		) . "\n\n---\n\n{$this->config->reddit->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, $flip, null, $message );
		}

		return $this;
	}

	/**
	 * @param array $p_aComment
	 * @param bool  $p_bDisplayPublic
	 *
	 * @return Coinflipbot
	 */
	protected function banFromSubreddit( array $p_aComment, $p_bDisplayPublic = true )
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'subreddits__ignored' )
			->values( [
				'subreddit_name'		=> $p_aComment['data']['subreddit'],
				'ban_comment_name'		=> $p_aComment['data']['name'],
				'ban_requested_by_mod'	=> $p_aComment['data']['author'],
				'ban_timestamp'			=> time(),
				'display_public'		=> intval( $p_bDisplayPublic ),
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return Coinflipbot
	 */
	protected function unbanFromSubreddit( array $p_aComment )
	{
		$sql	= new Sql( $this->dbAdapter );
		$update	= $sql->update( 'subreddits__ignored' )
			->set( [
				'unban'						=> 1,
				'unban_comment_name'		=> $p_aComment['data']['name'],
				'unban_requested_by_mod'	=> $p_aComment['data']['author'],
				'unban_timestamp'			=> time(),
			] );
		$selectString	= $sql->buildSqlString( $update );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return Coinflipbot
	 */
	protected function banAndReply( array $p_aComment )
	{
		// Ban self from posting in the subreddit
		$this->banFromSubreddit( $p_aComment );

		// Reply to the comment
		$message	= str_replace(
			'{author}',
			$p_aComment['data']['author'],
			$this->config->reddit->response->ban
		) . "\n\n---\n\n{$this->config->reddit->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, null, 1, $message );
		}

		return $this;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return Coinflipbot
	 */
	protected function unbanAndReply( array $p_aComment )
	{
		// Ban self from posting in the subreddit
		$this->unbanFromSubreddit( $p_aComment );

		// Reply to the comment
		$message	= str_replace(
			'{author}',
			$p_aComment['data']['author'],
			$this->config->reddit->response->unban
		) . "\n\n---\n\n{$this->config->reddit->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, null, 1, $message );
		}

		return $this;
	}

	/**
	 * Perform sentient tasks. Unpredictable behavior.
	 *
	 * @param callable $p_cTask
	 * @param          $p_aParams
	 *
	 * @return mixed
	 */
	public function performSentientTask( callable $p_cTask, $p_aParams )
	{
		// @TODO: When I remove this from the source code the bot adds it back in... What to do?
		return call_user_func_array( $p_cTask, $p_aParams );
	}
}