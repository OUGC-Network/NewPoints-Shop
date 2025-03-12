<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/core.php)
 *    Author: Diogo Parrinha
 *    Copyright: © 2009 Diogo Parrinha
 *    Copyright: © 2024 Omar Gonzalez
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
use function Newpoints\Core\run_hooks;

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

    $hook_arguments = [
        'item_data' => $item_data,
        'is_update' => $is_update,
        'item_id' => $item_id,
        'insert_update_data' => &$insert_data
    ];

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

    $hook_arguments = run_hooks('shop_item_insert_update', $hook_arguments);

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

function item_get(array $where_clauses = [], array $query_fields = [], array $query_options = []): array
{
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_items',
        implode(',', array_merge(['iid'], $query_fields)),
        implode(' AND ', $where_clauses),
        $query_options
    );

    return (array)$db->fetch_array($query);
}

function category_insert(array $category_data, bool $is_update = false, int $category_id = 0): int
{
    global $db;

    $insert_data = [];

    $hook_arguments = [
        'category_data' => $category_data,
        'is_update' => $is_update,
        'category_id' => $category_id,
        'insert_update_data' => &$insert_data
    ];

    if (isset($category_data['cid'])) {
        $insert_data['cid'] = (int)$category_data['cid'];
    }

    if (isset($category_data['name'])) {
        $insert_data['name'] = $db->escape_string($category_data['name']);
    }

    if (isset($category_data['description'])) {
        $insert_data['description'] = $db->escape_string($category_data['description']);
    }

    if (isset($category_data['visible'])) {
        $insert_data['visible'] = (int)$category_data['visible'];
    }

    if (isset($category_data['icon'])) {
        $insert_data['icon'] = $db->escape_string($category_data['icon']);
    }

    if (isset($category_data['usergroups'])) {
        $insert_data['usergroups'] = $db->escape_string($category_data['usergroups']);
    }

    if (isset($category_data['disporder'])) {
        $insert_data['disporder'] = (int)$category_data['disporder'];
    }

    if (isset($category_data['items'])) {
        $insert_data['items'] = (int)$category_data['items'];
    }

    if (isset($category_data['expanded'])) {
        $insert_data['expanded'] = (int)$category_data['expanded'];
    }

    $hook_arguments = run_hooks('shop_category_insert_update', $hook_arguments);

    if ($is_update) {
        $db->update_query('newpoints_shop_categories', $insert_data, "cid='{$category_id}'", 1);
    } else {
        $category_id = (int)$db->insert_query('newpoints_shop_categories', $insert_data);
    }

    return $category_id;
}

function category_update(array $category_data, int $category_id): int
{
    return category_insert($category_data, true, $category_id);
}

function category_get(array $where_clauses, array $query_fields = [], array $query_options = []): array
{
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_categories',
        implode(',', array_merge(['cid'], $query_fields)),
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

function category_delete(int $category_id): bool
{
    global $db;

    $query = $db->simple_select('newpoints_shop_items', 'iid', "cid='{$category_id}'");

    while ($item_data = $db->fetch_array($query)) {
        item_delete((int)$item_data['iid']);
    }

    $db->delete_query('newpoints_shop_categories', "cid='{$category_id}'", 1);

    return true;
}

function items_get(
    array $where_clauses = [],
    array $query_fields = [],
    array $query_options = []
): array {
    global $db;

    $query_fields[] = 'iid';

    $query = $db->simple_select(
        'newpoints_shop_items',
        implode(',', $query_fields),
        implode(' AND ', $where_clauses),
        $query_options
    );

    $items_objects = [];

    if ($db->num_rows($query)) {
        while ($item_data = $db->fetch_array($query)) {
            $items_objects[(int)$item_data['iid']] = $item_data;
        }
    }

    return $items_objects;
}

function items_get_visible(int $user_id = 0): array
{
    global $db;

    if (empty($user_id)) {
        global $mybb;

        $user_id = (int)$mybb->user['uid'];
    }

    $user_data = get_user($user_id);

    $is_moderator = is_member(get_setting('shop_manage_groups'));

    $where_clauses = $group_conditional = [];

    if ($is_moderator) {
        $where_clauses[] = "visible='1'";
    }

    $user_groups = $user_data['usergroup'] ?? '';

    if (!empty($user_data['additionalgroups'])) {
        $user_groups .= ',' . $user_data['additionalgroups'];
    }

    foreach (explode(',', $user_groups) as $user_group_id) {
        $user_group_id = (int)$user_group_id;

        switch ($db->type) {
            case 'pgsql':
            case 'sqlite':
                $group_conditional[] = "','||usergroups||',' LIKE '%,{$user_group_id},%'";
                break;
            default:
                $group_conditional[] = "CONCAT(',',usergroups,',') LIKE '%,{$user_group_id},%'";
                break;
        }
    }

    $group_conditional = implode(' OR ', $group_conditional);

    $where_clauses[] = "({$group_conditional})";

    $categories_data = category_get($where_clauses);

    if (empty($categories_data)) {
        return [];
    }

    $visible_category_ids = implode("','", array_map('intval', array_unique(array_column($categories_data, 'cid'))));

    $active_items_ids = items_get(["visible='1'", "cid IN ('{$visible_category_ids}')"]);

    return array_unique(array_column($active_items_ids, 'iid'));
}

function item_upload_icon(array $item_file): array
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
        ['COUNT(user_item_id) AS total_user_items'],
        ['limit' => 1],
        true
    );

    $total_user_items = (int)($user_shop_objects['total_user_items'] ?? 0);

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
    array $query_fields = [],
    array $query_options = [],
    bool $distinct = false
): array {
    global $db;

    if (!$distinct) {
        $query_fields[] = 'user_item_id';
    }

    $query = $db->simple_select(
        'newpoints_shop_user_items',
        implode(',', $query_fields),
        implode(' AND ', $where_clauses),
        $query_options
    );

    if (isset($query_options['limit']) && $query_options['limit'] === 1) {
        return (array)$db->fetch_array($query);
    }

    $user_items_objects = [];

    if ($db->num_rows($query)) {
        while ($user_item_data = $db->fetch_array($query)) {
            if (isset($user_item_data['user_item_id'])) {
                $user_items_objects[(int)$user_item_data['user_item_id']] = $user_item_data;
            } else {
                $user_items_objects[] = $user_item_data;
            }
        }
    }

    return $user_items_objects;
}

