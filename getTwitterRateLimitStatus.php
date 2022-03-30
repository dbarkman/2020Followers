<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if ($argv[1] == null) {
    exit('all clean' . PHP_EOL);
}

$gtrls = new getTwitterRateLimitStatus();
$gtrls->getRateLimitStatus($argv[1]);

class getTwitterRateLimitStatus
{
    private $_logger;

    public function __construct()
    {
        $container = new Container();

        $this->_logger = $container->getLogger();
    }

    public function getRateLimitStatus($params)
    {
        global $twitterCreds;

        $params = array(
            'screen_name' => $params
        );

        $twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
        $status = json_decode($twitter->getRateLimitStatus($params));
        var_dump($status);
    }
}
