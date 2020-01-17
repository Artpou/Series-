<?php

/*
 *View du modèle MVCR, c'est cet objet et uniquement cet objet (et ces enfants) qui s'occupera d'afficher du contenu à l'écran
 *
 */

require_once('model/Series/SeriesBuilder.php');

class View
{
	protected $router;
	protected $menu;
	protected $title;
	protected $content;
	public $feedback;

	/**
	 * Construit la vue du site, permettant d'afficher toutes les pages
	 *
	 * @param Router $router : le router du site
	 * @param string $feedback : du texte envoyé par le router a afficher 
	 */
	public function __construct(Router $router,$feedback = "")
	{
		$this->feedback = $feedback;
		$this->router   = $router;
		$this->menu     =
		"<ul>
			<li><a href = '" . $router->getHomeUrl() . "'>Accueil</a></li>
			<li><a href = '" . $router->getConnectionURL() . "'>Connection</a></li>
			<li><a href = '" . $router->getListURL() . "'>Liste des séries</a></li>
			<li><a href = '" . $router->getInfoURL() . "'>A Propos</a></li>
		</ul>";
		$this->title   = "";
		$this->content = "";
	}

	/**
	 * Construit la page de connection
	 *
	 * @return void
	 */
	public function makeConnectionPage()
	{
		$this->title = "Connection";
		if (!empty($this->feedback) && !is_array($this->feedback)) {
			$this->content .= "<p style=color:red>$this->feedback</p>";
		}

		$this->content .=
		'<form method="post" action="' . $this->router->getActionConnectionURL() . '" class="series">
			<label for="login">Pseudo : </label>
			<input type="text" id="login" name="login" required/>

			<label for="password">Mot de passe : </label>
			<input type="password" id="password" name="password" required/>

			<input type="submit" name="connexion" value="se connecter"/>
		</form>
		Pas encore inscrit ? <a href="'.$this->router->getAccountCreationURL().'">Cliquez ici</a>';
	}

	/**
	 * Construit la page de création d'un compte
	 *
	 * @return void
	 */
	public function makeAccountCreationPage()
	{
		$this->title = "Création d'un compte";

		//entre les champs déjà rempli 
		$pseudo = isset($this->feedback["login"]) ? $this->feedback["login"] : "";
		$mail = isset($this->feedback["email"]) ? $this->feedback["email"] : "";
		$name = isset($this->feedback["name"]) ? $this->feedback["name"] : "";

		//affiche les erreurs
		$pseudoError = isset($this->feedback["errorLogin"]) ? $this->feedback["errorLogin"] : "";
		$mailError = isset($this->feedback["errorEmail"]) ? $this->feedback["errorEmail"] : "";
		$passwordError = isset($this->feedback["errorPassword"]) ? $this->feedback["errorPassword"] : "";

		$this->content .=
			"<form method='post' action='" . $this->router->getActionAccountCreationURL() . "' class='series'>";
			
			if(!empty($pseudoError))
				$this->content .= "<p class='error'>$pseudoError</p>";
			$this->content .=
			"<label for='login' >Pseudo : </label>
			<input type='text' id='login' name='login' value='$pseudo' required/>";

			if(!empty($mailError))
				$this->content .= "<p class='error'>$mailError</p>";
			$this->content .=
			"<label for='email'>Adresse mail : </label>
			<input type='email' id='email' name='email' value='$mail' required/>

			<label for='name'>Nom : </label>
			<input type='text' id='name' name='name' required value='$name' />";
			
			if (!empty($passwordError))
				$this->content .= "<p class='error'>$passwordError</p>";
			$this->content .=
			"<label for='password'>Mot de passe : </label>
			<input id='password' type='password' name='password' required/>
			<label for='password2'>Valider mot de passe : </label>
			<input id='password2' type='password' name='passwordConfirm' required/>

			<input type='submit' name='connexion' value='se connecter'/>
		</form>";
	}

