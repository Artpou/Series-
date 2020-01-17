<?php
require_once("model/Series/Series.php");
require_once("model/Series/SeriesStorageMySQL.php");
require_once("model/Series/SeriesBuilder.php");

require_once("model/Account/Account.php");
require_once("model/Account/AccountStorageMySQL.php");
require_once("model/Account/AccountBuilder.php");

require_once("model/AuthenticationManager.php");


class Controller
{
    private $view;
    private $seriesDB;
    private $userDB;
    private $UserManager;

    /**
     * Construit le controller
     *
     * @param View $view
     * @param SeriesStorage $data
     * @param AccountStorage $users
     */
    public function __construct(View $view,SeriesStorage $data,AccountStorage $users)
    {
        $this->view = $view;
        $this->seriesDB = $data;
        $this->userDB = $users;
        $this->UserManager = new AuthenticationManager($users);
    }

    /**
     * Affiche les informations de la série
     * 
     * @param int id : id de la série
     * @return void
     */
    public function showInformation($id)
    {
        $series = $this->seriesDB->readSeries($id);
        if (isset($series)) {
            $this->view->makeSeriesPage($series);
        } else {
            $this->view->makeUnknownSeriesPage();
        }
        $this->view->render();
    }

    /**
     * Connecte un utilisateur
     *
     * @param array $data : donnée de l'utilisateur
     * @return bool : l'utilisateur est connecté
     */
    public function connectUser(array $data)
    {
        return $this->UserManager->connectUser(
            htmlspecialchars($data["login"]),
            htmlspecialchars($data["password"])
        );
    }

    /**
     * Affiche la page de connection
     *
     * @param array $error : les erreurs
     * @return void
     */
    public function showConnectionPage($error = false)
    {
        $this->view->makeConnectionPage($error);
        $this->view->render();
    }

    /**
     * Déconnecte l'utilisateur
     *
     * @return void
     */
    public function disconnectUser()
    {
        $this->UserManager->disconnectedUser();
    }

    /**
     * Affiche la liste des séries
     *
     * @param array $filter
     * @return void
     */
    public function showList($filter = null)
    {
        $allSeries = $this->seriesDB->readAllSeries($filter);

        if ($this->UserManager->isAdminConnected()) {
            $this->view->makeListPage($allSeries, $allSeries);
        } else if ($this->UserManager->isUserConnected()) {
            $userSeries = $this->seriesDB->readUserSeries($this->UserManager->getLogin());
            $this->view->makeListPage($allSeries, $userSeries);
        } else {
            $this->view->makeListPage($allSeries);
        }
        $this->view->render();
    }

    /**
     * Affiche la liste des comptes
     *
     * @return void
     */
    public function showAccountsList()
    {
        $allAccounts = $this->userDB->readAllAccounts();
        $this->view->makeAccountsList($allAccounts);
    }

    /**
     * Sauvegarde une nouvelle série
     *
     * @param array $data : les données entrées par l'utilisateur
     * @param array $img : l'image entrée par l'utilisateur
     * @param [type] $error : les erreurs générées
     * @return int id : l'id de la série créée
     */
    public function saveNewSeries(array $data,array $img, &$error)
    {
        //upload l'image et renvoit false si il y a une erreur
        $data['image'] = $this->uploadImage($img, $data["name"], $error);
        if (!isset($data["image"])) {
            return false;
        }
        //upload le synopsis et renvoit false si il y a une erreur
        $data['synopsis'] = $this->uploadSynopsis($data["synopsis"], $data["name"], $error);
        if (!isset($data["synopsis"])) {
            return false;
        }

        //creer la série et renvoit false si il y a des erreurs
        $builder = new SeriesBuilder($data, $this->seriesDB);
        if (!$builder->isValid()) {
            $error = $builder->getError();
            return false;
        }
        $series = $builder->create();

        $id = $this->seriesDB->create($series, $this->UserManager->getLogin());
        return $id;
    }

    /**
     * Créé un nouvelle utilisateur
     *
     * @param array $data : les données entrées par l'utilisateur
     * @param array $error : les erreurs générées
     * @return bool : l'utilisatuer a été créé
     */
    public function createUser(array $data, &$error)
    {
        $builder = new AccountBuilder($data, $this->userDB);
        if ($builder->isValid()) {
            $this->userDB->create($builder->create());
            return true;
        } else {
            $error = $builder->getErrors();
        }
        return false;
    }

    /**
     * Supprimer une série
     *
     * @param int $id : l'id de la série
     * @return bool : la série a été supprimée
     */
    public function deleteSeries($id)
    {
        $series = $this->seriesDB->readSeries($id);

        //la série n'existe pas
        if (!isset($series)) {
            return false;
        }

        //la série n'a pas était créé par l'user
        if (
            !$this->UserManager->isAdminConnected()
            && $series->login_creator != $this->UserManager->getLogin()
        ) {
            return false;
        }

        $this->seriesDB->delete($id);
        $this->deleteImage($series->name);
        $this->deleteSynopsis($series->name);
        return true;
    }

    /**
     * Passe administrateur un utilisateur
     *
     * @param int $id : id de l'utilisateur
     * @return bool : l'utilisateur est passé admin
     */
    public function grantAdmin($id)
    {
        $account = $this->userDB->readAccount($id);
        if (!isset($account)) {
            return false;
        }

        if($account->status==2) return false;
        //Seul un admin peu upgrade son compte
        if (!$this->UserManager->isAdminConnected()) return false;

        $this->userDB->grantAdmin($id);
        return true;
    }

