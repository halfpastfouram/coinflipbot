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

namespace Coinflipbot;

use Halfpastfour\Reddit\Interfaces\Bot;
use Halfpastfour\Reddit\Reddit;
use Halfpastfour\Reddit\TokenStorageMethod;
use Zend\Config\Config;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

/**
 * Class Coinflipbot
 * @package Coinflipbot
 */
class Coinflipbot implements Bot
{
	const PARSE_FLIP             = 1;
	const PARSE_BAN              = 2;
	const PARSE_UNBAN            = 4;
	const PARSE_WHITELIST        = 8;
	const PARSE_UNWHITELIST      = 16;
	const PARSE_WHITELISTED_FLIP = 32;

	/**
	 * The configuration the bot leans on.
	 *
	 * @var Config|null
	 */
	private $config;

	/**
	 * The database adapter used for reading from and writing to the database.
	 *
	 * @var Adapter
	 */
	private $dbAdapter;

	/**
	 * The api wrapper used for communicating with reddit.
	 *
	 * @var Reddit
	 */
	private $reddit;

	/**
	 * Overwrite the configuration values.
	 *
	 * @param Config $p_oConfig The configuration for the bot to use.
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
		// Set the default timezone to GMT+0
		date_default_timezone_set( 'UTC' );

		// Set up db adapter
		$this->dbAdapter	= new Adapter( $this->config->db->toArray() );
		// Try and set the timezone for the current connection
		$this->dbAdapter->query( 'SET TIME_ZONE = "Etc/UTC";' );

		// Set up the reddit client
		$this->reddit = new Reddit(
			$this->config->reddit->account->username,
			$this->config->reddit->account->password,
			$this->config->reddit->client->id,
			$this->config->reddit->client->secret
		);
		$this->reddit->setTokenStorageMethod( TokenStorageMethod::FILE, 'phpreddit:token', 'reddit.token' );
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
		$trigger	= $this->config->actions->trigger;
		$comments	= $this->reddit->getComments(
			$subreddits,
			$this->config->reddit->limit->max_comments
		);

		// Look for ban triggers. It's important to do this first to avoid parsing comments in a banned subreddit.
		print( "Looking for ban triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->ban->toArray(), self::PARSE_BAN ) as $comment ) {
			if( !$this->hasReplied( $comment ) && $this->isCommentFromSubredditMod( $comment )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Banning self from /r/{$comment['data']['subreddit']}\n" );
				$this->banAndReply( $comment );
			}
		}

		// Look for unban triggers
		print( "Looking for unban triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->unban->toArray(), self::PARSE_UNBAN ) as $comment ) {
			if( !$this->hasReplied( $comment ) && $this->isCommentFromSubredditMod( $comment )
				&& $this->isBannedFromSubreddit( $comment['data']['subreddit'], $comment['data']['created_utc'] )
			) {
				print( "Unbanning self from /r/{$comment['data']['subreddit']}\n" );
				$this->unbanAndReply( $comment );
			}
		}

		// Look for whitelist triggers. It's important to do this first to allow parsing comments for special commands
		// in a whitelisted subreddit.
		print( "Looking for whitelist triggers.\n" );
		$triggers	= $trigger->whitelist->toArray();
		foreach( $this->searchComments( $comments, $triggers, self::PARSE_WHITELIST ) as $comment ) {
			if( !$this->hasReplied( $comment ) && $this->isCommentFromSubredditMod( $comment )
				&& !$this->isWhitelistedInSubreddit( $comment['data']['subreddit'] )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Whitelisting /r/{$comment['data']['subreddit']}\n" );
				$this->whitelistAndReply( $comment );
			}
		}

		// Look for unwhitelist triggers
		print( "Looking for unwhitelist triggers.\n" );
		$triggers	= $trigger->unwhitelist->toArray();
		foreach( $this->searchComments( $comments, $triggers, self::PARSE_UNWHITELIST ) as $comment ) {
			if( !$this->hasReplied( $comment ) && $this->isCommentFromSubredditMod( $comment )
				&& $this->isWhitelistedInSubreddit( $comment['data']['subreddit'], $comment['data']['created_utc'] )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Unwhitelisting /r/{$comment['data']['subreddit']}\n" );
				$this->unwhitelistAndReply( $comment );
			}
		}

		// Look for whitelisted flip triggers
		print( "Looking for whitelisted flip triggers.\n" );
		$triggers	= $trigger->whitelisted->flip->toArray();
		foreach( $this->searchComments( $comments, $triggers, self::PARSE_WHITELISTED_FLIP ) as $comment ) {
			if( !$this->hasReplied( $comment ) && !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
				&& $this->isWhitelistedInSubreddit( $comment['data']['subreddit'] )
				&& $comment['data']['author'] != $this->config->reddit->account->username
			) {
				print( "Replying to {$comment['data']['name']}\n" );
				$this->flipAndReply( $comment );
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
	 * Get the thing name for the last parsed comment.
	 *
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
	 * Check if the given comment has been parsed already.
	 *
	 * @param array $p_aComment   The comment to check on.
	 * @param int   $p_iParseType The parse type indicating the type of action the comment was parsed for.
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
	 * Check if the bot is banned from the subreddit with the given name.
	 *
	 * @param string $p_sSubreddit The subreddit to check on.
	 * @param int    $p_iTimestamp Optional timestamp to compare with
	 *
	 * @return bool
	 */
	protected function isBannedFromSubreddit( $p_sSubreddit , $p_iTimestamp = null )
	{
		$statement	= new Sql( $this->dbAdapter );
		$parameters	= [ ':subreddit_name' => strval( $p_sSubreddit ) ];
		$select		= $statement->select()->from( 'subreddits__ignored' )
			->where( 'subreddit_name = :subreddit_name' )
			->where( 'unban IS NULL' );

		if( $p_iTimestamp ) {
			$parameters[ ':timestamp' ] = intval( $p_iTimestamp );
			$select->where( 'ban_timestamp <= :timestamp' );
		}

		$result = $statement->prepareStatementForSqlObject( $select )->execute( $parameters );

		return $result->count() > 0;
	}

