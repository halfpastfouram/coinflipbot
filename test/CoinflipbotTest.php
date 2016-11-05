<?php

namespace Halfpastfour\Test;

use Coinflipbot\Coinflipbot;
use Coinflipbot\Service\Comment;
use Coinflipbot\Service\Message;
use Coinflipbot\Service\Subreddit;
use Halfpastfour\Reddit\Reddit;
use PHPUnit\Framework\TestCase;
use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\Db\Adapter\Adapter;

/**
 * Class Coinflipbot
 * @package Halfpastfour\Test
 */
class CoinflipbotTest extends TestCase
{
	const DATA_FILES	= [
		'comments'	=> __DIR__ . '/data/subreddit/comments.php',
		'messages'	=> [
			'unread' => __DIR__ . '/data/message/unread.php',
			'inbox'  => __DIR__ . '/data/message/inbox.php',
		]
	];

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Adapter
	 */
	protected $dbAdapter;

	/**
	 * @var Reddit
	 */
	protected $reddit;

	/**
	 * @var Comment
	 */
	protected $commentService;

	/**
	 * @var Message
	 */
	protected $messageService;

	/**
	 * @var Subreddit
	 */
	protected $subredditService;

	/**
	 *
	 */
	public function setUp()
	{
		// Get the configuration
		$this->config	= Factory::fromFile( __DIR__ . '/config.ini', true );

		// Mock the database adapter
		$dbAdapter	= $this->getMockBuilder( 'Zend\Db\Adapter\Adapter' )
			->setConstructorArgs( [ $this->config->db->toArray() ] )
			->getMock();

		$this->dbAdapter	= $dbAdapter;

		// Mock the Reddit API client
		$reddit	= $this->getMockBuilder( 'Halfpastfour\Reddit\Reddit' )
			->setConstructorArgs( [
				$this->config->reddit->account->username,
				$this->config->reddit->account->password,
				$this->config->reddit->client->id,
				$this->config->reddit->client->secret
			] )->getMock();

		// Mock getComments method
		$reddit->expects( $this->any() )
			->method( 'getComments' )
			->will( $this->returnValue( include self::DATA_FILES['comments'] ) );

		// Mock getUnreadPrivateMessages method
		$reddit->expects( $this->any() )
			->method( 'getUnreadPrivateMessages' )
			->will( $this->returnValue( include self::DATA_FILES['messages']['unread'] ) );

		// Mock getPrivateMessages method
		$reddit->expects( $this->any() )
			->method( 'getPrivateMessages' )
			->will( $this->returnValue( include self::DATA_FILES['messages']['unread'] ) );

		$this->reddit	= $reddit;

		// Mock the adapters for the services
		$commentMapper		= $this->getMockBuilder( 'Coinflipbot\Mapper\Comment' )
			->setConstructorArgs( [ $this->dbAdapter ] )
			->getMock();

		$messageMapper		= $this->getMockBuilder( 'Coinflipbot\Mapper\Message' )
			->setConstructorArgs( [ $this->dbAdapter ] )
			->getMock();

		$subredditMapper	= $this->getMockBuilder( 'Coinflipbot\Mapper\Subreddit' )
			->setConstructorArgs( [ $this->dbAdapter ] )
			->getMock();

		$this->assertInstanceOf( \Coinflipbot\Mapper\Comment::class, $commentMapper, 'is comment mapper' );

		$this->commentService	= new Comment( $commentMapper );
		$this->messageService	= new Message( $messageMapper );
		$this->subredditService	= new Subreddit( $subredditMapper );
	}

	/**
	 *
	 */
	public function testWorkflow()
	{
		$coinflipbot = new Coinflipbot(
			$this->reddit,
			$this->config,
			$this->commentService,
			$this->messageService,
			$this->subredditService
		);

		// Perform a test run
		$runResult   = $coinflipbot->run();
		$this->assertEquals( $runResult, $coinflipbot, 'Running returns instance of Coinflipbot' );

		// Shutdown the bot
		$shutdownResult = $coinflipbot->shutdown();
		$this->assertEquals( $shutdownResult, $coinflipbot, 'Shutting down returns instance of Coinflipbot' );

		// Trying to run again results in an exception since the reddit client has been unset.
		$this->expectException( 'Error' );
		$this->expectExceptionMessage( 'Call to a member function getComments() on null' );
		$coinflipbot->run();
	}

	/**
	 *
	 */
	public function testParseComments()
	{
		$coinflipbot = new Coinflipbot(
			$this->reddit,
			$this->config,
			$this->commentService,
			$this->messageService,
			$this->subredditService
		);

		$this->assertTrue( $coinflipbot->parseMessages() instanceof Coinflipbot, 'Parse messages returns Coinflipbot' );
	}
}