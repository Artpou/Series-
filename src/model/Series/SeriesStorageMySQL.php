<?php
require_once("SeriesStorage.php");
require_once("SeriesBuilder.php");

class SeriesStorageMySQL implements SeriesStorage
{
    private $bd;

    /**
     * Construit la BDD des Séries
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
     * Lis une série dans la BDD
     *
     * @param int $id
     * @return Series
     */
    public function readSeries($id)
    {
        $stmt = $this->bd->prepare("SELECT * FROM series WHERE id=:id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $rawData = $stmt->fetch();

        if (empty($rawData)) {
            return null;
        }

        $builder = new SeriesBuilder($rawData);
        return $builder->create();
    }

    /**
     * Retourne l'id d'une Série dans la BDD
     *
     * @param Series $series
     * @return int $id
     */
    private function getID(Series $series)
    {
        $stmt = $this->bd->prepare("SELECT * FROM series WHERE name=:name");
        $stmt->bindParam(':name', $series->name);
        $stmt->execute();
        $rawData = $stmt->fetch();

        if (empty($rawData)) {
            return null;
        }
        return $rawData["id"];
    }

    /**
     * Lis toutes les séries dans la BDD
     *
     * @param string $filter
     * @return array
     */
    public function readAllSeries($filter = null)
    {
        if(isset($filter)) {
            $stmt = $this->bd->prepare("SELECT * FROM series
                WHERE name LIKE :filter OR creator LIKE :filter");
            $filter = "%".$filter."%";
            $stmt->bindParam(':filter', $filter);
        } else {
            $stmt = $this->bd->prepare("SELECT * FROM series");
        }
        $stmt->execute();
        $datas = $stmt->fetchAll();

        $allSeries = array();
        foreach ($datas as $series) {
            $builder = new SeriesBuilder($series);
            $allSeries[$series["id"]] = $builder->create();
        }

        return $allSeries;
    }

    /**
     * Lis les séries créée par un utilisateur
     *
     * @param string $login
     * @return array
     */
    public function readUserSeries($login)
    {
        $stmt = $this->bd->prepare("SELECT * FROM series WHERE login_creator=:login");
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        $datas = $stmt->fetchAll();

        $allSeries = array();
        foreach ($datas as $series) {
            $builder = new SeriesBuilder($series);
            $allSeries[$series["id"]] = $builder->create();
        }

        return $allSeries;
    }

    /**
     * Créé une série en BDD
     *
     * @param Series $series
     * @param string $login
     * @return void
     */
    public function create(Series $series, $login)
    {
        $stmt = $this->bd->prepare("INSERT INTO series VALUES
             (NULL, :series_name, :series_creator, :series_type, :series_date, :series_synopsis, :series_image, :login)");

        //converted date
        $date = date('Y-m-d H:i:s', strtotime($series->date));

        $stmt->bindParam(':series_name', $series->name);
        $stmt->bindParam(':series_creator', $series->creator);
        $stmt->bindParam(':series_type', $series->type);
        $stmt->bindParam(':series_date', $date);
        $stmt->bindParam(':series_synopsis', $series->synopsis);
        $stmt->bindParam(':series_image', $series->image);
        $stmt->bindParam(':login', $login);
        
        $stmt->execute();
        //l'id qui a été attribué à la série créée
        $id = $this->getId($series);
        
        return $id;
    }

    /**
     * Supprime une série en BDD
     *
     * @param int $id
     * @return void
     */
    public function delete($id)
    {
        $stmt = $this->bd->prepare("DELETE FROM series WHERE id=:id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    /**
     * Modifie une série en BDD
     *
     * @param int $id
     * @param Series $change : les changements de la série
     * @return void
     */
    public function update($id, Series $change)
    {
        $stmt = $this->bd->prepare("UPDATE series
                SET name=:new_name,
                    creator=:new_creator,
                    type=:new_type,
                    date=:new_date,
                    synopsis=:new_synopsis,
                    image=:new_image
                WHERE id=:series_id");

        //convert date
        $date = date('Y-m-d H:i:s', strtotime($change->date));

        $stmt->bindParam(':series_id', $id);
        $stmt->bindParam(':new_name', $change->name);
        $stmt->bindParam(':new_creator', $change->creator);
        $stmt->bindParam(':new_type', $change->type);
        $stmt->bindParam(':new_date', $date);
        $stmt->bindParam(':new_synopsis', $change->synopsis);
        $stmt->bindParam(':new_image', $change->image);

        $stmt->execute();
    }

    /**
     * Retourne si la série existe en BDD
     *
     * @param string $name
     * @return bool
     */
    public function serieExists($name)
    {
        $stmt = $this->bd->prepare("SELECT * FROM series WHERE name=:name");
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

}
