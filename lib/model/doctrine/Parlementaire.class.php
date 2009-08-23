<?php

require_once "myTools.class.php";

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Parlementaire extends BaseParlementaire
{

  /*  public function save() {
    Doctrine::getTable('Personnalite')->hasChanged();
    return parent::save($conn);
    }*/

  public function setCirconscription($str) {
    if (preg_match('/(.*)\((\d+)/', $str, $match)) {
      $this->nom_circo = trim($match[1]);
      $this->num_circo = $match[2];
    }
  }

  public function getNumCircoString($list = 0) {
    if ($this->num_circo == 1) $string = $this->num_circo.'ère circonscription';
    else $string = $this->num_circo.'ème circonscription';
    if ($list == 1 && $this->num_circo < 10) {
      $string = "&nbsp;".$string."&nbsp;";
      if ($this->num_circo == 1) $string .= "&nbsp;";
    }
    return $string;
  }

  public function getStatut($link = 0) {
    if ($this->type == 'depute') {
        if ($this->sexe == 'F') $type = 'députée';
        else $type = 'député';
    } else {
        if ($this->sexe == 'F') $type = 'sénatrice';
        else $type = 'sénateur';
    }
    $statut = "";
    if ($this->fin_mandat != null) {
      if ($this->sexe == 'F') $statut = 'ancienne ';
      else $statut = 'ancien ';
    }
    $groupe = "";
    if ($this->groupe_acronyme != "") {
      if ($link == 1)
        $groupe = " ".link_to($this->groupe_acronyme, '@list_parlementaires_groupe?acro='.$this->groupe_acronyme);
      else $groupe = " ".$this->groupe_acronyme;
    }
    return $statut.$type.$groupe;
  }
  
  public function getLongStatut($link = 0) {
    $circo = $this->nom_circo;
    if ($link == 1)
      $circo = link_to($this->nom_circo, '@list_parlementaires_circo?search='.$this->nom_circo);
    return $this->getStatut($link).' de la '.$this->getNumCircoString().' '.$this->getPrefixeCirconscription().$circo;
  }

  public function setDebutMandat($str) {
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $str, $m)) {
      $this->_set('debut_mandat', $m[3].'-'.$m[2].'-'.$m[1]);
    }
  }
  public function setFinMandat($str) {
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $str, $m)) {
      $this->_set('fin_mandat', $m[3].'-'.$m[2].'-'.$m[1]);
    }
  }
  public function setFonctions($array) {
    return $this->setPOrganisme('parlementaire', $array);
  }
  public function setExtras($array) {
    return $this->setPOrganisme('extra', $array);
  }
  public function setGroupe($array) {
    return $this->setPOrganisme('groupe', $array);
  }

  public function setPOrganisme($type, $array) {
    if (!$array)
      return;
    $orgas = $this->getParlementaireOrganismes();
    foreach($orgas->getKeys() as $key) {
      $o = $orgas->get($key);
      if ($o->type == $type)
	$orgas->remove($key);
    }
    foreach ($array as $args) {
      $orga = Doctrine::getTable('Organisme')->findOneByNom($args[0]);
      if (!$orga) {
	$orga = new Organisme();
	$orga->nom = $args[0];
	$orga->type = $type;
	$orga->save();
      }
      if ($type == 'groupe')
        $this->groupe_acronyme = $orga->getSmallNomGroupe();
      $po = new ParlementaireOrganisme();
      $po->setParlementaire($this);
      $po->setOrganisme($orga);
      $fonction = preg_replace("/\(/","",$args[1]);
      $po->setFonction($fonction);
      $importance = ParlementaireOrganisme::defImportance($fonction);
      $po->setImportance($importance);
  /*      if (isset($args[2])) {
	$po->setDebutFonction($args[2]);
	}*/
      $orgas->add($po);
    }
    $this->_set('ParlementaireOrganismes', $orgas);
  }

  private function getPOFromJoinIf($field, $value) {
    $p = $this->toArray();
    if (isset($p['ParlementaireOrganisme'])) {
      $i = 0;
      while (isset($p['ParlementaireOrganisme'][$i])) {
        if ($p['ParlementaireOrganisme'][$i]['Organisme'][$field] == $value) {
          $po = new ParlementaireOrganisme();
          $o = new Organisme();
          $o->fromArray($p['ParlementaireOrganisme'][$i]['Organisme']);
          $po->setFonction($p['ParlementaireOrganisme'][$i]['fonction']);
          $po->setParlementaire($this);
          $po->setOrganisme($o);
          return $po;
	}
	$i++;
      }
      return NULL;
    }
  }

  public function getPOrganisme($str) {
    if($po = $this->getPOFromJoinIf('nom', $str))
      return $po;
    foreach($this->getParlementaireOrganismes() as $po) {
      if ($po['Organisme']->nom == $str)
	return $po;
    }
  }
  public function setAutresMandats($array) {
    $this->_set('autres_mandats', serialize($array));
  }
  public function setMails($array) {
    $this->_set('mails', serialize($array));
  }
  public function setAdresses($array) {
    $this->_set('adresses', serialize($array));
  }
  public function getGroupe() {
    if($po = $this->getPOFromJoinIf('type', 'groupe'))
      return $po;
    foreach($this->getParlementaireOrganismes() as $po) {
      if ($po->type == 'groupe') 
	return $po;
    }
  }
  public function getExtras() {
    $res = array();
    foreach($this->getParlementaireOrganismes() as $po) {
      if ($po->type == 'extra') 
	array_push($res, $po);
    }
    return $res;
  }
  public function getResponsabilites() {
    $res = array();
    foreach($this->getParlementaireOrganismes() as $po) {
      if ($po->type == 'parlementaire') 
	$res[sprintf('%04d',abs(100-$po->importance)).$po->nom]=$po;
    }
    ksort($res);
    return array_values($res);
  }
  public function hasPhoto() 
  {
    $photo = $this->_get('photo');
    return (strlen($photo) > 0) ;
  }
  public function setPhoto($s) {
    if (preg_match('/http/', $s)) {
      $len = strlen($this->_get('photo'));
      if ($len < 150) {
	$s = file_get_contents($s);
      }else
	return true;
      if (!$s)
	return false;
    }
    $this->_set('photo', $s);
  }
  public function getPageLink() {
    return '@parlementaire?slug='.$this->slug;
  }

  public function getNomNumCirco() {
    $shortcirco = trim(strtolower($this->_get('nom_circo')));
    $shortcirco = preg_replace('/\s+/','-', $shortcirco);
    $shortcirco = preg_replace('/(é|è|e)/','e', $shortcirco);
    $shortcirco = preg_replace('/à/','a', $shortcirco);
    $shortcirco = preg_replace('/ô/','o', $shortcirco);
    return $this->_get('nom_circo')." (".$this->getNumeroDepartement($shortcirco).")";
  }

  public function getPrefixeCirconscription() {
    $hashmap = array(
     "Ain" => "de l'",
     "Aisne" => "de l'",
     "Allier" => "de l'",
     "Alpes-de-Haute-Provence" => "des",
     "Alpes-Maritimes" => "des",
     "Ardèche" => "de l'",
     "Ardennes" => "des",
     "Ariège" => "d'",
     "Aube" => "de l'",
     "Aude" => "de l'",
     "Aveyron" => "de l'",
     "Bas-Rhin" => "du",
     "Bouches-du-Rhône" => "des",
     "Calvados" => "du",
     "Cantal" => "du",
     "Charente" => "de",
     "Charente-Maritime" => "de",
     "Cher" => "du",
     "Corrèze" => "de",
     "Corse-du-Sud" => "de",
     "Côte-d'Or" => "de",
     "Côtes-d'Armor" => "des",
     "Creuse" => "de la",
     "Deux-Sèvres" => "des",
     "Dordogne" => "de la",
     "Doubs" => "du",
     "Drôme" => "de la",
     "Essonne" => "de l'",
     "Eure" => "de l'",
     "Eure-et-Loir" => "d'",
     "Finistère" => "du",
     "Gard" => "du",
     "Gers" => "du",
     "Gironde" => "de la",
     "Guadeloupe" => "de",
     "Guyane" => "de",
     "Haut-Rhin" => "du",
     "Haute-Corse" => "de",
     "Haute-Garonne" => "de la",
     "Haute-Loire" => "de la",
     "Haute-Marne" => "de la",
     "Haute-Saône" => "de la",
     "Haute-Savoie" => "de",
     "Haute-Vienne" => "de la",
     "Hautes-Alpes" => "des",
     "Hautes-Pyrénées" => "des",
     "Hauts-de-Seine" => "des",
     "Hérault" => "de l'",
     "Ille-et-Vilaine" => "d'",
     "Indre" => "de l'",
     "Indre-et-Loire" => "de l'",
     "Isère" => "de l'",
     "Jura" => "du",
     "Landes" => "des",
     "Loir-et-Cher" => "du",
     "Loire" => "de la",
     "Loire-Atlantique" => "de",
     "Loiret" => "du",
     "Lot" => "du",
     "Lot-et-Garonne" => "du",
     "Lozère" => "de la",
     "Maine-et-Loire" => "du",
     "Manche" => "de la",
     "Marne" => "de la",
     "Martinique" => "de",
     "Mayenne" => "de la",
     "Mayotte" => "de",
     "Meurthe-et-Moselle" => "de",
     "Meuse" => "de la",
     "Morbihan" => "du",
     "Moselle" => "de la",
     "Nièvre" => "de la",
     "Nord" => "du",
     "Nouvelle-Calédonie" => "de la",
     "Oise" => "de l'",
     "Orne" => "de l'",
     "Paris" => "de",
     "Pas-de-Calais" => "du",
     "Polynésie Française" => "de la",
     "Puy-de-Dôme" => "du",
     "Pyrénées-Atlantiques" => "des",
     "Pyrénées-Orientales" => "des",
     "Réunion" => "de la",
     "Rhône" => "du",
     "Saint-Pierre-et-Miquelon" => "de",
     "Saône-et-Loire" => "de",
     "Sarthe" => "de la",
     "Savoie" => "de",
     "Seine-et-Marne" => "de",
     "Seine-Maritime" => "de",
     "Seine-Saint-Denis" => "de",
     "Somme" => "de la",
     "Tarn" => "du",
     "Tarn-et-Garonne" => "du",
     "Territoire-de-Belfort" => "du",
     "Val-d'Oise" => "du",
     "Val-de-Marne" => "du",
     "Var" => "du",
     "Vaucluse" => "du",
     "Vendée" => "de",
     "Vienne" => "de la",
     "Vosges" => "des",
     "Wallis-et-Futuna" => "de",
     "Yonne" => "de l'",
     "Yvelines" => "des"
    );
    $prefixe = $hashmap[trim($this->nom_circo)];
    if (! preg_match("/'/", $prefixe)) $prefixe = $prefixe.' ';
    return $prefixe;
  }

  public static function getNomDepartement($numero) {
    $hashmap = array(
      "1" => "Ain",
      "2" => "Aisne",
      "3" => "Allier",
      "4" => "Alpes-de-Haute-Provence",
      "5" => "Hautes-Alpes",
      "6" => "Alpes-Maritimes",
      "7" => "Ardèche",
      "8" => "Ardennes",
      "9" => "Ariège",
      "10" => "Aube",
      "11" => "Aude",
      "12" => "Aveyron",
      "13" => "Bouches-du-Rhône",
      "14" => "Calvados",
      "15" => "Cantal",
      "16" => "Charente",
      "17" => "Charente-Maritime",
      "18" => "Cher",
      "19" => "Corrèze",
      "2A" => "Corse-du-Sud",
      "2B" => "Haute-Corse",
      "21" => "Côte-d'Or",
      "22" => "Côtes-d'Armor",
      "23" => "Creuse",
      "24" => "Dordogne",
      "25" => "Doubs",
      "26" => "Drôme",
      "27" => "Eure",
      "28" => "Eure-et-Loir",
      "29" => "Finistère",
      "30" => "Gard",
      "31" => "Haute-Garonne",
      "32" => "Gers",
      "33" => "Gironde",
      "34" => "Hérault",
      "35" => "Ille-et-Vilaine",
      "36" => "Indre",
      "37" => "Indre-et-Loire",
      "38" => "Isère",
      "39" => "Jura",
      "40" => "Landes",
      "41" => "Loir-et-Cher",
      "42" => "Loire",
      "43" => "Haute-Loire",
      "44" => "Loire-Atlantique",
      "45" => "Loiret",
      "46" => "Lot",
      "47" => "Lot-et-Garonne",
      "48" => "Lozère",
      "49" => "Maine-et-Loire",
      "50" => "Manche",
      "51" => "Marne",
      "52" => "Haute-Marne",
      "53" => "Mayenne",
      "54" => "Meurthe-et-Moselle",
      "55" => "Meuse",
      "56" => "Morbihan",
      "57" => "Moselle",
      "58" => "Nièvre",
      "59" => "Nord",
      "60" => "Oise",
      "61" => "Orne",
      "62" => "Pas-de-Calais",
      "63" => "Puy-de-Dôme",
      "64" => "Pyrénées-Atlantiques",
      "65" => "Hautes-Pyrénées",
      "66" => "Pyrénées-Orientales",
      "67" => "Bas-Rhin",
      "68" => "Haut-Rhin",
      "69" => "Rhône",
      "70" => "Haute-Saône",
      "71" => "Saône-et-Loire",
      "72" => "Sarthe",
      "73" => "Savoie",
      "74" => "Haute-Savoie",
      "75" => "Paris",
      "76" => "Seine-Maritime",
      "77" => "Seine-et-Marne",
      "78" => "Yvelines",
      "79" => "Deux-Sèvres",
      "80" => "Somme",
      "81" => "Tarn",
      "82" => "Tarn-et-Garonne",
      "83" => "Var",
      "84" => "Vaucluse",
      "85" => "Vendée",
      "86" => "Vienne",
      "87" => "Haute-Vienne",
      "88" => "Vosges",
      "89" => "Yonne",
      "90" => "Territoire-de-Belfort",
      "91" => "Essonne",
      "92" => "Hauts-de-Seine",
      "93" => "Seine-Saint-Denis",
      "94" => "Val-de-Marne",
      "95" => "Val-d'Oise",
      "971" => "Guadeloupe",
      "972" => "Martinique",
      "973" => "Guyane",
      "974" => "Réunion",
      "975" => "Saint-Pierre-et-Miquelon",
      "976" => "Mayotte",
      "977" => "Saint-Barthélémy",
      "978" => "Saint-Martin",
      "986" => "Wallis-et-Futuna",
      "987" => "Polynésie Française",
      "988" => "Nouvelle-Calédonie");
    if (isset($hashmap["$numero"])) return $numero = $hashmap["$numero"];
    else return 0;
  }

  public static function getNumeroDepartement($nom) {
    $hashmap = array(
      "ain" => "01",
      "aisne" => "02",
      "allier" => "03",
      "alpes-de-haute-provence" => "04",
      "hautes-alpes" => "05",
      "alpes-maritimes" => "06",
      "ardeche" => "07",
      "ardennes" => "08",
      "ariege" => "09",
      "aube" => "10",
      "aude" => "11",
      "aveyron" => "12",
      "bouches-du-rhone" => "13",
      "calvados" => "14",
      "cantal" => "15",
      "charente" => "16",
      "charente-maritime" => "17",
      "cher" => "18",
      "correze" => "19",
      "corse-du-sud" => "2A",
      "haute-corse" => "2B",
      "cote-d'or" => "21",
      "cotes-d'armor" => "22",
      "creuse" => "23",
      "dordogne" => "24",
      "doubs" => "25",
      "drome" => "26",
      "eure" => "27",
      "eure-et-loir" => "28",
      "finistere" => "29",
      "gard" => "30",
      "haute-garonne" => "31",
      "gers" => "32",
      "gironde" => "33",
      "herault" => "34",
      "ille-et-vilaine" => "35",
      "indre" => "36",
      "indre-et-loire" => "37",
      "isere" => "38",
      "jura" => "39",
      "landes" => "40",
      "loir-et-cher" => "41",
      "loire" => "42",
      "haute-loire" => "43",
      "loire-atlantique" => "44",
      "loiret" => "45",
      "lot" => "46",
      "lot-et-garonne" => "47",
      "lozere" => "48",
      "maine-et-loire" => "49",
      "manche" => "50",
      "marne" => "51",
      "haute-marne" => "52",
      "mayenne" => "53",
      "meurthe-et-moselle" => "54",
      "meuse" => "55",
      "morbihan" => "56",
      "moselle" => "57",
      "nièvre" => "58",
      "nord" => "59",
      "oise" => "60",
      "orne" => "61",
      "pas-de-calais" => "62",
      "puy-de-dôme" => "63",
      "pyrenees-atlantiques" => "64",
      "hautes-pyrenees" => "65",
      "pyrenees-orientales" => "66",
      "bas-rhin" => "67",
      "haut-rhin" => "68",
      "rhone" => "69",
      "haute-saone" => "70",
      "saone-et-loire" => "71",
      "sarthe" => "72",
      "savoie" => "73",
      "haute-savoie" => "74",
      "paris" => "75",
      "seine-maritime" => "76",
      "seine-et-marne" => "77",
      "yvelines" => "78",
      "deux-sevres" => "79",
      "somme" => "80",
      "tarn" => "81",
      "tarn-et-garonne" => "82",
      "var" => "83",
      "vaucluse" => "84",
      "vendée" => "85",
      "vienne" => "86",
      "haute-vienne" => "87",
      "vosges" => "88",
      "yonne" => "89",
      "territoire-de-belfort" => "90",
      "essonne" => "91",
      "hauts-de-seine" => "92",
      "seine-saint-denis" => "93",
      "val-de-marne" => "94",
      "val-d'oise" => "95",
      "guadeloupe" => "971",
      "martinique" => "972",
      "guyane" => "973",
      "reunion" => "974",
      "saint-pierre-et-miquelon" => "975",
      "mayotte" => "976",
      "saint-barthélémy" => "977",
      "saint-martin" => "978",
      "wallis-et-futuna" => "986",
      "polynésie-française" => "987",
      "nouvelle-calédonie" => "988");
    if (isset($hashmap[$nom])) return $numero = $hashmap[$nom];
    else return false;
  }
}
