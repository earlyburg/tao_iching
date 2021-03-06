<?php

class I_Ching {

  function __construct() {
    // container
    $this->iching = array("initial" => array(), "changed" => array(), "question" => "");
    // define the 8 trigrams
    $this->kun = array(0, 0, 0);
    $this->gen = array(1, 0, 0);
    $this->kan = array(0, 1, 0);
    $this->xun = array(1, 1, 0);
    $this->zhen = array(0, 0, 1);
    $this->li = array(1, 0, 1);
    $this->dui = array(0, 1, 1);
    $this->qian = array(1, 1, 1);
  }


  /**
   * generates i-ching lines
   */
  public function line() {
    $toss = array();
    $return_vals = array();
    $name = "";
    $code = "";
    $k = 0;
    // toss three coins, get the result (heads = 1, tails = 0)
    for ($k = 0 ; $k < 3; $k++) {
      $toss[] = rand(0, 1);
    }
    // get the name of the line
    switch ($toss) {
      case $toss == $this->kun:
        $name = "kun";
        $code = "000";
          break;
      case $toss == $this->gen:
        $name = "gen";
        $code = "100";
          break;
      case $toss == $this->kan:
        $name = "kan";
        $code = "010";
          break;
      case $toss == $this->xun:
        $name = "xun";
        $code = "110";
          break;
      case $toss == $this->zhen:
        $name = "zhen";
        $code = "001";
          break;
      case $toss == $this->li:
        $name = "li";
        $code = "101";
          break;
      case $toss == $this->dui:
        $name = "dui";
        $code = "011";
          break;
      case $toss == $this->qian:
        $name = "qian";
        $code = "111";
          break;
    }
    // check for changing lines
    if ($toss === $this->qian) {
      $val = 9;
      $line = "yang_changing";
    }
    if ($toss === $this->kun) {
      $val = 6;
      $line = "yin_changing";
    }
    // yin or yang
    $sumtest = array_sum($toss);
    if ($sumtest == 2) {
      $val = 8;
      $line = "yin";
    }
    if ($sumtest == 1) {
      $val = 7;
      $line = "yang";
    }
    $return_vals['line'] = $line;
    $return_vals[$name] = $code;
    $return_vals['coinsval'] = $val;
    return $return_vals;
  }


  /**
   * generates one complete hexagram
   */
  public function hexagram() {
    $i = 0;
    $hexagram = array();
    while($i < 6) {
      $hexagram[] = $this->line();
      $i++;
    }
    return $hexagram;
  }


  /**
   * generates complete i-ching
   */
  public function complete($hexagram) {
    $i = 0;
    $changedBucket = array();
    while ($i < 6) {
      $lineValKey = key($hexagram[$i]);
      $lineVal = $hexagram[$i][$lineValKey];
      if ($lineVal == "yang_changing") {
        $changedBucket[$i][$lineValKey] = "yin";
      } else if ($lineVal == "yin_changing") {
        $changedBucket[$i][$lineValKey] = "yang";
      } else {
        $changedBucket[$i][$lineValKey] = $lineVal;
      }
      $i++;
    }
    $this->iching['initial'] = $hexagram;
    foreach ($hexagram as $lineArray) {
      if ( array_key_exists("qian", $lineArray) || array_key_exists("kun", $lineArray) ) {
        $this->iching['changed'] = $changedBucket;
        continue;
      }
    }
    if ( $this->iching['changed'] == "" || empty($this->iching['changed']) ) {
      $this->iching['changed'] = "No Change";
    }
    // $this->iching['initial'][0] is the bottom line
    return $this->iching;
  }
// @TODO
// @TODO change this to get all the changing lines and get that info displayable
// @TODO

  /**
   * returns the number of the top changing line
   */
  public function findTopChanging($rawhex) {
    $lineBucket = array();
    foreach ($rawhex as $key => $value) {
      $lineBucket[$key] = $value['line'];
    }
    $yin_keys = array_keys($lineBucket, "yin_changing");
    $yang_keys = array_keys($lineBucket, "yang_changing");
    $integrated = array_merge($yin_keys, $yang_keys);
    sort($integrated, SORT_NUMERIC);
    $top_changed = $integrated[0];
      switch ($top_changed) {
        case "0":
          $line_pos = "six";
            break;
        case "1":
          $line_pos = "five";
            break;
        case "2":
          $line_pos = "four";
            break;
        case "3":
          $line_pos = "three";
            break;
        case "4":
          $line_pos = "two";
            break;
        case "5":
          $line_pos = "one";
            break;
      }
      $topchanging = "line_" . $line_pos;
      return $topchanging;
  }


  /**
   * removes "_changing"
   */
  public function rawhex_cleanup($raw) {
    $cleaned = array();
    foreach($raw as $key => $value) {
      switch ($value['line']) {
        case "yang_changing":
          $cleaned[$key]['line'] = "yang";
            break;
        case "yin_changing":
          $cleaned[$key]['line'] = "yin";
            break;
        default:
          $cleaned[$key]['line'] = $value['line'];
      }
    }
    return $cleaned;
  }


  /**
   * makes an ID and a timestamp array
   */
  public function makeID() {
    $time = time();
    $idReturn = array();
    $idReturn['timestamp'] = $time;
    $idReturn['id'] = hash("md5", $time);
    return $idReturn;
  }


  /**
   * gets an ID string from a sessionId
   */
  public function id($string) {
    $arr = explode("-", $string);
    return $arr[0];
  }


  /**
   * renders an ID array from an ID string
   */
  public function renderId($idString) {
    $idReturn = array();
    $idReturn['timestamp'] = time();
    $idReturn['id'] = $idString;
    return $idReturn;
  }


