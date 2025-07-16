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

class TaoIchingSettings extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected Connection $database;

  /**
   * Drupal config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\tao_iching\Service\IchingService $iChing
   */
  protected IchingService $iChing;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   */
  protected $loggerFactory;

  /**
   * Config settings.
   *
   * @var string
   */
  const TI_SETTINGS = 'tao_iching.adminsettings';

  /**
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\tao_iching\Service\IchingService $iChing_service
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    Connection $connection,
    IchingService $iChing_service,
    LoggerChannelFactory $logger_factory,
    ConfigFactoryInterface $config_factory) {
    $this->database = $connection;
    $this->iChing = $iChing_service;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    parent::__construct($config_factory);
  }

  /**
   * @param \Psr\Container\ContainerInterface $container
   *
   * @return \Drupal\Core\Form\ConfigFormBase|\Drupal\tao_iching\Form\TaoIchingSettings|static
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
   * @return string
   */
  public function getFormId() {
    return 'tao_iching_settings_form';
  }

  protected function getEditableConfigNames() {
    return [
      static::TI_SETTINGS,
    ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::TI_SETTINGS);
    $idArray =  $this->database->select('tao_iching_readings', 'n')
      ->fields('n', ['id', 'user_name', 'timestamp'])
      ->orderBy('timestamp','DESC')
      ->execute()
      ->fetchAll();
    $readCount = count($idArray);
    $form = [];

    $form['readings'] = [
      '#type' => 'details',
      '#title' => $this->t($readCount . ' Readings'),
      '#description' => $this->t('Delete individual readings.'),
      '#open' => FALSE,
    ];

    foreach ($idArray as $value) {
      $convertedtime = date('m/d/Y h:i:s', $value->timestamp);
      $form['readings']['chkbx_' . $value->timestamp] = [
        '#type' => 'checkbox',
        '#title' => $this->t('<a href="/result/' . $value->id . '" target="_new">' . $value->user_name . ' at ' . $convertedtime) . '</a>',
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
   * @param array $form
   * @param FormStateInterface $form_state
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    // delete individual readings
    foreach($formValues as $key => $value) {
      if(str_contains($key, 'chkbx_') && $value == 1) {
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
        } catch (InvalidPluginDefinitionException | EntityStorageException|PluginNotFoundException $e) {
          $this->loggerFactory->get('tao_iching')
            ->error('Function createTaoPageNode() returned - '. $e);
        }
      }
    }

    $this->config(static::TI_SETTINGS)
      ->set('lifespan', $form_state->getValue('lifespan'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