	/**
	 * Construit la page d'accueil
	 *
	 * @return void
	 */
	public function makeHomePage()
	{
		$this->title = "Bienvenue sur Series+ !";
		$this->content = "n'hésitez pas à consulter la page 'A Propos' pour la partie connection !";
		if(!empty($this->feedback)) {
			$this->content .= $this->feedback;
		}
	}

	public function makeInfoPage()
	{
		$this->title = "Informations";
		$this->content = "
			<p><ul><li>
			Ce site a été créée dans le cadre de notre formation en Licence Informatique (3eme année)</br>
			Il intègre le modèle php <i>MVC</i> et utilise la base de données MySQL de notre Campus</br>
			Ce site dispose de nombreuses fonctionnalités, à savoir : 
			<ul>
				<li> - L'affichage des différentes séries présentent sur le site</li>
				<li> - La création d'un compte et la connection à celui-ci (mdp chiffré)</li>
				<li> - La création d'une série pour un utilisateur, avec la gestion d'une image et d'un synopsis (max 10000 mots)</li>
				<li> - La modification d'une série (que celle créée par lui-même pour un utilisateur ou toutes les séries pour un admin)</li>
				<li> - La recherche d'une série (pour les utilisateurs) </li>
				<li> - L'affichage de tout les comptes pour un admin </li>
				<li> - La suppression d'un compte utilisateur ou son passage au rang admin pour un admin </li>
			</ul></li>

			Pour ce connecté, vous pouvez utiliser des comptes déjà créé :</br>
				<b>Admin : </b>login : admin    mdp : admin0 (a créé 3 séries)</br>
				<b>User  : </b>login : user     mdp : user0 (a créé 1 série)</br>
			... ou bien créer un compte à partir de l'onglet 'connection'</br></br>
			<i>Ce Site internet a été réalisé par AMIOUR Amine et POULLIN Arthur</i></br>
			</p>";
	}

	/**
	 * Construit la page "inconnu"
	 *
	 * @return void
	 */
	public function makeUnknownPage()
	{
		$this->title = "<p style=color:red>Page inconnu !</p>";
	}

	/**
	 * Construit la page "non-utilisateur"
	 *
	 * @return void
	 */
	public function makeNotUserPage()
	{
		$this->makeConnectionPage();
		$this->title = "<p style=color:red>Vous devez etre connecté pour afficher cette page !</p>";
	}

	/**
	 * Construit la page "non autorisé"
	 *
	 * @return void
	 */
	public function makeNotAllowedPage()
	{
		$this->title = "<p style=color:red>Vous n'avez pas les droits requis !</p>";
	}

	/**
	 * Construit la page "série inconnu"
	 *
	 * @return void
	 */
	public function makeUnknownSeriesPage()
	{
		$this->title = "<p style=color:red>Série inconnu !</p>";
	}
	
	/**
	 * Construit la liste de toutes les séries
	 *
	 * @param array $liste
	 * @return void
	 */
	public function makeListPage(array $liste)
	{
		$this->title = "Liste des séries";
        $this->content .=
        "<form action='" . $this->router->getListURL() . "' method='post'>
            <input type='text' name='search' class='search_series' placeholder='rechercher une série'>
            <input type='submit' value='Rechercher'>
		</form>";
		
		$this->content .= "<ul>";
		foreach ($liste as $e) {
			$this->content .= "<li>$e->name</li>";
		}
		$this->content .= "</ul>
			Pour voir les détails de chaque séries,
			<a href='".$this->router->getConnectionURL()."'>connectez vous</a>";

	}

	/**
	 * Affiche la page de débuggage
	 *
	 * @param $variable : la variable à afficher
	 * @return void
	 */
	public function makeDebugPage($variable)
	{
		$this->title = 'Debug';
		$this->content = '<pre>' . htmlspecialchars(var_export($variable, true)) . '</pre>';
	}

	/**
	 * Affiche la page construite en utilisant un fichier squelette
	 * Le titre et le contenu de la page construite sera utilisé par le
	 * squelette pour être affiché sous la forme d'un fichier HTML
	 *
	 * @return void
	 */
	public function render()
	{
		include ("Squelette.php");
	}
}
