<?php

namespace Yalogica\NinjaDraw\Models;

defined("ABSPATH") || exit;

class SettingsModel
{
  public const MENU_ICON = "<svg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
    <path style='fill:rgba(255,255,255,.5);stroke-width:0;' d='m18.447 1.1565c-0.44023-0.43911-1.0117-0.45733-1.4701-2e-7l-16.171 16.15v5.8728h5.8803l16.171-16.15c0.45849-0.45839 0.44106-1.0288 1e-6 -1.4682zm-0.73504 2.2023 2.9401 2.9364-1.4701 1.4682-2.9401-2.9364zm-2.9401 2.9364 2.9401 2.9364-10.29 10.277-2.9401-2.9364zm-11.761 11.746 2.9401 2.9364h-2.9401zm3.3697-17.22c-0.25977 0.0022195-0.51626 0.11922-0.74023 0.34375l-4.4922 4.4902c-0.44764 0.44821-0.46622 1.0294 0 1.4961l4.1621 4.1641 1.5-1.498c-0.2994-0.30021-0.60002-0.59919-0.90039-0.89844l1.5039-1.5039-1.4141-1.4141-1.5039 1.5039c-0.36896-0.36607-0.736-0.73405-1.1035-1.1016l2.9961-2.9941 3.4141 3.416 1.4941-1.4961-4.1602-4.1641c-0.23364-0.23341-0.49609-0.34597-0.75586-0.34375zm12.291 11.881-1.4961 1.4961 3.4297 3.4316-2.9922 2.9922c-0.37391-0.37088-0.74533-0.74425-1.1172-1.1172l1.5039-1.5039-1.4141-1.4141-1.5039 1.5039c-0.30109-0.29787-0.59938-0.59853-0.89844-0.89844l-1.4961 1.498 4.1758 4.1777c0.47328 0.473 1.118 0.37827 1.5723-0.07617l4.4141-4.4141c0.44853-0.44783 0.46709-1.0298 0-1.4961z' />
    </svg>";
  
  public static function get()
  {
    $data = [];
    $access = AccessModel::get();

    $roles = [];
    foreach (wp_roles()->roles as $key => $role) {
      if (array_key_exists("read", $role["capabilities"])) {
        $roles[] = [
          "id" => $key,
          "title" => translate_user_role($role["name"])
        ];
      }
    }

    foreach ($roles as $role) {
      $permissionType = AccessModel::ACCESS_TYPE_EDIT_ALL;
      $rights = AccessModel::getDefaultRights();

      foreach ($access[AccessModel::ACCESS_PERMISSIONS] as $permission) {
        if ($permission[AccessModel::ACCESS_ROLE] == $role["id"]) {
          $permissionType = $permission[AccessModel::ACCESS_TYPE];
          $rights = $permission[AccessModel::ACCESS_RIGHTS];
          break;
        }
      }

      $data[AccessModel::ACCESS_PERMISSIONS][] = [
        AccessModel::ACCESS_ROLE => [
          "id" => $role["id"],
          "title" => $role["title"]
        ],
        AccessModel::ACCESS_TYPE => [
          "id" => $permissionType,
          "title" => AccessModel::getPermissionTypeTitleById($permissionType)
        ],
        "rights" => $rights
      ];
    }

    return $data;
  }

  public static function set($data = null)
  {
    $access = [];
    foreach ($data[AccessModel::ACCESS_PERMISSIONS] as $permission) {
      $access[AccessModel::ACCESS_PERMISSIONS][] = [
        "role" => sanitize_key($permission[AccessModel::ACCESS_ROLE]["id"]),
        "type" => sanitize_key($permission[AccessModel::ACCESS_TYPE]["id"]),
        "rights" => [
          AccessModel::ACCESS_RIGHT_VIEW => boolval($permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_VIEW]),
          AccessModel::ACCESS_RIGHT_CREATE => boolval($permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_CREATE]),
          AccessModel::ACCESS_RIGHT_EDIT => boolval($permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_EDIT]),
          AccessModel::ACCESS_RIGHT_DELETE => boolval($permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_DELETE]),
          AccessModel::ACCESS_RIGHT_PUBLISH => boolval($permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_PUBLISH])
        ]
      ];
    }
    return AccessModel::set($access);
  }
}
