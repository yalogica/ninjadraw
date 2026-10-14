<?php

namespace Yalogica\NinjaDraw\Rest\Controllers;

defined("ABSPATH") || exit;

use Yalogica\NinjaDraw\Models\DataModel;
use Yalogica\NinjaDraw\Models\AccessModel;

class DataController
{
  public function __construct() {}

  public function registerRestRoutes()
  {
    register_rest_route(
      NINJADRAW_PLUGIN_REST_URL,
      '/items',
      [
        [
          'methods' => \WP_REST_Server::READABLE,
          'callback' => [$this, 'getItems'],
          'permission_callback' => function () {
            return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW);
          },
          'args' => [
            'page' => [
              'default' => 1,
              'type' => 'integer',
              'sanitize_callback' => 'absint',
              'validate_callback' => function ($param) {
                return is_numeric($param) && $param >= 1;
              }
            ],
            'perpage' => [
              'default' => 5,
              'type' => 'integer',
              'sanitize_callback' => 'absint',
              'validate_callback' => function ($param) {
                return is_numeric($param) && $param >= 1 && $param <= 100;
              }
            ]
          ]
        ]
      ]
    );

    register_rest_route(
      NINJADRAW_PLUGIN_REST_URL,
      '/items/(?P<id>\d+)',
      [
        [
          'methods' => \WP_REST_Server::READABLE,
          'callback' => [$this, 'getItem'],
          'permission_callback' => function () {
            return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW);
          },
          'args' => [
            'id' => [
              'type' => 'integer',
              'required' => true,
            ]
          ]
        ],
        [
          'methods' => \WP_REST_Server::EDITABLE,
          'callback' => [$this, 'updateItem'],
          'permission_callback' => function () {
            return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_EDIT);
          },
          'args' => [
            'id' => [
              'type' => 'integer',
              'required' => true,
            ]
          ]
        ],
        [
          'methods' => \WP_REST_Server::DELETABLE,
          'callback' => [$this, 'deleteItem'],
          'permission_callback' => function () {
            return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_DELETE);
          },
          'args' => [
            'id' => [
              'type' => 'integer',
              'required' => true,
            ]
          ]
        ]
      ]
    );

    register_rest_route(
      NINJADRAW_PLUGIN_REST_URL,
      '/items/(?P<id>\d+)/copy',
      [
        'methods' => \WP_REST_Server::CREATABLE,
        'callback' => [$this, 'copyItem'],
        'permission_callback' => function () {
          return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_CREATE);
        },
        'args' => [
          'id' => [
            'type' => 'integer',
            'required' => true,
          ]
        ]
      ]
    );

    register_rest_route(
      NINJADRAW_PLUGIN_REST_URL,
      '/items/(?P<id>\d+)/status/(?P<status>(?i)draft|publish)',
      [
        [
          'methods' => \WP_REST_Server::EDITABLE,
          'callback' => [$this, 'updateItemStatus'],
          'permission_callback' => function () {
            return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_PUBLISH);
          }
        ]
      ]
    );

    register_rest_route(
      NINJADRAW_PLUGIN_REST_URL,
      '/items/options',
      [
        [
          'methods' => \WP_REST_Server::READABLE,
          'callback' => [$this, 'getItemsOptions'],
          'permission_callback' => function () {
            return AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW);
          }
        ]
      ]
    );

    register_rest_route(
      NINJADRAW_PLUGIN_PUBLIC_REST_URL,
      "/images/(?P<id>\d+)",
      [
        [
          "methods" => \WP_REST_Server::READABLE,
          "callback" => [$this, "getImage"],
          "permission_callback" => "__return_true"
        ]
      ]
    );
  }

  public function getItems(\WP_REST_Request $request)
  {
    $page = intval($request->get_param('page'), 10);
    $perpage = intval($request->get_param('perpage'), 10);
    $filter = sanitize_key($request->get_param('filter'));
    $orderby = sanitize_key($request->get_param('orderby'));
    $order = sanitize_key($request->get_param('order'));
    $search = sanitize_text_field($request->get_param('search'));

    try {
      $data = DataModel::getItems($page, $perpage, $filter, $orderby, $order, $search);

      if ($data) {
        return new \WP_REST_Response([
          'message' => esc_html__('NinjaDraw data have been retrieved.', 'ninjadraw'),
          'data' => $data,
        ], 200);
      } else {
        return new \WP_REST_Response([
          'message' => esc_html__('Failed to get NinjaDraw data.', 'ninjadraw')
        ], 400);
      }
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to get NinjaDraw data.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function getItem(\WP_REST_Request $request)
  {
    $id = intval($request->get_param('id'), 10);

    try {
      $item = DataModel::getItem($id);

      if ($item) {
        return new \WP_REST_Response([
          'message' => esc_html__('Item data has been retrieved.', 'ninjadraw'),
          'data' => [
            'item' => $item
          ]
        ], 200);
      } else {
        return new \WP_REST_Response([
          'message' => esc_html__('Failed to get item data.', 'ninjadraw')
        ], 400);
      }
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to get item data.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function updateItem(\WP_REST_Request $request)
  {
    $id = intval($request->get_param('id'), 10);
    $data = $request->get_json_params();

    try {
      $result = DataModel::updateItem($id, $data);

      if ($result) {
        return new \WP_REST_Response([
          'message' => esc_html__('Item data has been saved.', 'ninjadraw')
        ], 200);
      } else {
        return new \WP_REST_Response([
          'message' => esc_html__('Failed to save item data.', 'ninjadraw')
        ], 400);
      }
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to save item data.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function deleteItem(\WP_REST_Request $request)
  {
    $id = intval($request->get_param('id'), 10);

    try {
      $result = DataModel::deleteItem($id);

      if ($result) {
        return new \WP_REST_Response([
          'message' => esc_html__('Item has been deleted.', 'ninjadraw')
        ], 200);
      } else {
        return new \WP_REST_Response([
          'message' => esc_html__('Failed to delete item.', 'ninjadraw')
        ], 400);
      }
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to delete item.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function copyItem(\WP_REST_Request $request)
  {
    $id = intval($request->get_param('id'), 10);

    try {
      $id = DataModel::copyItem($id);

      if ($id) {
        return new \WP_REST_Response([
          'message' => esc_html__('Item has been copied.', 'ninjadraw'),
          'data' => [
            'id' => $id
          ]
        ], 200);
      } else {
        return new \WP_REST_Response([
          'message' => esc_html__('Failed to copy item.', 'ninjadraw')
        ], 400);
      }
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to copy item.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function updateItemStatus(\WP_REST_Request $request)
  {
    $id = intval($request->get_param('id'), 10);
    $status = sanitize_key($request->get_param('status'));

    try {
      $result = DataModel::updateItemStatus($id, $status);

      if ($result) {
        return new \WP_REST_Response([
          'message' => esc_html__('Item status has been updated.', 'ninjadraw')
        ], 200);
      } else {
        return new \WP_REST_Response([
          'message' => esc_html__('Failed to update item status.', 'ninjadraw')
        ], 400);
      }
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to update item status.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function getItemsOptions(\WP_REST_Request $request)
  {
    try {
      $options = DataModel::getCurrentUserItems();

      return new \WP_REST_Response([
        'message' => esc_html__('NinjaDraw data have been retrieved.', 'ninjadraw'),
        'data' => [
          'options' => is_array($options) ? $options : []
        ]
      ], 200);
    } catch (\Exception $e) {
      return new \WP_REST_Response([
        'message' => esc_html__('Failed to get NinjaDraw data.', 'ninjadraw') . ' ' . esc_html($e->getMessage())
      ], 500);
    }
  }

  public function getImage(\WP_REST_Request $request)
  {
    $id = intval($request->get_param("id"), 10);

    $image = DataModel::getItemImage($id);
    $response = isset($image) ? ["success" => true, "image" => $image] : ["success" => false];

    return new \WP_REST_Response($response);
  }
}
