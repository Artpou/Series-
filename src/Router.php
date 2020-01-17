<?php

require_once("view/View.php");
require_once("view/PrivateView.php");
require_once("view/AdminView.php");
require_once("control/Controller.php");
require_once("model/AuthenticationManager.php");

class Router
{
	private $view;
	private $controller;

	public $accountsManager;

	/**
	 * Créé le routeur
	 *
	 * @param SeriesStorage $series : la BDD de toutes les séries
	 * @param AccountStorage $accounts : la BDD de tous les utilisateurs
	 */
	public function __construct($series, $accounts)
	{
		$this->accountsManager = new AuthenticationManager($accounts);
		$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
		$_SESSION['feedback'] = null;
		if($this->accountsManager->isUserConnected()){
			if ($this->accountsManager->isAdminConnected()) {
				$this->view = new AdminView($this, $feedback);
			} else {
				$this->view = new PrivateView($this, $feedback);
			}
		}else{
			$this->view = new View($this, $feedback);
		}
		$this->controller = new Controller($this->view, $series, $accounts);
		$this->view->controller = $this->controller;
	}

	/**
	 * La fonction principal du Router
	 * Cette fonction est appelé pour rediriger l'utilisateur en fonction du _GET de l'url
	 * Le Router peut alors afficher une page à partir de la vue ou appelé le Controller pour 
	 * effectuer un traitement spécifique
	 *
	 * @return void
	 */
	public function main()
	{
		$IsUser = $this->accountsManager->isUserConnected();
		$IsAdmin = $this->accountsManager->isAdminConnected();

		if (isset($_GET["action"])) {
			switch ($_GET["action"]) {
				case 'connection':
					if ($this->controller->connectUser($_POST)) {
						//renvoi vers la page d'accueil
						$this->POSTredirect($this->getHomeUrl(), "Vous êtes connecté !");
					} else {
						//renvoi sur la page de connection avec une erreur
						$this->POSTredirect($this->getConnectionURL(), "Pseudo ou Mot de passe invalide !");
					}
					break;

				case 'accountCreation':
					//tableau qui va etre rempli des erreurs produites par createUser
					$error = array();
					if ($this->controller->createUser($_POST, $error)) {
						//renvoi vers la page d'accueil
						$this->POSTredirect($this->getHomeUrl(), "Vous pouvez désormais vous connecter !");
					} else {
						//renvoi sur la page de création de compte avec toutes les erreurs
						$this->POSTredirect($this->getAccountCreationURL(), array_merge ($_POST,$error));
					}
					break;

				case 'disconnection':
					if ($IsUser) {
						$this->controller->disconnectUser();
						$this->POSTredirect($this->getHomeUrl(), "Vous êtes déconnecté !");
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;

				case 'save':
					if ($IsUser) {
						$this->view->makeSeriesCreationPage();
						$this->view->render();
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;

				case 'saveValidation':
					if ($IsUser) {
						$error = array();
						
						if ($id = $this->controller->saveNewSeries($_POST, $_FILES['image'], $error)) {
							//renvoi vers la page de la série créée
							$this->POSTredirect($this->getSeriesURL($id));
						} else {
							//renvoi vers la page de création avec les erreurs
							$this->POSTredirect($this->getSeriesCreationURL(), array_merge($_POST, $error));
						}
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;

				case 'update':
					if ($IsUser) {
						if (isset($_POST["action"]) && $_POST["action"] == "delete") {
							$this->POSTredirect($this->getDeleteURL($_POST["id"]));
						} else if (isset($_POST["action"]) && $_POST["action"] == "modify") {
							$this->POSTredirect($this->getModifyURL($_POST["id"]));
						} else if (isset($_POST["action"]) && $_POST["action"] == "deleteAccount") {
							$this->POSTredirect($this->getDeleteAccountURL($_POST["id"]));
						} else if (isset($_POST["action"]) && $_POST["action"] == "grantAdmin") {
							$this->POSTredirect($this->getGrantAdminURL($_POST["id"]));
						} else {
							$this->view->makeUnknownPage();
							$this->view->render();
						}
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;
				
				case 'delete':
					if ($IsUser) {
						if(isset($_GET["id"])) {
							if($this->controller->deleteSeries($_GET["id"])) {
								//renvoi vers la liste
								$this->POSTredirect($this->getListURL(), "La série a bien été supprimé !");
							} else {
								//l'utilisateur n'a pas les droits de supprimer cette série
								$this->POSTredirect($this->getNotAllowedURL());
							}
						} else {
							$this->view->makeUnknownPage();
							$this->view->render();	
						}
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;
					
				case 'grantAdmin':
					if ($IsAdmin) {
						if(isset($_GET["id"])) {
							if($this->controller->grantAdmin($_GET["id"])) {
								//renvoi vers la liste
								$this->POSTredirect($this->getAccountsURL(), "Le compte : ".$_GET['id']." est désormais un admin !");
							} else {
								//l'utilisateur n'a pas les droits de supprimer ce compte
								$this->POSTredirect($this->getNotAllowedURL());
							}
						} else {
							$this->view->makeUnknownPage();
							$this->view->render();	
						}
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;
					
				case 'deleteAccount':
					if ($IsAdmin) {
						if(isset($_GET["id"])) {
							if($this->controller->deleteAccount($_GET["id"])) {
								//renvoi vers la liste
								$this->POSTredirect($this->getAccountsURL(), "Le compte a bien été supprimé !");
							} else {
								//l'utilisateur n'a pas les droits de supprimer ce compte
								$this->POSTredirect($this->getNotAllowedURL());
							}
						} else {
							$this->view->makeUnknownPage();
							$this->view->render();	
						}
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;
				case 'modifyValidation':
					if ($IsUser) {
						$error = array();
						
						if ($this->controller->modifySeries($_GET["id"], $_POST, $error)) {
							//renvoi vers la liste des séries
							$this->POSTredirect($this->getListURL(), "La série '" . $_POST["name"] . "' a bien été modifié !");
						} else {
							if(!empty($error)) {
								//renvoi vers la page de création avec les erreurs
								$this->POSTredirect($this->getModifyURL($_GET["id"]), array_merge($_POST, $error));
							} else {
								//l'utilisateur n'a pas les droits de modifier cette série
								$this->POSTredirect($this->getNotAllowedURL($_GET["id"]));
							}
						}
					} else {
						$this->POSTredirect($this->getNotUserURL());
					}
					break;

				default:
					break;
			}
		} else if (isset($_GET["connection"])) {
			$this->controller->showConnectionPage();
		} else if (isset($_GET["modify"])) {
			if ($IsUser) {
				if(!$this->controller->showModificationPage($_GET["id"])) {					
					//si la page ne peut pas être créée
					$this->view->makeNotAllowedPage();
					$this->view->render();					
				}
			} else {
				 $this->POSTredirect($this->getNotUserURL());
			}
		} else if (isset($_GET["accountCreation"])) {
			$this->view->makeAccountCreationPage($_POST);
			$this->view->render();
		} else if (isset($_GET["info"])) {
			$this->view->makeInfoPage();
			$this->view->render();	
		} else if (isset($_GET["liste"])) {
			$filter = !empty($_POST["search"]) ? $_POST["search"] : null;			
			$this->controller->showList($filter);
		} else if (isset($_GET["accounts"])){
			if($IsAdmin){
				$this->controller->showAccountsList();
			}
			else{
				$this->POSTredirect($this->getNotAllowedURL());
			}
			$this->view->render();
		}else if (isset($_GET["NotUser"])) {
			$this->view->makeNotUserPage();
			$this->view->render();
		} else if (isset($_GET["NotAllowed"])) {
			$this->view->makeNotAllowedPage();
			$this->view->render();
		} else if (isset($_GET["id"])) {
				if ($IsUser) {
					$this->controller->showInformation($_GET["id"]);
				} else {
					 $this->POSTredirect($this->getNotUserURL());
				}
		} else if (empty($_GET)) {
			$this->view->makeHomePage();
			$this->view->render();
		} else {
			$this->view->makeUnknownPage();
			$this->view->render();
		}
	}

	/**
	 * Redirige l'utilisateur vers la page spécifié, en passant un feedback pouvant être afficher
	 * dans la page de redirection
	 *
	 * @param string $url
	 * @param $feedback
	 * @return void
	 */
	public function POSTredirect($url, $feedback = null)
	{
		$_SESSION['feedback'] = $feedback;
		header("Location: " . htmlspecialchars_decode($url), true, 303);
		die();
	}

	/**
	 * Retourne le lien vers la page "non-utilisateur"
	 *
	 * @return string
	 */
	public function getNotUserURL()
	{
		return "?NotUser";
	}

	/**
	 * Retourne le lien vers la page "non-autorisé"
	 *
	 * @return string
	 */
	public function getNotAllowedURL()
	{
		return "?NotAllowed";
	}

	/**
	 * Retourne le lien vers la page de connection
	 *
	 * @return string
	 */
	public function getConnectionURL()
	{
		return "?connection";
	}

	/**
	 * Retourne le lien vers la page d'info d'une série
	 *
	 * @return string
	 */
	public function getInfoURL()
	{
		return "?info";
	}

	/**
	 * Retourne le lien vers la page de création d'un compte
	 *
	 * @return string
	 */
	public function getAccountCreationURL()
	{
		return "?accountCreation";
	}

	/**
	 * Retourne le lien vers la liste des séries
	 *
	 * @return string
	 */
	public function getListURL()
	{
		return "?liste";
	}

	/**
	 * Retourne le lien vers la liste des comptes
	 *
	 * @return string
	 */	
	public function getAccountsURL()
	{
		return "?accounts";
	}

	/**
	 * Retourne le lien vers la page d'une série
	 *
	 * @param int $id
	 * @return string
	 */
	public function getSeriesURL($id)
	{
		return "?id=$id";
	}

	/**
	 * Retourne le lien vers la page d'accueil
	 *
	 * @return string
	 */
	public function getHomeUrl()
	{
		return ".";
	}

	/**
	 * Retourne le lien vers la page de connection
	 *
	 * @return string
	 */
	public function getActionConnectionURL()
	{
		return "?action=connection";
	}

	/**
	 * Retourne le lien vers la page de création d'un compte
	 *
	 * @return string
	 */
	public function getActionAccountCreationURL()
	{
		return "?action=accountCreation";
	}

	/**
	 * Retourne le lien vers la page de déconnection
	 *
	 * @return string
	 */
	public function getDisconnectionURL()
	{
		return "?action=disconnection";
	}

	/**
	 * Retourne le lien vers la page de création d'une série
	 *
	 * @return string
	 */
	public function getSeriesCreationURL()
	{
		return "?action=save";
	}

	/**
	 * Retourne le lien vers la page de validation d'une série
	 *
	 * @return string
	 */
	public function getSeriesSaveURL()
	{
		return "?action=saveValidation";
	}

	/**
	 * Retourne le lien vers la page d'update d'une série
	 *
	 * @return string
	 */
	public function getUpdateURL()
	{
		return "?action=update";
	}

	/**
	 * Retourne le lien vers la page de passage administrateur
	 *
	 * @return string
	 */	
	public function getGrantAdminURL($id)
	{
		return "?action=grantAdmin&id=$id";
	}

	/**
	 * Retourne le lien vers la page de modification d'une série
	 *
	 * @return string
	 */	
	public function getModifyURL($id)
	{
		return "?modify&id=$id";
	}

	/**
	 * Retourne le lien vers la page de supprésion d'une série
	 *
	 * @return string
	 */
	public function getDeleteURL($id)
	{
		return "?action=delete&id=$id";
	}
	
	/**
	 * Retourne le lien vers la page de supprésion d'un compte
	 *
	 * @return string
	 */
	public function getDeleteAccountURL($id)
	{
		return "?action=deleteAccount&id=$id";
	}
	
	/**
	 * Retourne le lien vers la page de validation des modifications
	 *
	 * @return string
	 */
	public function getModifyValidationURL($id)
	{
		return "?action=modifyValidation&id=$id";
	}
}
