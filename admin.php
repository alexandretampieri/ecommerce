<?php

use \Hcode\PageAdmin;

use \Hcode\Model\User;

use \Hcode\Model\Product;


$app->get("/admin", function() {
    
    User::VerifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");
	
});

$app->get("/admin/login", function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("login");

});

$app->get("/admin/logout", function() {

	User::logout();

	header("Location: /admin/login");

	exit;

});

$app->post("/admin/login", function() {

	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");

	exit;

});

?>