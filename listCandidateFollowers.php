<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) <= 1) {
    echo 'Include an argument, either minute or hour.' . "\n";
} else {
    $candidateArray = array(
        'realDonaldTrump',
        'JoeBiden',
        'BernieSanders',
        'EWarren',
        'PeteButtigieg',
        'MikeBloomberg',
        'AndrewYang',
        'AmyKlobuchar',
        'CoryBooker',
        'TulsiGabbard',
        'TomSteyer',
        'JulianCastro'
    );

    $date = date('c');
    $followersFile = '/var/www/html/2020Followers/Followers.txt';
//    $followersFile = 'Followers.txt';
    $tempCountFile = '/var/www/html/2020Followers/tempCount';
//    $tempCountFile = 'tempCount';

    $statusArray = array();
    $tempCountArray = array();
    $position = 0;
    $twitterUpdate = '';
    $rowCount = 0;
    if ($argv[1] == 'hour') {
        $tempCountArray = explode("\n", file_get_contents($tempCountFile));
        file_put_contents($tempCountFile, '');
    }

    $gf = new listCandidateFollowers();
    foreach ($candidateArray as $candidate) {
        $rowCount++;
        $count = $gf->getFollowers($candidate);
        $entry = $date . ',' . $count . PHP_EOL;

        file_put_contents($followersFile, $entry, FILE_APPEND);

        if ($argv[1] == 'hour') {
            $followerCount = explode(',', $count)[1];
            file_put_contents($tempCountFile, $followerCount . PHP_EOL, FILE_APPEND);

            $lastCount = $tempCountArray[$position];
            $twitterUpdate .= '@' . $candidate;
            if ($followerCount > $lastCount) {
                //they have more
                $change = $followerCount - $lastCount;
                $twitterUpdate .= ' gained ' . number_format($change);
            } else if ($followerCount < $lastCount) {
                //they have less
                $change = $lastCount - $followerCount;
                $twitterUpdate .= ' lost ' . number_format($change);
            } else if ($followerCount == $lastCount) {
                //they did not change
                $twitterUpdate .= ' remained at ' . number_format($followerCount);
            }
            $twitterUpdate .= ' Twitter followers in the last hour, for a current count of ' . number_format($followerCount) . ' followers.' . PHP_EOL;

            $this->_logger->info('Tweeting this: ' . $twitterUpdate);

            if ($rowCount == 2) {
                array_push($statusArray, $twitterUpdate);
                $twitterUpdate = '';
                $rowCount = 0;
            }
            $position++;
        }
    }

    foreach ($statusArray as $status) {
        $gf->sendTweet($status);
    }
}

class listCandidateFollowers
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
	}

	public function getFollowers($screenName)
	{
		$params = array(
			'screen_name' => $screenName
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
		    $this->_logger->info('Count for ' . $screenName . ': ' . $count . ' - followers, listed, friends');
		    $count = $screenName . ',' . $count;
		    return $count;
		}
	}

	public function sendTweet($status)
    {
        $this->_logger->info('Tweeting this: ' . $status);

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