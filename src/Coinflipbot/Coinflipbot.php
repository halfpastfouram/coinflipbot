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

use Coinflipbot\Service\Comment;
use Coinflipbot\Service\Message;
use Coinflipbot\Service\Subreddit;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Halfpastfour\Reddit\Reddit;
use Zend\Config\Config;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

/**
 * Class Coinflipbot
 * @package Coinflipbot
 */
class Coinflipbot
{
	const THING_COMMENT			= 1;
	const THING_ACCOUNT			= 2;
	const THING_LINK			= 3;
	const THING_MESSAGE			= 4;
	const THING_SUBREDDIT		= 5;

	/**
	 * The configuration the bot leans on.
	 *
	 * @var Config|null
	 */
	private $config;

	/**
	 * The api wrapper used for communicating with reddit.
	 *
	 * @var Reddit
	 */
	private $reddit;

	/**
	 * Receives injected dependencies.
	 *
	 * @param Reddit                         $reddit The reddit api client.
	 * @param Config                         $config The configuration data.
	 * @param \Coinflipbot\Service\Comment   $commentService
	 * @param \Coinflipbot\Service\Message   $messageService
	 * @param \Coinflipbot\Service\Subreddit $subredditService
	 */
	public function __construct(
		Reddit $reddit,
		Config $config,
		Comment $commentService,
		Message $messageService,
		Subreddit $subredditService
	) {
		print( "Initializing.\n" );

		// Set the default timezone to GMT+0
		date_default_timezone_set( 'UTC' );

		$this->reddit			= $reddit;
		$this->config			= $config;
		$this->commentService	= $commentService;
		$this->messageService	= $messageService;
		$this->subredditService	= $subredditService;
	}

	/**
	 * Should execute the logic performing the bot's job.
	 *
	 * @return Coinflipbot
	 */
	public function run() : Coinflipbot
	{
		print( "Running.\n" );

		// Start parsing comments
		$this->parseComments();

		// Start parsing private messages
		$this->parseMessages();

		return $this;
	}

