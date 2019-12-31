<?php

/**
 * includes.php
 * Description:
 *
 */

require_once dirname(__FILE__) . '/../config/config.php';
require_once dirname(__FILE__) . '/../config/credentials.php';

function autoloaddbFramework($className) {
	$filename = dirname(__FILE__) . '/../../dbFramework/controllers/' . $className . ".class.php";
    if (is_readable($filename)) {
		require_once $filename;
    }
}

function autoloadModels($className) {
    $filename = dirname(__FILE__) . '/../models/' . $className . ".model.php";
    if (is_readable($filename)) {
		require_once $filename;
    }
}

function autoloadControllers($className) {
    $filename = dirname(__FILE__) . '/../controllers/' . $className . ".controller.php";
    if (is_readable($filename)) {
		require_once $filename;
    }
}

spl_autoload_register("autoloaddbFramework");
//spl_autoload_register("autoloadModels");
spl_autoload_register("autoloadControllers");
