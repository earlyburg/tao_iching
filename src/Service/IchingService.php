<?php
/**
 * @file
 * Contains \Drupal\tao_iching\Service\IchingService.
 */
namespace Drupal\tao_iching\Service;

use Psr\Container\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 *
 */
class IchingService {

  /**
   * @var array
   */
  private array $iching;

  /**
   * @var int[]
   */
  private array $kun;

  /**
   * @var int[]
   */
  private array $gen;

  /**
   * @var int[]
   */
  private array $kan;

  /**
   * @var int[]
   */
  private array $xun;

  /**
   * @var int[]
   */
  private array $zhen;

  /**
   * @var int[]
   */
  private array $li;

  /**
   * @var int[]
   */
  private array $dui;

  /**
   * @var int[]
   */
  private array $qian;

  /**
   * @var Connection $connection
   */
  protected Connection $database;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface $entity_manager
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @param Connection $connection
   * @param EntityTypeManagerInterface $entity_manager
   *
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_manager) {
    $this->iching = ["initial" => [], "changed" => [], "question" => ""];
    $this->kun = [0, 0, 0];
    $this->gen = [1, 0, 0];
    $this->kan = [0, 1, 0];
    $this->xun = [1, 1, 0];
    $this->zhen = [0, 0, 1];
    $this->li = [1, 0, 1];
    $this->dui = [0, 1, 1];
    $this->qian = [1, 1, 1];
    $this->database = $connection;
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * @param ContainerInterface $container
   * @return static
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }



  /**
   * generates i-ching lines
   */
  public function line() {
    $toss = array();
    $return_vals = array();
    $name = "";
    $code = "";
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
      }
    }
    if ( $this->iching['changed'] == "" || empty($this->iching['changed']) ) {
      $this->iching['changed'] = "No Change";
    }
    // $this->iching['initial'][0] is the bottom line
    return $this->iching;
  }

  /**
   * returns an array containing the numeric positions of the changed lines
   */
  public function findTopChanging($rawhex) {
    $lineBucket = [];
    foreach ($rawhex as $key => $value) {
      $lineBucket[$key] = $value['line'];
    }
    $yin_keys = array_keys($lineBucket, "yin_changing");
    $yang_keys = array_keys($lineBucket, "yang_changing");
    $integrated = array_merge($yin_keys, $yang_keys);
    arsort($integrated,SORT_NATURAL);
    $changedArray = [];
    foreach($integrated as $lineVal) {
      switch ($lineVal) {
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
      $changedArray[] = "line_" . $line_pos;
    }
    return $changedArray;
  }

  /**
   * @param array $raw
   * @return array $cleaned
   *
   */
  public function rawhex_cleanup($raw) {
    $cleaned = [];
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
   * @param array $idArray
   * @return string
   */
  public function getSessionId($idArray) {
    return $idArray['id'].'-'.$idArray['timestamp'];
  }

  /**
   * @param $string
   * @return false|string
   *
   * gets an ID string from a sessionId string
   *
   */
  public function getReadingId($sessionIdString) {
    $return = FALSE;
    if(!empty($sessionIdString)) {
      $arr = explode("-", $sessionIdString);
      $return = $arr[0];
    }
    return $return;
  }

  /**
   * @param $timestamp
   * @return false|mixed
   *
   */
  public function getReadingIdFromTimestamp($timestamp) {
    $idString = $this->database->select('tao_iching_readings', 'n')
      ->fields('n', ['id'])
      ->condition('n.timestamp', $timestamp, '=')
      ->execute()
      ->fetchField();
    ($idString) ? $return = $idString : $return = FALSE;
    return $return;
  }

  /**
   * renders an ID array from an ID string
   */
  public function recreateIdArray($sessionIdString) {
    $idReturn = [];
    if($sessionIdString) {
      $arr = explode("-", $sessionIdString);
      $idReturn['timestamp'] = $arr[1];
      $idReturn['id'] = $arr[0];
    }
    return $idReturn;
  }

  /**
   * gets the users question associated with the reading id
   */
  public function fetchQuestion($idString) {
    $question = $this->database->select('tao_iching_readings', 'n')
      ->fields('n', ['question'])
      ->condition('n.id', $idString, '=')
      ->execute()
      ->fetchField();
    ($question) ? $return = $question : $return = '';
    return $return;
  }

  /**
   * checks if a unix timestamp is older than our storage time limit
   */
  public function checkTimestamp($timeStamp) {
    $currentTime = time();
    $lifespanValue = \Drupal::config('tao_iching.adminsettings')->get('lifespan');
    /* seconds in a day */
    $setInterval = intval($lifespanValue) * 86400;
    /* set future expire time */
    $futureTime = intval($timeStamp) + intval($setInterval);
    $return = ($currentTime > $futureTime) ? TRUE : FALSE;
    return $return;
  }

  /**
   * inserts a trigram to the lines table
   */
  public function insertLine($readingId, $throw_num, $line, $tri_name, $code, $coinsval) {
    $return = FALSE;
    if ( !empty($readingId)
      && !empty($throw_num)
      && !empty($line)
      && !empty($tri_name)
      && !empty($code)
      && !empty($coinsval) ) {
      if ($this->readingExist($readingId)) {
        $this->database->insert('tao_iching_lines')
          ->fields([
            'id' => $readingId,
            'throw_num' => $throw_num,
            'line' => $line,
            'tri_name' => $tri_name,
            'code' => $code,
            'coinsval' => $coinsval,
          ])
          ->execute();
       $return = TRUE ;
      }
    }
    return $return;
  }

  /**
   * @param $id
   * @return mixed
   *
   * Checks if a reading exists
   */
  public function readingExist($id) {
    $readingId = $this->database->select('tao_iching_readings', 'n')
      ->fields('n', ['id'])
      ->condition('n.id', $id, '=')
      ->execute()
      ->fetchField();
    ($readingId !== FALSE) ? $return = TRUE : $return = FALSE;
    return $return;
  }

  /**
   * deletes a reading from the database
   */
  public function deleteReading($id) {
    $lines_deleted = $this->database->delete('tao_iching_lines')
      ->condition('id', $id, '=')
      ->execute();
    $readings_deleted = $this->database->delete('tao_iching_readings')
      ->condition('id', $id, '=')
      ->execute();
    ($lines_deleted != FALSE && $readings_deleted != FALSE) ? $return = TRUE : $return = FALSE;
    return $return;
  }

  /**
   * @return void
   *
   * deletes all the readings from the database
   */
  public function deleteAllReadings() {
    $idArray = $this->database->select('tao_iching_readings', 'n')
      ->fields('n', ['id'])
      ->execute()
      ->fetchAll();
    $arrayCount = count($idArray);
    foreach ($idArray as $value) {
      $this->deleteReading($value->id);
    }
    \Drupal::messenger()->addStatus($arrayCount . " readings have been deleted.");
  }

  /**
   * checks the number of lines in a reading
   */
  public function checkNumber($id) {
    $count = $this->database->select('tao_iching_lines', 'n')
      ->fields('n', array('id'))
      ->condition('n.id', $id, '=')
      ->countQuery()
      ->execute()
      ->fetchField();
    ($count) ? $return = $count : $return = FALSE;
    return $return;
  }

  /**
   * initializes a reading
   */
  public function readingInit($idArray, $user_name = NULL, $question = '') {
    $return = FALSE;
    if ( !empty($idArray) ) {
      $this->database->insert('tao_iching_readings')
        ->fields([
          'id' => $idArray['id'],
          'user_name' => $user_name,
          'question' => $question,
          'timestamp' => $idArray['timestamp'],
        ])
        ->execute();
      $return = $idArray['timestamp'];
    }
    return $return;
  }

  /**
   * returns the current I-Ching object
   */
  public function myIching($id) {
    if ($this->readingExist($id)) {
      // get the question
      $qQuery = $this->database->select('tao_iching_readings', 'n');
      $qQuery->fields('n', array('question'));
      $qQuery->condition('n.id', $id, '=');
      $question = $qQuery->execute()->fetchField();
      // get the lines created
      $query = $this->database->select('tao_iching_lines', 'n');
      $query->fields('n', array('throw_num', 'line', 'tri_name', 'code', 'coinsval'));
      $query->condition('n.id', $id, '=');
      $lines = $query->execute()->fetchAll();
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
   * @param $hexagram
   * @return mixed
   *
   *
   * returns the book number when passed a hexagram array
   */
  public function findBooknum($hexagram) {
    $query = $this->database->select('tao_iching_hexagrams', 'n');
    $query->addField('n', 'book_number');
    $query->condition('n.line1', $hexagram[0]['line'], '=');
    $query->condition('n.line2', $hexagram[1]['line'], '=');
    $query->condition('n.line3', $hexagram[2]['line'], '=');
    $query->condition('n.line4', $hexagram[3]['line'], '=');
    $query->condition('n.line5', $hexagram[4]['line'], '=');
    $query->condition('n.line6', $hexagram[5]['line'], '=');
    $bookNumber = $query->execute()->fetchField();
    return $bookNumber;
  }

  /**
   * @param $hexagram
   * @return mixed
   *
   * returns the book contents when passed a hexagram array
   */
  public function findBook($hexagram) {
    $bookNumber = $this->findBooknum($hexagram);
    $query = $this->database->select('tao_iching_books', 'n');
    $query->fields('n', array(
      'descr',
      'judge',
      'image',
      'line_one',
      'line_two',
      'line_three',
      'line_four',
      'line_five',
      'line_six'));
    $query->condition('n.number', $bookNumber, '=');
    $bookArray = $query->execute()->fetchAssoc();
    return $bookArray;
  }

  /**
   * @return void
   *
   */
  public function cleanIchingDb() {
    $idArray = $this->database->select('tao_iching_readings' ,'i')
      ->fields('i', ['id'])
      ->execute()
      ->fetchAll();
    foreach ($idArray as $value) {
      if ($this->checkNumber($value->id) != 6) {
        $this->deleteReading($value->id);
      }
    }
  }

  /**
   * @return array[]
   *
   */
  public function createTaoTeChings() {
    return [
      0 =>['One', 'The Tao that can be told is not the eternal Tao.<br>The name that can be named is not the eternal name.<br>The nameless is the beginning of heaven and earth.<br>The named is the mother of ten thousand things.<br>Ever desireless, one can see the mystery.<br>Ever desiring, one can see the manifestations.<br>These two spring from the same source but differ in name;<br>This appears as darkness.<br>Darkness within darkness.<br>The gate to all mystery.'],
      1 =>['Two', 'Under heaven all can see beauty as beauty only because there is ugliness.<br>All can know good as good only because there is evil.<br><br>Therefore having and not having arise together;<br>Difficult and easy complement each other;<br>Long and short contrast each other;<br>High and low rest upon each other;<br>Voice and sound harmonize each other;<br>Front and back follow one another.<br><br>Therefore the sage goes about doing nothing, teaching no-talking.<br>The ten thousand things rise and fall without cease,<br>Creating, yet not possessing,<br>Working, yet not taking credit.<br>Work is done, then forgotten.<br>Therefore it lasts forever.'],
      2 =>['Three', 'Not exalting the gifted prevents quarreling.<br>Not collecting treasures prevents stealing.<br>Not seeing desirable things prevents confusion of the heart.<br><br>The wise therefore rule by emptying hearts and stuffing bellies,<br>By weakening ambitions and strengthening bones.<br>If people lack knowledge and desire,<br>Then intellectuals will not try to interfere.<br>If nothing is done, then all will be well.'],
      3 =>['Four', 'The Tao is an empty vessel; it is used, but never filled.<br>Oh, unfathomable source of ten thousand things!<br>Blunt the sharpness,<br>Untangle the knot,<br>Soften the glare,<br>Merge with dust.<br>Oh, hidden deep but ever present!<br>I do not know from whence it comes.<br>It is the forefather of the ancestors.'],
      4 =>['Five', 'Heaven and earth are ruthless;<br>They see the ten thousand things as dummies.<br>The wise are ruthless;<br>They see the people as dummies.<br><br>The space between heaven and earth is like a bellows.<br>The shape changes but not the form;<br>The more it moves, the more it yields.<br>More words count less.<br>Hold fast to the center.'],
      5 =>['Six', 'The valley spirit never dies;<br>It is the woman, primal mother.<br>Her gateway is the root of heaven and earth.<br>It is like a veil barely seen.<br>Use it; it will never fail.'],
      6 =>['Seven', 'Heaven and earth last forever.<br>Why do heaven and earth last forever?<br>They are unborn,<br>So ever living.<br>The sage stays behind, thus he is ahead.<br>He is detached, thus at one with all.<br>Through selfless action, he attains fulfillment.'],
      7 =>['Eight', 'The highest good is like water.<br>Water gives life to the ten thousand things and does not strive.<br>It flows in places people reject and so is like the Tao.<br><br>In dwelling, be close to the land.<br>In meditation, go deep in the heart.<br>In dealing with others, be gentle and kind.<br>In speech, be true.<br>In ruling, be just.<br>In business, be competent.<br>In action, watch the timing.<br><br>No fight: No blame.'],
      8 =>['Nine', 'Better stop short than fill to the brim.<br>Oversharpen the blade, and the edge will soon blunt.<br>Amass a store of gold and jade, and no one can protect it.<br>Claim wealth and titles, and disaster will follow.<br>Retire when the work is done.<br>This is the way of heaven.'],
      9 =>['Ten', 'Carrying body and soul and embracing the one,<br>Can you avoid separation?<br>Attending fully and becoming supple,<br>Can you be as a newborn babe?<br>Washing and cleansing the primal vision,<br>Can you be without stain?<br>Loving all men and ruling the country,<br>Can you be without cleverness?<br>Opening and closing the gates of heaven,<br>Can you play the role of woman?<br>Understanding and being open to all things,<br>Are you able to do nothing?<br>Giving birth and nourishing,<br>Bearing yet not possessing,<br>Working yet not taking credit,<br>Leading yet not dominating,<br>This is the Primal Virtue.'],
      10 =>['Eleven', 'Thirty spokes share the wheel’s hub;<br>It is the center hole that makes it useful.<br>Shape the clay into a vessel;<br>It is the space within that makes it useful.<br>Cut doors and windows for a room;<br>It is the holes which make it useful.<br>Therefore profit comes from what is there;<br>Usefulness from what is not there.'],
      11 =>['Twelve', 'The five colors blind the eye.<br>The five tones deafen the ear.<br>The five flavors dull the taste.<br>Racing and hunting madden the mind,<br>Precious things lead one astray.<br><br>Therefore the sage is guided by what he feels and not by what he sees,<br>He lets go of that and chooses this.'],
      12 =>['Thirteen', 'Accept disgrace willingly.<br>Accept misfortune as the human condition.<br><br>What do you mean by “Accept disgrace willingly”?<br>Accept being unimportant.<br>Do not be concerned with loss or gain.<br>This is called “accepting disgrace willingly.”<br><br>What do you mean by “Accept misfortune as the human condition”?<br>Misfortune comes from having a body.<br>Without a body, how could there be misfortune?<br><br>Surrender yourself humbly; then you can be trusted to care for all things.<br>Love the world as your own self; then you can truly care for all things.'],
      13 =>['Fourteen', 'Look, it cannot be seen—it is beyond form.<br>Listen, it cannot be heard—it is beyond sound.<br>Grasp, it cannot be held—it is intangible.<br>These three are indefinable;<br>Therefore they are joined in one.<br><br>From above it is not bright;<br>From below it is not dark:<br>An unbroken thread beyond description.<br>It returns to nothingness.<br>The form of the formless,<br>The image of the imageless,<br>It is called indefinable and beyond imagination.<br><br>Stand before it and there is no beginning.<br>Follow it and there is no end.<br>Stay with the ancient Tao,<br>Move with the present.<br><br>Knowing the ancient beginning is the essence of Tao.'],
      14 =>['Fifteen', 'The ancient masters were subtle, mysterious, profound, responsive.<br>The depth of their knowledge is unfathomable.<br>Because it is unfathomable,<br>All we can do is describe their appearance.<br>Watchful, like men crossing a winter stream.<br>Alert, like men aware of danger.<br>Courteous, like visiting guests.<br>Yielding, like ice about to melt.<br>Simple, like uncarved blocks of wood.<br>Hollow, like caves.<br>Opaque, like muddy pools.<br><br>Who can wait quietly while the mud settles?<br>Who can remain still until the moment of action?<br>Observers of the Tao do not seek fulfillment.<br>Not seeking fulfillment, they are not swayed by desire for change.'],
      15 =>['Sixteen', 'Empty yourself of everything.<br>Let the mind rest at peace.<br>The ten thousand things rise and fall while the Self watches their return.<br>They grow and flourish and then return to the source.<br>Returning to the source is stillness, which is the way of nature.<br>The way of nature is unchanging.<br>Knowing constancy is insight.<br>Not knowing constancy leads to disaster.<br>Knowing constancy, the mind is open.<br>With an open mind, you will be openhearted.<br>Being openhearted, you will act royally.<br>Being royal, you will attain the divine.<br>Being divine, you will be at one with the Tao.<br>Being at one with the Tao is eternal.<br>And though the body dies, the Tao will never pass away.'],
      16 =>['Seventeen', 'The very highest is barely known by men.<br>Then comes that which they know and love,<br>Then that which is feared,<br>Then that which is despised.<br><br>He who does not trust enough will not be trusted.<br><br>When actions are performed<br>Without unnecessary talk,<br>People say, “We did it!”'],
      17 =>['Eighteen', 'When the great Tao is forgotten,<br>Kindness and morality arise.<br>When wisdom and intelligence are born,<br>The great pretense begins.<br><br>When there is no peace within the family,<br>Filial piety and devotion arise.<br>When the country is confused and in chaos.<br>Loyal ministers appear.'],
      18 =>['Nineteen', 'Give up sainthood, renounce wisdom,<br>And it will be a hundred times better for everyone.<br><br>Give up kindness, renounce morality,<br>And men will rediscover filial piety and love.<br><br>Give up ingenuity, renounce profit,<br>And bandits and thieves will disappear.<br><br>These three are outward forms alone: they are not sufficient in themselves.<br>It is more important<br>To see the simplicity,<br>To realize our true nature,<br>To cast off selfishness<br>And temper desire.'],
      19 =>['Twenty', 'Give up learning, and put an end to your troubles.<br><br>Is there a difference between yes and no?<br>Is there a difference between good and evil?<br>Must I fear what others fear? What nonsense!<br>Other people are contented, enjoying the sacrificial feast of the ox.<br>In spring some go to the park and climb the terrace,<br>But I alone am drifting, not knowing where I am.<br>Like a newborn babe before it learns to smile,<br>I am alone, without a place to go.<br><br>Others have more than they need, but I alone have nothing.<br>I am a fool. Oh, yes! I am confused.<br>Other men are clear and bright,<br>But I alone am dim and weak.<br>Other men are sharp and clever,<br>But I alone am dull and stupid.<br>Oh, I drift like the waves of the sea,<br>Without direction, like the restless wind.<br><br>Everyone else is busy,<br>But I alone am aimless and depressed.<br>I am different.<br>I am nourished by the great mother.'],
      20 =>['Twenty-One', 'The greatest Virtue is to follow Tao and Tao alone.<br>The Tao is elusive and intangible.<br>Oh, it is intangible and elusive, and yet within is image.<br>Oh, it is elusive and intangible, and yet within is form.<br>Oh, it is dim and dark, and yet within is essence.<br>This essence is very real, and therein lies faith.<br>From the very beginning until now its name has never been forgotten.<br>Thus I perceive the creation.<br>How do I know the ways of creation?<br>Because of this.'],
      21 =>['Twenty-Two', 'Yield and overcome;<br>Bend and be straight;<br>Empty and be full;<br>Wear out and be new;<br>Have little and gain;<br>Have much and be confused.<br><br>Therefore wise men embrace the one<br>And set an example to all.<br>Not putting on a display,<br>They shine forth.<br>Not justifying themselves,<br>They are distinguished.<br>Not boasting,<br>They receive recognition.<br>Not bragging,<br>They never falter.<br>They do not quarrel,<br>So no one quarrels with them.<br>Therefore the ancients say, “Yield and overcome.”<br>Is that an empty saying?<br>Be truly whole,<br>And all things will come to you.'],
      22 =>['Twenty-Three', 'To talk little is natural.<br>High winds do not last all morning.<br>Heavy rain does not last all day.<br>Why is this? Heaven and earth!<br>If heaven and earth cannot make things last forever,<br>How is it possible for man?<br><br>He who follows the Tao<br>Is at one with the Tao.<br>He who is virtuous<br>Experiences Virtue.<br>He who loses the way<br>Feels lost.<br>When you are at one with the Tao,<br>The Tao welcomes you.<br>When you are at one with Virtue,<br>Virtue is always there.<br>When you are at one with loss,<br>Loss is experienced willingly.<br><br>He who does not trust enough<br>Will not be trusted.'],
      23 =>['Twenty-Four', 'He who stands on tiptoe is not steady.<br>He who strides cannot maintain the pace.<br>He who makes a show is not enlightened.<br>He who is self-righteous is not respected.<br>He who boasts achieves nothing.<br>He who brags will not endure.<br>According to followers of the Tao,<br> “These are unnecessary food and luggage.”<br>They do not bring happiness.<br>Therefore followers of the Tao avoid them.'],
      24 =>['Twenty-Five', 'Something mysteriously formed,<br>Born before heaven and earth.<br>In the silence and the void,<br>Standing alone and unchanging,<br>Ever present and in motion.<br>Perhaps it is the mother of ten thousand things.<br>I do not know its name.<br>Call it Tao.<br>For lack of a better word, I call it great.<br><br>Being great, it flows.<br>It flows far away.<br>Having gone far, it returns.<br><br>Therefore, “Tao is great;<br>Heaven is great;<br>Earth is great;<br>The king is also great.”<br>These are the four great powers<br> of the universe,<br>And the king is one of them.<br><br>Man follows the earth.<br>Earth follows heaven.<br>Heaven follows the Tao.<br>Tao follows what is natural.'],
      25 =>['Twenty-Six', 'The heavy is the root of the light;<br>The still is the master of unrest.<br><br>Therefore the sage, traveling all day,<br>Does not lose sight of his baggage.<br>Though there are beautiful things to be seen,<br>He remains unattached and calm.<br><br>Why should the lord of ten thousand chariots act lightly in public?<br>To be light is to lose one`s root.<br>To be restless is to lose one`s control.'],
      26 =>['Twenty-Seven', 'A good walker leaves no tracks;<br>A good speaker makes no slips;<br>A good reckoner needs no tally.<br>A good door needs no lock,<br>Yet no one can open it.<br>Good binding requires no knots,<br>Yet no one can loosen it.<br><br>Therefore the sage takes care of all men<br>And abandons no one.<br>He takes care of all things<br>And abandon nothing.<br><br>This is called “following the light.”<br><br>What is a good man?<br>The teacher of a bad man.<br>What is a bad man?<br>A good man’s charge.<br>If the teacher is not respected,<br>And the student not cared for,<br>Confusion will arise, however clever one is.<br>This is the crux of mystery.'],
      27 =>['Twenty-Eight', 'Know the strength of a man,<br>But keep a woman’s care!<br>Be the stream of the universe!<br>Being the stream of the universe,<br>Ever true and unswerving,<br>Become as a little child once more.<br><br>Know the white,<br>But keep the black!<br>Be an example to the world!<br>Being an example to the world,<br>Ever true and unwavering,<br>Return to the infinite.<br><br>Know honor,<br>Yet keep humility.<br>Be the valley of the universe!<br>Being the valley of the universe,<br>Ever true and resourceful,<br>Return to the state of the uncarved block.<br><br>When the block is carved, it becomes useful.<br>When the sage uses it, he becomes the ruler.<br>Thus, “A great tailor cuts little.”'],
      28 =>['Twenty-Nine', 'Do you think you can take over the universe and improve it?<br>I do not believe it can be done.<br><br>The universe is sacred.<br>You cannot improve it.<br>If you try to change it, you will ruin it.<br>If you try to hold on to it, you will lose it.<br><br>So sometimes things are ahead and sometimes they are behind;<br>Sometimes breathing is hard, sometimes it comes easily;<br>Sometimes there is strength, and sometimes weakness;<br>Sometimes one is up and sometimes down.<br><br>Therefore the sage avoids extremes, excesses, and complacency.'],
      29 =>['Thirty', 'Whenever you advise a ruler in the way of Tao,<br>Counsel him not to use force to conquer the universe.<br>For this would only cause resistance.<br>Thorn bushes spring up wherever the army has passed.<br>Lean years follow in the wake of a great war.<br>Just do what needs to be done.<br>Never take advantage of power.<br><br>Achieve results,<br>But never glory in them.<br>Achieve results,<br>But never boast.<br>Achieve results,<br>But never be proud.<br>Achieve results,<br>Because this is the natural way.<br>Achieve results,<br>But not through violence.<br><br>Force is followed by loss of strength.<br>This is not the way of the Tao.<br>That which goes against the Tao<br> comes to an early end.'],
      30 =>['Thirty-One', 'Good weapons are instruments of fear; all creatures hate them.<br>Therefore followers of Tao never use them.<br>The wise man prefers the left.<br>The man of war prefers the right.<br><br>Weapons are instruments of fear; they are not a wise man`s tools.<br>He uses them only when he has no choice.<br>Peace and quiet are dear to his heart,<br>And victory no cause for rejoicing.<br>If you rejoice in victory, then you delight in killing;<br>If you delight in killing, you cannot fulfill yourself.<br><br>On happy occasions precedence is given to the left,<br>On sad occasions to the right.<br>In the army the general stands on the left,<br>The commander-in-chief on the right.<br>This means that war is conducted like a funeral.<br>When many people are killed,<br>They should be mourned in heartfelt sorrow.<br>That is why a victory must be observed like a funeral.'],
      31 =>['Thirty-Two', 'The Tao is forever undefined.<br>Small though it is in the unformed state, it cannot be grasped.<br>If kings and lords could harness it,<br>The ten thousand things would naturally obey.<br>Heaven and earth would come together<br>And gentle rain fall.<br>Men would need no more instruction and all things would take their course.<br><br>Once the whole is divided, the parts need names.<br>There are already enough names.<br>One must know when to stop.<br>Knowing when to stop averts trouble.<br>Tao in the world is like a river flowing home to the sea.'],
      32 =>['Thirty-Three', 'Knowing others is wisdom;<br>Knowing the self is enlightenment.<br>Mastering others requires force;<br>Mastering the self needs strength.<br><br>He who knows he has enough is rich.<br>Perseverance is a sign of willpower.<br>He who stays where he is endures.<br>To die but not to perish is to be eternally present.'],
      33 =>['Thirty-Four', 'The great Tao flows everywhere, both to the left and to the right.<br>The ten thousand things depend on it; it holds nothing back.<br>It fulfills its purpose silently and makes no claim.<br><br>It nourishes the ten thousand things,<br>And yet is not their lord.<br>It has no aim; it is very small.<br><br>The ten thousand things return to it,<br>Yet it is not their lord.<br>It is very great.<br><br>It does not show greatness,<br>And is therefore truly great.'],
      34 =>['Thirty-Five', 'All men will come to him who keeps to the one,<br>For there lie rest and happiness and peace.<br><br>Passersby may stop for music and good food,<br>But a description of the Tao<br>Seems without substance or flavor,<br>It cannot be seen, it cannot be heard,<br>And yet it cannot be exhausted.'],
      35 =>['Thirty-Six', 'That which shrinks<br>Must first expand.<br>That which fails<br>Must first be strong.<br>That which is cast down<br>Must first be raised.<br>Before receiving<br>There must be giving.<br><br>This is called perception of the nature of things.<br>Soft and weak overcome hard and strong.<br><br>Fish cannot leave deep water,<br>And a country’s weapons should not be displayed.'],
      36 =>['Thirty-Seven', 'Tao abides in non-action,<br>Yet nothing is left undone.<br>If kings and lords observed this,<br>The ten thousand things would develop naturally.<br>If they still desired to act,<br>They would return to the simplicity of formless substance.<br>Without form there is no desire.<br>Without desire there is tranquillity.<br>And in this way all things would be at peace.'],
      37 =>['Thirty-Eight', 'A truly good man is not aware of his goodness,<br>And is therefore good.<br>A foolish man trys to be good,<br>And is therefore not good.<br><br>A truely good man does nothing,<br>Yet leaves nothing undone.<br>A foolish man is always doing,<br>Yet much remains to be done.<br><br>When a truely kind man does something, he leaves nothing undone.<br>When a just man does something, he leaves a great deal to be done.<br>When a diciplinarian does something and no one responds,<br>He rolls up his sleeves in an attempt to enforce order.<br><br>Therefore when Tao is lost, there is goodness.<br>When goodness is lost, there is kindness.<br>When kindness is lost, there is justice.<br>When justice is lost, there is ritual.<br>Now ritual is the husk of faith and loyalty, the beginning of confusion.<br>Knowledge of the future is only a flowery trapping of Tao.<br>It is the beginning of folly.<br><br>Therefore the truly great man dwells on what is real and not what is on the surface,<br>On the fruit and not the flower.<br>Therefore accept the one and reject the other.'],
      38 =>['Thirty-Nine', 'These things from ancient times arise from one:<br>The sky is whole and clear.<br>The earth is whole and firm.<br>The spirit is whole and strong.<br>The valley is whole and full.<br>The ten thousand things are whole and alive.<br>Kings and lords are whole, and the country is upright.<br>All these are in virtue of wholeness.<br><br>The clarity of the sky prevents it from falling.<br>The firmness of the earth prevents it from splitting.<br>The strength of the spirit prevents it from being exhausted.<br>The fullness of the valley prevents it`s running dry.<br>The growth of the ten thousand things prevents their dying out.<br>The leadership of kings and lords prevents the downfall<br> of the country.<br><br>Therefore the humble is the root of the noble.<br>The low is the foundation of the high.<br>Princes and lords consider themselves “orphaned,” “widowed,” and “worthless.”<br>Do they not depend on being humble?<br><br>Too much success is not an advantage.<br>Do not tinkle like jade<br>Or clatter like stone chimes.'],
      39 =>['Forty', 'Returning is the motion of the Tao.<br>Yielding is the way of the Tao.<br>The ten thousand things are born of being.<br>Being is born of not being.'],
      40 =>['Forty-One', 'The wise student hears of the Tao and practices it diligently.<br>The average student hears of the Tao and gives it a thought now and again.<br>The foolish student hears of the Tao and laughs aloud<br>If there were no laughter, the Tao would not be what it is.<br><br>Hence it is said:<br>The bright path seems dim;<br>Going forward seems like retreat;<br>The easy way seems hard;<br>The highest Virtue seems empty;<br>Great purity seems sullied;<br>A wealth of Virtue seems inadequate;<br>The strength of Virtue seems frail;<br>Real Virtue seems unreal;<br>The perfect square has no corners;<br>Great talents ripen late;<br>The highest notes are hard to hear;<br>The greatest form has no shape.<br>The Tao is hidden and without name.<br>The Tao alone nourishes and brings everything to fulfillment.'],
      41 =>['Forty-Two', 'The Tao begot one.<br>One begot two.<br>Two begot three.<br>And three begot the ten thousand things.<br><br>The ten thousand things carry yin and embrace yang.<br>They achieve harmony by combining these forces.<br>Men hate to be “orphaned,” “widowed,” or “worthless,”<br>But this is how kings and lords describe themselves.<br><br>For one gains by losing<br>And loses by gaining.<br><br>What others teach, I also teach; that is:<br>“A violent man will die a violent death!”<br>This will be the essence of my teaching.'],
      42 =>['Forty-Three', 'The softest thing in the universe<br>Overcomes the hardest thing in the universe.<br>That without substance can enter where there is no room.<br>Hence I know the value of non-action.<br><br>Teaching without words and working without doing<br>Are understood by very few.'],
      43 =>['Forty-Four', 'Fame or self: Which matters more?<br>Self or wealth: Which is more precious?<br>Gain or loss: Which is more painful?<br><br>He who is attached to things will suffer much.<br>He who saves will suffer heavy loss.<br>A contented man is never disappointed.<br>He who knows when to stop does not find himself in trouble.<br>He will stay forever safe.'],
      44 =>['Forty-Five', 'Great accomplishment seems imperfect,<br>Yet it does not outlive its usefulness.<br>Great fullness seems empty,<br>Yet it cannot be exhausted.<br><br>Great straightness seems twisted.<br>Great intelligence seems stupid.<br>Great eloquence seems awkward.<br><br>Movement overcomes cold.<br>Stillness overcomes heat.<br>Stillness and tranquillity set things in order in the universe.'],
      45 =>['Forty-Six', 'When the Tao is present in the universe,<br>The horses haul manure.<br>When the Tao is absent from the universe,<br>War horses are bred outside the city.<br><br>There is no greater sin than desire,<br>No greater curse than discontent,<br>No greater misfortune than wanting something for oneself.<br>Therefore he who know that enough is enough will always have enough.'],
      46 =>['Forty-Seven', 'Without going outside, you may know the whole world.<br>Without looking through the window, you may see the ways of heaven.<br>The farther you go, the less you know.<br><br>Thus the sage knows without traveling;<br>He sees without looking;<br>He works without doing.'],
      47 =>['Forty-Eight', 'In the pursuit of learning, every day something is acquired.<br>In the pursuit of the Tao, every day something is dropped.<br><br>Less and less is done<br>Until non-action is achieved.<br>When nothing is done, nothing is left undone.<br><br>The world is ruled by letting things take their course.<br>It cannot be ruled by interfering.'],
      48 =>['Forty-Nine', 'The sage has no mind of his own.<br>He is aware of the needs of others.<br><br>I am good to people who are good.<br>I am also good to people who are not good<br>Because Virtue is goodness.<br>I have faith in people who are faithful.<br>I also have faith in people who are not faithful<br>Because Virtue is faithfulness.<br><br>The sage is shy and humble --to the world he seems confusing.<br>Men look to him and listen.<br>He behaves like a little child.<br><br>'],
      49 =>['Fifty', 'Between birth and death,<br>Three in ten are followers of life,<br>Three in ten are followers of death,<br>And men just passing from birth to death also number three in ten.<br>Why is this so?<br>Because they live their lives on the gross level.<br><br>He who knows how to live can walk abroad<br>Without fear of rhinoceros or tiger.<br>He will not be wounded in battle.<br>For in him the rhinoceroses can find no place to thrust their horn,<br>Tigers no place to use their claws,<br>And weapons no place to pierce.<br>Why is this so?<br>Because he has no place for death to enter.'],
      50 =>['Fifty-One', 'All things arise from Tao.<br>They are nourished by Virtue.<br>They are formed from matter.<br>They are shaped by environment.<br>Thus the ten thousand things respect Tao and honor Virtue.<br>Respect of Tao and honor of Virtue are not demanded,<br>But they are in the nature of things.<br><br>Therefore all things arise from Tao.<br>By Virtue they are nourished,<br>Developed, cared for,<br>Sheltered, comforted,<br>Grown, and protected.<br>Creating without claiming,<br>Doing without taking credit,<br>Guiding without interfering.<br>This is Primal Virtue.'],
      51 =>['Fifty-Two', 'The beginning of the universe<br>Is the mother of all things.<br>Knowing the mother, one also knows the sons.<br>Knowing the sons, yet remaining in touch with the mother,<br>Brings freedom from the fear of death.<br><br>Keep your mouth shut,<br>Guard the senses,<br>And life is ever full.<br>Open your mouth,<br>Always be busy,<br>And life is beyond hope.<br><br>Seeing the small is insight;<br>Yielding to force is strength.<br>Using the outer light, return to insight,<br>And in this way be saved from harm.<br>This is learning constancy.'],
      52 =>['Fifty-Three', 'If I have even just a little sense,<br>I will walk on the main road and my only fear will be of straying from it.<br>Keeping to the main road is easy,<br>But people love to be sidetracked.<br><br>When the court is arrayed in splendor,<br>The fields are full of weeds,<br>And the granaries are bare.<br>Some wear gorgeous clothes,<br>Carry sharp swords,<br>And indulge themselves with food and drink;<br>They have more possessions than they can use.<br>They are robber barons.<br>This is certainly not the way of Tao.'],
      53 =>['Fifty-Four', 'What is firmly established cannot be uprooted.<br>What is firmly grasped cannot slip away.<br>It will be honored from generation to generation.<br><br>Cultivate Virtue in yourself,<br>And Virtue will be real.<br>Cultivate it in the family,<br>And Virtue will abound.<br>Cultivate it in the village,<br>And Virtue will grow.<br>Cultivate it in the nation,<br>And Virtue will be abundant.<br>Cultivate it in the universe,<br>And Virtue will be everywhere.<br><br>Therefore look at the body as body;<br>Look at the family as family;<br>Look at the village as village;<br>Look at the nation as nation;<br>Look at the universe as universe.<br><br>How do I know the universe is like this?<br>By looking!'],
      54 =>['Fifty-Five', 'He who is filled with Virtue is like a newborn child.<br>Wasps and serpents will not sting him;<br>Wild beasts will not pounce upon him;<br>He will not be attacked by birds of prey.<br>His bones are soft, his muscles weak,<br>But his grip is firm.<br>He has not experienced the union of man and woman, but is whole.<br>His manhood is strong.<br>He screams all day without becoming hoarse.<br>This is perfect harmony.<br><br>Knowing harmony is constancy.<br>Knowing constancy is enlightenment.<br><br>It is not wise to rush about.<br>Controlling the breath causes strain.<br>If too much energy is used, exhaustion follows.<br>This is not the way of Tao.<br>Whatever is contrary to Tao will not last long.'],
      55 =>['Fifty-Six', 'Those who know do not talk.<br>Those who talk do not know.<br><br>Keep your mouth closed.<br>Guard your senses.<br>Temper your sharpness.<br>Simplify your problems.<br>Mask your brightness.<br>Be at one with the dust of the earth.<br>This is primal union.<br><br>He who has achieved this state<br>Is unconcerned with friends and enemies,<br>With good and harm, with honor and disgrace.<br>This therefore is the highest state of man.'],
      56 =>['Fifty-Seven', 'Rule a nation with justice.<br>Wage war with surprise moves.<br>Become master of the universe without striving.<br>How do I know that this is so?<br>Because of this!<br><br>The more laws and restrictions there are,<br>The poorer people become.<br>The sharper men’s weapons,<br>The more trouble in the land.<br>The more ingenious and clever men are,<br>The more strange things happen.<br>The more rules and regulations,<br>The more thieves and robbers.<br><br>Therefore the sage says:<br>“I take no action and people are reformed.<br>I enjoy peace and people become honest.<br>I do nothing and people become rich.<br>I have no desires and people return to the good and simple life.”'],
      57 =>['Fifty-Eight', 'When the country is ruled with a light hand<br>The people are simple.<br>When the country is ruled with severity,<br>The people are cunning.<br><br>Happiness is rooted in misery.<br>Misery lurks beneath happiness.<br>Who knows what the future holds?<br>There is no honesty.<br>Honesty becomes dishonest.<br>Goodness becomes witchcraft.<br>Man’s bewitchment lasts for a long time.<br><br>Therefore the sage is sharp but not cutting,<br>Pointed but not piercing,<br>Straightforward but not unrestrained,<br>Brilliant but not blinding.'],
      58 =>['Fifty-Nine', 'In caring for others and serving heaven,<br>There is nothing like using restraint.<br>Restraint begins with giving up one`s own ideas.<br>This depends on Virtue gathered in the past.<br>If there is a good store of Virtue, then nothing is impossible.<br>If nothing is impossible, then there are no limits.<br>If a man knows no limits, then he is fit to be a ruler.<br>The mother principle of ruling holds good for a long time.<br>This is called having deep roots and a firm foundation,<br>The Tao of long life and eternal vision.'],
      59 =>['Sixty', 'Ruling the country is like cooking a small fish.<br>Approach the universe with Tao,<br>And evil will have no power.<br>Not that evil is not powerful,<br>But its power will not be used to harm others.<br>Not only will it do no harm to others,<br>But the sage himself will also be protected.<br>They do not hurt each other,<br>And the Virtue in each one refreshes both.'],
      60 =>['Sixty-One', 'A great country is like low land.<br>It is the meeting ground of the universe,<br>The mother of the universe.<br><br>The female overcomes the male with stillness,<br>Lying low in stillness.<br><br>Therefore if a great country gives way to a smaller country,<br>It will conquer the smaller country.<br>And if a small country submits to a great country,<br>It can conquer the great country.<br>Therefore those who would conquer must yield,<br>And those who conquer do so because they yield.<br><br>A great nation needs more people;<br>A small country needs to serve.<br>Each gets what it wants.<br>It is fitting for a great nation to yield.'],
      61 =>['Sixty-Two', 'Tao is the source of the ten thousand things.<br>It is the treasure of the good man and the refuge of the bad.<br>Sweet words can buy honor;<br>Good deeds can gain respect.<br>If a man is bad, do not abandon him.<br>Therefore on the day the emperor is crowned,<br>Or the three officers of state installed,<br>Do not send a gift of jade and a team of four horses,<br>But remain still and offer the Tao.<br>Why does everyone like the Tao so much at first?<br>Isn’t it because you find what you seek and are forgiven when you sin?<br>Therefore this is the greatest treasure in the universe.'],
      62 =>['Sixty-Three', 'Practice non-action.<br>Work without doing.<br>Taste the tasteless.<br>Magnify the small, increase the few.<br>Reward bitterness with care.<br><br>See simplicity in the complicated.<br>Achieve greatness in little things.<br><br>In the universe the difficult things are done as though they were easy.<br>In the universe great acts are made up of small deeds.<br>The sage does not attempt anything very big,<br>And thus achieves greatness.<br><br>Easy promises make for little trust.<br>Taking things lightly results in great difficulty.<br>Because the sage alwayss confront difficulties,<br>He never experiences them.'],
      63 =>['Sixty-Four', 'Peace is easily maintained;<br>Trouble is easily overcome before it starts.<br>The brittle is easily shattered;<br>The small is easily scattered.<br><br>Deal with it before it happens.<br>Set things in order before there is confusion.<br><br>A tree as great as a man’s embrace springs from a small shoot;<br>A terrace nine stories high begins with a pile of earth;<br>A journey of a thousand miles starts under one’s feet.<br><br>He who acts defeats his own purpose;<br>He who grasps loses.<br>The sage does not act and so is not defeated.<br>He does not grasp and therefore does not lose.<br><br>People usually fail when they are on the verge of success.<br>So give as much care to the end as to the beginning;<br>Then there will be no failure.<br><br>Therefore the sage seeks freedom from desire.<br>He does not collect precious things.<br>He learns not to hold on to ideas.<br>He brings men back to what they have lost.<br>He helps the ten thousand things find their own nature,<br>But refrains from action.'],
      64 =>['Sixty-Five', 'In the beginning those who knew the Tao did not try to enlighten others,<br>But kept them in the dark.<br>Why is it so hard to rule?<br>Because the people are so clever.<br>Rulers who try to use cleverness<br>Cheat the country.<br>Those who rule without cleverness<br>Are a blessing to the land.<br>These are the two alternatives.<br>Understanding these is Primal Virtue.<br>Primal Virtue is deep and far.<br>It leads all things back<br>Toward the great oneness.'],
      65 =>['Sixty-Six', 'Why is the sea king of a hundred streams?<br>Because it lies below them.<br>Therefore it is the king of a hundred streams.<br><br>If the sage would guide the people, he must serve with humility.<br>If he would lead them, he must follow behind.<br>In this way when the sage rules, the people will not feel oppressed;<br>When he stands before them, they will not be harmed.<br>The whole world will support him and will not tire of him.<br><br>Because he does not compete,<br>He does not meet competition.'],
      66 =>['Sixty-Seven', 'Everyone under heaven says that my Tao is great and beyond compare.<br>Because it is great, it seems different.<br>If it were not different, it would have vanished long ago.<br><br>I have three treasures which I hold and keep.<br>The first is mercy; the second is economy;<br>The third is daring not to be ahead of others.<br>From mercy comes courage; from economy comes generosity;<br>From humility comes leadership.<br><br>Nowadays men shun mercy but try to be brave;<br>They abandon economy but try to be generous;<br>They do not believe in humility but always try to be first.<br>This is certain death.<br><br>Mercy brings victory in battle and strength in defense.<br>It is the means by which heaven saves and guards.'],
      67 =>['Sixty-Eight', 'A good soldier is not violent.<br>A good fighter is not angry.<br>A good winner is not vengeful.<br>A good employer is humble.<br>This is known as the Virtue of not striving.<br>This is known as the ability to deal with people.<br>This since ancient times has been known<br> as the ultimate unity with heaven.'],
      68 =>['Sixty-Nine', 'There is a saying among soldiers:<br>“I dare not make the first move but would rather play the guest;<br>I dare not advance an inch but would rather withdraw a foot.”<br><br>This is called marching without appearing to move,<br>Rolling up your sleeves without showing your arm,<br>Capturing the enemy without attacking,<br>Being armed without weapons.<br><br>There is no greater catastrophe than underestimating the enemy.<br>By underestimating the enemy, I almost lose what I value.<br><br>Therefore when the battle is joined,<br>The underdog will win.'],
      69 =>['Seventy', 'My words are easy to understand and easy to perform,<br>Yet no man under heaven knows them or practices them<br><br>My words have ancient beginnings.<br>My actions are disciplined.<br>Because men do not understand, they have no knowledge of me.<br><br>Those that know me are few;<br>Those that abuse me are honored.<br>Therefore the sage wears rough clothing and holds the jewel in his heart.'],
      70 =>['Seventy-One', 'Knowing ignorance is strength.<br>Ignoring knowledge is sickness.<br><br>If one is sick of sickness, then one is not sick.<br>The sage is not sick because he is sick of sickness.<br>Therefore he is not sick.'],
      71 =>['Seventy-Two', 'When men lack a sense of awe, there will be disaster.<br><br>Do not intrude in their homes.<br>Do not harass them at work.<br>If you do not interfere, they will not weary of you.<br><br>Therefore the sage knows himself but make no show,<br>Has self-respect but is not arrogant.<br>He lets go of that and chooses this.'],
      72 =>['Seventy-Three', 'A brave and passionate man will kill or be killed.<br>A brave and calm man will always preserve life.<br>Of these two which is good and which is harmful?<br>Some things are not favored by heaven. Who knows why?<br>Even the sage is unsure of this.<br><br>The Tao of heaven does not strive and yet it overcomes.<br>It does not speak and yet is answered.<br>It does not ask, yet is supplied with all its needs.<br>It seems at ease, and yet if follows a plan.<br><br>Heaven’s net casts wide.<br>Though its meshes are coarse, nothing slips through.'],
      73 =>['Seventy-Four', 'If men are not afraid to die,<br>It is of no avail to threaten them with death.<br><br>If men live in constant fear of dying,<br>And if breaking the law means that a man will be killed,<br>Who will dare to break the law?<br><br>There is always an official executioner.<br>If you try to take his place,<br>It is like trying to be a master carpenter and cutting wood.<br>If you try to cut wood like a master carpenter,<br>you will only hurt your hand.'],
      74 =>['Seventy-Five', 'Why are the people starving?<br>Because the rulers eat up the money in taxes.<br>Therefore the people are starving.<br><br>Why are the people rebellious?<br>Because the rulers interfere too much.<br>Therefore they are rebellious.<br><br>Why do the people think so little of death?<br>Because the rulers demand too much of life.<br>Therefore the people take death lightly.<br><br>Having little to live on, one knows better than to value life too much.'],
      75 =>['Seventy-Six', 'A man is born gentle and weak.<br>At his death he is hard and stiff.<br>Green plants are tender and filled with sap.<br>At their death they are withered and dry.<br><br>Therefore the stiff and unbending is the disciple of death.<br>The gentle and yielding is the disciple of life.<br><br>Thus an army without flexibility never wins a battle.<br>A tree that is unbending is easily broken.<br><br>The hard and strong will fall.<br>The soft and weak will overcome.'],
      76 =>['Seventy-Seven', 'The Tao of heaven is like the bending of a bow.<br>The high is lowered and the low is raised.<br>If the string is too long, it is shortened;<br>If there is not enough, it is made longer.<br><br>The Tao of heaven is to take from those who have too much and give to those who do not have enough.<br>Man`s way is different.<br>He takes from those who do not have enough<br>to give to those who already have too much.<br>What man has more than enough and gives it to the world?<br>Only the man of Tao.<br><br>Therefore the sage works without recognition.<br>He achieves what has to be done without dwelling on it.<br>He does not try to show his knowledge.'],
      77 =>['Seventy-Eight', 'Under heaven nothing is more soft and yielding than water.<br>Yet for attacking the solid and strong, nothing is better;<br>It has no equal.<br>The weak can overcome the strong;<br>The supple can overcome the stiff.<br>Under heaven everyone knows this,<br>Yet no one puts it into practice.<br>Therefore the sage says:<br>He who takes upon himself the humiliation of the people, is fit to rule them. He who takes upon himself the country’s disasters deserves to be king of the universe.”<br>The truth often sounds paradoxical.'],
      78 =>['Seventy-Nine', 'After a bitter quarrel, some resentment must remain.<br>What can one do about it?<br>Therefore the sage keeps his half of the bargain<br>But does not exact his due.<br>A man of virtue performs his part,<br>But a man without Virtue requires others to fulfill their obligations.<br>The Tao of heaven is impartial.<br>It stays with good men all the time.'],
      79 =>['Eighty', 'A small country has fewer people.<br>Though there are machines that can work ten to a hundred times faster than man, they are not needed.<br>The people take death seriously and do not travel far.<br>Though they have boats and carriages, no one uses them.<br>Though they have armor and weapons, no one displays them.<br>Men return to the knotting of rope in place of writing.<br>Their food is plain and good, their clothes fine but simple, their homes secure;<br>They are happy in their ways.<br>Though they live within sight of their neighbors,<br>And crowing cocks and barking dogs are heard across the way,<br>Yet they leave each other in peace while they grow old and die.'],
      80 =>['Eighty-One', 'Truthful words are not beautiful.<br>Beautiful words are not truthful.<br>Good men do not argue.<br>Those who argue are not good.<br>Those who know are not learned.<br>The learned do not know.<br><br>The sage never tries to store things up.<br>The more he does for others, the more he has.<br>The more he gives to others, the greater his abundance.<br>The Tao of heaven is sharp but does no harm.<br>The Tao of the sage is work without effort.'],
    ];
  }

  /**
   * @param $array
   * @return true
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   */
  public function createTaoPageNode($array) {
    $newTaoPage = $this->entityTypeManager->getStorage('node')->create(['type' => 'tao_page']);
    $newTaoPage->set('title', $array[0]);
    $newTaoPage->set('body', ['value' => $array[1], 'format' => 'basic_html']);
    $newTaoPage->enforceIsNew();
    $newTaoPage->save();
    return true;
  }







}


