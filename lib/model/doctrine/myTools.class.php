<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class myTools
{

  static public function Permalinkise($str) {
    $perma = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
    $perma = strtolower($perma);
    $perma = preg_replace('/\W/', '-', $perma);
    return $perma;
  }
}