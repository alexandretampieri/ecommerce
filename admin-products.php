<?php

use \Hcode\PageAdmin;

use \Hcode\Model\User;

use \Hcode\Model\Product;


$app->get("/admin/products", function() {

	User::VerifyLogin();

	$products = Product::listAll();

	$page = new PageAdmin();

	$page->setTpl("products", [
		"products"=>$products
	]);

});

$app->get("/admin/products/create", function() {

	User::VerifyLogin();

	$products = Product::listAll();

	$page = new PageAdmin();

	$page->setTpl("products-create");

});

$app->post("/admin/products/create", function() {

	User::VerifyLogin();

	$product = new Product();

	$product->setData($_POST);

	$product->save();

	header("Location: /admin/products");

	exit;

});

$app->get("/admin/products/:idProduct/delete", function($idProduct) {

	User::VerifyLogin();

	$product = new Product();

	$product->get((int) $idProduct);

	$product->delete();

	header("Location: /admin/products");

	exit;

});

$app->get("/admin/products/:idProduct", function($idProduct) {

	User::VerifyLogin();

	$product = new Product();

	$product->get((int) $idProduct);

	$page = new PageAdmin();

	$page->setTpl("products-update", array(
		"product"=>$product->getValues()
	));

});

$app->post("/admin/products/:idProduct", function($idProduct) {

	User::VerifyLogin();

	$product = new Product();

	$product->get((int) $idProduct);

	$product->setData($_POST);

	$photo = $_FILES["file"];

	$product->save();

	$product->setPhoto($photo);

	header("Location: /admin/products");

	exit;

});

?>