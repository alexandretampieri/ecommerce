<?php 

use \Hcode\Page;

use \Hcode\Model\Category;

use \Hcode\Model\Product;

use \Hcode\Model\Cart;

use \Hcode\Model\Address;

use \Hcode\Model\User;


$app->get("/", function() {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index",[
		"products"=>Product::checkList($products)
	]);
	
});


$app->get("/categories/:idcategory", function($idcategory) {

	$nrPage = (isset($_GET["page"])) ? (int) $_GET["page"] : 1;

	$category = new Category();

	$category->get((int) $idcategory);

	$pagination = $category->getProductsPage($nrPage);

	$pages = [];

	for ($i = 1; $i <= $pagination["pages"] ; $i++) { 
		
		array_push($pages, [
			"link"=>"/categories/" . $idcategory . "?page=" . $i,
			"page"=>$i
		]);

	}

	$page = new Page();

	$page->setTpl("category", [
		"category"=>$category->getValues(),
		"products"=>$pagination["data"],
		"pages"=>$pages
	]);

});

$app->get("/products/:desurl", function($desurl) {

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		"product"=>$product->getValues(),
		"categories"=>$product->getCategories()
	]);

});

$app->get("/cart", function() {

	$cart = Cart::getFromSession();
	
	$page = new Page();

	$page->setTpl("cart", [
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>Cart::getMsgError()
	]);

});

$app->get("/cart/:idproduct/add", function($idproduct) {

	$product = new Product();

	$product->get((int) $idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET["qtd"])) ? (int) $_GET["qtd"] : 1;

	for ($i = 0; $i < $qtd; $i++) { 

		$cart->addProduct($product);

	}

	header("Location: /cart");

	exit;
	
});

$app->get("/cart/:idproduct/minus", function($idproduct) {

	$product = new Product();

	$product->get((int) $idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");

	exit;
	
});

$app->get("/cart/:idproduct/remove", function($idproduct) {

	$product = new Product();

	$product->get((int) $idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");

	exit;
	
});

$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST["zipcode"]);

	header("Location: /cart");

	exit;
	
});

$app->get("/checkout", function() {

    User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();

	$page->setTpl("checkout",[
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues(),
	]);

});

$app->get("/login", function() {

	$page = new Page();

	$page->setTpl("login", [
		"error"=>User::getError(),
		"errorRegister"=>User::getErrorRegister(),
		"registerValues"=>(isset($_SESSION["registerValues"])) ? $_SESSION["registerValues"] : ["name"=>"", "email"=>"", "phone"=>"", "password"=>""]
	]);

});

$app->post("/login", function() {

	$login = $_POST["login"];

	$password = $_POST["password"];

	try {

		User::login($login, $password);

	} catch (Exception $e) {

		User::setError($e->getMessage());

		header("Location: /login");

		exit;

	}

	header("Location: /checkout");

	exit;

});

$app->get("/logout", function() {

	User::logout();

	header("Location: /login");

	exit;

});

$app->post("/register", function() {

	$_SESSION["registerValues"] = $_POST;

	if (isset($_POST["name"]) && $_POST["name"] != "") {

		$name = $_POST["name"];

	}

	else {

		User::setErrorRegister("Preencha o seu nome.");

		header("Location: /login");

		exit;

	}

	if (isset($_POST["email"]) && $_POST["email"] != "") {

		$email = $_POST["email"];

	}

	else {

		User::setErrorRegister("Preencha o seu e-mail.");

		header("Location: /login");

		exit;

	}

	if (isset($_POST["phone"]) && $_POST["phone"] != "") {

		$phone = $_POST["phone"];

	}

	else {

		$phone = "";

	}

	if (isset($_POST["password"]) && $_POST["password"] != "") {
  
  		$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
    		"cost"=>12
 		]);

 		$passwordText = $_POST["password"];
 
	}

	else {

		User::setErrorRegister("Preencha a senha.");

		header("Location: /login");

		exit;

	}

	if (User::checkLoginExists($email) === true) {

		User::setErrorRegister("Endereço de e-mail já está sendo usado por outro usuário.");

		header("Location: /login");

		exit;

	}

	$user = new User();

	$user->setData([
		"inadmin"=>0,
		"deslogin"=>$email,
		"desperson"=>$name,
		"desemail"=>$email,
		"despassword"=>$password,
		"nrphone"=>$phone
	]);

	$user->save();

	User::login($email, $passwordText);

	header("Location: /checkout");

	exit;

});

?>