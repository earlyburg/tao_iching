<?php

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

class TaoIchingForm extends FormBase {

  /**
   * @var \Drupal\Core\Extension\ExtensionPathResolver $pathResolver
   */
  protected $pathResolver;

  /**
   * @var \Drupal\tao_iching\Service\IchingService $iChingService
   */
  protected IchingService $iChingService;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   */
  protected $loggerFactory;

  /**
   * @var \Drupal\tao_iching\Service\TaocookieService $taoCookieService
   */
  protected TaocookieService $taoCookieService;

  /**
   * @var \Drupal\Core\Session\AccountInterface $accountInterface
   */
  protected AccountInterface $accountInterface;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  protected $configFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\RedirectResponse $redirectResponse
   */
  protected RedirectResponse $redirectResponse;

  /**
   * @var \Drupal\Core\Url $url
   */
  protected Url $url;

  /**
   * @param ExtensionPathResolver $path_resolver
   * @param IchingService $iChing_service
   * @param MessengerInterface $messenger_interface
   * @param LoggerChannelFactory $logger_factory
   * @param TaocookieService $taoCookie_service
   * @param AccountInterface $account_interface
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(
    ExtensionPathResolver $path_resolver,
    IchingService $iChing_service,
    MessengerInterface $messenger_interface,
    LoggerChannelFactory $logger_factory,
    TaocookieService $taoCookie_service,
    AccountInterface $account_interface,
    ConfigFactoryInterface $config_factory) {
    $this->pathResolver = $path_resolver;
    $this->iChingService = $iChing_service;
    $this->messenger = $messenger_interface;
    $this->loggerFactory = $logger_factory;
    $this->taoCookieService = $taoCookie_service;
    $this->accountInterface = $account_interface;
    $this->configFactory = $config_factory;
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $localImagePath = '/'.$this->pathResolver->getPath('module', 'tao_iching').'/imgs';
    if(!$this->taoCookieService->getCookieValue()) {
      $idString = $this->iChingService->getSessionId($this->iChingService->makeID());
      $this->taoCookieService->setCookieValue($idString);
    }
    $form = [];
    $form['question'] = [
      '#type' => 'textarea',
      '#title' => 'Ask A Question.',
      '#cols' => 10,
      '#rows' => 1,
      '#default_value' => $this->iChingService->fetchQuestion($this->iChingService->getReadingId($this->taoCookieService->getCookieValue())),
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|mixed
   * @throws \Exception
   */
  public function tao_iching_submit_callback(array $form, FormStateInterface $form_state) {
    ($this->accountInterface->getDisplayName()) ? $uname = $this->accountInterface->getDisplayName() : $uname = 'Anonymous';
    $localImagePath = '/'.$this->pathResolver->getPath('module', 'tao_iching').'/imgs';
    $sessionIdString = $this->taoCookieService->getCookieValue();
    $readingId = $this->iChingService->getReadingId($sessionIdString);
    /* generate hexagram line */
    $user_click = $this->iChingService->line();
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
    if($this->iChingService->readingExist($readingId)) {
      $throw_num = $this->iChingService->checkNumber($readingId) + 1;
      $this->iChingService->insertLine($readingId, $throw_num, $line, $tri_name, $code, $coinsval);
      $form_state->setRebuild();
    }
    else {
      $throw_num = 1;
      $id = $this->iChingService->recreateIdArray($sessionIdString);
      $this->iChingService->readingInit($id, $uname, $form_state->getValue('question'));
      $this->iChingService->insertLine($readingId, $throw_num, $line, $tri_name, $code, $coinsval);
      $form_state->setRebuild();
    }
    /* 6 clicks method logic */
    if($this->iChingService->checkNumber($readingId) == 6) {
      $response = new AjaxResponse();
      $url = Url::fromRoute('tao_iching.result', ['callbackResult' => $readingId]);
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);
      $this->taoCookieService->setDeleteCookie();
      return $response;
    }
    /* safeguard against broken sessions */
    if ( $this->iChingService->checkNumber($readingId) >= 7 ) {
      $this->iChingService->deleteReading($readingId);
      $this->taoCookieService->setDeleteCookie();
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