  /**
   * gets the users question associated with the reading id
   */
  public function fetchQuestion($idString) {
    $question = db_select('tao_iching_readings', 'n')
      ->fields('n', array('question'))
      ->condition('n.id', $idString)
      ->execute()
      ->fetchField();
      ($question) ? $return = $question : $return = FALSE;
      return $return;
  }


  /**
   * checks if a unix timestamp is older than 1 hour
   */
  public function checkTimestamp($timeString) {
    if ( !empty($timeString) ) {
      $newTimeString = time();
      $difference = $newTimeString - $timeString;
      $difference = $difference/60; // minutes
      ($difference >= 60) ? $return = TRUE : $return = FALSE;
    }
    return $return;
  }


  /**
   * inserts a trigram to the lines table
   */
  public function insertLine($id, $throw_num, $line, $tri_name, $code, $coinsval) {
    if ( !empty($id) && !empty($throw_num) && !empty($line) && !empty($tri_name) && !empty($code) && !empty($coinsval) ) {
      if ( $this->readingExist($id) != FALSE ) {
        $ret = db_insert('tao_iching_lines')
          ->fields(array(
            'id' => $id,
            'throw_num' => $throw_num,
            'line' => $line,
            'tri_name' => $tri_name,
            'code' => $code,
            'coinsval' => $coinsval,
          ))
          ->execute();
        ($ret) ? $return = TRUE : $return = FALSE;
      }
    }
    return $return;
  }


  /**
   * checks if a reading exists
   */
  public function readingExist($id = NULL) {
    $ret = db_select('tao_iching_readings', 'n')
      ->fields('n', array('id'))
      ->condition('n.id', $id)
      ->execute()
      ->fetchField();
    ($ret) ? $return = TRUE : $return = FALSE;
    return $return;
  }


  /**
   * deletes a reading from the database
   */
  public function deleteReading($id = NULL) {
    $lines_deleted = db_delete('tao_iching_lines')
      ->condition('id', $id)
      ->execute();
    $readings_deleted = db_delete('tao_iching_readings')
      ->condition('id', $id)
      ->execute();
    ($lines_deleted != FALSE && $readings_deleted != FALSE) ? $return = TRUE : $return = FALSE;
    return $return;
  }


  /**
   * checks the number of lines in a reading
   */
  public function checkNumber($id = NULL) {
    $count = db_select('tao_iching_lines', 'n')
      ->fields('n', array('id'))
      ->condition('n.id', $id)
      ->execute()
      ->rowCount();
    ($count) ? $return = $count : $return = FALSE;
    return $return;
  }


  /**
   * initializes a reading
   */
  public function readingInit($idArray, $user_name = NULL, $question = '') {
    if ( !empty($idArray) ) {
      $insert = db_insert('tao_iching_readings')
        ->fields(array(
          'id' => $idArray['id'],
          'user_name' => $user_name,
          'question' => $question,
          'timestamp' => $idArray['timestamp'],
        ))
        ->execute();
      return $idArray['timestamp'];
    }
  }


  /**
   * returns the current I-Ching object
   */
  public function myIching($id = NULL) {
    if ( $this->readingExist($id) ) {
      // get the question
      $question = db_select('tao_iching_readings', 'n')
        ->fields('n', array('question'))
        ->condition('n.id', $id)
        ->execute()
        ->fetchField();
      // get the lines created
      $lines = db_select('tao_iching_lines', 'n')
        ->fields('n', array('throw_num', 'line', 'tri_name', 'code', 'coinsval'))
        ->condition('n.id', $id)
        ->execute()
        ->fetchAll();

      foreach($lines as $key => $value) {
        $this->iching['initial'][$key]['line'] = $value->line;
        foreach ($value as $arKey => $arVal) {
          if ($arKey != "line" && $arKey != "coinsval") {
            $arKeyBucket = $value->tri_name;
            $arValBucket = $arVal;
          }
        }
        $this->iching['initial'][$key][$arKeyBucket] = $arValBucket;
        $this->iching['initial'][$key]['coinsval'] = $value->coinsval;
      }
      ($question) ? $this->iching['question'] = $question : $this->iching['question'] = "";
    }
    return $this->iching;
  }


  /**
   * returns the book number when passed a hexagram array
   */
  public function findBooknum($hexagram) {
    $bookNumber = db_select('tao_iching_hexagrams', 'n')
      ->fields('n', array('book_number'))
      ->condition('n.line1', $hexagram[0]['line'])
      ->condition('n.line2', $hexagram[1]['line'])
      ->condition('n.line3', $hexagram[2]['line'])
      ->condition('n.line4', $hexagram[3]['line'])
      ->condition('n.line5', $hexagram[4]['line'])
      ->condition('n.line6', $hexagram[5]['line'])
      ->execute()
      ->fetchField();
      return $bookNumber;
  }


  /**
   * returns the book contents when passed a hexagram array
   */
  public function findBook($hexagram) {
    $bookNumber = db_select('tao_iching_hexagrams', 'n')
      ->fields('n', array('book_number'))
      ->condition('n.line1', $hexagram[0]['line'])
      ->condition('n.line2', $hexagram[1]['line'])
      ->condition('n.line3', $hexagram[2]['line'])
      ->condition('n.line4', $hexagram[3]['line'])
      ->condition('n.line5', $hexagram[4]['line'])
      ->condition('n.line6', $hexagram[5]['line'])
      ->execute()
      ->fetchField();
    $bookArray = db_select('tao_iching_books', 'n')
      ->fields('n', array(
        'descr',
        'judge',
        'image',
        'line_one',
        'line_two',
        'line_three',
        'line_four',
        'line_five',
        'line_six'))
      ->condition('n.number', $bookNumber)
      ->execute()
      ->fetchAssoc();
    return $bookArray;
  }


}
