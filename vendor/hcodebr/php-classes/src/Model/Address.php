<?php 

namespace Hcode\Model;

use \Hcode\Model;

use \Hcode\DB\Sql;


class Address extends Model {

    protected $fields = [
		"idaddress",
		"idperson",
		"desaddress",
		"descomplement",
		"descity",
		"desstate",
		"descountry",
		"nrzipcode",
		"dtregister"
	];

}

?>