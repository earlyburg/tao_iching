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
   * The block constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Drupal form builder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * The create function for dependency injection.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The Drupal service container.
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   *
   * @return \Drupal\tao_iching\Plugin\Block\IchingBlock|static
   *   An instance of this block plugin.
   *
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
   * The build method.
   *
   * @return array
   *   A render array containing the I-Ching form.
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
   * The getCacheMaxAge function.
   *
   * @return int
   *   The maximum cache age for this block.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * The blockForm function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['tao_iching_block_conf'] = [
      '#type' => 'markup',
      '#markup' => 'There is no configuration for this block.',
    ];
    return $form;
  }

  /**
   * The blockSubmit function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {}

  /**
   * The buildIchingBlock function.
   *
   * @return array
   *   A render array containing the I-Ching form.
   */
  public function buildIchingBlock() {
    $ichingForm = $this->formBuilder->getForm('\Drupal\tao_iching\Form\TaoIchingForm');
    $rendered['#iching_form'] = [$ichingForm];
    return $rendered;
  }

}
