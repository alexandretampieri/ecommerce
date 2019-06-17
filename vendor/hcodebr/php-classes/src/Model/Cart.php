<?php 

namespace Hcode\Model;

use \Hcode\Model;

use \Hcode\DB\Sql;

use \Hcode\Model\User;


class Cart extends Model {

	const SESSION = "Cart";

    protected $fields = [
		"idcart",
		"dessessionid",
		"iduser",
		"deszipcode",
		"vlfreight",
		"nrdays",
		"dtregister"
	];


	public static function getFromSession()
	{

		$cart = new Cart();

		if (isset($_SESSION[Cart::SESSION]) && (int) $_SESSION[Cart::SESSION]["idcart"] > 0) {

			$cart->get((int) $_SESSION[Cart::SESSION]["idcart"]);

		}

		else {

			$cart->getFromSessionID();

			if ((int) $cart->getidcart() <= 0) {

				$data = [
					"dessessionid"=>session_id()					
				];

				if (User::checkLogin(false)) {

					$user = User::getFromSession();

					$data["iduser"] = $user->getiduser();
				}

				$cart->setData($data);

				$cart->save();

				$cart->setToSession();

			}

		}

		return $cart;

	}

	public function setToSession() {

		$_SESSION[Cart::SESSION] = $this->getValues();

	}

	public function getFromSessionID()
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			":dessessionid"=>session_id()
		]);


		if (count($results) > 0) {

			$this->setData($results[0]);

		}

	}

	public function get(int $idcart)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
			":idcart"=>$idcart
		]);

		if (count($results) > 0) {

			$this->setData($results[0]);

		}

	}

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays);", [
			":idcart"=>$this->getidcart(),
			":dessessionid"=>$this->getdessessionid(),
			":iduser"=>$this->getiduser(),
			":deszipcode"=>$this->getdeszipcode(),
			":vlfreight"=>$this->getvlfreight(),
			":nrdays"=>$this->getnrdays()
		]);

		$this->setData($results[0]);

	}

	public function addProduct(Product $product)
	{

		$sql = new Sql();

var_dump($this);
var_dump($product);



		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct);", [
			"idcart"=>$this->getidcart(),
			"idproduct"=>$product->getidproduct()
		]);

	}

	public function removeProduct(Product $product, $all = false)
	{

		$sql = new Sql();

		if ($all) {

			$sql->query("
				UPDATE tb_cartsproducts 
				SET dtremoved = NOW() 
				WHERE idcart = :idcart AND 
					idproduct = :idproduct AND
					dtremoved IS NULL;", [
				"idcart"=>$this->getidcart(),
				"idproduct"=>$product->getidproduct()
			]);

		}

		else {

			$sql->query("
				UPDATE tb_cartsproducts 
				SET dtremoved = NOW() 
				WHERE idcart = :idcart AND 
					idproduct = :idproduct AND
					dtremoved IS NULL LIMIT 1;", [
				"idcart"=>$this->getidcart(),
				"idproduct"=>$product->getidproduct()
			]);

		}

	}

	public function getProducts()
	{

		$sql = new Sql();

		$rows = $sql->select("
			SELECT tab2.idproduct, tab2.desproduct, tab2.vlprice, tab2.vlwidth, tab2.vlheight, tab2.vllength, tab2.vlweight, tab2.desurl, COUNT(*) as nrqtd, SUM(tab2.vlprice) as vltotal
			FROM tb_cartsproducts tab1
			INNER JOIN tb_products tab2
			ON tab1.idproduct = tab2.idproduct
			WHERE tab1.idcart = :idcart AND
			tab1.dtremoved IS NULL
			GROUP BY tab2.idproduct, tab2.desproduct, tab2.vlprice, tab2.vlwidth, tab2.vlheight, tab2.vllength, tab2.vlweight, tab2.desurl
			ORDER BY tab2.desproduct;", [
				"idcart"=>$this->getidcart()
		]);

		return Product::checkList($rows);
	}

}

?>