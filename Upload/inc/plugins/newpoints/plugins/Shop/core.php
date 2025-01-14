<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/core.php)
 *    Author: Diogo Parrinha
 *    Copyright: © 2009 Diogo Parrinha
 *
 *    Website: https://ougc.network
 *
 *    Integrates a shop system with NewPoints.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

declare(strict_types=1);

namespace Newpoints\Shop\Core;

use function Newpoints\Core\get_setting;

use const Newpoints\Shop\ROOT;

const FILE_UPLOAD_ERROR_FAILED = 1;

const FILE_UPLOAD_ERROR_INVALID_TYPE = 2;

const FILE_UPLOAD_ERROR_UPLOAD_SIZE = 3;

const FILE_UPLOAD_ERROR_RESIZE = 4;

function templates_get(string $template_name = '', bool $enable_html_comments = true): string
{
    return \Newpoints\Core\templates_get($template_name, $enable_html_comments, ROOT, 'shop_');
}

function item_insert(array $item_data, bool $is_update = false, int $item_id = 0): int
{
    global $db;

    $insert_data = [];

    if (isset($item_data['iid'])) {
        $insert_data['iid'] = (int)$item_data['iid'];
    }

    if (isset($item_data['cid'])) {
        $insert_data['cid'] = (int)$item_data['cid'];
    }

    if (isset($item_data['name'])) {
        $insert_data['name'] = $db->escape_string($item_data['name']);
    }

    if (isset($item_data['description'])) {
        $insert_data['description'] = $db->escape_string($item_data['description']);
    }

    if (isset($item_data['price'])) {
        $insert_data['price'] = (float)$item_data['price'];
    }

    if (isset($item_data['icon'])) {
        $insert_data['icon'] = $db->escape_string($item_data['icon']);
    }

    if (isset($item_data['visible'])) {
        $insert_data['visible'] = (int)$item_data['visible'];
    }

    if (isset($item_data['disporder'])) {
        $insert_data['disporder'] = (int)$item_data['disporder'];
    }

    if (isset($item_data['infinite'])) {
        $insert_data['infinite'] = (int)$item_data['infinite'];
    }

    if (isset($item_data['user_limit'])) {
        $insert_data['user_limit'] = (int)$item_data['user_limit'];
    }

    if (isset($item_data['stock'])) {
        $insert_data['stock'] = (int)$item_data['stock'];
    }

    if (isset($item_data['sendable'])) {
        $insert_data['sendable'] = (int)$item_data['sendable'];
    }

    if (isset($item_data['sellable'])) {
        $insert_data['sellable'] = (int)$item_data['sellable'];
    }

    if (isset($item_data['pm'])) {
        $insert_data['pm'] = $db->escape_string($item_data['pm']);
    }

    if (isset($item_data['pmadmin'])) {
        $insert_data['pmadmin'] = $db->escape_string($item_data['pmadmin']);
    }

    if ($is_update) {
        $db->update_query('newpoints_shop_items', $insert_data, "iid='{$item_id}'", 1);
    } else {
        $item_id = (int)$db->insert_query('newpoints_shop_items', $insert_data);
    }

    return $item_id;
}

function item_delete(int $item_id): bool
{
    global $db;

    $db->delete_query('newpoints_shop_user_items', "item_id='{$item_id}'");

    $db->delete_query('newpoints_shop_items', "iid='{$item_id}'", 1);

    return true;
}

function item_update(array $item_data, int $item_id): int
{
    return item_insert($item_data, true, $item_id);
}

function item_get(array $where_clauses = [], array $query_fields = ['*'], array $query_options = []): array
{
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_items',
        implode(',', $query_fields),
        implode(' AND ', $where_clauses),
        $query_options
    );

    return (array)$db->fetch_array($query);
}

