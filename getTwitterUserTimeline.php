<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if ($argv[1] == null) {
        exit('all clean' . PHP_EOL);
}

$gtut = new getTwitterUserTimeline();
$gtut->getTimeline($argv[1]);

class getTwitterUserTimeline
{
	private $_logger;

	public function __construct()
	{
		$container = new Container();

		$this->_logger = $container->getLogger();
	}

	public function getTimeline($user)
	{
		global $twitterCreds;

		$user = array(
			'screen_name' => $user
		);

		$twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
		$timeline = json_decode($twitter->getStatuses($user));
		if ($timeline === false) {
			$this->_logger->error('Twitter get timeline failed');
		} else {
			foreach ($timeline as $tweet) {
				echo strlen($tweet->text) . ' - ' . $tweet->text . PHP_EOL; #earthquake
			}
//			var_dump($timeline);
		}
	}
}