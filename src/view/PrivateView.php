<?php
require_once("View.php");
require_once("model/Series/SeriesBuilder.php");
require_once("model/Series/SeriesBuilder.php");

class PrivateView extends View
{
    /**
     * Construit la vue Utilisateur qui étend la Vue
     *
     * @param Router $router
     * @param string $feedback
     */
    public function __construct($router, $feedback = "")
    {
        parent::__construct($router,$feedback);
        $this->menu = "<ul>
			<li><a href='" . $this->router->getHomeUrl() . "'>Accueil</a></li>
			<li><a href='" . $this->router->getDisconnectionURL() . "'>Déconnection</a></li>
			<li><a href='" . $this->router->getListURL() . "'>Liste des séries</a></li>
            <li><a href='" . $this->router->getSeriesCreationURL() . "'>Ajouter une série</a></li>
            <li><a href = '" . $router->getInfoURL() . "'>A Propos</a></li>
            </ul>
            ";
    }

    /**
     * Construit la page d'accueil d'un Utilisateur
     *
     * @return void
     */
    public function makeHomePage()
    {
        $this->title = "Bienvenue \"" .  $_SESSION['name'] . "\" sur Series+ !";
		if(!empty($this->feedback)) {
			$this->content = $this->feedback;
		}
    }

    /**
     * Construit la page d'une série
     *
     * @param Series
     * @return void
     */
    public function makeSeriesPage(Series $series)
    {	
        $image = !empty($series->image) ? $series->image : "src/view/Image/default";
        $synopsis = file_exists($series->synopsis) ? file_get_contents($series->synopsis) : "";

        $this->title = $series->name;       
        $this->content = "
		<img src= '$image'/></br>
		réalisateur : $series->creator</br>
			genre : $series->type</br>
			date de création : $series->date</br>
			synopsis :
            <p>$synopsis</p></br>
            <i>creator of this page : $series->login_creator</i>";
    }

    /**
     * Construit la liste de toutes les séries, avec pour chaque série
     * les actions disponibles en tant utilisateur
     *
     * @param array $liste
     * @return void
     */
    public function makeListPage(array $liste)
    {
        /* Utilisé pour surchager la method "makeListPage" et accepté 
        *  et accepter un 2eme argument "listeEditable"
        */
        $listeEditable = func_get_arg(1);

        $this->title = "Liste des séries";
        if (!empty($this->feedback) && !is_array($this->feedback)) {
            $this->content = $this->feedback."</br>";
        }

        $this->content .=
        "<form action='" . $this->router->getListURL() . "' method='post'>
            <input type='text' name='search' class='search_series' placeholder='rechercher une série'>
            <input type='submit' value='Rechercher'>
        </form>";

        $this->content .= "<ul>";
        foreach ($liste as $key => $e) {
            $url = $this->router->getSeriesURL($key);

            $image = !empty($e->image) ? $e->image : "src/view/Image/default";
            $synopsis = file_exists($e->synopsis) ? file_get_contents($e->synopsis) : "";

            $synopsis_max_length = 240;
            //réduit le synopsis pour l'afficher correctement
            $synopsis = substr($synopsis, 0,  $synopsis_max_length);
            //affiche ... si le synopsis est trop long
            if(strlen($synopsis) >= $synopsis_max_length) {
                $synopsis .= " ...";
            }

            $this->content .= 
            "<li>
                <div class='list_item'>
                    <a href='$url'>
                        <img src= '$image'/>
                    </a>
                    <div class='item_info'>
                        <a href='$url'>
                            <h3>$e->name</h3>
                        </a>
                        <p><b>Réalisateur :</b> $e->creator</p>
                        <p><b>Synopsis :</b> $synopsis<p>
                     </div>
                </div>";

            //affiche les modifications si la série a été créé par l'utilisateur
            if (in_array($e, $listeEditable)) {
                $this->content .=
                    "<form action='" . $this->router->getUpdateURL() . "' method='post'>
                        <input type='hidden' name='id' value='$key' >
                        <input type='hidden' name='name' value='$e->name'>
                        <input type='submit' name='action' value='modify'>
                        <input type='submit' name='action' value='delete' id='deleteButton'>
                    </form>";
            }
            $this->content .= "</li>";
        }
        $this->content .= "</ul>";
    }