function category_get(array $where_clauses, array $query_fields = ['*'], array $query_options = ['limit' => 1]): array
{
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_categories',
        implode(',', $query_fields),
        implode(' AND ', $where_clauses)
    );

    $category_objects = [];

    if (!$db->num_rows($query)) {
        return $category_objects;
    }

    if (!empty($query_options['limit']) && (int)$query_options['limit'] === 1) {
        return (array)$db->fetch_array($query);
    }

    while ($category_data = $db->fetch_array($query)) {
        if (isset($category_data['cid'])) {
            $category_objects[(int)$category_data['cid']] = $category_data;
        } else {
            $category_objects[] = $category_data;
        }
    }

    return $category_objects;
}

function items_get(
    array $where_clauses = [],
    array $query_fields = ['iid'],
    array $query_options = []
): array {
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_items',
        implode(',', $query_fields),
        implode(' AND ', $where_clauses),
        $query_options
    );

    $items_objects = [];

    if ($db->num_rows($query)) {
        while ($item_data = $db->fetch_array($query)) {
            if (!empty($item_data['iid'])) {
                $items_objects[(int)$item_data['iid']] = $item_data;
            } else {
                $items_objects[] = $item_data;
            }
        }
    }

    return $items_objects;
}

function items_get_visible(): array
{
    $categories_data = category_get(["visible='1'"], ['cid'], []);

    if (empty($categories_data)) {
        return [];
    }

    $visible_category_ids = implode("','", array_map('intval', array_unique(array_column($categories_data, 'cid'))));

    $active_items_ids = items_get(["visible='1'", "cid IN ('{$visible_category_ids}')"]);

    return array_unique(array_column($active_items_ids, 'iid'));
}

