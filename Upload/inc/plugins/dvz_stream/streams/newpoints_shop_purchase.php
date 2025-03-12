<?php

global $lang;

use dvzStream\Stream;
use dvzStream\StreamEvent;

use function dvzStream\addStream;
use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\points_format;
use function Newpoints\Core\url_handler_build;
use function Newpoints\ActivityRewards\Core\cache_get;
use function Newpoints\ActivityRewards\Core\log_get;
use function Newpoints\ActivityRewards\Core\templates_get;

use const Newpoints\ActivityRewards\Core\ACTIVITY_REWARDS_TYPE_POSTS;
use const Newpoints\ActivityRewards\Core\ACTIVITY_REWARDS_TYPE_REPUTATION;
use const Newpoints\ActivityRewards\Core\ACTIVITY_REWARDS_TYPE_THREADS;

$stream = new Stream();

$stream->setName(explode('.', basename(__FILE__))[0]);

language_load('newpoints_shop');

$stream->setTitle($lang->newpoints_shop_dvz_stream);

$stream->setEventTitle($lang->newpoints_shop_dvz_stream_event_purchase);

$stream->setFetchHandler(function (int $query_limit, int $last_user_item_id = 0) use ($stream) {
    global $db;

    $stream_events = [];

    $where_clauses = ["user_item_id>'{$last_user_item_id}'"];

    $shop_categories_cache = \Newpoints\Shop\Core\cache_get()['categories'] ?? [];

    $shop_items_cache = \Newpoints\Shop\Core\cache_get()['items'] ?? [];

    $shop_item_ids = [];

    foreach ($shop_items_cache as $item_id => $item_data) {
        if (!empty($item_data['visible']) && !empty($shop_categories_cache[$item_data['cid']]['visible'])) {
            $shop_item_ids[] = $item_id;
        }
    }

    $shop_item_ids = implode("','", array_map('intval', $shop_item_ids));

    $where_clauses[] = "item_id IN ('{$shop_item_ids}')";

    $user_item_objects = \Newpoints\Shop\Core\user_items_get(
        $where_clauses,
        ['item_id', 'user_id', 'user_item_stamp', 'item_price'],
        ['order_by' => 'user_item_stamp', 'order_dir' => 'desc', 'limit' => $query_limit]
    );

    $user_item_ids = implode("','", array_map('intval', array_column($user_item_objects, 'user_item_id')));

    $user_ids = implode("','", array_map('intval', array_column($user_item_objects, 'user_id')));

    $query = $db->simple_select(
        'users',
        'uid, username, usergroup, displaygroup, avatar',
        "uid IN ('{$user_ids}')"
    );

    $users_cache = $newpoints_logs_cache = [];

    while ($user_data = $db->fetch_array($query)) {
        $users_cache[(int)$user_data['uid']] = $user_data;
    }

    $query = $db->simple_select(
        'newpoints_log',
        'points, log_primary_id',
        "action='shop_purchase' AND log_primary_id IN ('{$user_item_ids}')"
    );

    while ($newpoints_log_data = $db->fetch_array($query)) {
        $newpoints_logs_cache[(int)$newpoints_log_data['log_primary_id']] = (float)$newpoints_log_data['points'];
    }

    foreach ($user_item_objects as $user_item_id => $user_item_data) {
        $streamEvent = new StreamEvent();

        $streamEvent->setStream($stream);

        $streamEvent->setId($user_item_id);

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
            'item_description' => $shop_items_cache[$user_item_data['item_id']]['name'],
            'item_icon' => $shop_items_cache[$user_item_data['item_id']]['icon'],
            'item_price' => (float)($shop_items_cache[$user_item_data['item_id']]['price'] ?? $user_item_data['item_price']),

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

    $item_icon = \Newpoints\Shop\Core\icon_get($stream_data);

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

    $stream_item = eval(\Newpoints\Shop\Core\templates_get('stream_item_purchase'));

    $streamEvent->setItem($stream_item);
});

addStream($stream);
