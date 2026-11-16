<?php

namespace Yalogica\NinjaDraw\Models;

defined('ABSPATH') || exit;

class DataModel
{
  public const POST_TYPE = 'ninjadraw';
  public const POST_STATUS_AUTO_DRAFT = 'auto-draft';
  public const POST_STATUS_DRAFT = 'draft';
  public const POST_STATUS_PUBLISHED = 'publish';
  public const POST_STATUS_TRASH = 'trash';

  public const META_EDITED_BY = '_ninjadraw_edited_by';
  public const META_ROLE = '_ninjadraw_role';
  public const META_FILES = '_ninjadraw_files';
  public const META_SVG_IMAGE = '_ninjadraw_svg';
  public const META_PRESENTATION = '_ninjadraw_presentation';

  public static function getSup()
  {
    return 'lite';
  }

  public static function getUpgradeUrl()
  {
    return admin_url('admin.php?page=ninjadraw-pricing');
  }

  public static function getPluginUrl()
  {
    return 'https://yalogica.com/ninjadraw/';
  }

  public static function getTotalCount()
  {
    $counts = wp_count_posts(self::POST_TYPE);
    return (int) ($counts->publish + $counts->draft);
  }

  private static function getTotal($filter, $post_statuses, $search = '%')
  {
    $permissionType = AccessModel::getCurrentUserPermissionType();

    if ($permissionType) {
      global $wpdb;
      $placeholder = implode(',', array_fill(0, count($post_statuses), '%s'));

      switch ($permissionType) {
        case AccessModel::ACCESS_TYPE_EDIT_ALL: {
            $sql = "
              SELECT COUNT(P.id) as total
              FROM {$wpdb->posts} AS P 
              WHERE P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
              . ($filter == 'mine' ? " AND P.post_author=%d" : " AND 0=%d");

            $args = [
              self::POST_TYPE,
              ...$post_statuses,
              $search,
              $filter == 'mine' ? get_current_user_id() : 0
            ];
          }
          break;
        case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
            $sql = "
              SELECT COUNT(P.id) as total
              FROM {$wpdb->posts} AS P
              LEFT JOIN {$wpdb->postmeta} AS MUR
              ON MUR.post_id=P.id AND MUR.meta_key=%s 
              WHERE MUR.meta_value = %s AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
              . ($filter == 'mine' ? " AND P.post_author=%d" : " AND 0=%d");

            $args = [
              self::META_ROLE,
              AccessModel::getCurrentUserRole(),
              self::POST_TYPE,
              ...$post_statuses,
              $search,
              $filter == "mine" ? get_current_user_id() : 0
            ];
          }
          break;
        case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
            $sql = "
              SELECT COUNT(P.id) as total
              FROM {$wpdb->posts} AS P
              WHERE P.post_author = %d AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s";

            $args = [
              get_current_user_id(),
              self::POST_TYPE,
              ...$post_statuses,
              $search
            ];
          }
          break;
      }

      // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $sql = $wpdb->prepare($sql, ...$args);
      return $wpdb->get_var($sql);
      // phpcs:enable
    }

    return null;
  }

  private static function getRows($filter, $post_statuses, $search, $orderby, $order, $offset, $count)
  {
    $permissionType = AccessModel::getCurrentUserPermissionType();

    if ($permissionType) {
      global $wpdb;
      $placeholder = implode(',', array_fill(0, count($post_statuses), '%s'));

      switch ($permissionType) {
        case AccessModel::ACCESS_TYPE_EDIT_ALL: {
            $sql = "
              SELECT P.id as id, P.post_title AS title, P.post_status AS status, P.post_author AS author, MLE.meta_value AS edited_by, MUR.meta_value AS role, P.post_date AS created, P.post_modified AS edited
              FROM {$wpdb->posts} AS P
              LEFT JOIN {$wpdb->postmeta} AS MLE
              ON MLE.post_id = P.id AND MLE.meta_key = %s
              LEFT JOIN {$wpdb->postmeta} AS MUR
              ON MUR.post_id = P.id AND MUR.meta_key = %s
              WHERE P.post_type=%s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
              . ($filter == 'mine' ? " AND P.post_author=%d" : " AND 0=%d") . "
              ORDER BY %1s %1s
              LIMIT %d, %d";

            $args = [
              self::META_EDITED_BY,
              self::META_ROLE,
              self::POST_TYPE,
              ...$post_statuses,
              $search,
              $filter == 'mine' ? get_current_user_id() : 0,
              $orderby,
              $order,
              $offset,
              $count
            ];
          }
          break;
        case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
            $sql = "
              SELECT P.id as id, P.post_title AS title, P.post_status AS status, P.post_author AS author, MLE.meta_value AS edited_by, MUR.meta_value AS role, P.post_date AS created, P.post_modified AS edited
              FROM {$wpdb->posts} AS P
              LEFT JOIN {$wpdb->postmeta} AS MLE
              ON MLE.post_id = P.id AND MLE.meta_key = %s
              LEFT JOIN {$wpdb->postmeta} AS MUR
              ON MUR.post_id = P.id AND MUR.meta_key = %s
              WHERE MUR.meta_value = %s AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
              . ($filter == 'mine' ? " AND P.post_author=%d" : " AND 0=%d") . "
              ORDER BY %1s %1s
              LIMIT %d, %d";

            $args = [
              self::META_EDITED_BY,
              self::META_ROLE,
              AccessModel::getCurrentUserRole(),
              self::POST_TYPE,
              ...$post_statuses,
              $search,
              $filter == 'mine' ? get_current_user_id() : 0,
              $orderby,
              $order,
              $offset,
              $count
            ];
          }
          break;
        case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
            $sql = "
              SELECT P.id as id, P.post_title AS title, P.post_status AS status, P.post_author AS author, MLE.meta_value AS edited_by, MUR.meta_value AS role, P.post_date AS created, P.post_modified AS edited
              FROM {$wpdb->posts} AS P
              LEFT JOIN {$wpdb->postmeta} AS MLE
              ON MLE.post_id = P.id AND MLE.meta_key = %s
              LEFT JOIN {$wpdb->postmeta} AS MUR
              ON MUR.post_id = P.id AND MUR.meta_key = %s
              WHERE P.post_author = %d AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s
              ORDER BY %1s %1s
              LIMIT %d, %d";

            $args = [
              self::META_EDITED_BY,
              self::META_ROLE,
              get_current_user_id(),
              self::POST_TYPE,
              ...$post_statuses,
              $search,
              $orderby,
              $order,
              $offset,
              $count
            ];
          }
          break;
      }

      // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
      $sql = $wpdb->prepare($sql, ...$args);
      return $wpdb->get_results($sql, 'ARRAY_A');
      // phpcs:enable
    }

    return null;
  }

  private static function getColumns()
  {
    return [
      ['id' => 'id',        'name' => 'id',        'sortable' => false],
      ['id' => 'title',     'name' => 'title',     'sortable' => true],
      ['id' => 'status',    'name' => 'status',    'sortable' => false],
      ['id' => 'shortcode', 'name' => 'shortcode', 'sortable' => false],
      ['id' => 'author',    'name' => 'author',    'sortable' => false],
      ['id' => 'edited_by', 'name' => 'edited by', 'sortable' => false],
      ['id' => 'role',      'name' => 'role',      'sortable' => false],
      ['id' => 'created',   'name' => 'created',   'sortable' => true],
      ['id' => 'edited',    'name' => 'edited',    'sortable' => true]
    ];
  }

  public static function getItems($page, $perpage = 10, $status = 'all', $orderby = null, $order = null, $search = null)
  {
    global $wpdb;

    $page = intval($page, 10);
    $page = $page <= 0 ? 1 : $page;
    $count = intval($perpage, 10);
    $offset = ($page - 1) * $count;

    $status_valid = ['all', 'published', 'draft'];
    $status = in_array($status, $status_valid, true) ? $status : $status_valid[0];

    switch ($status) {
      case 'all':
        $post_statuses = [self::POST_STATUS_PUBLISHED, self::POST_STATUS_DRAFT];
        break;
      case 'published':
        $post_statuses = [self::POST_STATUS_PUBLISHED];
        break;
      case 'draft':
        $post_statuses = [self::POST_STATUS_DRAFT];
        break;
    }

    $orderby_valid = ['title', 'created', 'edited'];
    $orderby = in_array($orderby, $orderby_valid, true) ? $orderby : 'edited';
    switch ($orderby) {
      case 'title':
        $orderBy = 'post_title';
        break;
      case 'created':
        $orderBy = 'post_date';
        break;
      case 'edited':
        $orderBy = 'post_modified';
        break;
    }

    $order_valid = ['asc', 'desc'];
    $order = in_array($order, $order_valid, true) ? $order : 'desc';

    $filter  = $search ? '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%' : '%';

    $total = self::getTotal($status, $post_statuses, $filter);
    $items = self::getRows($status, $post_statuses, $filter, $orderBy, $order, $offset, $count);

    $data = null;
    if (!$wpdb->last_error) {
      $items = array_map(function ($item) {
        $item['author'] = get_the_author_meta('display_name', $item['author']);
        $item['edited_by'] = get_the_author_meta('display_name', $item['edited_by']);
        $item['role'] = translate_user_role($item['role']);
        return $item;
      }, $items);

      $data['total'] = intval($total, 10);
      $data['pages'] = $count ? ceil($data['total'] / $count) : 1;
      $data['page'] = $page;
      $data['perpage'] = $perpage;
      $data['order'] = $order;
      $data['orderby'] = $orderby;
      $data['search'] = $search;
      $data['items'] = $items;
      $data['columns'] = self::getColumns();
    }
    return $data;
  }

  public static function getItemsCount()
  {
    global $wpdb;

    $sql = "
      SELECT COUNT(P.id) as total
      FROM {$wpdb->posts} AS P
      WHERE P.post_type = %s";

    $args = [
      self::POST_TYPE
    ];

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $sql = $wpdb->prepare($sql, ...$args);
    $count = intval($wpdb->get_var($sql));
    // phpcs:enable

    return $count;
  }

  public static function getFilterCounts()
  {
    return [
      'all' => self::getTotal('all', [self::POST_STATUS_PUBLISHED, self::POST_STATUS_DRAFT]),
      'mine' => self::getTotal('mine', [self::POST_STATUS_PUBLISHED, self::POST_STATUS_DRAFT]),
      'published' => self::getTotal('published', [self::POST_STATUS_PUBLISHED]),
      'draft' => self::getTotal('draft', [self::POST_STATUS_DRAFT]),
      'trash' => self::getTotal('all', [self::POST_STATUS_TRASH])
    ];
  }

  public static function getCurrentUserItems()
  {
    $permissionType = AccessModel::getCurrentUserPermissionType();

    if ($permissionType) {
      global $wpdb;

      switch ($permissionType) {
        case AccessModel::ACCESS_TYPE_EDIT_ALL: {
            $sql = "
              SELECT P.id as id, P.post_title AS title
              FROM {$wpdb->posts} P
              WHERE P.post_type = %s AND P.post_status = %s
              ORDER BY P.post_title";

            $args = [
              self::POST_TYPE,
              self::POST_STATUS_PUBLISHED
            ];
          }
          break;
        case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
            $sql = "
              SELECT P.id as id, P.post_title AS title
              FROM {$wpdb->posts} P
              LEFT JOIN {$wpdb->postmeta} AS MUR
              ON MUR.post_id = P.id AND MUR.meta_key = %s
              WHERE MUR.meta_value = %s AND P.post_type = %s AND P.post_status = %s
              ORDER BY P.post_title";

            $args = [
              self::META_ROLE,
              AccessModel::getCurrentUserRole(),
              self::POST_TYPE,
              self::POST_STATUS_PUBLISHED
            ];
          }
          break;
        case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
            $sql = "
              SELECT P.id as id, P.post_title AS title
              FROM {$wpdb->posts} P
              WHERE P.post_author = %d AND P.post_type = %s AND P.post_status = %s
              ORDER BY P.post_title";

            $args = [
              get_current_user_id(),
              self::POST_TYPE,
              self::POST_STATUS_PUBLISHED
            ];
          }
          break;
      }

      // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $sql = $wpdb->prepare($sql, ...$args);
      $items = $wpdb->get_results($sql, 'ARRAY_A');
      // phpcs:enable

      if (!$wpdb->last_error) {
        return $items;
      }
    }

    return null;
  }

  public static function currentUserCan($post_id, $right)
  {
    $permissionType = AccessModel::getCurrentUserPermissionType();

    switch ($permissionType) {
      case AccessModel::ACCESS_TYPE_EDIT_ALL: {
          return AccessModel::currentUserCan($right);
        }
        break;
      case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
          $role = get_post_meta($post_id, self::META_ROLE, true);
          if ($role === AccessModel::getCurrentUserRole()) {
            return AccessModel::currentUserCan($right);
          }
        }
        break;
      case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
          $post = get_post($post_id);
          if (!$post) {
            return false;
          }
          if ((int) $post->post_author === get_current_user_id()) {
            return AccessModel::currentUserCan($right);
          }
        }
        break;
    }

    return false;
  }

  public static function getItem($id)
  {
    $post = get_post($id);
    if ($post && $post->post_type === self::POST_TYPE) {
      $data = [];
      $data['id'] = $id;
      $data['title'] = $post->post_title;
      $data['status'] = $post->post_status;
      $data['source'] = $post->post_content;
      $data['files'] = get_post_meta($id, self::META_FILES, true);
      $data['author'] = get_the_author_meta('display_name', $post->post_author);
      $data['edited_by'] = get_the_author_meta('display_name', get_post_meta($id, self::META_EDITED_BY, true));
      $data['role'] = translate_user_role(get_post_meta($id, self::META_ROLE, true));
      $data['created'] = $post->post_date;
      $data['edited'] = $post->post_modified;

      return $data;
    }
    return null;
  }

  public static function createItemAutoDraft()
  {
    if (AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT])) {
      $post = [
        'post_type' => self::POST_TYPE,
        'post_name' => uniqid(),
        'post_status' => self::POST_STATUS_AUTO_DRAFT,
        'meta_input' => [
          self::META_EDITED_BY => get_current_user_id(),
          self::META_ROLE => AccessModel::getCurrentUserRole()
        ]
      ];
      $post_id = wp_insert_post($post);

      if ($post_id !== null) {
        return $post_id;
      }
    }
    return null;
  }

  private static function preparePresentationForStorage($data)
  {
    if (is_string($data)) {
      $presentation = json_decode($data, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
      }
    } elseif (is_array($data)) {
      $presentation = $data;
    } else {
      return null;
    }

    if (
      !is_array($presentation) ||
      !isset($presentation['version']) ||
      intval($presentation['version'], 10) !== 1 ||
      !isset($presentation['scene']) ||
      !is_array($presentation['scene']) ||
      !isset($presentation['padding']) ||
      !is_array($presentation['padding']) ||
      !isset($presentation['transition']) ||
      !is_array($presentation['transition']) ||
      !isset($presentation['slides']) ||
      !is_array($presentation['slides'])
    ) {
      return null;
    }

    if (
      array_key_exists('loop', $presentation) &&
      !is_bool($presentation['loop'])
    ) {
      return null;
    }

    if (
      array_key_exists('clip', $presentation) &&
      !is_bool($presentation['clip'])
    ) {
      return null;
    }

    $scene_width = $presentation['scene']['width'] ?? 0;
    $scene_height = $presentation['scene']['height'] ?? 0;

    if (
      !is_numeric($scene_width) ||
      !is_numeric($scene_height) ||
      floatval($scene_width) <= 0 ||
      floatval($scene_height) <= 0
    ) {
      return null;
    }

    foreach (
      ['top', 'right', 'bottom', 'left']
      as $padding_key
    ) {
      if (
        !isset($presentation['padding'][$padding_key]) ||
        !is_numeric($presentation['padding'][$padding_key]) ||
        floatval($presentation['padding'][$padding_key]) < 0
      ) {
        return null;
      }
    }

    $transition = $presentation['transition'];

    if (
      !isset($transition['type']) ||
      !in_array($transition['type'], ['none', 'pan', 'animation'], true) ||
      !isset($transition['duration']) ||
      !is_numeric($transition['duration']) ||
      floatval($transition['duration']) < 0 ||
      !isset($transition['easing']) ||
      !is_string($transition['easing']) ||
      trim($transition['easing']) === ''
    ) {
      return null;
    }

    foreach ($presentation['slides'] as $slide) {
      if (
        !is_array($slide) ||
        empty($slide['id']) ||
        !is_string($slide['id']) ||
        !isset(
          $slide['x'],
          $slide['y'],
          $slide['width'],
          $slide['height']
        ) ||
        !is_numeric($slide['x']) ||
        !is_numeric($slide['y']) ||
        !is_numeric($slide['width']) ||
        !is_numeric($slide['height']) ||
        floatval($slide['width']) <= 0 ||
        floatval($slide['height']) <= 0
      ) {
        return null;
      }

      if (
        array_key_exists('title', $slide) &&
        $slide['title'] !== null &&
        !is_string($slide['title'])
      ) {
        return null;
      }
    }

    $json = wp_json_encode(
      $presentation,
      JSON_UNESCAPED_UNICODE |
      JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
      return null;
    }

    return wp_slash($json);
  }

  public static function createItem($data)
  {
    if (is_array($data) && AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT])) {
      $config = wp_slash(json_encode($data['source'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      if ($config === false) {
        return null;
      }
      
      $presentation = null;
      if (array_key_exists('presentation', $data)) {
        $presentation = self::preparePresentationForStorage($data['presentation']);
        if ($presentation === null) {
          return null;
        }
      }

      $post = [
        'post_type' => self::POST_TYPE,
        'post_name' => uniqid(),
        'post_status' => self::POST_STATUS_DRAFT,
        'post_mime_type' => 'application/json',
        'post_title' => isset($data['title']) ? sanitize_text_field($data['title']) : '',
        'post_content' => $config,
        'meta_input' => array_filter([
          self::META_EDITED_BY => get_current_user_id(),
          self::META_ROLE => AccessModel::getCurrentUserRole(),
          self::META_FILES => wp_slash($data['files']),
          self::META_SVG_IMAGE => wp_slash($data['svg']),
          self::META_PRESENTATION => $presentation,
        ], static function ($value) {
          return $value !== null;
        })
      ];
      $post_id = wp_insert_post($post);

      if ($post_id !== null) {
        return $post_id;
      }
    }
    return null;
  }
  
  public static function trashItem($id)
  {
    if (self::currentUserCan($id, AccessModel::ACCESS_RIGHT_DELETE)) {
      $post = get_post($id);
      if (!$post || $post->post_type !== self::POST_TYPE) {
        return;
      }

      wp_trash_post($id);
    }
  }

  public static function restoreItem($id)
  {
    if (self::currentUserCan($id, AccessModel::ACCESS_RIGHT_DELETE)) {
      $post = get_post($id);
      if (!$post || $post->post_type !== self::POST_TYPE) {
        return;
      }

      wp_untrash_post($id);
    }
  }

  public static function deleteItem($id)
  {
    if (!self::currentUserCan($id, AccessModel::ACCESS_RIGHT_DELETE)) {
      return false;
    }

    $id = intval($id);
    if ($id <= 0) {
      return false;
    }

    $post = get_post($id);
    if (!$post || $post->post_type !== self::POST_TYPE) {
      return false;
    }

    return wp_delete_post($id, true);
  }

  public static function copyItem($id)
  {
    if (!self::currentUserCan($id, [AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT])) {
      return null;
    }

    $id = intval($id);
    if ($id <= 0) {
      return null;
    }

    $source = get_post($id);
    if (!$source || $source->post_type !== self::POST_TYPE) {
      return null;
    }

    $title = $source->post_title . (stripos($source->post_title, '[Copy]') ? '' : ' [Copy]');

    $post = [
      'post_type' => self::POST_TYPE,
      'post_name' => uniqid(),
      'post_status' => self::POST_STATUS_DRAFT,
      'post_mime_type' => 'application/json',
      'post_author' => get_current_user_id(),
      'post_title'  => $title,
      'post_content' => $source->post_content
    ];
    $post_id = wp_insert_post($post);

    $post_meta = get_post_custom($id);
    foreach ($post_meta as $key => $values) {
      if (in_array($key, ['_edit_lock', '_edit_last', self::META_EDITED_BY, self::META_ROLE], true)) {
        continue;
      }
      foreach ($values as $value) {
        add_post_meta($post_id, $key, $value, true);
      }
    }

    add_post_meta($post_id, self::META_EDITED_BY, get_current_user_id(), true);
    add_post_meta($post_id, self::META_ROLE, AccessModel::getCurrentUserRole(), true);

    if ($post_id !== null) {
      return $post_id;
    }

    return null;
  }

  public static function updateItem($id, $data)
  {
    $post = get_post($id);
    if (!$post || $post->post_type !== self::POST_TYPE) {
      return false;
    }

    if (is_array($data) && self::currentUserCan($id, AccessModel::ACCESS_RIGHT_EDIT)) {
      $presentation = null;
      if (array_key_exists('presentation', $data)) {
        $presentation = self::preparePresentationForStorage($data['presentation']);
        if ($presentation === null) {
          return false;
        }
      }

      $post = [
        'ID' => $id,
        'post_mime_type' => 'application/json',
        'post_title' => sanitize_text_field($data['title']),
        'post_content' => wp_slash($data['source']),
        'meta_input' => array_filter([
          self::META_EDITED_BY => get_current_user_id(),
          self::META_FILES => wp_slash($data['files']),
          self::META_SVG_IMAGE => wp_slash($data['svg']),
          self::META_PRESENTATION => $presentation,
        ], static function ($value) {
          return $value !== null;
        })
      ];

      if (in_array($data['status'], [self::POST_STATUS_AUTO_DRAFT])) {
        $post['post_status'] = self::POST_STATUS_DRAFT;
      }

      $post_id = wp_update_post($post);

      if ($post_id !== null) {
        return true;
      }
    }

    return false;
  }

  public static function updateItemStatus($id, $status)
  {
    $post = get_post($id);
    if (!$post || $post->post_type !== self::POST_TYPE) {
      return false;
    }

    if (in_array($status, [self::POST_STATUS_DRAFT, self::POST_STATUS_PUBLISHED]) && self::currentUserCan($id, AccessModel::ACCESS_RIGHT_PUBLISH)) {
      $post = [
        'ID' => $id,
        'post_status' => sanitize_key($status)
      ];
      $post_id = wp_update_post($post);

      if ($post_id !== null) {
        return true;
      }
    }

    return false;
  }

  public static function getItemImage($id)
  {
    $post = get_post($id);
    if (!$post || $post->post_type !== self::POST_TYPE || $post->post_status !== self::POST_STATUS_PUBLISHED) {
      return null;
    }

    $image = get_post_meta($id, self::META_SVG_IMAGE, true);
    if ($image) {
      return $image;
    }

    return null;
  }

  public static function getItemPresentation($id)
  {
    $post = get_post($id);

    if (!$post || $post->post_type !== self::POST_TYPE || $post->post_status !== self::POST_STATUS_PUBLISHED) {
      return null;
    }

    $svg = get_post_meta($id, self::META_SVG_IMAGE, true);
    $manifest_json = get_post_meta($id, self::META_PRESENTATION, true);

    if (empty($svg) || empty($manifest_json)) {
      return null;
    }

    $presentation = json_decode($manifest_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($presentation)) {
      return null;
    }

    return [
      'svg' => $svg,
      'presentation' => $presentation,
    ];
  }
}
