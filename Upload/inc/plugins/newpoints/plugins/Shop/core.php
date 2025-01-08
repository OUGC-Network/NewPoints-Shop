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

    if (isset($item_data['limit'])) {
        $insert_data['limit'] = (int)$item_data['limit'];
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

function item_update(array $item_data, int $item_id): int
{
    return item_insert($item_data, true, $item_id);
}

function item_get(array $where_clauses = [], array $query_fields = ['*'], array $query_options = []): array
{
    global $db;

    $query = $db->simple_select(
        'newpoints_shop_items',
        implode(' AND ', $query_fields),
        implode(' AND ', $where_clauses),
        $query_options
    );

    return (array)$db->fetch_array($query);
}

function category_get(int $cid = 0): array
{
    if (!$cid) {
        return [];
    }

    global $db;

    $query = $db->simple_select('newpoints_shop_categories', '*', "cid='{$cid}'");

    if (!$db->num_rows($query)) {
        return [];
    }

    return (array)$db->fetch_array($query);
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

function user_item_insert(array $item_data = []): int
{
    global $db;

    $insert_data = [];

    if (isset($item_data['user_id'])) {
        $insert_data['user_id'] = (int)$item_data['user_id'];
    }

    if (isset($item_data['item_id'])) {
        $insert_data['item_id'] = (int)$item_data['item_id'];
    }

    if (isset($item_data['item_price'])) {
        $insert_data['item_price'] = (float)$item_data['item_price'];
    }

    if (isset($item_data['is_visible'])) {
        $insert_data['is_visible'] = (int)$item_data['is_visible'];
    }

    if (isset($item_data['display_order'])) {
        $insert_data['display_order'] = (int)$item_data['display_order'];
    }

    if (isset($item_data['user_item_stamp'])) {
        $insert_data['user_item_stamp'] = (int)$item_data['user_item_stamp'];
    } else {
        $insert_data['user_item_stamp'] = TIME_NOW;
    }

    return (int)$db->insert_query('newpoints_shop_user_items', $insert_data);
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