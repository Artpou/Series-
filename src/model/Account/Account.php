<?php
    class Account 
    {
        public $login;
        public $password;
        public $name;
        public $email;
        public $status;

        /**
         * Contruit un nouvelle utilisateur
         *
         * @param string $login
         * @param string $password
         * @param string $name
         * @param string $email
         * @param string $status
         */
        public function __construct($login,$password,$name,$email,$status) {
            $this->login    = $login;
            $this->password = $password;
            $this->name     = $name;
            $this->email    = $email;
            $this->status   = $status;
        }
    }
    
?>