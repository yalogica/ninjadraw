<?php

namespace Yalogica\NinjaDraw\System;

defined('ABSPATH') || exit;

use Yalogica\NinjaDraw\Models\DataModel;

class Shortcode
{
  public function __construct()
  {
    add_action('init', [$this, 'init']);
  }

  public function init()
  {
    add_shortcode(NINJADRAW_SHORTCODE_NAME, [$this, 'run']);
    self::registerAssets();
  }

  public static function registerAssets()
  {
    wp_register_style('ninjadraw-shortcode', NINJADRAW_PLUGIN_URL . 'assets/css/shortcode.css', [], NINJADRAW_PLUGIN_VERSION, 'all');
    wp_register_script('ninjadraw-shortcode', NINJADRAW_PLUGIN_URL . 'assets/js/shortcode.js', [], NINJADRAW_PLUGIN_VERSION, false);
    wp_localize_script('ninjadraw-shortcode', 'NinjaDrawShortcode', self::getGlobals());
  }

  public static function enqueueAssets()
  {
    wp_enqueue_style('ninjadraw-shortcode');
    wp_enqueue_script('ninjadraw-shortcode');
  }

  public static function getGlobals()
  {
    return [
      'api' => [
        'public_url' => esc_url_raw(rest_url(NINJADRAW_PLUGIN_PUBLIC_REST_URL))
      ]
    ];
  }

  public function run($attributes = [])
  {
    self::enqueueAssets();
    return self::render($attributes);
  }

  public static function render($attributes = [])
  {
    $attributes = array_change_key_case($attributes, CASE_LOWER);
    $defaults = [
      'id' => null,
      'title' => false,
      'class' => null
    ];
    $attributes = shortcode_atts($defaults, $attributes);

    $id = null;
    $title = false;
    $class = null;

    foreach ($attributes as $key => $value) {
      if (!$key || empty($value)) {
        continue;
      }

      switch ($key) {
        case 'id': {
          $id = intval($value, 10);
        }
        break;
        case 'title': {
          $title = boolval($value);
        }
        break;
        case 'class': {
          $class = sanitize_html_class($value);
        }
        break;
      }
    }

    if (empty($id)) {
      return '<div class="components-notice is-warning"><div class="components-notice__content"><p>' . esc_html__('NinjaDraw diagram is not selected.', 'ninjadraw') . '</p></div></div>';
    }

    $item = DataModel::getItem($id);
    if (empty($item)) {
      return '<div class="components-notice is-error"><div class="components-notice__content"><p>' . esc_html__('NinjaDraw diagram not found.', 'ninjadraw') . '</p></div></div>';
    }

    $image = DataModel::getItemImage($id);
    if (empty($image)) {
      return '<div class="components-notice is-error"><div class="components-notice__content"><p>' . esc_html__('NinjaDraw diagram image not found.', 'ninjadraw') . '</p></div></div>';
    }

    if (!preg_match('/<svg[^>]+>/i', $image, $matches)) {
      return '<div class="components-notice is-error"><div class="components-notice__content"><p>' . esc_html__('NinjaDraw diagram has the wrong format.', 'ninjadraw') . '</p></div></div>';
    }
    $image = $matches[0] . '</svg>';

    $classes = ['ninjadraw'];
    if (!empty($class)) {
      $classes[] = $class;
    }

    $data  = '<div class="' . esc_attr(implode(' ', $classes)) . '" data-id="' . esc_attr($id) . '" >';
    $data .= '<div class="ninjadraw-image">';
    $data .= $image;
    $data .= '</div>';
    if ($title) {
      $data .= '<div class="ninjadraw-title">';
      $data .= $item['title'];
      $data .= '</div>';
    }
    $data .= '</div>';

    return $data;
  }
}
