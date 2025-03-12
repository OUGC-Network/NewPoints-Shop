<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/dvz_stream/streams/newpoints_shop_purchase.php)
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

use dvzStream\Stream;
use dvzStream\StreamEvent;

use function dvzStream\addStream;
use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\points_format;
use function Newpoints\Core\url_handler_build;
use function Newpoints\Shop\Core\cache_get;
use function Newpoints\Shop\Core\icon_get;
use function Newpoints\Shop\Core\templates_get;

global $lang;

$stream = new Stream();

$stream->setName(explode('.', basename(__FILE__))[0]);

language_load('newpoints_shop');

$stream->setTitle($lang->newpoints_shop_dvz_stream_purchases);

$stream->setEventTitle($lang->newpoints_shop_dvz_stream_event_purchase);

$stream->setFetchHandler(function (int $query_limit, int $last_log_id = 0) use ($stream) {
    global $db;

    $stream_events = [];

    $shop_categories_cache = cache_get()['categories'] ?? [];

    $shop_items_cache = cache_get()['items'] ?? [];

    $shop_item_ids = [];

    foreach ($shop_items_cache as $item_id => $item_data) {
        if (!empty($item_data['visible']) && !empty($shop_categories_cache[$item_data['cid']]['visible'])) {
            $shop_item_ids[] = $item_id;
        }
    }

    $shop_item_ids = implode("','", array_map('intval', $shop_item_ids));

    $users_cache = $newpoints_logs_cache = [];

    $query = $db->simple_select(
        'newpoints_log l',
        'l.lid AS log_id, l.date AS user_item_stamp, l.uid AS user_id, l.points AS item_price, l.log_primary_id AS user_item_id, l.log_secondary_id AS item_id',
        "l.lid>'{$last_log_id}' AND l.action='shop_purchase' AND l.log_secondary_id IN ('{$shop_item_ids}')",
        ['order_by' => 'user_item_stamp', 'order_dir' => 'desc', 'limit' => $query_limit]
    );

    while ($newpoints_log_data = $db->fetch_array($query)) {
        $newpoints_logs_cache[(int)$newpoints_log_data['log_id']] = $newpoints_log_data;
    }

    $user_ids = implode("','", array_map('intval', array_column($newpoints_logs_cache, 'user_id')));

    $query = $db->simple_select(
        'users',
        'uid, username, usergroup, displaygroup, avatar',
        "uid IN ('{$user_ids}')"
    );

    while ($user_data = $db->fetch_array($query)) {
        $users_cache[(int)$user_data['uid']] = $user_data;
    }

    foreach ($newpoints_logs_cache as $log_id => $user_item_data) {
        $streamEvent = new StreamEvent();

        $streamEvent->setStream($stream);

        $streamEvent->setId($log_id);

        $streamEvent->setDate($user_item_data['user_item_stamp']);

        $streamEvent->setUser([
            'id' => $user_item_data['user_id'],
            'username' => $users_cache[$user_item_data['user_id']]['username'],
            'usergroup' => $users_cache[$user_item_data['user_id']]['usergroup'],
            'displaygroup' => $users_cache[$user_item_data['user_id']]['displaygroup'],
            'avatar' => $users_cache[$user_item_data['user_id']]['avatar'],
        ]);

        $streamEvent->addData([
            'item_id' => (int)$user_item_data['item_id'],
            'item_name' => $shop_items_cache[$user_item_data['item_id']]['name'],
            'item_description' => $shop_items_cache[$user_item_data['item_id']]['description'],
            'item_icon' => $shop_items_cache[$user_item_data['item_id']]['icon'],
            'item_price' => (float)$user_item_data['item_price'],
        ]);

        $stream_events[] = $streamEvent;
    }

    return $stream_events;
});

$stream->addProcessHandler(function (StreamEvent $streamEvent) {
    global $mybb, $lang;

    $stream_data = $streamEvent->getData();

    $action_name = get_setting('shop_action_name');

    $item_name = htmlspecialchars_uni($stream_data['item_name']);

    $item_icon = icon_get($stream_data);

    $item_price = strip_tags(points_format($stream_data['item_price']));

    $stream_text = $lang->sprintf(
        $lang->newpoints_shop_dvz_stream_purchased,
        $item_name,
        $item_price,
        get_setting('main_curname')
    );

    $user_data = $streamEvent->getUser();

    $my_items_url = url_handler_build([
        'action' => $action_name,
        'view' => 'my_items',
        'uid' => $user_data['id']
    ]);

    $stream_item = eval(templates_get('stream_item'));

    $streamEvent->setItem($stream_item);
});

addStream($stream);
