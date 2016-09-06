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
use Zend\Config\Config;

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
		// Set up the reddit client
		$this->reddit = new Reddit(
			$this->config->reddit->account->username,
			$this->config->reddit->account->password,
			$this->config->reddit->client->id,
			$this->config->reddit->client->secret
		);
		$this->reddit->setUserAgent( $this->config->info->description );

		return $this;
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return bool
	 */
	protected function hasReplied( array $p_aComment )
	{
		return in_array( $p_aComment['data']['name'], $this->replied );
	}

	/**
	 * @param array $p_aComment
	 *
	 * @return Coinflipbot
	 */
	public function replyWithFlip( array $p_aComment )
	{
		$flip    = rand( 0, 1 );
		$result  = $flip ? 'heads' : 'tails';
		$message = "Hey there, /u/{$p_aComment['data']['author']}! I flipped a coin for you and the result was: "
			. "{$result}.";

		if( $this->reddit->comment( $p_aComment['data']['name'], $message ) ) {
			$this->replied[] = $p_aComment['data']['name'];
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
	protected function searchComments( array $p_aComments, $p_sNeedle )
	{
		$hits = [ ];
		foreach( $p_aComments as $index => $commentData ) {
			$comment = $commentData['data'];
			if( strpos( $comment['body'], strval( $p_sNeedle ) ) !== false ) {
				$hits[] = $commentData;
			}
		}

		return $hits;
	}

	/**
	 * Should execute the logic performing the bot's job.
	 *
	 * @return Bot
	 */
	public function run()
	{
		$comments = $this->reddit->getComments( 'coinflipbot', 100 );

		foreach( $this->searchComments( $comments, '+/u/coinflipbot' ) as $comment ) {
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
		// TODO: Implement shutdown() method.
		return $this;
	}
}