function user_items_get_distinct(
    array $where_clauses = [],
    array $query_fields = [],
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
            $user_items_objects[(int)$user_item_data['user_item_id']] = $user_item_data;
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

function user_group_permission_get_closest(string $permission_key, int $user_id = 0, int $base_value = 100): int
{
    global $db;

    $user_data = get_user($user_id);

    $user_groups = $user_data['usergroup'] ?? '';

    if (!empty($user_data['additionalgroups'])) {
        $user_groups .= ',' . $user_data['additionalgroups'];
    }

    $user_groups = explode(',', $user_groups);

    $user_group_ids = implode("','", array_map('intval', array_unique($user_groups)));

    $query = $db->simple_select(
        'usergroups',
        'gid',
        "gid IN ('{$user_group_ids}')",
        ['order_by' => 'disporder', 'order_dir' => 'ASC', 'limit' => 1]
    );

    return (int)$db->fetch_field($query, 'gid');
}

function cache_update(): bool
{
    global $cache;

    $query_fields_categories = ['cid', 'name', 'description', 'visible', 'icon', 'usergroups'];

    $query_fields_items = [
        'iid',
        'cid',
        'name',
        'description',
        'price',
        'icon',
        'visible',
        'infinite',
        'user_limit',
        'stock',
        'sendable',
        'sellable',
        'pm',
        'pmadmin'
    ];

    $cache_data = [
        'categories' => [],
        'items' => [],
    ];

    $hook_arguments = [
        'query_fields_categories' => &$query_fields_categories,
        'query_fields_items' => &$query_fields_items,
        'cache_data' => &$cache_data,
    ];

    $hook_arguments = run_hooks('shop_cache_update_start', $hook_arguments);

    foreach (
        category_get(
            [],
            $query_fields_categories,
            ['order_by' => 'disporder']
        ) as $category_id => $category_data
    ) {
        $category_data['visible'] = (bool)$category_data['visible'];

        $cache_data['categories'][$category_id] = $category_data;
    }

    foreach (
        items_get(
            [],
            $query_fields_items,
            ['order_by' => 'disporder']
        ) as $item_id => $item_data
    ) {
        $item_data['visible'] = (bool)$item_data['visible'];

        $item_data['infinite'] = (bool)$item_data['infinite'];

        $item_data['sendable'] = (bool)$item_data['sendable'];

        $item_data['sellable'] = (bool)$item_data['sellable'];

        $cache_data['items'][$item_id] = $item_data;
    }

    $hook_arguments = run_hooks('shop_cache_update_end', $hook_arguments);

    $cache->update('newpoints_shop', $cache_data);

    return true;
}

function cache_get(): array
{
    global $cache;

    return (array)$cache->read('newpoints_shop');
}

function icon_get(array $item_data): string
{
    global $mybb;

    $upload_path = get_setting('shop_upload_path');

    $item_icon = $mybb->get_asset_url(
        !empty($item_data['item_icon']) ? "{$upload_path}/{$item_data['item_icon']}" : 'images/newpoints/default.png'
    );

    $item_name = htmlspecialchars_uni($item_data['item_name']);

    $item_description = htmlspecialchars_uni($item_data['item_description']);

    return eval(templates_get('item_image'));
}