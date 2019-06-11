<?php 

namespace Hcode\Model;

use \Hcode\Model;

use \Hcode\DB\Sql;


class Product extends Model {

    protected $fields = [
		"idproduct",
		"desproduct",
		"vlprice", 
		"vlwidth",
		"vlheight",
		"vllength",
		"vlweight",
		"desurl",
		"desphoto"
	];

	public static function listAll() 

	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");

	}

	public static function checkList($list)
	{

		foreach ($list as &$row) {

			$p = new Product;

			$p->setData($row);

			$row = $p->getValues();

		}

		return $list;

	}

	
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", 
			array (
				":idproduct"=>$this->getidproduct(),
				":desproduct"=>$this->getdesproduct(),
				":vlprice"=>$this->getvlprice(),
				":vlwidth"=>$this->getvlwidth(),
				":vlheight"=>$this->getvlheight(),
				":vllength"=>$this->getvllength(),
				":vlweight"=>$this->getvlweight(),
				":desurl"=>$this->getdesurl()
		));

		$this->setData($results[0]);

	}

	public function get($idproduct)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
				":idproduct"=>$idproduct
		));

		$this->setData($results[0]);

	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", 
			array (
				":idproduct"=>$this->getidproduct()
		));

	}

	public function checkPhoto()
	{

		$url = "/res/site/img/products/";

		$filename = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "site" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "products" . DIRECTORY_SEPARATOR . $this->getidproduct() . ".jpg";

		if (file_exists($filename)) {

			$urlPhoto = $url . $this->getidproduct() . ".jpg";
		}

		else {
			
			$urlPhoto = $url . "Product.jpg";
		
		}

		return $this->setdesphoto($urlPhoto);

	}

	public function getValues()
	{

		$this->checkPhoto();

		$values = parent::getValues();

		return $values;

	}

	public function setPhoto($file)
	{

		$filenameTmp = $file["tmp_name"];

		if (file_exists($filenameTmp)) {

			$filename = $file["name"];

			$arrayFile = explode(".", $filename);

			$extension = end($arrayFile);

			switch ($extension) {
				case "jpg":
				case "jpeg":
						$image = imagecreatefromjpeg($filenameTmp);
					break;
				
				case "gif":
						$image = imagecreatefromgif($filenameTmp);
					break;
				
				case "png":
						$image = imagecreatefrompng($filenameTmp);
					break;
				
				default:
					   throw new Exception("Extensão inválida" . $file["name"]);
					   
					break;
			}

			$filename = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "site" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "products" . DIRECTORY_SEPARATOR . $this->getidproduct() . ".jpg";

			imagejpeg($image, $filename);

			imagedestroy($image);

		}
		
		$this->checkPhoto();

	}

}

?>