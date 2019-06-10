<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;

use \Hcode\Page;


$app = new Slim();

$app->config("debug", true);

$app->get("/", function() {
    
	$page = new Page();

	$page->setTpl("index");
	
});

require_once("admin.php");

require_once("admin-users.php");

require_once("admin-login.php");

require_once("admin-categories.php");

require_once("admin-products.php");

$app->run();

?>