<?
//echo str_pad("Last Execution Time: " . date('l jS \of F Y h:i:s A'), 100);
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../');

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

require($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

//error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
error_reporting(E_ERROR);

CModule::IncludeModule('main');
CModule::IncludeModule("search");

//$APPLICATION = new CMain();

require('autoloader.php');

@set_time_limit(3000000);
ini_set('max_execution_time', 3000000);


require('Updater.php');

while (ob_get_level()) {
ob_end_flush();
}

$updater = new Updater;
$updater->updateCatalog();