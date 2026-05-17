<?php

namespace Drupal\tao_iching\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\tao_iching\Service\IchingService;
use Drupal\tao_iching\Service\TaocookieService;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The ViewController class.
 */
class ViewController extends ControllerBase {

  /**
   * The I-Ching service.
   *
   * @var \Drupal\tao_iching\Service\IchingService
   */
  protected IchingService $iChing;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The local path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $localPath;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected LoggerChannelFactory $logger;

  /**
   * The Tao Cookie service.
   *
   * @var \Drupal\tao_iching\Service\TaocookieService
   */
  protected TaocookieService $taoCookie;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  private KillSwitch $killSwitch;

  /**
   * Symphony http request stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * The ViewController constructor.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $pathResolver
   *   The local path resolver.
   * @param \Drupal\tao_iching\Service\IchingService $iChingService
   *   The I-Ching service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   The logger factory.
   * @param \Drupal\tao_iching\Service\TaocookieService $taoCookieService
   *   The Tao Cookie service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   The kill switch.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The symphony http request stack.
   *
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   * @throws \Exception
   */
  public function __construct(
    ExtensionPathResolver $pathResolver,
    IchingService $iChingService,
    Connection $connection,
    LoggerChannelFactory $loggerFactory,
    TaocookieService $taoCookieService,
    KillSwitch $killSwitch,
    RequestStack $requestStack,
  ) {
    $this->localPath = $pathResolver;
    $this->iChing = $iChingService;
    $this->database = $connection;
    $this->logger = $loggerFactory;
    $this->taoCookie = $taoCookieService;
    $this->killSwitch = $killSwitch;
    $this->requestStack = $requestStack;
  }

  /**
   * The ViewController create method.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container interface.
   *
   * @return static
   *   An instance of the ViewController class.
   *
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   * @throws \Exception
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.path.resolver'),
      $container->get('tao_iching.service'),
      $container->get('database'),
      $container->get('logger.factory'),
      $container->get('taocookie.service'),
      $container->get('page_cache_kill_switch'),
      $container->get('request_stack'),
    );
  }

  /**
   * The taoIchingViewpage function.
   *
   * @param array $callbackResult
   *   The callback result from submission, retrieves the I-Ching reading.
   *
   * @return array
   *   An array containing the content to be rendered on the results page.
   *
   * @throws \Exception
   */
  public function taoIchingViewpage($callbackResult) {
    if ($this->iChing->readingExist($callbackResult)) {
      $content = $this->sixClicksMethod($callbackResult);
    }
    elseif ($callbackResult == "instant") {
      $content = $this->instantReading();
    }
    else {
      $content = "<div class='iching-blurb'>";
      $content .= "Sorry, that I-Ching reading is not in our database.<br>";
      $content .= "<a href='/i-ching'><b>Click Here to start again.</b></a>";
      $content .= "</div>";
    }
    $element = [
      '#type' => 'markup',
      '#attached' => [
        'library' => [
          'tao_iching/tao_iching.library',
        ],
      ],
      '#markup' => $content,
    ];
    return $element;
  }

