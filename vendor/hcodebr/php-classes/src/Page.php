<?php

namespace Hcode;

use Rain\Tpl;

class Page
{

	private $tpl;

	private $options = [];

	private $defaults = [
		"header"=>true,
		"footer"=>true,
		"data"=>[]
	];

	public function __construct($opts = array()) 
	{

		$this->options = array_merge($this->defaults, $opts);

		$config = array(
	    	"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . "/views/",
	    	"cache_dir"     => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/",
	    	"debug"         => false // set to false to improve the speed
		);

		Tpl::configure($config);

		$this->tpl = new Tpl;

		$this->setData($this->options["data"]);

		$this->tpl->draw("header");

	}

	
	private function setData($data = array()) 
	{

		foreach($data as $key => $val)
		{

			$this->tpl->assign($key, $val);

		}

	}


	public function setTpl($tplname, $data = array(), $returnHTML = false)
	{

		$this->setData($data);

		return $this->tpl->draw($tplname, $returnHTML);

	}


	public function __destruct() 
	{

		$this->tpl->draw("footer");

	}

}

?>