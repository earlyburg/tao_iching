<?php

namespace Drupal\tao_iching\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Psr\Container\ContainerInterface;

/**
 * Provides an I-Ching Block.
 *
 * @Block(
 *   id = "iching_block",
 *   admin_label = @Translation("Tao I-Ching Block"),
 * )
 */
class IchingBlock extends BlockBase implements ContainerFactoryPluginInterface {

 /**
  * Form builder will be used via Dependency Injection.
  *
  * @var \Drupal\Core\Form\FormBuilderInterface
  */
 protected FormBuilderInterface $formBuilder;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param FormBuilderInterface $form_builder
   *
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * @param \Psr\Container\ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   *
   * @return \Drupal\tao_iching\Plugin\Block\IchingBlock|static
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * @return array
   *
   */
  public function build() {
    $build = [
      '#theme' => 'block__i_ching_block',
      '#iching' => $this->buildIchingBlock(),
    ];
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  /**
   * @return int
   *
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * @param $form
   * @param FormStateInterface $form_state
   * @return array
   *
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['tao_iching_block_conf'] = array(
      '#type' => 'markup',
      '#markup' => 'There is no configuration for this block.',
    );
    return $form;
  }

  /**
   * @param $form
   * @param FormStateInterface $form_state
   * @return void
   *
   */
  public function blockSubmit($form, FormStateInterface $form_state) {}

  /**
   * Build the tao_iching block.
   *
   * @return array
   */
  public function buildIchingBlock() {
    $ichingForm = $this->formBuilder->getForm('\Drupal\tao_iching\Form\TaoIchingForm');
    $rendered['#iching_form'] = [$ichingForm];
    return $rendered;
  }




}