    /**
     * Supprime un utilisateur
     *
     * @param int $id : l'id de l'utilisateur
     * @return bool : l'utilisateur a été supprimé
     */
    public function deleteAccount($id)
    {
        $account = $this->userDB->readAccount($id);
        if (!isset($account)) {
            return false;
        }
        //Seul un admin peu Delete son compte
        if (!$this->UserManager->isAdminConnected()) {
            return false;
        }

        $this->userDB->delete($id);
        return true;
    }

    /**
     * Affiche la page de modification d'une série
     *
     * @param int $id : l'id de la série
     * @return bool : la série existe
     */
    public function showModificationPage($id)
    {
        $series = $this->seriesDB->readSeries($id);

        //la série n'existe pas
        if (!isset($series)) {
            return false;
        }

        $synopsis = file_exists($series->synopsis) ? file_get_contents($series->synopsis) : "";

        $series = $this->seriesDB->readSeries($id);
        $this->view->makeModifyPage($id,$series,$synopsis);
        $this->view->render();
        return true;
    }

    /**
     * Modifie une série
     *
     * @param  int $id : id de la série
     * @param array $data : les données a modifier
     * @param array $error : les erreurs générées
     * @return bool : la série a été modifiée
     */
    public function modifySeries($id, array $data, &$error)
    {
        $series = $this->seriesDB->readSeries($id);

        //la série n'existe pas
        if (!isset($series)) {
            return false;
        }


        //la série n'a pas était créé par l'user et n'est pas un admin
        if (
            !$this->UserManager->isAdminConnected()
            && $series->login_creator != $this->UserManager->getLogin()
        ) {
            return false;
        }

        //upload le synopsis et renvois false si il y a des erreurs
        $data["synopsis"] = $this->uploadSynopsis($data["synopsis"], $series->name, $error);
        if(!isset($data["synopsis"])) {
            return false;
        }

        $builder = new SeriesBuilder($data);
        
        //renvoit false si il y a des erreurs
        if (!$builder->isValid()) {
            throw new Exception("valid", 1);

            $error = $builder->getError();
            return false;
        }

        //creation la serie modifié
        $newSeries = $builder->create();

        //update dans la base de données
        $this->seriesDB->update($id, $newSeries);
        return true;
    }

    /**
     * Upload une image sur le serveur
     *
     * @param array $img : l'image entrée par l'utilisatuer
     * @param string $name : le nom de la série
     * @param array $error : les erreurs générées
     * @return bool : l'image a été uploadée
     */
    private function uploadImage(array $img, $name, &$error)
    {
        //permet de remplacer les caractères interdit en HTML5 par des "_"
        $pattern = '/ /';
        $name = preg_replace($pattern, "_", $name); 

        //aucun upload nécessaire car pas d'image entrée
        if (empty($img['tmp_name']))
            return "";

        $dir = 'src/view/Image';
        $tmp_img = $img['tmp_name'];

        $allowed_ext = array("jpg", "jpeg", "png", "gif");
        $ext_img = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));

        if (!in_array($ext_img, $allowed_ext)) {
            $error["errorImg"] =
                "L'extension n'est pas autorisé (extension autorisé : " . implode(", ", $allowed_ext) . ")";
            return null;
        }

        if (!move_uploaded_file($tmp_img, "$dir/$name.$ext_img")) {
            $error["errorImg"] = "L'upload à échoué !";
            return null;
        }

        return "$dir/$name";
    }

    /**
     * Supprime une image
     *
     * @param string $name : nom de l'image
     * @return void
     */
    private function deleteImage($name)
    {
        $pattern = '/ /';
        $name = preg_replace($pattern, "_", $name); 

        $path = "src/view/Image";
        $dir = glob("$path/$name.*");
        foreach ($dir as $image) {
            if (file_exists("$path$image")) {
                unlink("$path$image");
            }
        }
    }

    /**
     * Upload un synopsis
     *
     * @param string $synopsis : le synopsis de la série
     * @param string $name : le nom de la série
     * @param array $error : les erreurs générée
     * @return bool : la série a été supprimée
     */
    private function uploadSynopsis($synopsis, $name, &$error)
    {
        //le synopsis est vide
        if(!isset($synopsis) || empty($synopsis)) {
            return "";
        }

        if (strlen($synopsis) > 10000) {
            $error["errorSynopsis"] = "Synopsis trop long (10 000 caractères max) !";
            return null;
        }

        $dir = 'src/view/Synopsis';

        if (!$file = fopen("$dir/$name.html", "w+")) {
            $error["errorSynopsis"] = "L'ajout du synopsis à échoué !";
            return null;
        }
        if (!fwrite($file, $synopsis)) {
            $error["errorSynopsis"] = "Le synopsis n'est pas valide !";
            return null;
        }
        fclose($file);
        return "$dir/$name.html";
    }

    /**
     * Supprime un synopsis
     *
     * @param string $name : le nom du synopsis
     * @return void
     */
    private function deleteSynopsis($name)
    {
        $path = "src/view/Synopsis/$name.html";
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
