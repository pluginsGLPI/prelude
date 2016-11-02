<?php // all tasks are defined in RoboFile.php
class RoboFile extends \Robo\Tasks {

   public function localesExtract() {
      $this->_exec('tools/extract_template.sh');

      return $this;
   }

   public function localesPush() {
      $this->_exec('tx push -s');

      return $this;
   }


   public function localesPull() {
      $this->_exec('tx pull -s --minimum-perc=70');

      return $this;
   }


   public function localesMo() {
      $this->_exec('tx pull -s --minimum-perc=70');

      return $this;
   }


   public function localesSend() {
      $this->localesExtract()
           ->localesPush();

      return $this;
   }

   public function localesGenerate() {
      $this->localesPull()
           ->localesMo();

      return $this;
   }
}