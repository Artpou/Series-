<?php

class AccountBuilder
{
    private $isValid;
    private $error;
  
    public $LOGIN_REF;
    public $PASSWORD_REF;
    public $NAME_REF;
    public $EMAIL_REF;
	public $STATUS_REF='user';

    /**
     * Construit un nouvelle Utilisateur en fonction des données passées en paramètre
     * Des erreurs sont générées si les données ne sont pas correct
     *
     * @param array $data : les données a utiliser
     * @param AccountStorageMySQL $bd : la BDD des utilisateurs
     */
    function __construct($data,AccountStorageMySQL $bd=null)
    {
        $this->data = $data;
        $this->bd   = $bd;

        $this->isValid = true;
		if(isset($bd)){
			if ($bd->doesLoginExist($data["login"])) { 
				$this->error["errorLogin"] = "Ce pseudo existe déjà !";
				$this->isValid = false;
			} else {
				$this->LOGIN_REF = $data["login"];
			}

			if ($data["password"] != $data["passwordConfirm"]) { 
				$this->error["errorPassword"] = "Les 2 mots de passe doivent correspondre !";
				$this->isValid = false;
			} else if (strlen ($data["password"]) < 8) {
				$this->error["errorPassword"] = "Le mot de passe doit faire au moins 8 caractères !";
				$this->isValid = false;
			} else {
				$this->PASSWORD_REF = $data["password"];
			}

			if ($bd->doesLoginExist($data["email"])) { 
				$this->error["errorEmail"] = "Cet email possède déjà un compte !";
				$this->isValid = false;
			} else {
				$this->EMAIL_REF = $data["email"];
			}
        	$this->NAME_REF = $data["name"];
		}else{
			$this->LOGIN_REF = $data["login"];
			$this->PASSWORD_REF = $data["password"];
			$this->EMAIL_REF = $data["email"];
			$this->NAME_REF = $data["name"];
			$this->STATUS_REF=$data["status_id"];
		}
    }

    /**
     * @return boolean : le Builder est valide
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * Créée l'Utilisateur
     *
     * @return Account
     */
    public function create()
    {
        if(!$this->isValid) {
            throw new Exception("Error are present in fields of AccountBuilder", 1);  
        }
        return new Account(
            $this->LOGIN_REF,
            $this->PASSWORD_REF,
            $this->NAME_REF,
            $this->EMAIL_REF,
			$this->STATUS_REF
        );
    }

    /**
     * retourne les erreurs générées
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->error;
    }

    /**
     * Retourne le login de l'utilisateur
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->LOGIN_REF;
    }
}
