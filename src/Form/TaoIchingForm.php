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
use Psr\Container\ContainerInterface;
use Drupal\Core\Url;

/**
 * The TaoIchingForm class.
 */
class TaoIchingForm extends FormBase {

  /**
   * The ExtensionPathResolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $pathResolver;

  /**
   * The I Ching service.
   *
   * @var \Drupal\tao_iching\Service\IchingService
   */
  protected IchingService $iChingService;

  /**
   * The messenger interface service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The TaoCookie service.
   *
   * @var \Drupal\tao_iching\Service\TaocookieService
   */
  protected TaocookieService $taoCookieService;

  /**
   * The account interface service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $accountInterface;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The URL service.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $url;

  /**
   * The TaoIchingForm service class constructor.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $path_resolver
   *   The extension path resolver service.
   * @param \Drupal\tao_iching\Service\IchingService $iChing_service
   *   The I Ching service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger_interface
   *   The messenger interface service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger channel factory service.
   * @param \Drupal\tao_iching\Service\TaocookieService $taoCookie_service
   *   The TaoCookie service.
   * @param \Drupal\Core\Session\AccountInterface $account_interface
   *   The account interface service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   *
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   */
  public function __construct(
    ExtensionPathResolver $path_resolver,
    IchingService $iChing_service,
    MessengerInterface $messenger_interface,
    LoggerChannelFactory $logger_factory,
    TaocookieService $taoCookie_service,
    AccountInterface $account_interface,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->pathResolver = $path_resolver;
    $this->iChingService = $iChing_service;
    $this->messenger = $messenger_interface;
    $this->loggerFactory = $logger_factory;
    $this->taoCookieService = $taoCookie_service;
    $this->accountInterface = $account_interface;
    $this->configFactory = $config_factory;
  }

  /**
   * The TaoIchingForm create method.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container interface.
   *
   * @return static
   *   An instance of the TaoIchingForm class.
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
   * The getFormId method.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'tao_iching_form';
  }

  /**
   * The buildForm method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $localImagePath = '/' . $this->pathResolver->getPath('module', 'tao_iching') . '/imgs';
    if (!$this->taoCookieService->getCookieValue()) {
      $idString = $this->iChingService->getSessionId($this->iChingService->makeId());
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
    $coinMarkup .= '<img src="' . $localImagePath . '/heads.png">';
    $coinMarkup .= '<img src="' . $localImagePath . '/heads.png">';
    $coinMarkup .= '<img src="' . $localImagePath . '/heads.png">';
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
        'callback' => '::taoIchingSubmitCallback',
        'wrapper' => 'iching_box',
      ],
      '#value' => $this->t('Toss The Coins'),
      '#attributes' => [
        'class' => [
          'i-ching-submit',
        ],
      ],
    ];
    return $form;
  }

  /**
   * The iChingFormValidate method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function iChingFormValidate(array &$form, FormStateInterface $form_state) {}

  /**
   * The submitForm method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * The taoIchingSubmitCallback function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|void
   *   An AjaxResponse object for redirecting to the results page, or void.
   */
  public function taoIchingSubmitCallback(array $form, FormStateInterface $form_state) {
    ($this->accountInterface->getDisplayName()) ? $uname = $this->accountInterface->getDisplayName() : $uname = 'Anonymous';
    $localImagePath = '/' . $this->pathResolver->getPath('module', 'tao_iching') . '/imgs';
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
    if ($this->iChingService->readingExist($readingId)) {
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
    if ($this->iChingService->checkNumber($readingId) == 6) {
      $response = new AjaxResponse();
      $url = Url::fromRoute('tao_iching.result', ['callbackResult' => $readingId]);
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);
      $this->taoCookieService->setDeleteCookie();
      return $response;
    }
    /* safeguard against broken sessions */
    if ($this->iChingService->checkNumber($readingId) >= 7) {
      $this->iChingService->deleteReading($readingId);
      $this->taoCookieService->setDeleteCookie();
      $this->messenger()->addWarning('Apologies, there was a problem with the website. Please try again.');
    }
    // Create our output.
    $markup = "<div id='coin'>";
    // @todo Figure out if we need the zero case.
    switch ($coinsval) {
      case 9:
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        break;

      case 8:
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/tails.png">';
        break;

      case 7:
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/tails.png">';
        $markup .= '<img src="' . $localImagePath . '/tails.png">';
        break;

      case 6:
        $markup .= '<img src="' . $localImagePath . '/tails.png">';
        $markup .= '<img src="' . $localImagePath . '/tails.png">';
        $markup .= '<img src="' . $localImagePath . '/tails.png">';
        break;

      case 0:
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        break;

      default:
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
        $markup .= '<img src="' . $localImagePath . '/heads.png">';
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
    $markup .= '<img src="' . $localImagePath . '/' . $line . '_sm.png">';
    $markup .= "</div>"; /* .sm_line */
    /* markup form element */
    $element = $form['iching_box'];
    $element['#markup'] = $markup;

    return $element;

  }

}
