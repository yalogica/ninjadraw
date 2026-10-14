<?php

namespace Yalogica\NinjaDraw\System;

defined('ABSPATH') || exit;

use Yalogica\NinjaDraw\Models\AccessModel;

class ImageBlock
{
  public function __construct()
  {
    add_action('init', [$this, 'init']);
  }

  public function init()
  {
    if (!function_exists('register_block_type')) {
      return;
    }

    wp_register_script(
      'ninjadraw-image-block-editor',
      NINJADRAW_PLUGIN_URL . 'blocks/gutenberg/image-block/index.js',
      ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch', 'wp-server-side-render'],
      NINJADRAW_PLUGIN_VERSION,
      true
    );
    wp_set_script_translations('ninjadraw-image-block-editor', 'ninjadraw');

    wp_localize_script('ninjadraw-image-block-editor', 'yalogica_ninjadraw_image_block', [
      'optionsUrl'  => esc_url_raw(rest_url(NINJADRAW_PLUGIN_REST_URL . '/items/options')),
      'publicUrl'   => esc_url_raw(rest_url(NINJADRAW_PLUGIN_PUBLIC_REST_URL)),
      'editUrl'     => esc_url_raw(admin_url('admin.php?page=ninjadraw&action=edit&id=')),
      'canEdit'     => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_EDIT),
    ]);

    register_block_type(NINJADRAW_PLUGIN_PATH . '/blocks/gutenberg/image-block', [
      'render_callback' => [$this, 'renderBlock'],
    ]);
  }

  public function renderBlock($attributes = [], $content = '', $block = null)
  {
    $attributes = array_change_key_case($attributes, CASE_LOWER);

    $shortcode_attributes = [
      'id' => isset($attributes['itemid']) ? intval($attributes['itemid'], 10) : 0,
      'title' => !empty($attributes['showtitle']),
      'class' => null
    ];

    Shortcode::enqueueAssets();

    return '<div ' . get_block_wrapper_attributes() . '>' . Shortcode::render($shortcode_attributes) . '</div>';
  }
}