<?php

/**
 *
 */
class SeriesBuilder
{
  private $isValid = false;

  public $NAME_REF;
  public $CREATOR_REF;
  public $TYPE_REF;
  public $DATE_REF;
  public $SYNOPSIS_REF;
  public $IMAGE_REF;
  public $LOGIN_CREATOR_REF;

  /**
   * Construction du builder d'une série
   *
   * @param array $data
   * @param SeriesStorageMySQL $db
   */
  function __construct($data,SeriesStorageMySQL $db = null)
  {
    $this->isValid = true;

    // Test si une BDD est passé et si le nom de la série est déjà présent
    if (isset($db) && $db->serieExists($data['name'])) {
      $this->isValid = false;
      $this->error["errorName"] = "Ce nom de Série existe déjà !";
    } else {
      $this->NAME_REF = $data['name'];
    }
    $this->CREATOR_REF       = $data['creator'];
    $this->TYPE_REF          = $data['type'];
    $this->DATE_REF          = $data['date'];
    $this->SYNOPSIS_REF      = isset($data['synopsis']) ? $data['synopsis'] : '';
    $this->IMAGE_REF         = isset($data['image']) ? $data['image'] : '';    
    $this->LOGIN_CREATOR_REF = $data['login_creator'];
  }

  /**
   * retourn les erreurs
   *
   * @return array
   */
  public function getError()
  {
    return $this->error;
  }

  /**
   * Créer la série
   *
   * @return Series
   */
  public function create()
  {
    if(!$this->isValid) {
      throw new Exception("Error are present in fields of SeriesBuilder", 1);  
    }
    return new Series(
      $this->NAME_REF,
      $this->CREATOR_REF,
      $this->TYPE_REF,
      $this->DATE_REF,
      $this->LOGIN_CREATOR_REF,
      $this->SYNOPSIS_REF,
      $this->IMAGE_REF
    );
  }

  /**
   * Retourne si la série est valide
   *
   * @return boolean
   */
  function isValid()
  {
    return $this->isValid;
  }
}
