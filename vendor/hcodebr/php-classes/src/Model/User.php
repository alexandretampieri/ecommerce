<?php 

namespace Hcode\Model;

use \Hcode\Model;

use \Hcode\DB\Sql;

use \Hcode\Mailer;


class User extends Model {

	const SESSION = "User";

	const SECRET = "HcodePHP7_Secret";

    const METODO_CIPHER = "AES-128-CBC";

    const MENSAGEM_USUARIO = "Usuário inexistente ou senha inválida.";

    const MENSAGEM_SENHA = "Não foi possível recuperar sua senha.";

	const ERROR = "UserError";

	const ERROR_REGISTER = "UserErrorRegister";

    protected $fields = [
		"iduser", 
		"desperson", 
		"deslogin", 
		"despassword", 
		"desemail",
		"nrphone",
		"inadmin", 
		"dtregister"
	];

	public static function getFromSession()
	{

		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]["iduser"] > 0) {

			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;

	}

	public static function checkLogin($inadmin = true)
	{

		if (! isset($_SESSION[User::SESSION])             || 
			! $_SESSION[User::SESSION]                    ||
			! (int) $_SESSION[User::SESSION]["iduser"] > 0) {

			//Usuário não está logado

			return false;

		}

		else {

			if ($inadmin === true && (bool) $_SESSION[User::SESSION]["inadmin"] === true)	{

				return true;

			}		

			else if ($inadmin === false) {
		
					return true;

				}

				else {

					return false;

				}

		}

	}

	public static function login($login, $password):User
	{

		$db = new Sql();

		$results = $db->select("
			SELECT * FROM tb_users
			WHERE deslogin = :LOGIN", 
			array(
				":LOGIN"=>$login
		));

		if (count($results) === 0) {

			throw new \Exception(User::MENSAGEM_USUARIO);

		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"])) {

			$user = new User;

			$user->get($data["iduser"]);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {

			throw new \Exception(User::MENSAGEM_USUARIO);

		}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

	public static function verifyLogin($inadmin = true)
	{
 
		if (! User::checkLogin($inadmin)) {
			
			if ($inadmin) {

				header("Location: /admin/login");

			}
			
			else {

				header("Location: /login");

			}

		exit;

		}

	}

	public static function listAll() 

	{

		$sql = new Sql();

		return $sql->select("
			SELECT * FROM tb_users tab1
			INNER JOIN tb_persons tab2 
			USING(idperson) 
			ORDER BY tab2.desperson"
		);

	}

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array (
				":desperson"=>$this->getdesperson(),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>$this->getdespassword(),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function get($iduser)
	{

		$sql = new Sql();

		$results = $sql->select(
			"SELECT * FROM tb_users tab1 
			INNER JOIN tb_persons tab2 
			USING (idperson) 
			WHERE tab1.iduser = :iduser", 
			array(
				":iduser"=>$iduser
		));

		$data = $results[0];

//		$data["desperson"] = utf8_decode($data["desperson"]);

		$this->setData($data);

	}

	public function update()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
			array (
				":iduser"=>$this->getiduser(),
				":desperson"=>$this->getdesperson(),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>$this->getdespassword(),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", 
			array (
				":iduser"=>$this->getiduser()
		));

	}

	public static function getForgot($email)
	{

		$sql = new Sql();

		$results = $sql->select(
			"SELECT * FROM tb_users tab1 
			INNER JOIN tb_persons tab2 
			USING(idperson) 
			WHERE tab2.desemail = :email", 
			array(
				":email"=>$email
		));

		if (count($results) === 0) {

			throw new \Exception(User::MENSAGEM_SENHA);
			
		}

		else {

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", 
				array(
					":iduser"=>$data["iduser"],
					":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($results2) === 0) {

				throw new \Exception(MENSAGEM_SENHA);
			
			}

			else {

				$dataRecovery = $results2[0];

		        $secret4 = pack("a16", "senha");

    	        $secret = pack("a16", "senha");

				$code = base64_encode(openssl_encrypt(
					$dataRecovery["idrecovery"], 
					User::METODO_CIPHER,
					$secret,
					0,
					$secret4
				));

				$link = "http://www.ecommerce.com.br/admin/forgot/reset?code=$code";

				$mailer = new Mailer(
					$data["desemail"], 
					$data["desperson"], 
					"Redefinição da Senha da Hcode Store",
					"forgot",
					array(
						"name"=>$data["desperson"],
						"link"=>$link
					)
				);

				$mailer->send();

				return $data;

			}
			
		}

	}

	public static function validForgotDecrypt($code)
	{

        $secret4 = pack("a16", "senha");

        $secret = pack("a16", "senha");

		$idrecovery = openssl_decrypt(
			base64_decode($code), 
			User::METODO_CIPHER,
			$secret,
			0,
			$secret4
		);

		$sql = new Sql();

		$results = $sql->select("
			SELECT * FROM tb_userspasswordsrecoveries tab1
			INNER JOIN tb_users tab2 USING(iduser)
			INNER JOIN tb_persons tab3 USING(idperson)
			WHERE tab1.idrecovery = :idrecovery AND
				tab1.dtrecovery IS NULL AND
				DATE_ADD(tab1.dtregister, INTERVAL 1 HOUR) >= NOW();",
			array(
				":idrecovery"=>$idrecovery

		));

		if (count($results) === 0) 
		{

			throw new \Exception(User::MENSAGEM_SENHA);
			
		}

		else
		{

			return $results[0];

		}

	}

	public static function setForgotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array (
			":idrecovery"=>$idrecovery
		));

	}

	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array (
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));

	}

	public static function setError($msg)
	{

		$_SESSION[User::ERROR] = $msg;

	}

	public static function getError()
	{

		if (isset($_SESSION[User::ERROR])) {

			$msg = $_SESSION[User::ERROR];
		
		}

		else {

			$msg = "";
		
		}

		User::clearError();

		return $msg;
		
	}

	public static function clearError()
	{

		$_SESSION[User::ERROR] = NULL;
		
	}

	public static function getPasswordHash($password)
	{

        $passwordHash = password_hash($password, PASSWORD_DEFAULT, [
			"cost"=>12
		]);

		return $passwordHash;

	}

	public static function setErrorRegister($msg)
	{

		$_SESSION[User::ERROR_REGISTER] = $msg;

	}

	public static function getErrorRegister()
	{

		if (isset($_SESSION[User::ERROR_REGISTER])) {

			$msg = $_SESSION[User::ERROR_REGISTER];
		
		}

		else {

			$msg = "";
		
		}

		User::clearErrorRegister();

		return $msg;
		
	}

	public static function clearErrorRegister()
	{

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}

	public static function checkLoginExists($login)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT COUNT(*) FROM tb_users WHERE deslogin = :deslogin", [
				":deslogin"=>$login
		]);

		return (count($results) > 0);

	}

}

?>