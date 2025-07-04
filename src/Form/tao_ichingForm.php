<?php
/**
 * @file
 * Contains \Drupal\tao_iching\Form\tao_ichingForm.
 */
namespace Drupal\tao_iching\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tao_iching\Service\IchingService;
use Drupal\tao_iching\Service\TaocookieService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Drupal\Core\Url;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class tao_ichingForm extends FormBase {

  /**
   * @var ExtensionPathResolver $pathResolver
   */
  protected ExtensionPathResolver $localPath;

  /**
   * @var IchingService $iChingService
   */
  protected IchingService $iChing;

  /**
   * @var MessengerInterface $messengerInterface
   */
  protected $messenger;

  /**
   * @var LoggerChannelFactory $loggerFactory
   */
  protected LoggerChannelFactory $logger;

  /**
   * @var TaocookieService $taoCookieService
   */
  protected TaocookieService $taoCookie;

  /**
   * @var AccountInterface $accountInterface
   */
  protected AccountInterface $account;

  /**
   * @var ConfigFactoryInterface $configFactory
   */
  protected ConfigFactoryInterface $config;

  /**
   * @var RedirectResponse $redirectResponse
   */
  protected RedirectResponse $redirect;

  /**
   * @var Url $url
   */
  protected Url $url;

  /**
   * @param ExtensionPathResolver $pathResolver
   * @param IchingService $iChingService
   * @param MessengerInterface $messengerInterface
   * @param LoggerChannelFactory $loggerFactory
   * @param TaocookieService $taoCookieService
   * @param AccountInterface $accountInterface
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(
    ExtensionPathResolver $pathResolver,
    IchingService $iChingService,
    MessengerInterface $messengerInterface,
    LoggerChannelFactory $loggerFactory,
    TaocookieService $taoCookieService,
    AccountInterface $accountInterface,
    ConfigFactoryInterface $configFactory) {
    $this->localPath = $pathResolver;
    $this->iChing = $iChingService;
    $this->messenger = $messengerInterface;
    $this->logger = $loggerFactory;
    $this->taoCookie = $taoCookieService;
    $this->account = $accountInterface;
    $this->config = $configFactory;
  }

  /**
   * @param ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.path.resolver'),
      $container->get('tao_iching.service'),
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('taocookie.service'),
      $container->get('current_user'),
      $container->get('config.factory'),
    );
  }

  /**
   * @return string
   *
   */
  public function getFormId() {
    return 'tao_iching_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $localImagePath = '/'.$this->localPath->getPath('module', 'tao_iching').'/imgs';
    if(!$this->taoCookie->getCookieValue()) {
      $idString = $this->iChing->getSessionId($this->iChing->makeID());
      $this->taoCookie->setCookieValue($idString);
    }
    $form = [];
    $form['question'] = [
      '#type' => 'textarea',
      '#title' => 'Ask A Question.',
      '#cols' => 10,
      '#rows' => 1,
      '#default_value' => $this->iChing->fetchQuestion($this->iChing->getReadingId($this->taoCookie->getCookieValue())),
    ];

    $coinMarkup = "<div id='coin'>";
    $coinMarkup .= '<img src="'.$localImagePath.'/heads.png">';
    $coinMarkup .= '<img src="'.$localImagePath.'/heads.png">';
    $coinMarkup .= '<img src="'.$localImagePath.'/heads.png">';
    $coinMarkup .= "</div>";
    $coinMarkup .= "<div id='iching_form_blurb'></div><div class='sm_line'></div>";

    $form['iching_box'] = [
      '#prefix' => '<div id="iching_box">',
      '#suffix' => '</div>',
      '#markup' => $coinMarkup,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#ajax' => [
        'callback' => '::tao_iching_submit_callback',
        'wrapper' => 'iching_box',
      ],
      '#value' => $this->t('Toss The Coins'),
      '#attributes' => [
        'class' => [
          'i-ching-submit'
        ],
      ],
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   *
   */
  public function iChingFormValidate(array &$form, FormStateInterface $form_state) {}

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return mixed|void
   *
   */
  public function tao_iching_submit_callback(array $form, FormStateInterface $form_state) {
    ($this->account->getDisplayName()) ? $uname = $this->account->getDisplayName() : $uname = 'Anonymous';
    $localImagePath = '/'.$this->localPath->getPath('module', 'tao_iching').'/imgs';
    $sessionIdString = $this->taoCookie->getCookieValue();
    $readingId = $this->iChing->getReadingId($sessionIdString);
    /* generate hexagram line */
    $user_click = $this->iChing->line();
    /* get & set the values from the toss */
    foreach ($user_click as $key => $value) {
      if ($key != "line" && $key != "coinsval") {
        $tri_name = $key;
        $code = $value;
      }
    }
    $line = $user_click['line'];
    $coinsval = $user_click['coinsval'];
    /* start a reading database session */
    if($this->iChing->readingExist($readingId)) {
      $throw_num = $this->iChing->checkNumber($readingId) + 1;
      $this->iChing->insertLine($readingId, $throw_num, $line, $tri_name, $code, $coinsval);
      $form_state->setRebuild();
    }
    else {
      $throw_num = 1;
      $id = $this->iChing->recreateIdArray($sessionIdString);
      $this->iChing->readingInit($id, $uname, $form_state->getValue('question'));
      $this->iChing->insertLine($readingId, $throw_num, $line, $tri_name, $code, $coinsval);
      $form_state->setRebuild();
    }
    /* 6 clicks method logic */
    if($this->iChing->checkNumber($readingId) == 6) {
      $response = new AjaxResponse();
      $url = Url::fromRoute('tao_iching.result', ['callbackResult' => $readingId]);
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);
      $this->taoCookie->setDeleteCookie();
      return $response;
    }
    /* safeguard against broken sessions */
    if ( $this->iChing->checkNumber($readingId) >= 7 ) {
      $this->iChing->deleteReading($readingId);
      $this->taoCookie->setDeleteCookie();
      $this->messenger()->addWarning('Apologies, there was a problem with the website. Please try again.');
    }
    // create our output
    $markup = "<div id='coin'>";
    // TODO figure out if we need the zero case
    switch ($coinsval) {
      case 9:
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        break;
      case 8:
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/tails.png">';
        break;
      case 7:
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/tails.png">';
        $markup .= '<img src="'.$localImagePath.'/tails.png">';
        break;
      case 6;
        $markup .= '<img src="'.$localImagePath.'/tails.png">';
        $markup .= '<img src="'.$localImagePath.'/tails.png">';
        $markup .= '<img src="'.$localImagePath.'/tails.png">';
        break;
      case 0;
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        break;
      default:
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
        $markup .= '<img src="'.$localImagePath.'/heads.png">';
    }
    $markup .= "</div>"; /* #coin */
    /* fix our output to be readable */
    $raw = $line;
    switch ($raw) {
      case "yang_changing":
        $legible = str_replace("yang_changing", "Yang, changing.", $raw);
        break;
      case "yin":
        $legible = str_replace("yin", "Yin.", $raw);
        break;
      case "yin_changing":
        $legible = str_replace("yin_changing", "Yin, changing.", $raw);
        break;
      case "yang":
        $legible = str_replace("yang", "Yang.", $raw);
        break;
    }
    $markup .= "<div id='iching_form_blurb'>";
    $markup .= "Coin toss " . $throw_num . " yields " . $legible;
    $markup .= "</div>"; /* #iching_form_blurb */
    $markup .= "<div class='sm_line'>";
    $markup .= '<img src="'.$localImagePath.'/'.$line.'_sm.png">';
    $markup .= "</div>"; /* .sm_line */
    /* markup form element */
    $element = $form['iching_box'];
    $element['#markup'] = $markup;

    return $element;

  }

}
