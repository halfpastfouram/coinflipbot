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

namespace Halfpastfour\Reddit\Interfaces;

use Zend\Config\Config;

/**
 * Interface Bot
 * @package Halfpastfour\Reddit\Interfaces
 */
Interface Bot
{
	/**
	 * Overwrite the configuration values.
	 *
	 * @param Config $p_oConfig
	 *
	 * @return Bot
	 */
	public function setConfig( Config $p_oConfig );

	/**
	 * Should take care of requirements before doing its job.
	 *
	 * @return Bot
	 */
	public function init();

	/**
	 * Should execute the logic performing the bot's job.
	 *
	 * @return Bot
	 */
	public function run();

	/**
	 * Should shut down all activity or open connections and should be the last method to be executed.
	 *
	 * @return Bot
	 */
	public function shutdown();
}