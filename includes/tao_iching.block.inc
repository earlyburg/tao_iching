<?php

/**
 * Implements hook_block_info().
 */
function tao_iching_block_info() {
  $blocks['tao_iching_widget'] = array(
    'info' => t('Tao I-Ching: widget'),
    'description' => 'The I-Ching widget.',
  );
  return $blocks;
}

/**
 * Implements hook_block_view().
 */
function tao_iching_block_view($delta = '', $settings = array(), $contexts = array()) {
  switch ($delta) {
    case 'tao_iching_widget':
      $block['subject'] = t('Tao I-Ching');
      $block['content'] = tao_iching_contents($settings);
      break;
  }
  return $block;
}

/**
 * @return form without template
 */
function tao_iching_contents($settings) {
  $ichingForm = backdrop_get_form('tao_iching_reading');
  // remove the theme template from our display
  $ichingForm['#theme'] = array();
  $ret = backdrop_render($ichingForm);
  return $ret;
}
