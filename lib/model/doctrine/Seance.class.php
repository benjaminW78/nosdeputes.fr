<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Seance extends BaseSeance
{
  public function __tostring() {
    return 'séance du '.myTools::displayDate($this->date).', '.$this->moment;
  }
  
  static $debut_session = null;
  public static function identifySession($date) {
    if (!self::$debut_session) {
      $session = Doctrine::getTable('VariableGlobale')->findOneByChamp('session');
      self::$debut_session = unserialize($session->value);
    }
    if (!is_array(self::$debut_session))
	return ;
    foreach(array_keys(self::$debut_session) as $session) {
      if (strtotime($date) >= strtotime(self::$debut_session[$session]))
	return $session;
    }
  }

  public function setSession($session) {
    if (!$session && $this->date)
      $session = self::identifySession($this->date);
    return $this->_set('session', $session);
  }
  public function addPresence($parlementaire, $type, $source) {
    $q = Doctrine::getTable('Presence')->createQuery('p');
    $q->where('parlementaire_id = ?', $parlementaire->id)->andWhere('seance_id = ?', $this->id);
    $presence = $q->execute()->getFirst();
    if (!$presence) {
      $presence = new Presence();
      $presence->Parlementaire = $parlementaire;
      $presence->Seance = $this;
      $presence->date = $this->date;
      $presence->save();
    }
    $res = $presence->addPreuve($type, $source);
    return $res;
  }
 
  public function addPresenceLight($parlementaire_id, $type, $source) {
    $q = Doctrine::getTable('Presence')->createQuery('p');
    $q->where('parlementaire_id = ?', $parlementaire_id)->andWhere('seance_id = ?', $this->id);
    $presence = $q->execute()->getFirst();
    if (!$presence) {
      $presence = new Presence();
      $presence->_set('parlementaire_id', $parlementaire_id);
      $presence->Seance = $this;
      $presence->date = $this->date;
      $presence->save();
    }
    $res = $presence->addPreuve($type, $source);
    return $res;
  }

 
  public static function convertMoment($moment) {
    if (!$moment)
      return '1ère séance';
    if (preg_match('`(seance|séance|réunion|reunion)`i', $moment)) {
        if (preg_match('/1/', $moment)) return "1ère séance";
        if (preg_match('/(\d{1})/', $moment, $match)) return $match[1]."ème séance";
        return $moment;
    }
    if (preg_match('/(\d{1,2})[:h](\d{2})/', $moment, $match)) {
      $moment = sprintf("%02d:%02d", $match[1], $match[2]);
      return $moment;
    }
    return $moment;
  }
 
  public function getShortMoment() {
    if (preg_match('/:/', $this->moment))
      return preg_replace('/^0/', '', str_replace('00', '', str_replace(':', 'h', $this->moment)));
    else if (!$this->moment)
      return "réunion";
    return $this->moment;
  }
 
  public function setDate($date) {
    if (!$this->_set('date', $date))
      return false;
    $date = strtotime($date);
    $annee = date('Y', $date);
    $semaine = date('W', $date);
    if ($semaine == 53) {
      $annee++;
      $semaine = 1;
    }
    $this->_set('annee', $annee);
    $this->_set('numero_semaine', $semaine);
    return true;
  }
  public function getInterventions() {
    $q = Doctrine::getTable('Intervention')->createQuery('i')->where('seance_id = ?', $this->id)->leftJoin('i.Personnalite p')->leftJoin('i.Parlementaire pa')->orderBy('i.timestamp ASC');
    return $q->execute();
  }
  public function getTableMatiere() {
    $q = Doctrine_Query::create()->select('s.titre, s.id, s.section_id, s.nb_interventions')->from('Section s')->leftJoin('s.Interventions i')->where('i.seance_id = ?', $this->id)->orderBy('i.timestamp ASC');
    return $q->fetchArray();
  }

  public function getTypeOrga() {
    if ($this->type == 'hemicycle') return "Hémicycle";
    return $this->getOrganisme()->getNom();
  }

  public function getTitre($miniature = 0, $hemicycle = 0, $ref = '') {
    $titre = '';
    if ($ref != '')
      $titre .= '<a href="'.url_for('@interventions_seance?seance='.$this->id).'#inter_'.$ref.'">';
    if ($miniature == 0)
      $titre .= 'S';
    else $titre .= 's';
    $titre .= 'éance ';
    if ($hemicycle == 1)
      $titre .= 'en hémicycle ';
    $titre .= 'du '.preg_replace('/^0(\d)/', '\\1', myTools::displayDate($this->getDate()));
    if ($moment = $this->getMoment()) {
      if (preg_match('/(réunion|^\d+$)/', $moment))
        $titre .= ' : ';
      else $titre .= ' à ';
      $titre .= $moment;
    }
    $titre = preg_replace('/00:00/', 'minuit', $titre);
    $titre = preg_replace('/0(\d:\d{2})/', '\\1', $titre);
    $titre = preg_replace('/ (\d+):(\d{2})/', ' \\1h\\2', $titre);
    if ($ref != '')
      $titre .= '</a>';
    return $titre;
  }
}
