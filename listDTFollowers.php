<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

echo 'here';
$gf = new getFollowers();
echo 'here';
$count = $gf->getFollowers();
echo 'here';
//$entry = date('c') . ',' . $count . PHP_EOL;
//echo 'here';
//
//$dtFollowersFile = '/var/www/html/Earthquakes/Followers.txt';
//file_put_contents($dtFollowersFile, $entry, FILE_APPEND | LOCK_EX);

class getFollowers
{
	private $_logger;
	private $_container;
	private $_twitter;

	public function __construct()
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();

		global $twitterCreds;
		$this->_twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
        echo 'here';
	}

	public function getFollowers()
	{
        $this->_logger->info("Let's see how many followers Donald Trump has!");

		$params = array(
			'screen_name' => 'realDonaldTrump'
		);

		$response = $this->_twitter->getUser($params);
		$responseDecoded = json_decode($response, true);
		if (isset($responseDecoded['errors'])) {
			echo $responseDecoded['errors'][0]['message'] . PHP_EOL;
			return '0,0,0';
		} else {
		    $followers_count = $responseDecoded['followers_count'];
		    $friends_count = $responseDecoded['friends_count'];
		    $listed_count = $responseDecoded['listed_count'];
		    $count = $followers_count . ',' . $listed_count . ',' . $friends_count;
		    $this->_logger->info($count . ' - followers, listed, friends');
		    return $count;
		}
	}
}