	/**
	 * Check if the bot is whitelisted in the subreddit with the given name.
	 *
	 * @param string $p_sSubreddit The subreddit to check on.
	 * @param int    $p_iTimestamp Optional timestamp to compare with
	 *
	 * @return bool
	 */
	protected function isWhitelistedInSubreddit( $p_sSubreddit , $p_iTimestamp = null )
	{
		$statement	= new Sql( $this->dbAdapter );
		$parameters	= [ ':subreddit_name' => strval( $p_sSubreddit ) ];
		$select		= $statement->select()->from( 'subreddits__whitelisted' )
			->where( 'subreddit_name = :subreddit_name' )
			->where( 'unwhitelist IS NULL' );

		if( $p_iTimestamp ) {
			$parameters[ ':timestamp' ] = intval( $p_iTimestamp );
			$select->where( 'whitelist_timestamp <= :timestamp' );
		}

		$result = $statement->prepareStatementForSqlObject( $select )->execute( $parameters );

		return $result->count() > 0;
	}

	/**
	 * Check if the given comment is from a user that is a moderator in the comment's subreddit.
	 *
	 * @param array $p_aComment The comment to check on.
	 *
	 * @return bool
	 */
	protected function isCommentFromSubredditMod( array $p_aComment )
	{
		if( $mods = $this->reddit->subreddit( $p_aComment['data']['subreddit'] )->getMods() ) {
			return in_array( $p_aComment['data']['author'], $mods );
		}
		return false;
	}

