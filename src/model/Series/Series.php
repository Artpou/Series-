<?php
    class Series
    {
        public $name;
        public $creator;
        public $type;
        public $date;
        public $image;
        public $synopsis;
        public $login_creator;

        /**
         * Construit une nouvelle série
         *
         * @param string $name
         * @param string $creator
         * @param string $type
         * @param string $date
         * @param string $login_creator
         * @param string $synopsis
         * @param string $image
         */
        public function __construct($name, $creator,$type, $date,$login_creator, $synopsis = '', $image = '') {
            $this->name          = $name;
            $this->creator       = $creator;
            $this->type          = $type;
            $this->date          = $date;
            $this->synopsis      = $synopsis;  
            $this->image         = $image;  
            $this->login_creator = $login_creator;
        }
    }
    
?>