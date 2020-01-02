<?php

/**
 * Container.controller.php
 * Description:
 *
 */

class Container
{
	static protected $shared = array();

	private $_logFile;
	private $_logLevel;
	private $_properties;

	public function __construct()
	{
		$this->_properties = new FollowersProperties();
		$this->_logFile = $this->_properties->getLogFile();
		$this->_logLevel = $this->_properties->getLogLevel();
	}

	public function getProperties()
	{
		if (isset(self::$shared['properties'])) {
			return self::$shared['properties'];
		}

		return self::$shared['properties'] = $this->_properties;
	}

	public function getLogger()
	{
		if (isset(self::$shared['logger'])) {
			return self::$shared['logger'];
		}

		$logger = new Logger($this->_logLevel, $this->_logFile);

		return self::$shared['logger'] = $logger;
	}

	public function getMySQLDBConnect()
	{
		if (isset(self::$shared['mysqlDBConnect'])) {
			return self::$shared['mysqlDBConnect'];
		}

		global $followersDBLogin;
		$mysqlDBConnect = new MySQLConnect($followersDBLogin['server'], $followersDBLogin['username'], $followersDBLogin['password'], $followersDBLogin['database']);
		$mysqlConnection = $mysqlDBConnect->db;

		return self::$shared['mysqlDBConnect'] = $mysqlConnection;
	}
}
