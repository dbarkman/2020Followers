<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$fcf = new fetchCandidateFollowers();
$fcf->fetchFollowers();

class fetchCandidateFollowers
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

	public function fetchFollowers()
	{
        $candidates = $this->getCandidates();

        $date = date('c');
        foreach ($candidates as $candidate) {
            $screenName = $candidate['screenName'];
            $params = array(
                'screen_name' => $screenName
            );

            $response = $this->_twitter->getUser($params);
            $responseDecoded = json_decode($response, true);
            $countArray = array();
            if (isset($responseDecoded['errors'])) {
                $this->_logger->info('Cannot retrieve twitter stats: ' . $responseDecoded['errors'][0]['message'] . PHP_EOL);
                $countArray = array(0,0,0,0,0);
                array_push($countArray, 0,0,0,0,0);
            } else {
                $followersCount = $responseDecoded['followers_count'];
                $friendsCount = $responseDecoded['friends_count'];
                $listedCount = $responseDecoded['listed_count'];
                $favouritesCount = $responseDecoded['favourites_count'];
                $statusesCount = $responseDecoded['statuses_count'];
                $count = $followersCount . ',' . $listedCount . ',' . $friendsCount . ',' . $favouritesCount . ',' . $statusesCount;
                $this->_logger->info('Count for ' . $screenName . ': ' . $count . ' - followers, listed, friends, favourites, statuses');
                array_push($countArray, $followersCount, $listedCount, $friendsCount, $favouritesCount, $statusesCount);
            }
            $success = $this->storeFollowers($date, $screenName, $countArray);
            if (!$success) {
                $this->_logger->info('Count database insert failed');
            }
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
				rank
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($rows, $row);
        }
        return $rows;
    }

    public function storeFollowers($date, $screenName, $countArray)
    {
        $sql = "
			INSERT INTO
				counts
			SET
				date = '$date',
				screenName = '$screenName',
				followersCount = '$countArray[0]',
				listedCount = '$countArray[1]',
				friendsCount = '$countArray[2]',
				favouritesCount = '$countArray[3]',
				statusesCount = '$countArray[4]'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}