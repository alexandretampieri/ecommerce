<?php 

namespace Hcode\Model;

use \Hcode\Model;

use \Hcode\DB\Sql;


class Category extends Model {

    protected $fields = [
		"idcategory", 
		"descategory", 
		"dtregister"
	];

	public static function listAll() 

	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");

	}

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array (
				":idcategory"=>$this->getidcategory(),
				":descategory"=>$this->getdescategory()
		));

		$this->setData($results[0]);

		Category::updateFile();


	}

	public function get($idcategory)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
				":idcategory"=>$idcategory
		));

		$this->setData($results[0]);

	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", 
			array (
				":idcategory"=>$this->getidcategory()
		));

		Category::updateFile();

	}

	public static function updateFile()
	{

		$categories = Category::listAll();

		$html = [];

		foreach ($categories as $row) {

			array_push($html, '<li><a href="/categories/' . $row["idcategory"] . '">' . $row["descategory"] . '</a></li>');
		
		}

		file_put_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR ."views" . DIRECTORY_SEPARATOR . "categories-menu.html", 
		implode("", $html));
	}

	public function getProducts($related = true)
	{

		$sql = new Sql();

		if ($related) {

			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct IN (
					SELECT tab1.idproduct
					FROM tb_products tab1 INNER JOIN
						tb_productscategories tab2 ON tab1.idproduct = tab2.idproduct
					WHERE tab2.idcategory = :idcategory);", 
				[
					":idcategory"=>$this->getidcategory()
			]);

		}

		else {

			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct NOT IN (
					SELECT tab1.idproduct
					FROM tb_products tab1 INNER JOIN
						tb_productscategories tab2 ON tab1.idproduct = tab2.idproduct
					WHERE tab2.idcategory = :idcategory);", 
				[
					":idcategory"=>$this->getidcategory()
			]);

		}

	}

	public function addProduct(Product $product)
	{

		$sql = new SQL;

		$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct);", [
			":idcategory"=>$this->getidcategory(),
			":idproduct"=>$product->getidproduct()
		]);

	}

	public function removeProduct(Product $product)
	{

		$sql = new SQL;


		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct;", [
			":idcategory"=>$this->getidcategory(),
			":idproduct"=>$product->getidproduct()
		]);

	}

}

?>