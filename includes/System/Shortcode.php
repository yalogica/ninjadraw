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
    wp_register_script('ninjadraw-presentation', NINJADRAW_PLUGIN_URL . 'assets/components/presentation/index.js', [], NINJADRAW_PLUGIN_VERSION, true);
    wp_localize_script('ninjadraw-shortcode', 'yalogica_ninjadraw_global', self::getGlobals());
    wp_localize_script('ninjadraw-presentation', 'yalogica_ninjadraw_presentation_global', self::getGlobals());
  }

  public static function enqueueAssets()
  {
    wp_enqueue_style('ninjadraw-shortcode');
    wp_enqueue_script('ninjadraw-shortcode');
  }

  public static function enqueuePresentationAssets()
  {
    wp_enqueue_style('ninjadraw-shortcode');
    wp_enqueue_script('ninjadraw-presentation');
  }

  public static function getGlobals()
  {
    return [
      'api' => [
        'public_url' => esc_url_raw(rest_url(NINJADRAW_PLUGIN_PUBLIC_REST_URL)),
        'presentation_url' => esc_url_raw(rest_url(NINJADRAW_PLUGIN_PUBLIC_REST_URL . '/presentations')),
      ]
    ];
  }

  public function run($attributes = [])
  {
    $type = isset($attributes['type']) ? sanitize_key($attributes['type']) : 'image';

    if ($type === 'presentation') {
      self::enqueuePresentationAssets();
    } else {
      self::enqueueAssets();
    }

    return self::render($attributes);
  }

  private static function renderImage($id, $item, $title, $class)
  {
    $image = DataModel::getItemImage($id);

    if (empty($image)) {
      return
        '<div class="components-notice is-error">' .
        '<div class="components-notice__content">' .
        '<p>' .
        esc_html__(
          'NinjaDraw diagram image not found.',
          'ninjadraw'
        ) .
        '</p>' .
        '</div>' .
        '</div>';
    }

    if (!preg_match('/<svg[^>]+>/i', $image, $matches)) {
      return
        '<div class="components-notice is-error">' .
        '<div class="components-notice__content">' .
        '<p>' .
        esc_html__(
          'NinjaDraw diagram has the wrong format.',
          'ninjadraw'
        ) .
        '</p>' .
        '</div>' .
        '</div>';
    }

    // Выводим только пустую SVG-оболочку,
    // полный SVG загружается через shortcode.js.
    $image = $matches[0] . '</svg>';

    $classes = ['ninjadraw'];

    if (!empty($class)) {
      $classes[] = $class;
    }

    $data =
      '<div class="' .
      esc_attr(implode(' ', $classes)) .
      '" data-id="' .
      esc_attr($id) .
      '">';

    $data .=
      '<div class="ninjadraw-image">' .
      $image .
      '</div>';

    if ($title) {
      $data .=
        '<div class="ninjadraw-title">' .
        esc_html($item['title']) .
        '</div>';
    }

    $data .= '</div>';

    return $data;
  }

  private static function renderPresentation($id, $item, $title, $class, $width, $height)
  {
    $presentation = DataModel::getItemPresentation($id);

    if (empty($presentation)) {
      return
        '<div class="components-notice is-error">' .
        '<div class="components-notice__content">' .
        '<p>' .
        esc_html__(
          'NinjaDraw presentation not found.',
          'ninjadraw'
        ) .
        '</p>' .
        '</div>' .
        '</div>';
    }

    if (empty($presentation['presentation']['slides'])) {
      return
        '<div class="components-notice is-warning">' .
        '<div class="components-notice__content">' .
        '<p>' .
        esc_html__(
          'NinjaDraw presentation has no slides.',
          'ninjadraw'
        ) .
        '</p>' .
        '</div>' .
        '</div>';
    }

    $classes = ['ninjadraw-presentation'];

    if (!empty($class)) {
      $classes[] = $class;
    }
  
    $style = sprintf(
      'width:%s;height:%s;',
      $width,
      $height
    );

    $data =
      '<div class="' .
        esc_attr(implode(' ', $classes)) .
      '" data-id="' .
        esc_attr($id) .
      '" style="' .
        esc_attr($style) .
      '"></div>';

    if ($title) {
      $data .=
        '<div class="ninjadraw-title">' .
        esc_html($item['title']) .
        '</div>';
    }

    return $data;
  }

  private static function sanitizeSize($value, $default)
  {
    $value = trim(sanitize_text_field($value));

    if (preg_match('/^(?:0|(?:\d+(?:\.\d+)?)(?:px|%|rem|em|vw|vh|vmin|vmax))$/i', $value)) {
      return $value;
    }

    return $default;
  }

  public static function render($attributes = [])
  {
    $attributes = array_change_key_case($attributes, CASE_LOWER);
    $defaults = [
      'id' => null,
      'type' => 'image',
      'title' => false,
      'class' => null,
      'width' => '100%',
      'height' => '400px',
    ];
    $attributes = shortcode_atts($defaults, $attributes);

    $id = null;
    $type = 'image';
    $title = false;
    $class = null;
    $width = '100%';
    $height = '400px';

    foreach ($attributes as $key => $value) {
      if (!$key || empty($value)) {
        continue;
      }

      switch ($key) {
        case 'id': {
            $id = intval($value, 10);
          }
          break;
        case 'type': {
            $type = sanitize_key($value);
            if (!in_array($type, ['image', 'presentation'], true)) {
              $type = 'image';
            }
          }
          break;
        case 'title': {
            $title = filter_var(
              $value,
              FILTER_VALIDATE_BOOLEAN
            );
          }
          break;
        case 'class': {
            $class = sanitize_html_class($value);
          }
          break;
        case 'width': {
            $width = self::sanitizeSize($value, '100%');
          }
          break;
        case 'height': {
            $height = self::sanitizeSize($value, '400px');
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

    if ($type === 'presentation') {
      return self::renderPresentation($id, $item, $title, $class, $width, $height);
    }

    return self::renderImage($id, $item, $title, $class);
  }
}