    /**
     * Construit la page de création d'une série
     *
     * @return void
     */
    public function makeSeriesCreationPage()
    {
        $name = isset($this->feedback["name"]) ? $this->feedback["name"] : '';
        $creator = isset($this->feedback["creator"]) ? $this->feedback["creator"] : '';
        $type = isset($this->feedback["type"]) ? $this->feedback["type"] : '';
        $date = isset($this->feedback["date"]) ? $this->feedback["date"] : '2019-01-01';
        $synopsis = isset($this->feedback["synopsis"]) ? $this->feedback["synopsis"] : '';

        $nameError = isset($this->feedback['errorName']) ? $this->feedback["errorName"] : "";
        $imgError = isset($this->feedback['errorImg']) ? $this->feedback["errorImg"] : "";
        $synopsisError = isset($this->feedback['errorSynopsis']) ? $this->feedback["errorSynopsis"] : "";


        $this->title = "Nouvelle Série";
        $this->content =
            "<form action='" . $this->router->getSeriesSaveURL() . "' method='post' enctype='multipart/form-data' class='series'>
                <input type='hidden' name='login_creator' value='".$this->router->accountsManager->getLogin()."'/>";

        if(!empty($imgError)) {
            $this->content .= "<p class='error'>".$imgError."</p>";
        }
        $this->content .=
                "<label for='image'>Image :</label>
                <input type='file' name='image'>";
          
        if(!empty($nameError)) {
            $this->content .= "<p class='error'>".$nameError."</p>";
        }
        $this->content .=
                "<label for='name'>Nom :</label>
                <input type='text' name='name' value='$name' required>

                <label for='creator'>Réalisateur :</label>
                <input type='text' name='creator' value='$creator' required>

                <label for='type'>Type de série :</label>
                <input type='text' name='type' value='$type'>

                <label for='date'>Date :</label>
                <input type='date' name='date' value='$date'>";

        if(!empty($synopsisError)) {
            $this->content .= "<p class='error'>".$synopsisError."</p>";
        }
        $this->content .=
               "<label for='synopsis'>Synopsis :</label>
                <textarea name='synopsis'>$synopsis</textarea></br>

                <input type='submit' value='Ajouter'>
            </form>";
    }

    /**
     * Construit la page de modification d'une série
     *
     * @param int $id
     * @param Series $series
     * @param string $synopsis
     * @return void
     */
    public function makeModifyPage($id, Series $series, $synopsis)
    {
        $name = isset($series->name) ? $series->name : '';
        $creator = isset($series->creator) ? $series->creator : '';
        $type = isset($series->type) ? $series->type : '';
        $date = isset($series->date) ? $series->date : '';
        $synopsis = file_exists($series->synopsis) ? file_get_contents($series->synopsis) : "";

        $this->title = "Modifier cette Série";
        $this->content =
            "<form action='" . $this->router->getModifyValidationURL($id) . "' method='post' class='series'>
                <input type='hidden' name='login_creator' value='".$this->router->accountsManager->getLogin()."'/>
                
                <label for='name'>Nom :</label>
                <input type='text' name='name' value='$name' readonly />
                
                <label for='creator'>Réalisateur :</label>
                <input type='text' name='creator' value='$creator' required/>
                
                <label for='type'>Type :</label>
                <input type='text' name='type' value='$type'/>
                
                <label for='date'>Date :</label>
                <input type='date' name='date' value='$date'/>
                
                <label for='synopsis'>Synopsis :</label>
                <textarea name='synopsis'>$synopsis</textarea></br>
                
                <input type='submit' value='Modifier'>
            </form>";
    }
}
