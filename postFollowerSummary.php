<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) <= 1) {
    echo 'Include an argument, either hour, hours or day.' . "\n";
} else {
    $hours = '';
    if (isset($argv[2])) {
        $hours = $argv[2];
    }

    $pfs = new postFollowerSummary();
    $pfs->buildAndSendSummary($argv[1], $hours);
}

class postFollowerSummary
{
    private $_container;
    private $_logger;
    private $_db;
    private $_twitter;

	public function __construct()
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
        $this->_db = $this->_container->getMySQLDBConnect();

		global $twitterCreds;
		$this->_twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
	}

	public function buildAndSendSummary($time, $hours)
    {
        $candidates = $this->getCandidates();
        $statusArray = array();
        $rowCount = 0;
        $twitterUpdate = '';
        foreach ($candidates as $candidate) {
            $rowCount++;
            $screenName = $candidate['screenName'];
            $counts = $this->getFollowerChangeCount($screenName, $time, $hours);
            $followerCount = $counts[0];
            $lastCount = $counts[1];

            if ($time == 'day') {
                $twitterUpdate .= '' . $screenName;
            } else if ($time == 'hours') {
                $twitterUpdate .= '' . $screenName;
                $hours .= ' ';
            } else {
                $twitterUpdate .= '' . $screenName;
            }
            if ($followerCount > $lastCount) {
                $change = $followerCount - $lastCount;
                $percentChange = ($change / $followerCount) * 100;
                $twitterUpdate .= ' gained ' . number_format($change) . ' Twitter followers in the last ' . $hours . $time . ', for a ' . number_format($percentChange, 4) . '% increase,';
                $twitterUpdate .= ' with a current count of ' . number_format($followerCount) . ' followers.' . PHP_EOL;
            } else if ($followerCount < $lastCount) {
                $change = $lastCount - $followerCount;
                $percentChange = ($change / $followerCount) * 100;
                $twitterUpdate .= ' lost ' . number_format($change) . ' Twitter followers in the last ' . $hours . $time . ', for a ' . number_format($percentChange, 4) . '% decrease,';
                $twitterUpdate .= ' with a current count of ' . number_format($followerCount) . ' followers.' . PHP_EOL;
            } else if ($followerCount == $lastCount) {
                $twitterUpdate .= ' remained at ' . number_format($followerCount) . ' Twitter followers for the last ' . $hours . $time . '.' . PHP_EOL;
            }

            $twitterUpdate .= '#2020election';
            array_push($statusArray, $twitterUpdate);
            $twitterUpdate = '';
            $hours = trim($hours);
        }

        foreach ($statusArray as $status) {
//            echo $status . PHP_EOL;
//            echo PHP_EOL;
            $this->sendTweet($status);
        }
    }

    public function getCandidates() {
        $sql = "
			SELECT
				screenName
			FROM
				candidates
			WHERE
				active = '1'
            ORDER BY
				rank DESC
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($rows, $row);
        }
        return $rows;
    }

    public function getFollowerChangeCount($screenName, $time, $hours)
	{
	    $limit = 60;
        if ($time == 'hours') $limit = $limit * $hours;
        if ($time == 'day') $limit = 1440;

        $sql = "
            SELECT
                followersCount
            FROM
                counts
            WHERE
                screenName = '$screenName'
            ORDER BY
                insertDate DESC
            LIMIT
                $limit
        ";

        $result = mysqli_query($this->_db, $sql);
        $rows = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($rows, $row['followersCount']);
        }
        if ($time == 'day') return array($rows[0], $rows[1439]);
        if ($time == 'hours') return array($rows[0], $rows[$limit - 1]);
        return array($rows[0], $rows[59]);
	}

    public function sendTweet($status)
    {
        $this->_logger->info('Tweeting this:' . PHP_EOL . $status);

        $tweet = array(
            'status' => $status
        );

        $response = $this->_twitter->tweet($tweet);
        $responseDecoded = json_decode($response, true);
        $curlErrno = $responseDecoded['curlErrno'];
        $curlInfo = $responseDecoded['curlInfo'];
        $httpCode = $curlInfo['http_code'];

        if ($curlErrno != 0) {
            $this->_logger->error('Twitter post failed again: Curl error: ' . $curlErrno);
        } else if ($httpCode != 200) {
            $this->_logger->error('Twitter post failed again: Twitter error: ' . $httpCode);
        } else {
            $this->_logger->debug('Tweet sent.');
        }
    }
}