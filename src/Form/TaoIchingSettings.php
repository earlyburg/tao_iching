<?php

namespace Drupal\tao_iching\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\tao_iching\Service\IchingService;
use Psr\Container\ContainerInterface;

/**
 * The TaoIchingSettings method.
 */
class TaoIchingSettings extends ConfigFormBase {

  /**
   * The Drupal database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Drupal config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The I-Ching service.
   *
   * @var \Drupal\tao_iching\Service\IchingService
   */
  protected IchingService $iChing;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Config settings.
   *
   * @var string
   */
  const TI_SETTINGS = 'tao_iching.adminsettings';

  /**
   * TaoIchingSettings constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Drupal database connection.
   * @param \Drupal\tao_iching\Service\IchingService $iChing_service
   *   The I-Ching service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The Drupal logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory.
   */
  public function __construct(
    Connection $connection,
    IchingService $iChing_service,
    LoggerChannelFactory $logger_factory,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->database = $connection;
    $this->iChing = $iChing_service;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    parent::__construct($config_factory);
  }

  /**
   * The create function.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return \Drupal\Core\Form\ConfigFormBase|\Drupal\tao_iching\Form\TaoIchingSettings|static
   *   An instance of this form.
   *
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('tao_iching.service'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
    );
  }

  /**
   * The getFormId function.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'tao_iching_settings_form';
  }

  /**
   * The getEditibleConfigNames method.
   *
   * @return string[]
   *   An array of config names that will be editable through this form.
   */
  protected function getEditableConfigNames() {
    return [
      static::TI_SETTINGS,
    ];
  }

  /**
   * The buildForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::TI_SETTINGS);
    $idArray = $this->database->select('tao_iching_readings', 'n')
      ->fields('n', ['id', 'user_name', 'timestamp'])
      ->orderBy('timestamp', 'DESC')
      ->execute()
      ->fetchAll();
    $readCount = count($idArray);
    $form = [];

    $form['readings'] = [
      '#type' => 'details',
      '#title' => $readCount . ' Readings',
      '#description' => $this->t('Delete individual readings.'),
      '#open' => FALSE,
    ];

    foreach ($idArray as $value) {
      $convertedtime = date('m/d/Y h:i:s', $value->timestamp);
      $form['readings']['chkbx_' . $value->timestamp] = [
        '#type' => 'checkbox',
        '#title' => '<a href="/result/' . $value->id . '" target="_new">' . $value->user_name . ' at ' . $convertedtime . '</a>',
        '#default_value' => 0,
        '#required' => FALSE,
        '#attributes' => ['class' => ['ic1-class-1']],
      ];
    }

    $form['all'] = [
      '#type' => 'details',
      '#title' => $this->t('Delete All Readings'),
      '#open' => TRUE,
    ];

    $form['all']['delete_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete All Readings in the database.'),
      '#default_value' => 0,
      '#required' => FALSE,
    ];

    $form['database'] = [
      '#type' => 'details',
      '#title' => $this->t('Storage Settings'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    $form['database']['lifespan'] = [
      '#type' => 'select',
      '#title' => $this->t('The length of time to store readings.'),
      '#options' => [
        '1' => $this->t('1 day'),
        '7' => $this->t('1 week'),
        '30' => $this->t('1 month'),
        '90' => $this->t('3 months'),
        '180' => $this->t('6 months'),
        '270' => $this->t('9 months'),
        '365' => $this->t('1 year'),
        '0' => $this->t('Forever'),
      ],
      '#default_value' => $config->get('lifespan'),
      '#description' => $this->t('Set the storage settings.'),
    ];

    $form['generate'] = [
      '#type' => 'details',
      '#title' => $this->t('Generate I-Ching Readings'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    $form['generate']['howmany'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Generate Readings'),
      '#required' => FALSE,
      '#default_value' => '0',
      '#description' => $this->t("Input the number of readings to generate."),
      '#size' => 6,
      '#maxlength' => 6,
    ];

    $form['tao_te_ching'] = [
      '#type' => 'details',
      '#title' => $this->t('Tao Te Ching'),
      '#open' => TRUE,
    ];

    $form['tao_te_ching']['create_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create the 81 Tao Te Ching reading nodes.'),
      '#default_value' => 0,
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * The validateForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * The submitForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    // Delete individual readings.
    foreach ($formValues as $key => $value) {
      if (str_contains($key, 'chkbx_') && $value == 1) {
        $cleanKey = substr($key, strlen('chkbx_'));
        $readingId = $this->iChing->getReadingIdFromTimestamp($cleanKey);
        $this->iChing->deleteReading($readingId);
      }
    }
    if ($formValues['delete_all'] == 1) {
      $this->iChing->deleteAllReadings();
    }
    if ($formValues['howmany'] != '' && !empty($formValues['howmany'])) {
      $numberToGenerate = $formValues['howmany'];
      $batch = [
        'title' => $this->t('Generate Readings...'),
        'operations' => [],
        'init_message' => $this->t('Initializing...'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'error_message' => $this->t('An error occurred during processing'),
        'finished' => 'tao_iching_create_finished',
      ];
      for ($i = 0; $i < intval($numberToGenerate); $i++) {
        $batch['operations'][] = ['tao_iching_create_reading', []];
      }
      batch_set($batch);
    }

    if ($formValues['create_pages'] == 1) {
      foreach ($this->iChing->createTaoTeChings() as $taoArray) {
        try {
          $this->iChing->createTaoPageNode($taoArray);
        }
        catch (InvalidPluginDefinitionException | EntityStorageException | PluginNotFoundException $e) {
          $this->loggerFactory->get('tao_iching')
            ->error('Function createTaoPageNode() returned - ' . $e);
        }
      }
    }

    $this->config(static::TI_SETTINGS)
      ->set('lifespan', $form_state->getValue('lifespan'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
