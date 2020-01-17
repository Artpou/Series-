<?php
require_once("AccountStorage.php");

class AccountStorageMySQL implements AccountStorage
{
    private $bd;

    /**
     * Construit la base de donnée des Utilisateurs
     */
    public function __construct()
    {
        $dsn  = 'mysql:host=mysql.info.unicaen.fr;port=3306;dbname=21700543_dev;charset=utf8';
        $user = '21700543';
        $pass = 'Aexei4EeGhah9ep4';
        $this->bd = new PDO($dsn, $user, $pass);
        $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Lis un Utilisateur dans la BDD
     *
     * @param int $id
     * @return Serie
     */
	public function readAccount($id){
		$stmt = $this->bd->prepare('SELECT * FROM user WHERE login=:id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $rawData = $stmt->fetch();

        if (empty($rawData)) {
            return null;
        }

        $builder = new AccountBuilder($rawData);
        return $builder->create();
	}
    
    /**
     * Supprime un Utilisateur dans la BDD
     *
     * @param int $id
     * @return void
     */
	public function delete($id){
		$stmt = $this->bd->prepare("DELETE FROM user WHERE login=:id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
	}
    
    /**
     * Passe un Utilisateur administrateur
     *
     * @param int $id
     * @return void
     */
	public function grantAdmin($id){
        $stmt = $this->bd->prepare("UPDATE user
                SET status_id=:status_id
                WHERE login=:id");
		$x=2;
        $stmt->bindParam(':status_id', $x);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
    
    /**
     * Lis tous les Utilisateurs dans la BDD
     *
     * @return array
     */
	public function readAllAccounts()
    {
        $stmt = $this->bd->prepare("SELECT * FROM user");
        $stmt->execute();
        $datas = $stmt->fetchAll();

        $allAccounts = array();
        foreach ($datas as $accounts) {
            $builder = new AccountBuilder($accounts);
            $allAccounts[$accounts["login"]] = $builder->create();
        }

        return $allAccounts;
    }

    /**
     * Créer l'utilisateur si le login et le mot de passe existe dans la BDD
     *
     * @param string $login
     * @param string $password
     * @return Account
     */
    public function checkAuth($login, $password)
    {
        $stmt = $this->bd->prepare("SELECT * FROM user WHERE login=:login");
        $stmt->bindParam(':login', $login);
        $stmt->execute();

        $data = $stmt->fetch();

        //return null si le login n'existe pas dans la DB
        // ou si le mot de passe est invalide
        if ($stmt->rowCount() == 0 || !password_verify($password, $data["password"])) {
            return null;
        }

        //récupère le rôle de l'utilisateur (user ou admin)
        $stmt = $this->bd->prepare("SELECT name FROM status WHERE id=:id");
        $stmt->bindParam(':id', $data['status_id']);
        $stmt->execute();

        $status = $stmt->fetch();

        if(empty($status)) {
            return null;
        }

        return new Account(
            $data["login"],
            $data["password"],
            $data["name"],
            $data["email"],
            $status["name"]
        );
    }

    /**
     * Créé un utilisateur dans la BDD
     *
     * @param Account $account
     * @return void
     */
    public function create(Account $account)
    {
        $stmt = $this->bd->prepare("INSERT INTO user VALUES
        (:login, :password, :email, :name, 1)");

        $stmt->bindParam(':login', $account->login);
        $password =  password_hash($account->password, PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $account->email);
        $stmt->bindParam(':name', $account->name);

        $stmt->execute();

        return true;
    }

    /**
     * Retourne si le login existe dans la BDD
     *
     * @param string $login
     * @return bool
     */
    public function doesLoginExist($login)
    {
        $stmt = $this->bd->prepare("SELECT login FROM user WHERE login=:login");
        $stmt->bindParam(':login', $login);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

     /**
     * Retourne si l'email existe dans la BDD
     *
     * @param string $login
     * @return bool
     */   
    public function doesEmailExist($email)
    {
        $stmt = $this->bd->prepare("SELECT * FROM user WHERE email=:email");
        $stmt->bindParam(':login', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
