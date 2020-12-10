<?php

 /*
  * Implements hook_admin().
  */
 function tao_iching_admin() {
   global $user;
   $config = config('tao_iching_admin.settings');
   $idArray = db_query('SELECT id, timestamp FROM {tao_iching_readings}')->fetchAll();
   $readCount = count($idArray);
   $form = array();
   $form['#config'] = 'tao_iching_admin.settings';
   $form['readings'] = array(
     '#type' => 'fieldset',
     '#title' => t($readCount . ' Readings'),
     '#description' => t('Delete individual readings.'),
     '#collapsible' => TRUE,
     '#collapsed' => TRUE,
   );
   foreach ($idArray as $key => $value) {
     $convertedtime = date('d/m/Y h:i:s', $value->timestamp);
     $form['readings']["'" . $value->timestamp . "'"] = array(
       '#type' => 'checkbox',
       '#title' => t("&nbsp;&nbsp;<a href='/result/" . $value->id . "' target='_new'>" . $user->name . " at " . $convertedtime) . "</a>",
       '#default_value' => 0,
       '#required' => FALSE,
       '#attributes' => array('class' => array('ic1-class-1')),
     );
   }
   $form['all'] = array(
     '#type' => 'fieldset',
     '#title' => t('Delete All Readings'),
     '#collapsible' => TRUE,
     '#collapsed' => FALSE,
   );
   $form['all']['delete_all'] = array(
     '#type' => 'checkbox',
     '#title' => t('Delete All Readings in the database.'),
     '#default_value' => 0,
     '#required' => FALSE,
   );
   $form['database'] = array(
     '#type' => 'fieldset',
     '#title' => t('Database'),
     '#collapsible' => TRUE,
     '#collapsed' => FALSE,
   );
   $form['database']['hourly'] = array(
     '#type' => 'checkbox',
     '#title' => t('Delete readings after 1 hour. (default)'),
     '#default_value' => $config->get('hourly'),
     '#required' => FALSE,
   );
   $form['database']['selected'] = array(
     '#type' => 'select',
     '#title' => t('The length of time to store readings.'),
     '#options' => array(
       '1' => t('1 day'),
       '7' => t('1 week'),
       '30' => t('1 month'),
       '90' => t('3 months'),
       '180' => t('6 months'),
       '270' => t('9 months'),
       '365' => t('1 year'),
       'forever' => t('forever'),
     ),
     '#default_value' => $config->get('selected'),
     '#description' => t('Set this to change the default storage setting.'),
     '#states' => array(
     'visible' => array(
       ':input[name="hourly"]' => array('checked' => FALSE),
       ),
     ),
   );
   $form['generate'] = array(
     '#type' => 'fieldset',
     '#title' => t('Generate'),
     '#collapsible' => TRUE,
     '#collapsed' => FALSE,
   );
   $form['generate']['howmany'] = array(
     '#type' => 'textfield',
     '#title' => t('Generate Readings'),
     '#required' => FALSE,
     '#default_value' => '0',
     '#description' => "Input up to 100",
     '#size' => 6,
     '#maxlength' => 6,
   );
   return system_settings_form($form);
 }

 /**
  * Implements hook_validate().
  */
 function tao_iching_admin_validate($form, &$form_state) {
   $formValues = $form_state['values'];
   $I_Ching = new I_Ching();
   $dbArray = tao_iching_chkbx_adm();
   foreach ($dbArray as $key => $value) {
     if ( $formValues["'" . $key . "'"] == 1) {
       $I_Ching->deleteReading($value);
     }
   }
   if ($formValues['delete_all'] == 1) {
     tao_iching_rdngs_dlt();
   }
   if ($formValues['howmany'] != "") {
     tao_iching_rdngs_gnrt($formValues['howmany']);
   }
 }

/**
 * create a checkbox array
 */
function tao_iching_chkbx_adm() {
  $returnArray = array();
  $idArray = db_query('SELECT id, timestamp FROM {tao_iching_readings}')->fetchAll();
  foreach ($idArray as $key => $value) {
    $returnArray[$value->timestamp] = $value->id;
  }
  return $returnArray;
}

/**
 * delete all readings
 */
function tao_iching_rdngs_dlt() {
  $I_Ching = new I_Ching();
  $idArray = db_query('SELECT id, timestamp FROM {tao_iching_readings}')->fetchAll();
  $arrayCount = count($idArray);
  foreach ($idArray as $key => $value) {
      $I_Ching->deleteReading($value->id);
  }
  backdrop_set_message($arrayCount . " readings have been deleted.");
}

/**
 * generate readings
 * @return = a $number of I-Ching readings
 */
function tao_iching_rdngs_gnrt($number) {
  if ($number != "" && $number <= 100) {
    $time_start = microtime(true);
    $I_Ching = new I_Ching();
    $count = 0;
    while ($count < $number) {
      sleep(2);
      /* set id */
      $id = $I_Ching->makeID();
      $sessionId = $id['id'];
      /* initialize a reading */
      $question = "";
      $I_Ching->readingInit($id, $question);
      $i = 1;
      while($i < 7) {
        /* generate hexagram line */
        $user_click = $I_Ching->line();
        foreach ($user_click as $key => $value) {
          if ($key != "line" && $key != "coinsval") {
            $keyBucket = $key;
            $valBucket = $value;
          }
        }
        $line = $user_click['line'];
        $tri_name = $keyBucket;
        $code = $valBucket;
        $coinsval = $user_click['coinsval'];

        $I_Ching->insertLine($sessionId, $i, $line, $tri_name, $code, $coinsval);
        $i++;
      }
    $count++;
    }
    $time_end = microtime(true);
    $execution_time = $time_end - $time_start;
    if ($number != 0) {
      backdrop_set_message($number . " readings generated in " . round($execution_time, 2) . " seconds.");
    }
  } else {
    backdrop_set_message("Number to generate must be 100 or less.");
  }
}

/**
 * @return TRUE if set time is expired
 */
function tao_iching_crn_tm() {
  $config = config('tao_iching_admin.settings');
  $currentTime = time();
  $futureTime = "";
  /* get the time of the oldest reading */
  $tsArray = db_query('SELECT timestamp FROM {tao_iching_readings} order by timestamp ASC')->fetchAll();
  if ($tsArray) {
    $oldest = $tsArray[0]->timestamp;
  }
  /* select value */
  $savedValue = $config->get('selected');
  /* checkbox value */
  $notSaveValue = $config->get('hourly');
  /* seconds in a day */
  $setInterval = intval($savedValue) * 86400;
  /* set future expire time */
  if ($tsArray) {
    $futureTime = intval($oldest) + intval($setInterval);
  }
  if ($notSaveValue != 1) {
    $timeReturn = "";
    switch ($savedValue) {
      case 1:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      case 7:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      case 30:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      case 90:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      case 180:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      case 270:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      case 365:
        $timeReturn = ($currentTime >= $futureTime) ? TRUE : FALSE;
        break;
      default:
        $timeReturn = FALSE;
    }
    return $timeReturn;
  } else {
    return TRUE;
  }
}

/**
 * delete broken or incomplete readings
 */
function tao_iching_crn_cln_db() {
  $I_Ching = new I_Ching();
  $idArray = db_query('SELECT id FROM {tao_iching_readings}')->fetchAll();
  foreach ($idArray as $key => $value) {
    if ( $I_Ching->checkNumber($value->id) != 6 ) {
      $I_Ching->deleteReading($value->id);
    }
  }
}
