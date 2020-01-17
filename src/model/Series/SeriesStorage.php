<?php
    interface SeriesStorage {
        public function readSeries($id);
        public function readAllSeries();
        public function create(Series $series,$login);
    }
?>
