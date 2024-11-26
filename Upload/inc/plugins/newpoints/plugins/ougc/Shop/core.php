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

use const Newpoints\Shop\ROOT;

function templates_get(string $template_name = '', bool $enable_html_comments = true): string
{
    return \Newpoints\Core\templates_get($template_name, $enable_html_comments, ROOT, 'shop_');
}

function item_get(int $iid = 0): array
{
    if (!$iid) {
        return [];
    }

    global $db;

    $query = $db->simple_select('newpoints_shop_items', '*', "iid='{$iid}'");

    if (!$db->num_rows($query)) {
        return [];
    }

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