	/**
	 * Parse recent comments.
	 *
	 * @return Coinflipbot
	 */
	private function parseComments() : Coinflipbot
	{
		print( "Parsing comments.\n" );

		$subreddits	= $this->config->reddit->subreddit->toArray();
		$trigger	= $this->config->actions->trigger;
		$comments	= $this->reddit->getComments(
			$subreddits,
			$this->config->reddit->limit->max_comments
		);

		// Look for ban triggers. It's important to do this first to avoid parsing comments in a banned subreddit.
		print( "Looking for ban triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->ban->toArray(), ParseType::BAN ) as $comment ) {
			if( !$this->hasReplied( self::THING_COMMENT, $comment['data']['name'] )
				&& $this->isCommentFromSubredditMod( $comment )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Banning self from /r/{$comment['data']['subreddit']}\n" );
				$this->banAndReply( $comment );
			}
		}

		// Look for unban triggers
		print( "Looking for unban triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->unban->toArray(), ParseType::UNBAN ) as $comment ) {
			if( !$this->hasReplied( self::THING_COMMENT, $comment['data']['name'] )
				&& $this->isCommentFromSubredditMod( $comment )
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
		foreach( $this->searchComments( $comments, $triggers, ParseType::WHITELIST ) as $comment ) {
			if( !$this->hasReplied( self::THING_COMMENT, $comment['data']['name'] )
				&& $this->isCommentFromSubredditMod( $comment )
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
		foreach( $this->searchComments( $comments, $triggers, ParseType::UNWHITELIST ) as $comment ) {
			if( !$this->hasReplied( self::THING_COMMENT, $comment['data']['name'] )
				&& $this->isCommentFromSubredditMod( $comment )
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
		foreach( $this->searchComments( $comments, $triggers, ParseType::WHITELISTED_FLIP ) as $comment ) {
			if( !$this->hasReplied( self::THING_COMMENT, $comment['data']['name'] )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
				&& $this->isWhitelistedInSubreddit( $comment['data']['subreddit'] )
				&& $comment['data']['author'] != $this->config->reddit->account->username
			) {
				print( "Replying to {$comment['data']['name']}\n" );
				$this->flipAndReply( self::THING_COMMENT, $comment, $comment['data']['user'] );
			}
		}

		// Look for flip triggers
		print( "Looking for flip triggers.\n" );
		foreach( $this->searchComments( $comments, $trigger->flip->toArray(), ParseType::FLIP ) as $comment ) {
			if( !$this->hasReplied( self::THING_COMMENT, $comment )
				&& !$this->isBannedFromSubreddit( $comment['data']['subreddit'] )
			) {
				print( "Replying to {$comment['data']['name']}\n" );
				$this->flipAndReply( self::THING_COMMENT, $comment, $comment['data']['user'] );
			}
		}
		return $this;
	}

	/**
	 * @return Coinflipbot
	 */
	public function parseMessages() : Coinflipbot
	{
		print( "Parsing messages.\n" );

		$trigger	= $this->config->actions->pm->trigger;
		$messages	= $this->reddit->getUnreadPrivateMessages( $this->config->reddit->limit->max_messages );

		print( "Looking for ban triggers.\n" );
		$filter	= [ 'distinguished' => 'moderator', 'author' => null ];
		foreach( $this->searchMessages( $messages, $trigger->ban->toArray(), ParseType::BAN, $filter ) as $message ) {
			$this->banFromSubreddit( $message['data']['subreddit'], $message['data']['name'], '' );
		}

		print( "Looking for flip triggers.\n" );
		foreach( $this->searchMessages( $messages, $trigger->flip->toArray(), ParseType::FLIP ) as $message ) {
			if( !$this->hasReplied( self::THING_MESSAGE, $message['data']['name'] ) ) {
				$this->flipAndReply( self::THING_MESSAGE, $message, $message['data']['author'] );
			}
		}

		return $this;
	}

	/**
	 * Should shut down all activity or open connections and should be the last method to be executed.
	 *
	 * @return Coinflipbot
	 */
	public function shutdown() : Coinflipbot
	{
		print( "Shutting down.\n" );

		// Unset the reddit client and the database adapter.
		$this->reddit    = null;
		$this->dbAdapter = null;

		return $this;
	}

	/**
	 * Get the thing name for the last parsed comment.
	 *
	 * @return string
	 */
	protected function getLastParsedCommentName() : string
	{
		$statement	= new Sql( $this->dbAdapter );
		$select	= $statement->select()->from( 'comments__parsed' )->order( 'id DESC' )->limit( 1 );
		$result	= $statement->prepareStatementForSqlObject( $select )->execute();

		return $result->count() ? $result->current()['comment_name'] : '';
	}

	/**
	 * Return the table and column prefix names for the given thing.
	 *
	 * @param int $thing The thing to get the information for.
	 *
	 * @return array
	 */
	protected function getTableAndColumnForThing( int $thing ) : array
	{
		switch( $thing ) {
			case self::THING_MESSAGE:
				$table	= 'messages';
				$column	= 'message';
				break;
			case self::THING_COMMENT:
				$table	= 'comments';
				$column	= 'comment';
				break;
			default:
				$table	= null;
				$column	= null;
				break;
		}

		return [ $table, $column ];
	}

	/**
	 * Check if the given thing has been parsed already.
	 *
	 * @param int    $thing     The thing type to check on
	 * @param string $name      The name of the thing to check on.
	 * @param int    $parseType The parse type indicating the type of action the comment was parsed for.
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	protected function hasParsed( int $thing, string $name, int $parseType ) : bool
	{
		switch( $thing ) {
			case self::THING_COMMENT:
				return !!$this->commentService->findParsedByName( $name, $parseType );
			case self::THING_MESSAGE:
				return !!$this->messageService->findParsedByName( $name, $parseType );
			default:
				throw new \InvalidArgumentException( 'Invalid thing specified. Expecting Coinflipbot::THING_COMMENT or '
					. 'Coinflipbot::THING_MESSAGE' );
		}
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
	 * Check if the bot is white listed in the subreddit with the given name.
	 *
	 * @param string $subreddit    The subreddit to check on.
	 * @param int    $p_iTimestamp Optional timestamp to compare with
	 *
	 * @return bool
	 */
	protected function isWhitelistedInSubreddit( string $subreddit , int $p_iTimestamp = null ) : bool
	{
		$statement	= new Sql( $this->dbAdapter );
		$parameters	= [ ':subreddit_name' => strval( $subreddit ) ];
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
	 * @param array $comment The comment to check on.
	 *
	 * @return bool
	 */
	protected function isCommentFromSubredditMod( array $comment ) : bool
	{
		if( $mods = $this->reddit->subreddit( $comment['data']['subreddit'] )->getMods() ) {
			return in_array( $comment['data']['author'], $mods );
		}
		return false;
	}

	/**
	 * Save the thing name of the given thing to the database with an indicator if the thing has triggered a
	 * response from the bot.
	 *
	 * @param int    $thing     The thing to save
	 * @param string $name      The name to save.
	 * @param bool   $hit       Indicating if an action was performed because of this thing.
	 * @param int    $parseType Indicating what type of action the thing was parsed for.
	 *
	 * @return Coinflipbot
	 */
	protected function saveParsedThing( int $thing, string $name, bool $hit, int $parseType ) : Coinflipbot
	{
		switch( $thing ) {
			case self::THING_COMMENT:
				$this->commentService->saveParsed( [
					"comment_name" => $name,
					'timestamp'    => time(),
					'parse_type'   => $parseType,
					'hit'          => $hit,
				] );
				break;
			case self::THING_MESSAGE:
				$this->messageService->saveParsed( [
					"comment_name" => $name,
					'timestamp'    => time(),
					'parse_type'   => $parseType,
					'hit'          => $hit,
				] );
				break;
			default:
				throw new InvalidArgumentException( 'Invalid thing identifier given' );
		}

		return $this;
	}

	/**
	 * Search comment body for given needle and return only those that have the needle.
	 *
	 * @param array $comments  An array of comments to search.
	 * @param mixed $needle    A string or array of strings that will be searched for in the comments
	 * @param int   $parseType Indicating what type of action the comments should be searched for.
	 *
	 * @return array
	 */
	protected function searchComments( array $comments, $needle, int $parseType ) : array
	{
		$needles	= !is_array( $needle ) ? array( $needle ) : $needle;
		$hits		= [ ];
		foreach( $comments as $index => $commentData ) {
			$comment	= $commentData['data'];
			$hit		= 0;
			// Skip this comment if it has already been parsed before
			if( $this->hasParsed( self::THING_COMMENT, $comment['name'], $parseType ) ) {
				continue;
			}

			foreach( $needles as $currentNeedle ) {
				if( stripos( $comment['body'], strval( $currentNeedle ) ) !== false ) {
					$hits[] = $commentData;
					$hit    = 1;
					break;
				}
			}
			$this->saveParsedThing( self::THING_COMMENT, $comment['name'], $hit, $parseType );
		}

		return $hits;
	}

	/**
	 * Search message body for given needle and return only those that have the needle.
	 *
	 * @param array $messages  An array of messages to search.
	 * @param array $needle    An array of strings that will be searched for in the messages
	 * @param int   $parseType Indicating what type of action the messages should be searched for.
	 * @param array $filter    A key=>value pairs used as an extra filter.
	 *
	 * @return array
	 */
	protected function searchMessages( array $messages, array $needle, int $parseType, array $filter = [] ) : array
	{
		// internal function to apply filters to a message and return a bool to indicate success.
		$applyFilters	= function( $message ) use ( $filter ) : bool {
			$conditionsMet	= true;
			foreach( $filter as $item => $value ) {
				if( $message[ $item ] != $value ) return false;
			}
			return $conditionsMet;
		};

		$needles	= !is_array( $needle ) ? array( $needle ) : $needle;
		$hits		= [ ];
		foreach( $messages as $index => $messageData ) {
			// Skip this message if it is not an actual private message.
			if( $messageData['kind'] != 't4' ) continue;
			$message	= $messageData['data'];
			$hit		= 0;

			// Skip this message if it has already been parsed before
			if( $this->hasParsed( self::THING_MESSAGE, $message['name'], $parseType ) ) {
				continue;
			}

			foreach( $needles as $currentNeedle ) {
				// If the needle is in the body
				if( ( stripos( $message['body'], strval( $currentNeedle ) ) !== false
					// Or the needle is in the subject and this is a parent message
					|| (
						stripos( $message['subject'], strval( $currentNeedle ) ) !== false
						&& !$message['parent_id']
					) )
					// If filters have been provided, apply them
					&& ( !$filter || $applyFilters( $message ) )
				) {
					$hits[] = $messageData;
					$hit    = 1;
					break;
				}
			}
			$this->saveParsedThing( self::THING_MESSAGE, $message['name'], $hit, $parseType );
		}

		return $hits;
	}

	/**
	 * Check if the bot has already replied to the given comment.
	 *
	 * @param int    $thing The thing to check on.
	 * @param string $name  The name to check on.
	 *
	 * @return bool
	 */
	protected function hasReplied( int $thing, string $name ) : bool
	{
		$statement = new Sql( $this->dbAdapter );
		list( $table, $column ) = $this->getTableAndColumnForThing( $thing );

		$select = $statement->select()->from( "{$table}__replied" )->where( "{$column}_name = :thing_name" );
		$result = $statement->prepareStatementForSqlObject( $select )->execute( [ ':thing_name' => strval( $name ) ] );

		return $result->count() > 0;
	}

	/**
	 * Save the given comment reply to the database with an indicator if the reply is about a ban, white list or a flip.
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
	protected function saveCommentReply(
		array $p_aComment,
		$p_iFlip			= null,
		$p_bBan				= null,
		$p_bWhitelist		= null,
		$p_sMessage
	) : Coinflipbot {
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
	 * Save the given message reply to the database with an indicator if the reply is about a ban or a flip.
	 *
	 * @param array  $p_aMessage The message to be saved.
	 * @param int    $p_iFlip    A flip (1 for heads, 0 for tails and null for no flip-related action).
	 * @param bool   $p_bBan     A ban (1 for ban, 0 for unban and null for no ban-related action).
	 * @param string $p_sMessage The message that was sent as a reply.
	 *
	 * @return Coinflipbot
	 */
	protected function saveMessageReply(
		array $p_aMessage,
		$p_iFlip			= null,
		$p_bBan				= null,
		$p_sMessage
	) : Coinflipbot {
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'messages__replied' )
			->values( [
				'message_name'		=> $p_aMessage['data']['name'],
				'timestamp'			=> time(),
				'flip'				=> !is_null( $p_iFlip ) ? intval( $p_iFlip ) : null,
				'ban'				=> !is_null( $p_bBan ) ? intval( $p_bBan ) : null,
				'user'				=> $p_aMessage['data']['author'],
				'subreddit_name'	=> $p_aMessage['data']['subreddit'],
				'post_name'			=> $p_aMessage['data']['link_id'],
				'post_title'		=> $p_aMessage['data']['link_title'],
				'url'				=> $p_aMessage['data']['link_url'],
				'reply'				=> strval( $p_sMessage ),
			] );
		$selectString	= $sql->buildSqlString( $insert );
		$this->dbAdapter->query( $selectString, Adapter::QUERY_MODE_EXECUTE );

		return $this;
	}

	/**
	 * Flip a coin and reply telling the user the result.
	 *
	 * @param int    $thing
	 * @param array  $p_aThing The thing data to reply to.
	 * @param string $user The user to reply to.
	 *
	 * @return Coinflipbot
	 */
	protected function flipAndReply( int $thing, array $p_aThing, string $user ) : Coinflipbot
	{
		// Determine what message template should be used
		$template	= $thing == self::THING_MESSAGE
			? $this->config->actions->pm->response->flip
			: $this->config->actions->response->flip;

		// Generate flip: 0 is tails, 1 is heads.
		$flip    = rand( 0, 1 );

		// Reply to the comment
		$message = str_replace( [
				'{author}',
				'{flip-result}'
			], [
				$user,
				$flip ? 'heads' : 'tails'
			], $template
		) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aThing['data']['name'], $message ) ) {
			if( $thing == self::THING_MESSAGE ) {
				$this->saveCommentReply( $p_aThing, $flip, null, null, $message );
			} else {
				$this->saveMessageReply( $p_aThing, $flip, null, $message );
			}
		}

		return $this;
	}

	/**
	 * Ban the bot from using the subreddit of the given comment with an indicator whether or not the ban should
	 * be made public.
	 *
	 * @param string $subreddit        The subreddit the bot should ban itself from.
	 * @param string $thingName        The thing that told the bot to ban itself from the subreddit.
	 * @param string $user             The user that told the bot to ban itself from the subreddit.
	 * @param bool   $p_bDisplayPublic Indicating whether or not the ban should be made public.
	 *
	 * @return Coinflipbot
	 */
	protected function banFromSubreddit(
		string $subreddit,
		string $thingName,
		string $user,
		$p_bDisplayPublic = true
	) : Coinflipbot {
		$sql	= new Sql( $this->dbAdapter );
		$insert	= $sql->insert( 'subreddits__ignored' )
			->values( [
				'subreddit_name'		=> $subreddit,
				'ban_thing_name'		=> $thingName,
				'ban_requested_by_mod'	=> $user ?: null,
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
	 * @param string $subreddit $The subreddit the bot should be unbanned from.
	 * @param string $thingName The thing that told the bot to unban itself from the subreddit.
	 * @param string $user      The user that told the bot to unban itself from the subreddit.
	 *
	 * @return Coinflipbot
	 */
	protected function unbanFromSubreddit( string $subreddit, string $thingName, string $user ) : Coinflipbot
	{
		$sql	= new Sql( $this->dbAdapter );
		$update	= $sql->update( 'subreddits__ignored' )
			->set( [
				'unban'                  => 1,
				'unban_thing_name'       => $thingName,
				'unban_requested_by_mod' => $user,
				'unban_timestamp'        => time(),
			] )->where( [ "subreddit_name" => $subreddit ] );
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
			] )->where( [
				'subreddit' => $p_aComment['data']['subreddit'],
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
		$this->banFromSubreddit(
			$p_aComment['data']['subreddit'],
			$p_aComment['data']['name'],
			$p_aComment['data']['author']
		);

		// Reply to the comment
		$message	= str_replace(
			'{author}',
			$p_aComment['data']['author'],
			$this->config->actions->response->ban
		) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveCommentReply( $p_aComment, null, 1, null, $message );
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
		$this->unbanFromSubreddit(
			$p_aComment['data']['subreddit'],
			$p_aComment['data']['name'],
			$p_aComment['data']['author']
		);

		// Reply to the comment
		$message	= str_replace(
			'{author}',
			$p_aComment['data']['author'],
			$this->config->actions->response->unban
		) . "\n\n---\n\n{$this->config->actions->response->footer}";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->saveCommentReply( $p_aComment, null, 0, null, $message );
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
			$this->saveCommentReply( $p_aComment, null, null, 1, $message );
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
			$this->saveCommentReply( $p_aComment, null, null, 0, $message );
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