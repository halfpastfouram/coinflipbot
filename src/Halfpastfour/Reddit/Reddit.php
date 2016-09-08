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

namespace Halfpastfour\Reddit;

use LukeNZ\Reddit\HttpMethod;

/**
 * Class Reddit
 * @package Halfpastfour\Reddit
 */
class Reddit extends \LukeNZ\Reddit\Reddit
{
	/**
	 * Makes an OAuth request to Reddit's servers.
	 * Overrides parent::httpRequest because that one returns an object instead of a json string.
	 *
	 * @param   string  $method The method that the Reddit API expects to be used.
	 * @param   string  $url    URL to send to.
	 * @param   array   $body   The body of the request.
	 *
	 * @return string
	 */
	public function httpRequest( $method, $url, $body = null )
	{
		try {
			$tokenReflectionMethod	= new \ReflectionMethod( parent::class, 'getRedditToken' );
			$tokenReflectionMethod->setAccessible( true );
			$tokenReflectionMethod->invoke( $this );

			$headersReflectionMethod	= new \ReflectionMethod( parent::class, 'getHeaders' );
			$headersReflectionMethod->setAccessible( true );

			$headersAndBody	= [
				'headers' => $headersReflectionMethod->invoke( $this )
			];

			if( !is_null( $body ) ) {
				$headersAndBody['form_params']	= $body;
			}

			// Perform the request and return the response
			/** @var \GuzzleHttp\Psr7\Response $result */
			$result			= $this->client->{$method}(Reddit::OAUTH_URL . $url, $headersAndBody);
			$returnValue	= $result->getBody()->getContents();
		} catch( \Exception $exception ) {
			// A problem occurred
			print( "EXCEPTION CAUGHT:\n" );
			print( $exception->getMessage() . "\n" );
			print( "STACK TRACE:\n" );
			print( $exception->getTraceAsString() . "\n" );
			$returnValue	= null;
		}
		return $returnValue;
	}

	/**
	 * Fetches the user currently logged and returns their data.
	 * Overrides parent::me because that method returns a stdObject instead of an array
	 *
	 * @return mixed    The user currently logged in.
	 */
	public function me()
	{
		$response = $this->httpRequest( HttpMethod::GET, 'api/v1/me' );
		return json_decode( $response, true );
	}

	/**
	 * For a given subreddit or list of subreddits, returns the comments.
	 *
	 * @param string|array $p_mSubreddit
	 * @param int          $p_iLimit
	 * @param string|null  $p_sAfter
	 * @param string|null  $p_sBefore
	 *
	 * @return array       The requested comments.
	 */
	public function getComments( $p_mSubreddit, $p_iLimit = 100, $p_sAfter = null, $p_sBefore = null )
	{
		if( !is_array( $p_mSubreddit ) ) {
			$subreddits	= [ strval( $p_mSubreddit ) ];
		} else {
			$subreddits	= array_map( 'strval', $p_mSubreddit );
		}

		// Create the permalink
		$permalink	= 'r/' . implode( '+', $subreddits ) . '/comments.json?limit=' . intval( $p_iLimit );
		if( $p_sAfter ) $permalink .= '&after=' . strval( $p_sAfter );
		if( $p_sBefore ) $permalink .= '&before=' . strval( $p_sBefore );
		$response	= $this->httpRequest( HttpMethod::GET, $permalink );
		if( $response ) {
			return json_decode( $response, true )['data']['children'];
		} else {
			return [];
		}
	}

	/**
	 * Post a comment to a thing identified by the given thing name. Returns the created comment upon sucess.
	 *
	 * @param string $p_sThingName
	 * @param string $p_sReply
	 *
	 * @return array|null
	 */
	public function comment( $p_sThingName, $p_sReply )
	{
		$response	= $this->httpRequest( HttpMethod::POST, '/api/comment.json', [
			'text'		=> strval( $p_sReply ),
			'thing_id'	=> $p_sThingName,
			'api_type'	=> 'json',
		] );

		$result	= json_decode( $response, true );

		return $response && isset( $result['json']['data']['things'][0]['data'] )
			? $result['json']['data']['things'][0]['data']
			: null;
	}

	/**
	 * Return an array with the names of the moderators of the given subreddit.
	 *
	 * @param string $p_sSubreddit
	 *
	 * @return array|null
	 */
	public function getModsFromSubreddit( $p_sSubreddit )
	{
		$response	= $this->httpRequest( HttpMethod::GET, "r/{$p_sSubreddit}/about/moderators.json", [
			'api_type'	=> 'json',
		] );

		$result	= json_decode( $response, true );

		return $response && isset( $result['data']['children'] )
			? array_column( $result['data']['children'], 'name' )
			: null;
	}
}