	/**
	 * Save the thing name of the given comment to the database with an indicator if the comment has triggered an
	 * response from the bot.
	 *
	 * @param array $p_aCommentData The comment to save.
	 * @param bool  $p_bHit         Indicating if an action was performed because of this comment.
	 * @param int   $p_iParseType   Indicating what type of action the comment was parsed for.
	 *
	 * @return Coinflipbot
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
	 * @param array $p_aComments  An array of comments to search.
	 * @param mixed $p_mNeedle    A string or array of strings that will be searched for in the comments
	 * @param int   $p_iParseType Indicating what type of action the comments should be searched for.
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
				continue;
			}

			foreach( $needles as $needle ) {
				if( stripos( $comment['body'], strval( $needle ) ) !== false ) {
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
	 * Check if the bot has already replied to the given comment.
	 *
	 * @param array $p_aComment The comment to check on.
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
	 * Save the given comment reply to the database with an indicator if the reply is about a ban or a flip.
	 *
	 * @param array  $p_aComment   The comment to be saved.
	 * @param int    $p_iFlip      A flip (1 for heads, 0 for tails and null for no flip-related action).
	 * @param bool   $p_bBan       A ban (1 for ban, 0 for unban and null for no ban-related action).
	 * @param bool   $p_bWhitelist A whitelist (1 for whitelist, 0 for unwhitelist and null for no whitelist-related
	 *                             action).
	 * @param string $p_sMessage   The message that was sent as a reply.
	 *
	 * @return Coinflipbot
	 */
	protected function saveReply(
		array $p_aComment,
		$p_iFlip			= null,
		$p_bBan				= null,
		$p_bWhitelist		= null,
		$p_sMessage
	) {
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'comments__replied' )
			->values( [
				'comment_name'		=> $p_aComment['data']['name'],
				'timestamp'			=> time(),
				'flip'				=> !is_null( $p_iFlip ) ? intval( $p_iFlip ) : null,
				'ban'				=> !is_null( $p_bBan ) ? intval( $p_bBan ) : null,
				'whitelist'			=> !is_null( $p_bWhitelist ) ? intval( $p_bWhitelist ) : null,
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
	 * Flip a coin and reply with a comment telling the user the result.
	 *
	 * @param array $p_aComment The comment to reply to.
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
			], $this->config->actions->response->flip
		) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, $flip, null, null, $message );
		}

		return $this;
	}

	/**
	 * Ban the bot from using the subreddit of the given comment with an indicator whether or not the ban should
	 * be made public.
	 *
	 * @param array $p_aComment       The comment that told the bot to ban itself from the subreddit.
	 * @param bool  $p_bDisplayPublic Indicating whether or not the ban should be made public.
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
	 * Unban the bot from the subreddit of the given comment.
	 *
	 * @param array $p_aComment The comment that told the bot to unban itself from the subreddit.
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
	 * Whitelist the bot in the subreddit of the given comment with an indicator whether or not the whitelist should
	 * be made public.
	 *
	 * @param array $p_aComment       The comment that told the bot to whitelist itself from the subreddit.
	 * @param bool  $p_bDisplayPublic Indicating whether or not the whitelist should be made public.
	 *
	 * @return Coinflipbot
	 */
	protected function whitelistSubreddit( array $p_aComment, $p_bDisplayPublic = true )
	{
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'subreddits__whitelisted' )
			->values( [
				'subreddit_name'             => $p_aComment['data']['subreddit'],
				'whitelist_comment_name'     => $p_aComment['data']['name'],
				'whitelist_requested_by_mod' => $p_aComment['data']['author'],
				'whitelist_timestamp'        => time(),
				'display_public'             => intval( $p_bDisplayPublic ),
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * Unwhitelist the bot in the subreddit of the given comment.
	 *
	 * @param array $p_aComment The comment that told the bot to unwhitelist itself in the subreddit.
	 *
	 * @return Coinflipbot
	 */
	protected function unwhitelistSubreddit( array $p_aComment )
	{
		$sql	= new Sql( $this->dbAdapter );
		$update	= $sql->update( 'subreddits__whitelisted' )
			->set( [
				'unwhitelist'                  => 1,
				'unwhitelist_comment_name'     => $p_aComment['data']['name'],
				'unwhitelist_requested_by_mod' => $p_aComment['data']['author'],
				'unwhitelist_timestamp'        => time(),
			] );
		$selectString	= $sql->buildSqlString( $update );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * Ban the bot from using the subreddit of the given comment and reply with a comment.
	 *
	 * @param array $p_aComment The comment telling the bot to ban itself from a subreddit.
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
			$this->config->actions->response->ban
		) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, null, 1, null, $message );
		}

		return $this;
	}

	/**
	 * Unban the bot from the subreddit of the given comment and reply with a comment.
	 *
	 * @param array $p_aComment The comment telling the bot to unban itself from a subreddit.
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
			$this->config->actions->response->unban
		) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, null, 0, null, $message );
		}

		return $this;
	}

	/**
	 * Whitelist the bot in the subreddit of the given comment and reply with a comment.
	 *
	 * @param array $p_aComment The comment telling the bot to whitelist itself in a subreddit.
	 *
	 * @return Coinflipbot
	 */
	protected function whitelistAndReply( array $p_aComment )
	{
		// Ban self from posting in the subreddit
		$this->whitelistSubreddit( $p_aComment );

		// Reply to the comment
		$message	= str_replace(
				'{author}',
				$p_aComment['data']['author'],
				$this->config->actions->response->whitelist
			) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, null, null, 1, $message );
		}

		return $this;
	}

	/**
	 * Unwhitelist the bot in the subreddit of the given comment and reply with a comment.
	 *
	 * @param array $p_aComment The comment telling the bot to unwhitelist itself in a subreddit.
	 *
	 * @return Coinflipbot
	 */
	protected function unwhitelistAndReply( array $p_aComment )
	{
		// Ban self from posting in the subreddit
		$this->unwhitelistSubreddit( $p_aComment );

		// Reply to the comment
		$message	= str_replace(
				'{author}',
				$p_aComment['data']['author'],
				$this->config->actions->response->unwhitelist
			) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveReply( $p_aComment, null, null, 0, $message );
		}

		return $this;
	}

	/**
	 * Perform sentient tasks. Unpredictable behavior.
	 *
	 * @param callable $p_cTask
	 * @param array    $p_aParams
	 *
	 * @return mixed
	 */
	public function performSentientTask( callable $p_cTask, $p_aParams )
	{
		// @TODO: When I remove this from the source code the bot adds it back in... What to do?
		return call_user_func_array( $p_cTask, $p_aParams );
	}
}