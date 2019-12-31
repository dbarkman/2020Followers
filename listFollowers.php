<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$gef = new getMyFollowers();
$nextCursorString = $gef->getFollowers();
echo 'Verified Users: ' . count($gef->verifiedUsers) . PHP_EOL;
echo 'Unverified Users: ' . count($gef->unVerifiedUsers) . PHP_EOL;
$gef->getRateLimitStatus();
while ($nextCursorString != "0") {
	sleep(61);
	$nextCursorString = $gef->getFollowers($nextCursorString);
	echo 'Verified Users: ' . count($gef->verifiedUsers) . PHP_EOL;
	echo 'Unverified Users: ' . count($gef->unVerifiedUsers) . PHP_EOL;
	$gef->getRateLimitStatus();
}

$verifiedUsersFile = 'verifiedUseres.txt';
$unVerifiedUsersFile = 'unVerifiedUseres.txt';
file_put_contents($verifiedUsersFile, '', LOCK_EX);
file_put_contents($unVerifiedUsersFile, '', LOCK_EX);

foreach ($gef->verifiedUsers as $user) {
	$userInfo = $user['followers_count'] . '/' . $user['friends_count'] . '/' . $user['listed_count'] . '/' . $user['statuses_count'] . ' - ' . $user['screen_name'] . ' - ' . $user['name'] . ' - ' . $user['description'];
	file_put_contents($verifiedUsersFile, $userInfo . PHP_EOL, FILE_APPEND | LOCK_EX);
}
foreach ($gef->unVerifiedUsers as $user) {
	$userInfo = $user['followers_count'] . '/' . $user['friends_count'] . '/' . $user['listed_count'] . '/' . $user['statuses_count'] . ' - ' . $user['screen_name'] . ' - ' . $user['name'] . ' - ' . $user['description'];
	file_put_contents($unVerifiedUsersFile, $userInfo . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$gef->getRateLimitStatus();

class getMyFollowers
{
	private $_logger;
	private $_container;
	private $_twitter;
	public $verifiedUsers = array();
	public $unVerifiedUsers = array();

	public function __construct()
	{
		$this->_container = new Container();

		$this->_logger = $this->_container->getLogger();

		global $twitterCreds;
		$this->_twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
	}

	public function getFollowers($cursor = '-1')
	{
		$params = array(
			'screen_name' => '2020followers',
			'count' => '200',
			'skip_status' => 'true',
			'cursor' => $cursor
		);

		$response = $this->_twitter->getFollowers($params);
		$responseDecoded = json_decode($response, true);
		if (isset($responseDecoded['errors'])) {
			echo $responseDecoded['errors'][0]['message'] . PHP_EOL;
			if ($responseDecoded['errors'][0]['code'] == 88) {
				$this->getRateLimitStatus();
			}
			return '0';
		} else {
			$nextCursorString = $responseDecoded['next_cursor_str'];
			$users = $responseDecoded['users'];
			$this->setUsers($users);
			return $nextCursorString;
		}
	}

	private function setUsers($users)
	{
		foreach ($users as $user) {
			$this->users[] = $user;
			if ($user['verified']) {
				$this->verifiedUsers[] = $user;
			} else {
				$this->unVerifiedUsers[] = $user;
			}
//			echo $user['name'] . ' - ' . $user['screen_name'] . ' - ' . $user['followers_count'] . ' - ' . $verified . PHP_EOL;
		}
	}

	public function getRateLimitStatus()
	{
		$params = array(
			'resources' => 'followers'
		);

		$errorResponse = $this->_twitter->getRateLimitStatus($params);
		$errorResponseDecoded = json_decode($errorResponse, true);
		$resource = $errorResponseDecoded['resources']['followers']['/followers/list'];
		$limit = $resource['limit'];
		$remaining = $resource['remaining'];
		$reset = $resource['reset'];
		$secondsTillReset = gmdate('i:s', $reset - time());
		echo 'Followers/List Rate Limit Status: ' . $remaining . '/' . $limit . ' - TTR: ' . $secondsTillReset . PHP_EOL;
	}
}