  /**
   * The sixClicksMethod function.
   *
   * @param array $callbackResult
   *   The callback result from submission, retrieves the I-Ching reading.
   *
   * @return array
   *   The HTML content to be rendered on the results page.
   */
  public function sixClicksMethod($callbackResult) {
    /* figure out the return path if the transaction breaks */
    $previousPageUrl = $this->requestStack->getCurrentRequest()->headers->get('referer');
    $drupalBaseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $this->requestStack->getCurrentRequest()->getHttpHost();
    $returnPathUri = str_replace($drupalBaseUrl, '', $previousPageUrl);
    $currentPageUri = $this->requestStack->getCurrentRequest()->getRequestUri();
    ($returnPathUri != $currentPageUri) ? $previousPageUri = $returnPathUri : $previousPageUri = $drupalBaseUrl;
    /* clear the page cache for fresh Ajax POST vars */
    $this->killSwitch->trigger();
    $localImagePath = $this->localPath->getPath('module', 'tao_iching') . '/imgs';
    /* retrieve the first hexagram */
    $localChing = $this->iChing->myIching($callbackResult);
    /* generate the final reading */
    $finalChing = $this->iChing->complete($localChing['initial']);
    $finalchg = $finalChing['changed'];
    /* flip the arrays because traditionally I-Ching is read bottom to top */
    $flippedInitial = array_reverse($finalChing['initial']);
    if ($finalchg != "No Change") {
      $flippedChanged = array_reverse($finalChing['changed']);
    }
    /* create the display START .iching-content */
    $content = '<div class="iching-content">';
    $content .= '<div class="sm-hexag">';
    foreach ($flippedInitial as $value) {
      $content .= '<div class="min-line">';
      $content .= '<img src="/' . $localImagePath . '/' . $value['line'] . '_min.png">';
      $content .= '</div>';
    }
    $content .= '</div>';
    $content .= '<div class="sm-hexag">';
    if ($finalChing['changed'] == 'No Change') {
      foreach ($flippedInitial as $value) {
        $content .= '<div class="min-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $value['line'] . '_min.png">';
        $content .= '</div>';
      }
    }
    else {
      foreach ($flippedChanged as $value) {
        $content .= '<div class="min-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $value['line'] . '_min.png">';
        $content .= '</div>';
      }
    }
    $content .= '</div>';
    $content .= '<div class="iching-blurb">';
    $content .= '<a href="' . $previousPageUri . '"><b>Click Here to start again.</b></a>';
    $content .= '</div>';
    $content .= '</div>';
    // END .iching-content.
    /* get rid of the change indicators and display the final hexagram(s) */
    $finalhex = $this->iChing->rawhexCleanup($flippedInitial);
    /* get the book numbers for the hexagram(s) */
    $bookname = $this->iChing->findBooknum($finalhex);
    /* get the book from the first hexagram */
    $origBook = $this->iChing->findBook($finalhex);
    if ($finalchg != 'No Change') {
      $ch_bookname = $this->iChing->findBooknum($flippedChanged);
      /* get the second book if there is one */
      $chBook = $this->iChing->findBook($flippedChanged);
    }
    $content .= '<div class="iching_container">';
    /* print the question */
    if ($finalChing['question'] != NULL) {
      $content .= '<div class="iching-blurb">';
      $content .= $finalChing['question'];
      $content .= '</div>';
    }
    /* Tabs */
    $content .= '<div class="iching-tab">';
    $content .= '<div class="tablinks">Current</div>';
    $content .= '<div class="tablinks">Changing</div>';
    $content .= '<div class="tablinks">Transformed</div>';
    $content .= '</div>';
    /* Current */
    $content .= '<div id="current" class="tabcontent">';
    $content .= '<h3>Current</h3>'; /* CURRENT */
    $content .= '<div id="current-flex">';
    $content .= '<div class="hexag">';
    foreach ($finalhex as $name) {
      $content .= '<div class="sm-line">';
      $content .= '<img src="/' . $localImagePath . '/' . $name['line'] . '_sm.png">';
      $content .= '</div>';
    }
    $content .= '</div>';
    $content .= '<div class="ic-content">';
    $content .= $bookname . ' - ' . $origBook['descr'];
    $content .= '<h4>The Judgement</h4>';
    $content .= $origBook['judge'];
    $content .= '<h4>The Image</h4>';
    $content .= $origBook['image'];
    $content .= '</div>';
    $content .= '</div>';
    // END #current-flex.
    $content .= '</div>';
    // END #current.
    /* Changing */
    $content .= '<div id="changing" class="tabcontent">';
    $content .= '<h3>Changing</h3>'; /* CHANGING */
    $content .= '<div id="changing-flex">';
    if ($finalchg != 'No Change') {
      $content .= '<div class="hexag">';
      foreach ($flippedInitial as $name) {
        $content .= '<div class="sm-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $name['line'] . '_sm.png">';
        $content .= '</div>';
      }
      $content .= '</div>';
      $content .= '<div class="ic-content">';
      $valArray = $this->iChing->findTopChanging($flippedInitial);
      foreach ($valArray as $val) {
        $content .= '<div class="ic-row">';
        $content .= $chBook[$val];
        $content .= '</div>';
      }
      $content .= '</div>';
    }
    else {
      $content .= '<div>';
      $content .= 'There are no changing lines. The situation is expected to remain the same in the near future.';
      $content .= '</div>';
    }
    $content .= '</div>';
    // END #changing-flex.
    $content .= '</div>';
    // END #changing.
    /* Transformed */
    $content .= '<div id="transformed" class="tabcontent">';
    $content .= '<h3>Transformed</h3>'; /* TRANSFORMED */
    $content .= '<div id="transformed-flex">';
    if ($finalchg != 'No Change') {
      $content .= '<div class="hexag">';
      foreach ($flippedChanged as $chgname) {
        $content .= '<div class="sm-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $chgname['line'] . '_sm.png">';
        $content .= '</div>';
      }
      $content .= '</div>';
      $content .= '<div class="ic-content">';
      $content .= $ch_bookname . ' - ' . $chBook['descr'];
      $content .= '<h4>The Judgement</h4>';
      $content .= $chBook['judge'];
      $content .= '<h4>The Image</h4>';
      $content .= $chBook['image'];
      $content .= '</div>';
    }
    else {
      $content .= '<div>';
      $content .= 'There are no changing lines. The situation is expected to remain the same in the near future.';
      $content .= '</div>';
    }
    $content .= '</div>';
    // END #transformed-flex.
    $content .= '</div>';
    // END #transformed.
    $content .= '</div>';
    // END .iching_container.
    return $content;
  }