function item_upload_icon(array $item_file, int $item_id): array
{
    require_once MYBB_ROOT . 'inc/functions_upload.php';

    if (!is_uploaded_file($item_file['tmp_name'])) {
        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    $file_extension = get_extension(my_strtolower($item_file['name']));

    if (!preg_match('#^(gif|jpg|jpeg|jpe|bmp|png)$#i', $file_extension)) {
        return ['error' => FILE_UPLOAD_ERROR_INVALID_TYPE];
    }

    $upload_path = get_setting('shop_upload_path');

    $file_name = 'icon_' . TIME_NOW . '_' . md5(uniqid((string)rand(), true)) . '.' . $file_extension;

    $file_upload = upload_file($item_file, $upload_path, $file_name);

    $full_file_path = "{$upload_path}/{$file_name}";

    if (!empty($file_upload['error'])) {
        delete_uploaded_file($full_file_path);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    if (!file_exists($full_file_path)) {
        delete_uploaded_file($full_file_path);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    $image_dimensions = getimagesize($full_file_path);

    if (!is_array($image_dimensions)) {
        delete_uploaded_file($full_file_path);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    if (get_setting('upload_dimensions')) {
        list($maximum_width, $maximum_height) = preg_split('/[|x]/', get_setting('upload_dimensions'));

        if (($maximum_width && $image_dimensions[0] > $maximum_width) || ($maximum_height && $image_dimensions[1] > $maximum_height)) {
            require_once MYBB_ROOT . 'inc/functions_image.php';

            $thumbnail = generate_thumbnail(
                $full_file_path,
                $upload_path,
                $file_name,
                $maximum_height,
                $maximum_width
            );

            if (empty($thumbnail['filename'])) {
                delete_uploaded_file($full_file_path);

                return ['error' => FILE_UPLOAD_ERROR_RESIZE];
            } else {
                copy_file_to_cdn("{$upload_path}/{$thumbnail['filename']}");

                $item_file['size'] = filesize($full_file_path);

                $image_dimensions = getimagesize($full_file_path);
            }
        }
    }

    $item_file['type'] = my_strtolower($item_file['type']);

    switch ($item_file['type']) {
        case 'image/gif':
            $imageType = 1;
            break;
        case 'image/jpeg':
        case 'image/x-jpg':
        case 'image/x-jpeg':
        case 'image/pjpeg':
        case 'image/jpg':
            $imageType = 2;
            break;
        case 'image/png':
        case 'image/x-png':
            $imageType = 3;
            break;
        case 'image/bmp':
        case 'image/x-bmp':
        case 'image/x-windows-bmp':
            $imageType = 6;
            break;
    }

    if (empty($imageType) || (int)$image_dimensions[2] !== $imageType) {
        delete_uploaded_file($full_file_path);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    if (get_setting('upload_size') > 0 && $item_file['size'] > (get_setting('upload_size') * 1024)) {
        delete_uploaded_file($full_file_path);

        return ['error' => FILE_UPLOAD_ERROR_UPLOAD_SIZE];
    }

    return [
        'file_name' => $file_name,
        'file_width' => (int)$image_dimensions[0],
        'file_height' => (int)$image_dimensions[1]
    ];
}

function user_update(int $user_id, array $update_data): int
{
    global $db;

    return (int)$db->update_query(
        'users',
        $update_data,
        "uid='{$user_id}'",
        1
    );
}

function user_update_details(int $user_id): int
{
    $user_shop_objects = user_items_get(
        ["user_id='{$user_id}'", "is_visible='1'"],
        ['COUNT(user_item_id) AS total_user_items']
    );

    $total_user_items = (int)($user_shop_objects[0]['total_user_items'] ?? 0);

    return user_update($user_id, ['newpoints_shop_total_items' => $total_user_items]);
}

function user_item_insert(array $user_item_data = [], bool $is_update = false, int $user_item_id = 0): int
{
    global $db;

    $insert_data = [];

    if (isset($user_item_data['user_id'])) {
        $insert_data['user_id'] = (int)$user_item_data['user_id'];
    }

    if (isset($user_item_data['item_id'])) {
        $insert_data['item_id'] = (int)$user_item_data['item_id'];
    }

    if (isset($user_item_data['item_price'])) {
        $insert_data['item_price'] = (float)$user_item_data['item_price'];
    }

    if (isset($user_item_data['is_visible'])) {
        $insert_data['is_visible'] = (int)$user_item_data['is_visible'];
    }

    if (isset($user_item_data['display_order'])) {
        $insert_data['display_order'] = (int)$user_item_data['display_order'];
    }

    if (isset($user_item_data['user_item_stamp'])) {
        $insert_data['user_item_stamp'] = (int)$user_item_data['user_item_stamp'];
    } else {
        $insert_data['user_item_stamp'] = TIME_NOW;
    }

    if ($is_update) {
        $db->update_query('newpoints_shop_user_items', $insert_data, "user_item_id='{$user_item_id}'", 1);
    } else {
        $user_item_id = (int)$db->insert_query('newpoints_shop_user_items', $insert_data);
    }

    return $user_item_id;
}

function user_item_update(array $user_item_data = [], int $user_item_id = 0): int
{
    return user_item_insert($user_item_data, true, $user_item_id);
}

function user_items_get(
    array $where_clauses = [],
    array $query_fields = ['user_item_id'],
    array $query_options = []
): array {
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_user_items',
        implode(',', $query_fields),
        implode(' AND ', $where_clauses),
        $query_options
    );

    $user_items_objects = [];

    if ($db->num_rows($query)) {
        while ($user_item_data = $db->fetch_array($query)) {
            if (!empty($user_item_data['user_item_id'])) {
                $user_items_objects[(int)$user_item_data['user_item_id']] = $user_item_data;
            } else {
                $user_items_objects[] = $user_item_data;
            }
        }
    }

    return $user_items_objects;
}

function user_item_delete(int $user_item_id): int
{
    global $db;

    return (int)$db->delete_query('newpoints_shop_user_items', "user_item_id='{$user_item_id}'", 1);
}

function can_manage_quick_edit(): bool
{
    return (bool)is_member(get_setting('quick_edit_manage_groups'));
}