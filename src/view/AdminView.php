<?php
require_once("View.php");
require_once("model/Series/SeriesBuilder.php");
require_once("model/Series/SeriesBuilder.php");

class AdminView extends PrivateView
{
	/**
	 * Construit la vue administrateur qui étend la vue des utilisateurs
	 *
	 * @param Router $router
	 * @param string $feedback
	 */
    public function __construct($router,$feedback = "")
    {
        parent::__construct($router,$feedback);
        $this->menu = "<ul>
			<li><a href='" . $this->router->getHomeUrl() . "'>Accueil</a></li>
			<li><a href='" . $this->router->getDisconnectionURL() . "'>Déconnection</a></li>
			<li><a href='" . $this->router->getListURL() . "'>Liste des séries</a></li>
			<li><a href='" . $this->router->getSeriesCreationURL() . "'>Ajouter une série</a></li>
			<li><a href='" . $this->router->getAccountsURL() . "'>Liste des utilisateurs</a></li>
			<li><a href = '" . $router->getInfoURL() . "'>A Propos</a></li>
            </ul>
            ";
    }
	
	/**
	 * Affiche la liste des comptes créés
	 *
	 * @param array $liste
	 * @return void
	 */
	public function makeAccountsList(array $liste){
		$this->title = "Liste des comptes";
		if (!empty($this->feedback) && !is_array($this->feedback)) {
            $this->content = $this->feedback."<br>";
        }
		$this->content .= "<ul>";
		foreach ($liste as $key =>$account) {
			if($account->status==2){
				$status="admin";
				$adminBtn="";
				$deleteAccountBtn ="";
			}else{
				$status="user";
				$adminBtn="<input type='submit' name='action' value='grantAdmin' id='adminBtn'>";
				$deleteAccountBtn ="<input type='submit' name='action' value='deleteAccount' id='deleteButton'>";
			}
			
			$this->content .= "<li>
				$account->login<br>
				name : $account->name<br>
				email : $account->email<br>
				status : $status<br>
				<form action='" . $this->router->getUpdateURL() . "' method='post'>
							<input type='hidden' name='id' value='$key' >
							$deleteAccountBtn
							$adminBtn
				</form>
			</li>";
		}
		$this->content .= "</ul>";
	}

}