  /**
   * The instantReading function.
   *
   * @return array
   *   Returns a complete Iching reading.
   */
  public function instantReading() {
    $this->killSwitch->trigger();
    $localImagePath = $this->localPath->getPath('module', 'tao_iching') . '/imgs';
    $finalChing = $this->iChing->complete($this->iChing->hexagram());
    $finalchg = $finalChing['changed'];
    /* flip the arrays because traditionally I-Ching is read bottom to top */
    $flippedInitial = array_reverse($finalChing['initial']);
    if ($finalchg != 'No Change') {
      $flippedChanged = array_reverse($finalChing['changed']);
    }
    /* create the display START .iching-content */
    $content = '<div class="iching-content">';
    $content .= '<div class="sm-hexag">';
    foreach ($flippedInitial as $value) {
      $content .= '<div class="min-line">';
      $content .= '<img src="/' . $localImagePath . '/' . $value['line'] . '_min.png">';
      $content .= '</div>';
    }
    $content .= '</div>';
    $content .= '<div class="sm-hexag">';
    if ($finalChing['changed'] == 'No Change') {
      foreach ($flippedInitial as $value) {
        $content .= '<div class="min-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $value['line'] . '_min.png">';
        $content .= '</div>';
      }
    }
    else {
      foreach ($flippedChanged as $value) {
        $content .= '<div class="min-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $value['line'] . '_min.png">';
        $content .= '</div>';
      }
    }
    $content .= '</div>';
    $content .= '</div>';
    // END .iching-content.
    /* get rid of the change indicators and display the final hexagram(s) */
    $finalhex = $this->iChing->rawhexCleanup($flippedInitial);
    /* get the book numbers for the hexagram(s) */
    $bookname = $this->iChing->findBooknum($finalhex);
    /* get the book from the first hexagram */
    $origBook = $this->iChing->findBook($finalhex);
    if ($finalchg != 'No Change') {
      $ch_bookname = $this->iChing->findBooknum($flippedChanged);
      /* get the second book if there is one */
      $chBook = $this->iChing->findBook($flippedChanged);
    }
    $content .= '<div class="iching_container">';
    /* print the question */
    if ($finalChing['question'] != NULL) {
      $content .= '<div class="iching-blurb">';
      $content .= $finalChing['question'];
      $content .= '</div>';
    }
    /* Tabs */
    $content .= '<div class="iching-tab">';
    $content .= '<div class="tablinks">Current</div>';
    $content .= '<div class="tablinks">Changing</div>';
    $content .= '<div class="tablinks">Transformed</div>';
    $content .= '</div>';
    /* Current */
    $content .= '<div id="current" class="tabcontent">';
    $content .= '<h3>Current</h3>'; /* CURRENT */
    $content .= '<div id="current-flex">';
    $content .= '<div class="hexag">';
    foreach ($finalhex as $name) {
      $content .= '<div class="sm-line">';
      $content .= '<img src="/' . $localImagePath . '/' . $name['line'] . '_sm.png">';
      $content .= '</div>';
    }
    $content .= '</div>';
    $content .= '<div class="ic-content">';
    $content .= $bookname . ' - ' . $origBook['descr'];
    $content .= '<h4>The Judgement</h4>';
    $content .= $origBook['judge'];
    $content .= '<h4>The Image</h4>';
    $content .= $origBook['image'];
    $content .= '</div>';
    $content .= '</div>';
    // END #current-flex.
    $content .= '</div>';
    // END #current.
    /* Changing */
    $content .= '<div id="changing" class="tabcontent">';
    $content .= '<h3>Changing</h3>';
    /* CHANGING */
    $content .= '<div id="changing-flex">';
    if ($finalchg != 'No Change') {
      $content .= '<div class="hexag">';
      foreach ($flippedInitial as $name) {
        $content .= '<div class="sm-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $name['line'] . '_sm.png">';
        $content .= '</div>';
      }
      $content .= '</div>';
      $content .= '<div class="ic-content">';
      $valArray = $this->iChing->findTopChanging($flippedInitial);
      foreach ($valArray as $val) {
        $content .= '<div class="ic-row">';
        $content .= $chBook[$val];
        $content .= '</div>';
      }
      $content .= '</div>';
    }
    else {
      $content .= '<div>';
      $content .= 'There are no changing lines. The situation is expected to remain the same in the near future.';
      $content .= '</div>';
    }
    $content .= '</div>';
    // END #changing-flex.
    $content .= '</div>';
    // END #changing.
    /* Transformed */
    $content .= '<div id="transformed" class="tabcontent">';
    $content .= '<h3>Transformed</h3>';
    /* TRANSFORMED */
    $content .= '<div id="transformed-flex">';
    if ($finalchg != 'No Change') {
      $content .= '<div class="hexag">';
      foreach ($flippedChanged as $chgname) {
        $content .= '<div class="sm-line">';
        $content .= '<img src="/' . $localImagePath . '/' . $chgname['line'] . '_sm.png">';
        $content .= '</div>';
      }
      $content .= '</div>';
      $content .= '<div class="ic-content">';
      $content .= $ch_bookname . ' - ' . $chBook['descr'];
      $content .= '<h4>The Judgement</h4>';
      $content .= $chBook['judge'];
      $content .= '<h4>The Image</h4>';
      $content .= $chBook['image'];
      $content .= '</div>';
    }
    else {
      $content .= '<div>';
      $content .= 'There are no changing lines. The situation is expected to remain the same in the near future.';
      $content .= '</div>';
    }
    $content .= '</div>';
    // END #transformed-flex.
    $content .= '</div>';
    // END #transformed.
    $content .= '</div>';
    // END .iching_container.
    return $content;
  }

}
