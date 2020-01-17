<?php

class AuthenticationManager
{
  private $bd;

  /**
   * Contruit la Manager de compte
   *
   * @param AccountStorageMySQL $bd
   */
  function __construct(AccountStorageMySQL $bd)
  {
    $this->bd = $bd;
  }

  /**
   * Connecte un utilisateur
   *
   * @param string $login
   * @param string $password
   * @return bool : l'utilisateur est connecté
   */
  function connectUser($login, $password)
  {
    $user = $this->bd->checkAuth($login, $password);

    if (isset($user)) {
      //TODO
      //$_SESSION['user'] = serialize($user);

      $_SESSION['login']    = $user->login;
      $_SESSION['password'] = $user->password;
      $_SESSION['name']     = $user->name;
      $_SESSION['status']   = $user->status;
      return true;
    }
    return false;
  }

  /**
   * Un utilisateur est connecté
   *
   * @return boolean
   */
  function isUserConnected()
  {
    return isset($_SESSION['login']);
  }

  /**
   * Un admin est connecté
   *
   * @return boolean
   */
  function isAdminConnected()
  {
    return isset($_SESSION['login']) && isset($_SESSION['status']) &&
      $_SESSION['status']  == 'admin';
  }

  /**
   * Le login de l'utilisateur connecté
   *
   * @return string
   */
  function getLogin()
  {
    if($this->isUserConnected())
      return $_SESSION['login'];
    return null;
  }

  /**
   * Déconnecte l'utilisateur
   *
   * @return void
   */
  function disconnectedUser()
  {
    session_destroy();
  }
}
