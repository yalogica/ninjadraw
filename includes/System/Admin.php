<?php

namespace Yalogica\NinjaDraw\System;

defined('ABSPATH') || exit;

use Yalogica\NinjaDraw\Models\DataModel;
use Yalogica\NinjaDraw\Models\AccessModel;
use Yalogica\NinjaDraw\Models\SettingsModel;

class Admin
{
  private const NOTICE_META_KEY = '_ninjadraw_admin_notice_hide_until';
  private const NOTICE_NONCE_ACTION = 'ninjadraw_dismiss_admin_notice';

  public function __construct()
  {
    add_action('init', [$this, 'init']);
    add_action('admin_init', [$this, 'redirect']);
  }

  public function init()
  {
    $this->registerPostType();

    if (!is_admin()) {
      return;
    }

    if (AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW)) {
      add_action('admin_menu', [$this, 'generalMenu']);
      add_filter('submenu_file', [$this, 'highlightAdminMenu'], 10, 2);

      add_action('admin_notices', [$this, 'adminNotices']);
      add_action('admin_enqueue_scripts', [$this, 'adminNoticesScripts']);
      add_action('wp_ajax_ninjadraw_dismiss_admin_notice', [$this, 'adminNoticesAjaxDismiss']);
    } else if (current_user_can('administrator')) {
      add_action('admin_menu', [$this, 'adminMenu']);
    }
  }

  public function registerPostType()
  {
    $labels = [
      'name' => esc_html__('NinjaDraw Diagrams', 'ninjadraw'),
      'singular_name' => esc_html__('NinjaDraw Diagram', 'ninjadraw'),
    ];
    $args = [
      'labels' => $labels,
      'public' => false,  // removes the permalink option
      'publicly_queryable' => false, // remove preview page
      'exclude_from_search' => true,
      'show_ui' => false,
      'show_in_menu' => false,
      'show_in_rest' => false,
      'rewrite' => false,
      'query_var' => false,
      'capability_type' => 'post',
      'has_archive' => false,
      'hierarchical' => false,
      'supports' => ['title']
    ];

    register_post_type(DataModel::POST_TYPE, $args);
  }

  public function generalMenu()
  {
    add_menu_page(esc_html__('NinjaDraw', 'ninjadraw'), esc_html__('NinjaDraw', 'ninjadraw'), 'read', 'ninjadraw', [$this, 'showPage'], 'data:image/svg+xml;base64,' . base64_encode(SettingsModel::MENU_ICON));
    add_submenu_page('ninjadraw', esc_html__('NinjaDraw', 'ninjadraw'), esc_html__('All Diagrams', 'ninjadraw'), 'read', 'ninjadraw', [$this, 'showPage']);
    
    if (AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT])) {
      add_submenu_page('ninjadraw', esc_html__('NinjaDraw', 'ninjadraw'), esc_html__('Add New', 'ninjadraw'), 'read', 'ninjadraw&action=new', [$this, 'showPage']);
    }
    
    add_submenu_page('ninjadraw', esc_html__('NinjaDraw', 'ninjadraw'), esc_html__('How To', 'ninjadraw'), 'read', 'ninjadraw-guide', [$this, 'showPageGuide']);
    add_submenu_page('ninjadraw', esc_html__('NinjaDraw', 'ninjadraw'), esc_html__('Settings', 'ninjadraw'), 'manage_options', 'ninjadraw-settings', [$this, 'showPage']);
  }

  public function adminMenu()
  {
    add_menu_page(esc_html__('NinjaDraw', 'ninjadraw'), esc_html__('NinjaDraw', 'ninjadraw'), 'manage_options', 'ninjadraw-settings', [$this, 'showPage'], 'data:image/svg+xml;base64,' . base64_encode(SettingsModel::MENU_ICON));
  }
  
  public function highlightAdminMenu($submenu_file, $parent_file)
  {
     $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));
    if (in_array($page, ['ninjadraw'], true)) {
      $action = sanitize_key(filter_input(INPUT_GET, 'action', FILTER_DEFAULT));
      if (in_array($action, ['new'], true)) {
        $submenu_file = "ninjadraw&action={$action}";
      } elseif ($action === 'settings') {
        $submenu_file = 'ninjadraw-settings';
      }
    }
    return $submenu_file;
  }

  public function redirect()
  {
    $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));

    if ($page === 'ninjadraw-settings') {
      $redirect_url = add_query_arg(['page' => 'ninjadraw', 'action' => 'settings'], admin_url('admin.php'));
      wp_redirect($redirect_url, 301);
      exit;
    }

    if ($page === 'ninjadraw') {
      $action = sanitize_key(filter_input(INPUT_GET, 'action', FILTER_DEFAULT));

      if ($action === '') {
        $redirect_url = add_query_arg(['page' => 'ninjadraw', 'action' => 'list'], admin_url('admin.php'));
        wp_redirect($redirect_url);
        exit;
      } elseif (!in_array($action, ['list', 'new', 'edit', 'settings'], true)) {
        $redirect_url = admin_url();
        wp_redirect($redirect_url, 303);
        exit;
      } elseif ($action === 'list') {
        if ((!AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_VIEW]))) {
          wp_die(
            esc_html__('Sorry, you are not allowed to access this page.', 'ninjadraw'),
            esc_html__('Access Denied', 'ninjadraw'),
            403
          );
        }
      } elseif ($action === 'new') {
        if ((!AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT]))) {
          wp_die(
            esc_html__('Sorry, you are not allowed to access this page.', 'ninjadraw'),
            esc_html__('Access Denied', 'ninjadraw'),
            403
          );
        }

        $id = DataModel::createItemAutoDraft();
        if (!$id) {
          wp_die(
            esc_html__('Failed to create a new item. Please try again or check your server logs for more details.', 'ninjadraw'),
            esc_html__('Server Error', 'ninjadraw'),
            500
          );
        }

        $redirect_url = add_query_arg(['page' => 'ninjadraw', 'action' => 'edit', 'id' => $id], admin_url('admin.php'));
        wp_redirect($redirect_url);
        exit;
      } elseif ($action === 'edit') {
        $id = sanitize_key(filter_input(INPUT_GET, 'id', FILTER_DEFAULT));
        if (!$id || !DataModel::currentUserCan($id, AccessModel::ACCESS_RIGHT_EDIT)) {
          wp_die(
            esc_html__('Sorry, you are not allowed to access this page.', 'ninjadraw'),
            esc_html__('Access Denied', 'ninjadraw'),
            403
          );
        }
      } elseif ($action === 'settings') {
        if (!current_user_can('manage_options')) {
          wp_die(
            esc_html__('Sorry, you are not allowed to manage plugin settings. Only administrators can access this page.', 'ninjadraw'),
            esc_html__('Access Denied', 'ninjadraw'),
            403
          );
        }
      }
    }
  }

  public function adminNotices()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $page = sanitize_key($_GET['page'] ?? '');
    if (!in_array($page, ['ninjadraw', 'ninjadraw-guide'], true)) {
      return;
    }

    $user_id = get_current_user_id();
    $hide_until = (int) get_user_meta($user_id, self::NOTICE_META_KEY, true);
    if ($hide_until > time()) {
      return;
    }

    $upgrade_url = esc_url(DataModel::getUpgradeUrl());
    $free_vs_pro_url = esc_url(DataModel::getPluginUrl());

    echo '<div class="notice notice-info is-dismissible ninjadraw-admin-notice" style="margin-bottom: 10px; background-image: linear-gradient(to bottom, rgb(87 162 255 / 20%) 0%, #fff 100%);">';

    echo '<h3>';
    echo esc_html__('Thank you for using NinjaDraw! ✨', 'ninjadraw');
    echo '</h3>';

    echo '<p>' . wp_kses(__('The free version already gives you unlimited diagrams and all the essential tools. Take your visuals further with <b>NinjaDraw PRO</b>:', 'ninjadraw'), ['b' => []]) . '</p>';
    echo '<ul style="margin: 0 0 0.75em 1.25em; list-style: none;">';
    echo '<li>✅ ' . esc_html__('Image canvas support (Media Library)', 'ninjadraw') . '</li>';
    echo '<li>✅ ' . esc_html__('Frame tool', 'ninjadraw') . '</li>';
    echo '<li>✅ ' . esc_html__('Interactive web embeds + Laser pointer', 'ninjadraw') . '</li>';
    echo '<li>✅ ' . esc_html__('Mermaid import', 'ninjadraw') . '</li>';
    echo '<li>✅ ' . esc_html__('Native .excalidraw files', 'ninjadraw') . '</li>';
    echo '<li>✅ ' . esc_html__('Global settings and user permissions to manage your whole team.', 'ninjadraw') . '</li>';
    echo '</ul>';
    
    echo '<p style="margin-top:25px">';
    echo '<a class="button button-primary" href="' . esc_url($upgrade_url) . '">' . esc_html__('Get NinjaDraw PRO', 'ninjadraw') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url($free_vs_pro_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn More', 'ninjadraw') . '</a>';
    echo '</p>';

    echo '</div>';
  }

  public function adminNoticesScripts($hook_suffix)
  {
    $page = sanitize_key($_GET['page'] ?? '');
    if (!in_array($page, ['ninjadraw', 'ninjadraw-guide'], true)) {
      return;
    }

    wp_enqueue_script('ninjadraw-admin-notice', NINJADRAW_PLUGIN_URL . 'assets/js/admin-notice.js', ['jquery'], NINJADRAW_PLUGIN_VERSION, true);
    wp_localize_script('ninjadraw-admin-notice', 'yalogica_ninjadraw_global_admin_notice', [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce(self::NOTICE_NONCE_ACTION),
    ]);
  }

  public function adminNoticesAjaxDismiss()
  {
    check_ajax_referer(self::NOTICE_NONCE_ACTION, 'nonce');

    update_user_meta(
      get_current_user_id(),
      self::NOTICE_META_KEY,
      time() + 30 * DAY_IN_SECONDS
    );

    wp_send_json_success();
  }

  public function showPage()
  {
    $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));

    if ($page !== 'ninjadraw') {
      return;
    }

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    wp_enqueue_style('ninjadraw-excalidraw', NINJADRAW_PLUGIN_URL . 'assets/vendor/excalidraw/index.css', [], NINJADRAW_PLUGIN_VERSION, 'all');
    wp_enqueue_style('ninjadraw-style', NINJADRAW_PLUGIN_URL . 'assets/css/style.css', [], NINJADRAW_PLUGIN_VERSION, 'all');

    wp_enqueue_style('ninjadraw-admin', NINJADRAW_PLUGIN_URL . 'assets/components/admin/index.css', [], NINJADRAW_PLUGIN_VERSION, 'all');
    wp_enqueue_script_module('ninjadraw-admin', NINJADRAW_PLUGIN_URL . 'assets/components/admin/index.js', [], NINJADRAW_PLUGIN_VERSION);

    echo '<script id="ninjadraw-admin-config">window.yalogica_ninjadraw_global = ' . wp_json_encode($this->getGlobals($id)) . ';</script>';

    wp_enqueue_media();

    require_once(NINJADRAW_PLUGIN_PATH . '/includes/Views/admin.php');
  }

  public function showPageGuide()
  {
    $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));

    if ($page !== 'ninjadraw-guide') {
      return;
    }

    wp_enqueue_style('ninjadraw-style', NINJADRAW_PLUGIN_URL . 'assets/css/style.css', [], NINJADRAW_PLUGIN_VERSION, 'all');

    require_once(NINJADRAW_PLUGIN_PATH . '/includes/Views/guide.php');
  }

  private function getGlobals($id = null)
  {
    $upload_dir = wp_upload_dir();
    

    $data = [
      'item_id' => $id,
      'site_url' => get_option('siteurl'),
      'plugin_url' => NINJADRAW_PLUGIN_URL,
      'icons_url' => NINJADRAW_PLUGIN_URL . 'assets/icons/',
      'upload_url' => $upload_dir['baseurl'] . '/ninjadraw/',
      'upgrade_url' => DataModel::getUpgradeUrl(),
      'is_pretty_permalinks' => get_option('permalink_structure') !== '',
      'rights' => [
        'view' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW),
        'create' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_CREATE),
        'edit' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_EDIT),
        'delete' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_DELETE),
        'publish' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_PUBLISH)
      ],
    ];

    return [
      'data' => $data,
      'api' => [
        'nonce' => wp_create_nonce('wp_rest'),
        'url' => esc_url_raw(rest_url(NINJADRAW_PLUGIN_REST_URL))
      ]
    ];
  }
}
