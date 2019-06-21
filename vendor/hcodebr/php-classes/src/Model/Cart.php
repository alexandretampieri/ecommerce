<?php 

namespace Hcode\Model;

use \Hcode\Model;

use \Hcode\DB\Sql;

use \Hcode\Model\User;


class Cart extends Model {

	const SESSION = "Cart";

	const SESSION_ERROR = "CartError";

    protected $fields = [
		"idcart",
		"dessessionid",
		"iduser",
		"deszipcode",
		"vlfreight",
		"nrdays",
		"dtregister",
		"vlsubtotal",
		"vltotal",
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

		if ($cart->getvlsubtotal() === NULL) {

			$cart->setvlsubtotal(0);

			$cart->setvltotal(0);

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

		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct);", [
			"idcart"=>$this->getidcart(),
			"idproduct"=>$product->getidproduct()
		]);

		$this->getCalculateTotal();

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

		$this->getCalculateTotal();

	}

	public function getProducts()
	{

		$sql = new Sql();

		$rows = $sql->select("
			SELECT tab2.idproduct, tab2.desproduct, tab2.vlprice, tab2.vlwidth, tab2.vlheight, tab2.vllength, tab2.vlweight, tab2.desurl, COUNT(*) AS nrqtd, SUM(tab2.vlprice) AS vltotal
			FROM tb_cartsproducts tab1
			INNER JOIN tb_products tab2
			ON tab1.idproduct = tab2.idproduct
			WHERE tab1.idcart = :idcart AND
			tab1.dtremoved IS NULL
			GROUP BY tab2.idproduct, tab2.desproduct, tab2.vlprice, tab2.vlwidth, tab2.vlheight, tab2.vllength, tab2.vlweight, tab2.desurl
			ORDER BY tab2.desproduct;", [
				"idcart"=>$this->getidcart()
		]);

		if (count($rows) > 0) {

			return Product::checkList($rows);

		}

		else {

			return [];

		}

	}

	public function getProductsTotals()
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT SUM(tab1.vlprice) AS vlprice, SUM(tab1.vlwidth) AS vlwidth, SUM(tab1.vlheight) AS vlheight, SUM(tab1.vllength) AS vllength, SUM(tab1.vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products tab1
			INNER JOIN tb_cartsproducts tab2
			ON tab1.idproduct = tab2.idproduct
			WHERE tab2.idcart = :idcart AND
			tab2.dtremoved IS NULL;", [
				"idcart"=>$this->getidcart()
		]);

		if (count($results) > 0) {

			return $results[0];

		}

		else {

			return [];

		}

	}

	public function setFreight($nrzipcode)
	{

		$nrzipcode = str_replace("-", "", $nrzipcode);

		$totals = $this->getProductsTotals();

		if ($totals["nrqtd"] > 0) {

			if ($totals["vlheight"] < 2) $totals["vlheight"] = 2;

			if ($totals["vllength"] < 16) $totals["vllength"] = 16;

			$qs = http_build_query([
				"nCdEmpresa"=>"",
				"sDsSenha"=>"",
				"nCdServico"=>"04014",
				"sCepOrigem"=>"09853120",
				"sCepDestino"=>$nrzipcode,
				"nVlPeso"=>$totals["vlweight"],
				"nCdFormato"=>1,
				"nVlComprimento"=>$totals["vllength"],
				"nVlAltura"=>$totals["vlheight"],
				"nVlLargura"=>$totals["vlwidth"],
				"nVlDiametro"=>"0",
				"sCdMaoPropria"=>"S",
				"nVlValorDeclarado"=>$totals["vlprice"],
				"sCdAvisoRecebimento"=>"S"
			]);

			$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" . $qs;

			$xml = simplexml_load_file($url);

			$result = (array) $xml->Servicos->cServico;

			if ($result["MsgErro"] != "") {

				Cart::setMsgError($result["MsgErro"]);
				
			}

			else {

				Cart::clearMsgError();

			}

			$this->setnrdays($result["PrazoEntrega"]);

			$this->setvlfreight(Cart::formatValueToDecimal($result["Valor"]));

			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;

		}

		else {

			$this->setdeszipcode("");

			$this->setnrdays(0);

			$this->setvlfreight(0);

			$this->save();

		}

	}

	public static function formatValueToDecimal($value):float
	{

		$value = str_replace(".", "", $value);

		return str_replace(",", ".", $value);

	}

	public static function setMsgError($msg)
	{

		$_SESSION[Cart::SESSION_ERROR] = $msg;

	}

	public static function getMsgError()
	{

		if (isset($_SESSION[Cart::SESSION_ERROR])) {

			$msg = $_SESSION[Cart::SESSION_ERROR];
		
		}

		else {

			$msg = "";
		
		}

		Cart::clearMsgError();

		return $msg;
		
	}

	public static function clearMsgError()
	{

		$_SESSION[Cart::SESSION_ERROR] = NULL;
		
	}

	public function updateFreight()
	{

		if ($this->getdeszipcode() != "") {

			$this->setFreight($this->getdeszipcode());

		}

	}

	public function getValues()
	{
		$this->getCalculateTotal();

		return parent::getValues();

	}

	public function getCalculateTotal()
	{

		$this->updateFreight();

		$totals = $this->getProductsTotals();

		if ($totals["nrqtd"] != 0) {

			$vlsubtotal = $totals["vlprice"];
			
			$vltotal = $vlsubtotal + $this->getvlfreight();

		}

		else {

			$vlsubtotal = 0;
			
			$vltotal = 0;

			$this->setvlfreight(0);

		}

		$this->setvlsubtotal($vlsubtotal);;
			
		$this->setvltotal($vltotal);

	}

}

?>