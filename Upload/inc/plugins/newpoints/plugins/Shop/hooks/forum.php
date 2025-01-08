<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/hooks/forum.php)
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

namespace Newpoints\Shop\Hooks\Forum;

use MyBB;

use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\log_add;
use function Newpoints\Core\page_build_purchase_confirmation;
use function Newpoints\Core\points_add_simple;
use function Newpoints\Core\points_format;
use function Newpoints\Core\points_subtract;
use function Newpoints\Core\post_parser_parse_message;
use function Newpoints\Core\private_message_send;
use function Newpoints\Core\rules_get_group_rate;
use function Newpoints\Core\rules_group_get;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\url_handler_build;
use function Newpoints\Core\users_get_by_username;
use function Newpoints\Shop\Core\can_manage_quick_edit;
use function Newpoints\Shop\Core\category_get;
use function Newpoints\Shop\Core\item_get;
use function Newpoints\Shop\Core\item_update;
use function Newpoints\Shop\Core\items_get_visible;
use function Newpoints\Shop\Core\templates_get;
use function Newpoints\Shop\Core\items_get;
use function Newpoints\Shop\Core\user_item_delete;
use function Newpoints\Shop\Core\user_item_insert;
use function Newpoints\Shop\Core\user_item_update;
use function Newpoints\Shop\Core\user_items_get;

use function Newpoints\Shop\Core\user_update_details;

use const Newpoints\Core\LOGGING_TYPE_CHARGE;
use const Newpoints\Core\LOGGING_TYPE_INCOME;

function newpoints_global_start(array &$hook_arguments): array
{
    $hook_arguments['newpoints.php'] = array_merge($hook_arguments['newpoints.php'], [
        'newpoints_shop_my_items_content',
        'newpoints_shop_my_items_empty',
        'newpoints_shop_my_items_row',
        'newpoints_shop_my_items_row_icon',
        'newpoints_shop_my_items_row_options',
        'newpoints_shop_my_items_row_options_sell',
        'newpoints_shop_my_items_row_options_send',
    ]);

    $hook_arguments['member.php'] = array_merge($hook_arguments['member.php'], [
        'newpoints_shop_profile_icon',
        'newpoints_shop_profile_view_all',
        'newpoints_shop_profile',
    ]);

    $hook_arguments['showthread.php'] = array_merge($hook_arguments['showthread.php'], [
        'newpoints_shop_post_icon',
        'newpoints_shop_post_view_all',
        'newpoints_shop_post',
    ]);

    return $hook_arguments;
}

function newpoints_terminate(): bool
{
    global $mybb;

    if ($mybb->get_input('action') !== get_setting('shop_action_name')) {
        return false;
    }

    global $action_name;

    $action_name = get_setting('shop_action_name');

    $mybb->input['iid'] = $mybb->get_input('item_id', MyBB::INPUT_INT);

    $per_page = get_setting('shop_per_page');

    if ($per_page < 1) {
        $per_page = 10;
    }

    $hook_arguments = [
        'per_page' => &$per_page
    ];

    global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options;
    global $page_title, $newpoints_pagination, $newpoints_menu, $newpoints_errors, $newpoints_additional, $newpoints_content;

    $footer .= eval(templates_get('css'));

    $formUrl = url_handler_build([
        'action' => $action_name
    ]);

    language_load('shop');

    $current_user_id = (int)$mybb->user['uid'];

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        add_breadcrumb($lang->newpoints_shop, url_handler_build(['action' => 'shop']));

        $errors = [];

        run_hooks('do_shop_start');

        switch ($mybb->get_input('view')) {
            case 'buy':
                $hook_arguments = run_hooks('shop_buy_start', $hook_arguments);

                $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);

                $item_data = item_get(
                    ["iid='{$item_id}'", "visible='1'"],
                    [
                        'iid',
                        'cid',
                        'name',
                        'description',
                        'price',
                        'icon',
                        'infinite',
                        'stock',
                    ]
                );

                if (!$item_data) {
                    error($lang->newpoints_shop_invalid_item);
                }

                $category_data = category_get(["cid='{$item_data['cid']}'", "visible='1'"], ['usergroups']);

                if (!$category_data) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                if (!is_member($category_data['usergroups'])) {
                    error_no_permission();
                }

                $item_name = htmlspecialchars_uni($item_data['name']);

                $item_description = post_parser_parse_message($item_data['description'], ['allow_imgcode' => false]);

                $rule_group = rules_group_get((int)$mybb->user['usergroup']);

                if (!$rule_group) {
                    $rule_group['items_rate'] = 1.0;
                }

                $item_price = (float)$item_data['price'];

                if ($rule_group['items_rate']) {
                    $item_price = $item_price * (float)$rule_group['items_rate'];
                }

                if ($item_price > $mybb->user['newpoints']) {
                    $errors[] = $lang->newpoints_shop_not_enough;
                }

                if (empty($item_data['infinite']) && $item_data['stock'] < 1) {
                    $errors[] = $lang->newpoints_shop_out_of_stock;
                }

                if (!empty($item_data['limit'])) {
                    $total_user_items = user_items_get(
                        ["item_id='{$item_id}'"],
                        ['COUNT(user_item_id) AS total_user_items']
                    );

                    $total_user_items = (int)($total_user_items[0]['total_user_items'] ?? 0);

                    if ($total_user_items >= $item_data['limit']) {
                        $errors[] = $lang->newpoints_shop_limit_reached;
                    }
                }

                if (empty($errors) && isset($mybb->input['confirm'])) {
                    $insert_data = [
                        'user_id' => $current_user_id,
                        'item_id' => $item_id,
                        'item_price' => $item_price
                    ];

                    $user_item_id = user_item_insert($insert_data);

                    if (empty($item_data['infinite'])) {
                        item_update(['stock' => $item_data['stock'] - 1], $item_id);
                    }

                    points_subtract($current_user_id, $item_price);

                    log_add(
                        'shop_purchase',
                        '',
                        $mybb->user['username'] ?? '',
                        $current_user_id,
                        $item_price,
                        $user_item_id,
                        $item_id,
                        0,
                        LOGGING_TYPE_CHARGE
                    );

                    if (!empty($item_data['pm']) || get_setting('shop_pm_default')) {
                        $item_data['pm'] = str_replace(
                            ['{itemname}', '{itemid}', '{item_name}', '{item_id}'],
                            [$item_name, $item_id, $item_name, $item_id],
                            $item_data['pm'] ?? get_setting('shop_pm_default')
                        );

                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_bought_item_pm_subject,
                                'message' => $item_data['pm'],
                                'touid' => $current_user_id,
                                'receivepms' => 1
                            ],
                            -1
                        );
                    }

                    if (!empty($item_data['pmadmin']) || get_setting('shop_pmadmins')) {
                        $item_data['pmadmin'] = str_replace(
                            ['{itemname}', '{itemid}', '{item_name}', '{item_id}'],
                            [$item_data['name'], $item_data['iid'], $item_data['name'], $item_data['iid']],
                            $item_data['pmadmin'] ?? get_setting('shop_pmadmin_default')
                        );

                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_bought_item_pmadmin_subject,
                                'message' => $item_data['pmadmin'],
                                'touid' => [explode(',', get_setting('shop_pmadmins'))],
                                'receivepms' => 1
                            ],
                            $current_user_id
                        );
                    }

                    $item_data = run_hooks('shop_buy_end', $item_data);

                    user_update_details($current_user_id);

                    redirect(
                        $mybb->settings['bburl'] . '/' . $formUrl,
                        $lang->newpoints_shop_item_bought,
                        $lang->newpoints_shop_item_bought_title
                    );
                }

                if (!isset($mybb->input['confirm'])) {
                    $message = $lang->sprintf(
                        $lang->newpoints_shop_sell_item_confirm,
                        $item_name,
                        points_format($item_price)
                    );

                    $item_price = points_format($item_price);

                    $item_icon = htmlspecialchars_uni(
                        $mybb->get_asset_url($item_data['icon'] ?? 'images/newpoints/default.png')
                    );

                    $view_item_url = url_handler_build([
                        'action' => $action_name,
                        'view' => 'item',
                        'item_id' => $item_id
                    ]);

                    $item_icon = eval(templates_get('confirm_buy_icon'));

                    page_build_purchase_confirmation(
                        $lang->newpoints_shop_confirm_buy_description,
                        'item_id',
                        $item_id,
                        'buy',
                        eval(templates_get('confirm_buy'))
                    );
                }

                break;
            case 'send':
                $user_item_id = $mybb->get_input('user_item_id', MyBB::INPUT_INT);

                if (!empty($user_item_id)) {
                    $user_items_objects = user_items_get(
                        ["user_id='{$current_user_id}'", "user_item_id='{$user_item_id}'"], ['item_id']
                    );

                    $item_id = (int)($user_items_objects[0]['item_id'] ?? 0);
                } else {
                    $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);
                }

                $item_data = item_get(["iid='{$item_id}'", "visible='1'"], ['cid', 'name']);

                run_hooks('shop_send_start');

                if (empty($item_data)) {
                    error($lang->newpoints_shop_invalid_item);
                }

                $category_data = category_get(["cid='{$item_data['cid']}'", "visible='1'"], ['usergroups']);

                if (!$category_data) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                if (!is_member($category_data['usergroups'])) {
                    error_no_permission();
                }

                $item_name = htmlspecialchars_uni($item_data['name']);

                if (empty($user_item_id)) {
                    $user_items_objects = user_items_get(["user_id='{$current_user_id}'", "item_id='{$item_id}'"]);

                    $user_item_id = (int)(array_column($user_items_objects, 'user_item_id')[0] ?? 0);
                }

                if (empty($user_item_id)) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                $lang->newpoints_page_confirm_table_purchase_title = $lang->newpoints_shop_confirm_send_title;

                $lang->newpoints_page_confirm_table_purchase_button = $lang->newpoints_shop_confirm_send_button;

                $user_name = '';

                $user_name = $mybb->get_input('username');

                run_hooks('shop_send_intermediate');

                if (isset($mybb->input['confirm'])) {
                    $user_data = users_get_by_username($user_name);

                    if (empty($user_data)) {
                        $errors[] = $lang->newpoints_shop_invalid_user;
                    }

                    if ((int)$user_data['uid'] === $current_user_id) {
                        $errors[] = $lang->newpoints_shop_cant_send_item_self;
                    }

                    if (empty($errors)) {
                        $user_id = (int)$user_data['uid'];

                        run_hooks('shop_do_send_start');

                        user_item_update(
                            ['user_id' => $user_id],
                            $user_item_id
                        );

                        log_add(
                            'shop_send',
                            '',
                            $mybb->user['username'] ?? '',
                            $current_user_id,
                            0,
                            $user_item_id,
                            $item_id,
                            $user_id
                        );

                        log_add(
                            'shop_item_received',
                            '',
                            $user_data['username'] ?? '',
                            $user_id,
                            0,
                            $user_item_id,
                            $item_id,
                            $current_user_id
                        );

                        user_update_details($current_user_id);

                        user_update_details($user_id);

                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_item_received_title,
                                'message' => $lang->sprintf(
                                    $lang->newpoints_shop_item_received,
                                    htmlspecialchars_uni($mybb->user['username']),
                                    $item_name
                                ),
                                'touid' => $user_data['uid'],
                                'receivepms' => 1
                            ],
                            -1
                        );

                        $my_items_url = url_handler_build([
                            'action' => $action_name,
                            'view' => 'my_items'
                        ]);

                        run_hooks('shop_do_send_end');

                        redirect(
                            $mybb->settings['bburl'] . '/' . $my_items_url,
                            $lang->newpoints_shop_item_sent,
                            $lang->newpoints_shop_item_sent_title
                        );
                    } else {
                        $newpoints_errors = inline_error($errors);

                        unset($mybb->input['confirm']);
                    }

                    $user_name = htmlspecialchars_uni($user_name);
                }

                run_hooks('shop_send_end');

                if (!isset($mybb->input['confirm'])) {
                    page_build_purchase_confirmation(
                        $lang->newpoints_shop_confirm_send_description,
                        'user_item_id',
                        $user_item_id,
                        'send',
                        eval(templates_get('confirm_send'))
                    );
                }
                break;
            case 'sell':
                $user_item_id = $mybb->get_input('user_item_id', MyBB::INPUT_INT);

                if (!empty($user_item_id)) {
                    $user_items_objects = user_items_get(
                        ["user_id='{$current_user_id}'", "user_item_id='{$user_item_id}'"], ['item_id', 'item_price']
                    );

                    $item_id = (int)($user_items_objects[0]['item_id'] ?? 0);

                    $item_price = (int)(array_column($user_items_objects, 'item_price')[0] ?? 0);
                } else {
                    $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);
                }

                $item_data = item_get(["iid='{$item_id}'", "visible='1'"], ['cid', 'name', 'stock']);

                run_hooks('shop_sell_start');

                if (empty($item_data)) {
                    error($lang->newpoints_shop_invalid_item);
                }

                $category_data = category_get(["cid='{$item_data['cid']}'", "visible='1'"], ['usergroups']);

                if (!$category_data) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                if (!is_member($category_data['usergroups'])) {
                    error_no_permission();
                }

                $item_name = htmlspecialchars_uni($item_data['name']);

                if (empty($user_item_id)) {
                    $user_items_objects = user_items_get(
                        ["user_id='{$current_user_id}'", "item_id='{$item_id}'"],
                        ['user_item_id', 'item_price']
                    );

                    $user_item_id = (int)(array_column($user_items_objects, 'user_item_id')[0] ?? 0);

                    $item_price = (int)(array_column($user_items_objects, 'item_price')[0] ?? 0);
                }

                if (empty($user_item_id)) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                if (!isset($item_price)) {
                    $item_price = 0;
                }

                if (get_setting('shop_percent')) {
                    $item_price = $item_price * get_setting('shop_percent');
                }

                $lang->newpoints_page_confirm_table_purchase_title = $lang->newpoints_shop_confirm_sell_title;

                $lang->newpoints_page_confirm_table_purchase_button = $lang->newpoints_shop_confirm_sell_button;

                $user_name = '';

                $user_name = $mybb->get_input('username');

                run_hooks('shop_sell_intermediate');

                if (isset($mybb->input['confirm'])) {
                    if (empty($errors)) {
                        run_hooks('shop_do_sell_start');

                        user_item_delete($user_item_id);

                        if (empty($item_data['infinite'])) {
                            item_update(['stock' => $item_data['stock'] + 1], $item_id);
                        }

                        points_add_simple($current_user_id, $item_price);

                        log_add(
                            'shop_sell',
                            '',
                            $mybb->user['username'] ?? '',
                            $current_user_id,
                            $item_price,
                            $item_id,
                            $user_item_id,
                            0,
                            LOGGING_TYPE_INCOME
                        );

                        user_update_details($current_user_id);

                        $my_items_url = url_handler_build([
                            'action' => $action_name,
                            'view' => 'my_items'
                        ]);

                        run_hooks('shop_do_sell_end');

                        redirect(
                            $mybb->settings['bburl'] . '/' . $my_items_url,
                            $lang->newpoints_shop_item_sell,
                            $lang->newpoints_shop_item_sell_title
                        );
                    } else {
                        $newpoints_errors = inline_error($errors);

                        unset($mybb->input['confirm']);
                    }

                    $user_name = htmlspecialchars_uni($user_name);
                }

                run_hooks('shop_sell_end');

                if (!isset($mybb->input['confirm'])) {
                    $message = $lang->sprintf(
                        $lang->newpoints_shop_sell_item_confirm,
                        $item_name,
                        points_format($item_price)
                    );

                    page_build_purchase_confirmation(
                        $lang->newpoints_shop_confirm_sell_description,
                        'user_item_id',
                        $user_item_id,
                        'sell',
                        eval(templates_get('confirm_sell'))
                    );
                }
                break;
        }

        if (!empty($errors)) {
            $newpoints_errors = inline_error($errors);
        }

        run_hooks('do_shop_end');
    }

    add_breadcrumb($lang->newpoints_shop, url_handler_build(['action' => 'shop']));

    run_hooks('shop_start');

    $newpoints_buttons = '';

    if ($mybb->get_input('view') == 'item') {
        $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);

        $item_data = item_get(
            ["iid='{$item_id}'", "visible='1'"],
            ['cid', 'name', 'description', 'price', 'icon', 'infinite', 'stock', 'sendable', 'sellable']
        );

        if (!$item_data) {
            error($lang->newpoints_shop_invalid_item);
        }

        $category_id = (int)$item_data['cid'];

        $category_data = category_get(["cid='{$category_id}'", "visible='1'"], ['usergroups']);

        if (empty($category_data)) {
            error($lang->newpoints_shop_invalid_cat);
        }

        if (!is_member($category_data['usergroups'])) {
            error_no_permission();
        }

        $item_name = htmlspecialchars_uni($item_data['name']);

        $item_description = post_parser_parse_message($item_data['description'], ['allow_imgcode' => false]);

        // check group rules - primary group check
        $rule_group = rules_group_get((int)$mybb->user['usergroup']);
        if (!$rule_group) {
            $rule_group['items_rate'] = 1.0;
        } // no rule set so default income rate is 1

        $item_price = (float)$item_data['price'];

        if ($item_price > $mybb->user['newpoints']) {
            $item_price_class = 'insufficient_funds';
        } else {
            $item_price_class = 'sufficient_funds';
        }

        if ($rule_group['items_rate']) {
            $item_price = $item_price * $rule_group['items_rate'];
        }

        if ($item_price > $mybb->user['newpoints']) {
            $price_class = 'insufficient_funds';
        } else {
            $price_class = 'sufficient_funds';
        }

        $item_price = points_format($item_price);

        $item_icon = htmlspecialchars_uni(
            $mybb->get_asset_url($item_data['icon'] ?? 'images/newpoints/default.png')
        );

        $view_item_url = url_handler_build([
            'action' => $action_name,
            'view' => 'item',
            'item_id' => $item_id
        ]);

        $item_icon = eval(templates_get('view_item_icon'));

        if (!empty($item_data['infinite'])) {
            $item_stock = $lang->newpoints_shop_infinite;
        } else {
            $item_stock = my_number_format($item_data['stock']);
        }

        if (!empty($item_data['sendable'])) {
            $item_can_be_sent = $lang->newpoints_shop_yes;
        } else {
            $item_can_be_sent = $lang->newpoints_shop_no;
        }

        if (!empty($item_data['sellable'])) {
            $item_can_be_sold = $lang->newpoints_shop_yes;
        } else {
            $item_can_be_sold = $lang->newpoints_shop_no;
        }

        $buy_item_url = url_handler_build([
            'action' => get_setting('shop_action_name'),
            'view' => 'buy'
        ]);

        $page_title = $lang->newpoints_shop_view_item;

        $newpoints_content = eval(templates_get('view_item'));

        $page = eval(\Newpoints\Core\templates_get('page'));
    } elseif ($mybb->get_input('view') == 'my_items') {
        $user_id = $mybb->get_input('uid', MyBB::INPUT_INT);

        if (empty($user_id)) {
            $user_id = $current_user_id;
        }

        $url_params = ['action' => get_setting('shop_action_name'), 'view' => 'item'];

        $user_data = get_user($user_id);

        if (!$user_data || $user_id !== $current_user_id && !get_setting('shop_viewothers')) {
            error_no_permission();
        }

        $lang->newpoints_shop_myitems = $lang->sprintf(
            $lang->newpoints_shop_items_username,
            htmlspecialchars_uni($user_data['username'])
        );

        $visible_items_ids = items_get_visible();

        $visible_items_ids = implode("','", $visible_items_ids);

        $where_clauses = ["user_id='{$user_id}'", "is_visible='1'", "item_id IN ('{$visible_items_ids}')"];

        $total_user_items = count(
            user_items_get(
                $where_clauses,
                ['item_id', 'COUNT(DISTINCT user_item_id) AS total_user_items'],
                ['group_by' => 'item_id']
            )
        );

        if ($mybb->get_input('page', MyBB::INPUT_INT) > 1) {
            $start = ($mybb->get_input('page', MyBB::INPUT_INT) * $per_page) - $per_page;
        } else {
            $mybb->input['page'] = 1;

            $start = 0;
        }

        if ($total_user_items > $per_page) {
            if ($user_id !== $current_user_id) {
                $my_items_url = url_handler_build([
                    'action' => $action_name,
                    'view' => 'my_items',
                    'uid' => $user_id
                ]);
            } else {
                $my_items_url = url_handler_build([
                    'action' => $action_name,
                    'view' => 'my_items'
                ]);
            }

            $newpoints_pagination = (string)multipage(
                $total_user_items,
                $per_page,
                $mybb->get_input('page', MyBB::INPUT_INT),
                $mybb->settings['bburl'] . '/' . $my_items_url
            );
        }

        $user_items_objects = user_items_get(
            $where_clauses,
            ['user_item_id', 'item_id'],
            ['order_by' => 'user_item_stamp', 'order_dir' => 'desc', 'limit' => $per_page, 'limit_start' => $start]
        );

        $user_items_objects = user_items_get(
            $where_clauses,
            ['item_id', 'COUNT(DISTINCT user_item_id) AS total_user_items'],
            [
                'group_by' => ' item_id',
                'order_by' => 'item_id',
                'order_dir' => 'desc',
                'limit' => $per_page,
                'limit_start' => $start
            ]
        );

        $items_ids = implode("','", array_map('intval', array_unique(array_column($user_items_objects, 'item_id'))));

        $post_items_cache = items_get(
            ["iid IN ('{$items_ids}')", "visible='1'"],
            ['iid', 'name', 'description', 'price', 'icon', 'sendable', 'sellable']
        );

        $shop_items = '';

        $alternative_background = alt_trow(true);

        $inverted_background = alt_trow();

        foreach ($user_items_objects as $user_item_data) {
            $item_id = $url_params['item_id'] = (int)$user_item_data['item_id'];

            if (empty($post_items_cache[$item_id])) {
                continue;
            }

            $item_data = $post_items_cache[$item_id];

            $option_buttons = $button_sell = $button_send = '';

            if (get_setting('shop_sellable') && !empty($item_data['sellable'])) {
                $button_sell = eval(templates_get('my_items_row_options_sell'));
            }

            if (get_setting('shop_sendable') && !empty($item_data['sendable'])) {
                $button_send = eval(templates_get('my_items_row_options_send'));
            }

            if ($button_send && $button_sell) {
                $option_buttons = eval(templates_get('my_items_row_options'));
            }

            $item_name = htmlspecialchars_uni($item_data['name']);

            $item_description = post_parser_parse_message($item_data['description'], ['allow_imgcode' => false]);

            $item_icon = htmlspecialchars_uni(
                $mybb->get_asset_url($item_data['icon'] ?? 'images/newpoints/default.png')
            );

            $view_item_url = url_handler_build($url_params);

            $item_icon = eval(templates_get('my_items_row_icon'));

            $rule_group = rules_group_get((int)$mybb->user['usergroup']);

            if (!$rule_group) {
                $rule_group['items_rate'] = 1.0;
            }

            if (!(float)$rule_group['items_rate']) {
                $item_data['price'] = 0;
            } else {
                $item_data['price'] = $item_data['price'] * (float)$rule_group['items_rate'];
            }

            $item_price = (float)$item_data['price'];

            if ($item_price > $mybb->user['newpoints']) {
                $price_class = 'insufficient_funds';
            } else {
                $price_class = 'sufficient_funds';
            }

            $item_price = points_format($item_price);

            $item_stock = my_number_format($user_item_data['total_user_items']);

            $item_data = run_hooks('shop_myitems_item', $item_data);

            $view_item_url = url_handler_build([
                'action' => $action_name,
                'view' => 'item',
                'item_id' => $item_id
            ]);

            $shop_items .= eval(templates_get('my_items_row'));

            $alternative_background = alt_trow();

            $inverted_background = alt_trow();
        }

        unset($url_params['item_id']);

        if (!$shop_items) {
            $shop_items = eval(templates_get('my_items_empty'));
        }

        $newpoints_content = eval(templates_get('my_items_content'));

        $page = eval(\Newpoints\Core\templates_get('page'));
    } else {
        $group_rules = rules_get_group_rate();

        $rule_group = rules_group_get((int)$mybb->user['usergroup']);

        if (empty($rule_group['items_rate'])) {
            $items_rate = 1.0;
        } else {
            $items_rate = $rule_group['items_rate'];
        }

        global $cats, $items;

        $query = $db->simple_select(
            'newpoints_shop_categories',
            'cid, name, description, visible, icon, usergroups, disporder, items, expanded',
            '',
            ['order_by' => 'disporder', 'order_dir' => 'ASC']
        );

        $categories_cache = [];

        $hook_arguments['items_rate'] = &$items_rate;

        $hook_arguments['categories_cache'] = &$categories_cache;

        while ($category_data = $db->fetch_array($query)) {
            $categories_cache[] = [
                'category_id' => (int)$category_data['cid'],
                'name' => (string)$category_data['name'],
                'description' => (string)$category_data['description'],
                'is_visible' => (bool)$category_data['visible'],
                'icon_url' => (string)$category_data['icon'],
                'allowed_groups' => (string)$category_data['usergroups'],
                'total_items' => (function () use ($category_data): int {
                    if (!is_member(get_setting('shop_moderator_groups'))) {
                        return (int)$category_data['items'];
                    }

                    global $db;

                    $query = $db->simple_select(
                        'newpoints_shop_items',
                        'COUNT(iid) AS total_items',
                        'cid=' . (int)$category_data['cid']
                    );

                    return (int)$db->fetch_field($query, 'total_items');
                })(),
                'is_expanded' => (bool)$category_data['expanded'],
                'get_items' => function (int $category_id, array $category_data) use ($per_page): array {
                    global $mybb, $db;

                    $items_objects = [];

                    $where_clauses = ["cid='{$category_id}'"];

                    if (!is_member(get_setting('shop_moderator_groups'))) {
                        $where_clauses[] = "visible='1'";
                    }

                    if ($mybb->get_input("page{$category_id}", MyBB::INPUT_INT) > 0) {
                        $start_page = ($mybb->get_input("page{$category_id}", MyBB::INPUT_INT) - 1) * $per_page;

                        $total_pages = ceil($category_data['total_items'] / $per_page);

                        if ($mybb->get_input("page{$category_id}", MyBB::INPUT_INT) > $total_pages) {
                            $start_page = 0;

                            $mybb->input["page{$category_id}"] = 1;
                        }
                    } else {
                        $start_page = 0;

                        $mybb->input["page{$category_id}"] = 1;
                    }

                    $query = $db->simple_select(
                        'newpoints_shop_items',
                        'iid, name, description, price, icon, visible, disporder, infinite, stock',
                        implode(' AND ', $where_clauses),
                        [
                            'order_by' => 'disporder',
                            'order_dir' => 'ASC',
                            'limit' => $per_page,
                            'limit_start' => $start_page
                        ]
                    );

                    while ($item = $db->fetch_array($query)) {
                        $items_objects[] = [
                            'item_id' => (int)$item['iid'],
                            'name' => (string)$item['name'],
                            'description' => (string)$item['description'],
                            'price' => (float)$item['price'],
                            'icon_url' => (string)$item['icon'],
                            'is_visible' => (bool)$item['visible'],
                            'is_infinite' => (bool)$item['infinite'],
                            'stock' => (int)$item['stock'],
                            'allowed_groups' => -1
                        ];
                    }

                    return $items_objects;
                },
                'type' => 'shop'
            ];
        }

        $hook_arguments = run_hooks('shop_main_start', $hook_arguments);

        $is_moderator = (bool)is_member(get_setting('shop_moderator_groups'));

        global $extdisplay, $expcolimage, $expdisplay, $expaltext;

        $newpoints_content = '';

        $pagination_input_array = ['action' => $action_name];

        foreach ($categories_cache as $category_data) {
            $category_id = $category_data['category_id'];

            if (isset($mybb->input["page{$category_id}"])) {
                $pagination_input_array["page{$category_id}"] = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);
            }
        }

        foreach ($categories_cache as $category_data) {
            $hook_arguments['category_data'] = &$category_data;

            $hook_arguments = run_hooks('shop_main_category_start', $hook_arguments);

            if (
                (!$is_moderator && empty($category_data['is_visible'])) ||
                (!$is_moderator && !is_member($category_data['allowed_groups']))
            ) {
                continue;
            }

            $category_id = $category_data['category_id'];

            $expdisplay = '';

            if (empty($category_data['is_expanded'])) {
                $expcolimage = 'collapse_collapsed.png';

                $expdisplay = 'display: none;';

                $expaltext = '[+]';
            } else {
                $expcolimage = 'collapse.png';

                $expaltext = '[-]';
            }

            $category_description = htmlspecialchars_uni($category_data['description']);

            $category_name = htmlspecialchars_uni($category_data['name']);

            $category_icon = '';

            if (!empty($category_data['icon_url'])) {
                $category_icon = $mybb->get_asset_url($category_data['icon_url']);

                $category_icon = eval(templates_get('category_icon'));
            }

            $items_rows = '';

            $alternative_background = alt_trow();

            $category_type = $category_data['type'] ?? 'shop';

            if ($category_data['total_items'] > 0 && is_callable($category_data['get_items'])) {
                $alternative_background = alt_trow();

                foreach ($category_data['get_items']($category_id, $category_data) as $item_data) {
                    $hook_arguments['item_data'] = &$item_data;

                    $item_id = $item_data['item_id'];

                    $hook_arguments = run_hooks('shop_main_category_item_start', $hook_arguments);

                    if (
                        (!$is_moderator && empty($item_data['is_visible'])) ||
                        (!$is_moderator && !is_member($item_data['allowed_groups']))
                    ) {
                        continue;
                    }

                    if (!empty($item_data['is_infinite'])) {
                        $item_stock = $lang->newpoints_shop_infinite;
                    } else {
                        $item_stock = my_number_format($item_data['stock']);
                    }

                    $item_name = htmlspecialchars_uni($item_data['name']);

                    $item_description = post_parser_parse_message(
                        $item_data['description'],
                        ['allow_imgcode' => false]
                    );

                    $item_price = (float)$item_data['price'] * $items_rate;

                    if ($item_price > $mybb->user['newpoints']) {
                        $price_class = 'insufficient_funds';
                    } else {
                        $price_class = 'sufficient_funds';
                    }

                    $item_price = points_format($item_price);

                    $item_icon = '';

                    if (!empty($item_data['icon_url'])) {
                        $item_icon = $mybb->get_asset_url($item_data['icon_url']);

                        $item_icon = eval(templates_get('item_icon'));
                    }

                    if (isset($item_data['item_url'])) {
                        $view_item_url = $item_data['item_url'];
                    } else {
                        $view_item_url = url_handler_build([
                            'action' => $action_name,
                            'view' => 'item',
                            'item_id' => $item_id
                        ]);
                    }

                    $buy_item_url = url_handler_build([
                        'action' => $action_name,
                        'view' => 'buy',
                        'item_id' => $item_id
                    ]);

                    $items_rows .= eval(templates_get('item'));
                }
            }

            $pagination = '';

            if ($category_data['total_items'] > $per_page) {
                $pagination_input_array["page{$category_id}"] = '{page}';

                $newpoints_pagination = multipage(
                    $category_data['total_items'],
                    $per_page,
                    $mybb->get_input("page{$category_id}", MyBB::INPUT_INT),
                    url_handler_build($pagination_input_array, false, false)
                );

                if ($newpoints_pagination) {
                    $pagination = eval(\Newpoints\Core\templates_get('page_pagination'));
                }

                $pagination_input_array["page{$category_id}"] = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);
            } else {
                unset($pagination_input_array["page{$category_id}"]);
            }

            if (!$items_rows) {
                $items_rows = eval(templates_get('no_items'));
            }

            $newpoints_content .= eval(templates_get('category'));
        }

        if (!$newpoints_content) {
            $newpoints_content = eval(templates_get('no_cats'));
        }

        $my_items_url = url_handler_build(['action' => $action_name, 'view' => 'my_items']);

        $newpoints_buttons .= eval(templates_get('page_button_my_items'));

        $newpoints_pagination = '';

        $page = eval(\Newpoints\Core\templates_get('page'));
    }

    $page = run_hooks('shop_end', $page);

    output_page($page);

    exit;
}

function newpoints_stats_start()
{
    global $mybb, $db, $templates, $cache, $theme, $lang;
    global $last_purchases, $statistics_items;

    language_load('shop');

    $last_purchases = '';

    $alternative_background = alt_trow(true);

    foreach (
        user_items_get(
            ["is_visible='1'"],
            ['user_item_id', 'user_id, item_id', 'user_item_stamp'],
            ['order_by' => 'user_item_stamp', 'order_dir' => 'desc']
        ) as $user_item_id => $user_item_data
    ) {
        $item_id = (int)$user_item_data['item_id'];

        $item_data = item_get(["iid='{$item_id}'", "visible='1'"], ['iid', 'name']);

        if (empty($item_data)) {
            continue;
        }

        $item_name = htmlspecialchars_uni($item_data['name']);

        $user_id = $user_item_data['user_id'];

        $user_data = get_user($user_id);

        $user_profile_link = '';

        if (!empty($user_data)) {
            $user_profile_link = build_profile_link(
                format_name(
                    htmlspecialchars_uni($user_data['username']),
                    $user_data['usergroup'],
                    $user_data['displaygroup']
                ),
                $user_id
            );
        }

        $user_item_stamp = my_date('normal', $user_item_data['user_item_stamp']);

        $last_purchases .= eval(templates_get('stats_row'));

        $alternative_background = alt_trow();
    }

    if (!$last_purchases) {
        $last_purchases = eval(templates_get('stats_empty'));
    }

    $statistics_items[] = eval(templates_get('stats'));
}

function member_profile_end(): bool
{
    $newpoints_shop_profile = '';

    $display_limit = (int)get_setting('shop_itemsprofile');

    if (!get_setting('shop_itemsprofile') || $display_limit < 1) {
        return false;
    }

    global $mybb, $lang, $memprofile, $newpoints_shop_profile;

    language_load('shop');

    $shop_items = '';

    $url_params = ['action' => get_setting('shop_action_name'), 'view' => 'item'];

    if (!empty($memprofile['newpoints_shop_total_items'])) {
        $user_id = (int)$memprofile['uid'];

        $user_items_objects = user_items_get(
            ["user_id='{$user_id}'", "is_visible='1'"],
            ['user_item_id', 'item_id'],
            ['order_by' => 'user_item_stamp', 'order_dir' => 'desc']
        );

        $items_ids = implode("','", array_map('intval', array_unique(array_column($user_items_objects, 'item_id'))));

        $user_limit = $display_limit;

        foreach (items_get(["iid IN ('{$items_ids}')", "visible='1'"], ['iid', 'name', 'icon']) as $item_data) {
            if ($user_limit < 1) {
                break;
            }

            $item_id = $url_params['item_id'] = (int)$item_data['iid'];

            $view_item_url = url_handler_build($url_params);

            $item_icon = htmlspecialchars_uni(
                $mybb->get_asset_url($item_data['icon'] ?? 'images/newpoints/default.png')
            );

            $item_name = htmlspecialchars_uni($item_data['name']);

            $shop_items .= eval(templates_get('profile_icon'));

            --$user_limit;
        }

        unset($url_params['item_id']);
    }

    if (!$shop_items) {
        $shop_items = $lang->newpoints_shop_profile_items_empty;
    }

    $url_params['view'] = 'my_items';

    $url_params['uid'] = $memprofile['uid'];

    $view_all_link = '';

    if ($memprofile['newpoints_shop_total_items'] > $display_limit) {
        $my_items_url = url_handler_build($url_params);

        $view_all_link = eval(templates_get('profile_view_all'));
    }

    $newpoints_shop_profile = eval(templates_get('profile'));

    return true;
}

function postbit_prev(array &$postData): array
{
    return postbit($postData);
}

function postbit_pm(array $postData): array
{
    return postbit($postData);
}

function postbit_announcement(array $postData): array
{
    return postbit($postData);
}

function postbit(array $post): array
{
    global $mybb, $lang, $db;

    $post['newpoints_shop_items'] = $post['newpoints_shop_items_count'] = '';

    $display_limit = (int)get_setting('shop_itemspostbit');

    if (!get_setting('shop_itemspostbit') || $display_limit < 1) {
        return $post;
    }

    $post['newpoints_shop_items_count'] = my_number_format($post['newpoints_shop_total_items']);

    $post_user_id = (int)$post['uid'];

    static $post_items_cache = null;

    static $post_user_items_cache = null;

    if (!isset($post_items_cache)) {
        global $db;

        $user_ids = [$post_user_id];

        if (isset($GLOBALS['pids'])) {
            $query = $db->simple_select('posts', 'uid', "{$GLOBALS['pids']}");

            while ($post_data = $db->fetch_array($query)) {
                $user_ids[] = (int)$post_data['uid'];
            }
        }

        $user_ids = implode("','", array_unique($user_ids));

        $where_clauses = [
            "user_id IN ('{$user_ids}')",
            "is_visible='1'",
        ];

        $user_items_objects = user_items_get(
            $where_clauses,
            ['user_item_id', 'user_id', 'item_id'],
            ['order_by' => 'user_item_stamp', 'order_dir' => 'desc']
        );

        foreach ($user_items_objects as $user_item) {
            if (!isset($post_user_items_cache[$user_item['user_id']])) {
                $post_user_items_cache[$user_item['user_id']] = [];
            }

            $post_user_items_cache[(int)$user_item['user_id']][(int)$user_item['user_item_id']] = (int)$user_item['item_id'];
        }

        $items_ids = implode("','", array_map('intval', array_unique(array_column($user_items_objects, 'item_id'))));

        $post_items_cache = items_get(["iid IN ('{$items_ids}')", "visible='1'"], ['iid', 'name', 'icon']);
    }

    if (empty($post_user_items_cache[$post_user_id]) || empty($post_items_cache)) {
        return $post;
    }

    language_load('shop');

    $user_items_objects = array_unique($post_user_items_cache[$post_user_id]);

    $user_limit = $display_limit;

    $shop_items = '';

    $url_params = ['action' => get_setting('shop_action_name'), 'view' => 'item'];

    foreach ($user_items_objects as $user_item_id => $item_id) {
        if ($user_limit < 1 || empty($post_items_cache[$item_id])) {
            break;
        }

        $item_data = $post_items_cache[$item_id];

        $url_params['item_id'] = $item_id;

        $view_item_url = url_handler_build($url_params);

        $item_icon = htmlspecialchars_uni(
            $mybb->get_asset_url($item_data['icon'] ?? 'images/newpoints/default.png')
        );

        $item_name = htmlspecialchars_uni($item_data['name']);

        $shop_items .= eval(templates_get('post_icon'));

        --$user_limit;
    }

    unset($url_params['item_id']);

    if (!$shop_items) {
        $shop_items = $lang->newpoints_shop_post_items_empty;
    }

    $url_params['view'] = 'my_items';

    $url_params['uid'] = $post['uid'];

    $view_all_link = '';

    if ($post['newpoints_shop_total_items'] > $display_limit) {
        $my_items_url = url_handler_build($url_params);

        $view_all_link = eval(templates_get('post_view_all'));
    }

    $post['newpoints_shop_items'] = eval(templates_get('post'));

    return $post;
}

function newpoints_default_menu(array &$menu): array
{
    language_load('shop');

    $menu[get_setting('shop_menu_order')] = [
        'action' => get_setting('shop_action_name'),
        'lang_string' => 'newpoints_shop_page_title'
    ];

    return $menu;
}

function newpoints_quick_edit_start(array &$hook_arguments): array
{
    if (can_manage_quick_edit()) {
        $hook_arguments['db_fields'][] = 'newpoints_shop_total_items';
    }

    return $hook_arguments;
}

function newpoints_quick_edit_post_start(array &$hook_arguments): array
{
    if (!can_manage_quick_edit()) {
        return $hook_arguments;
    }

    global $mybb, $lang, $db;

    $selected_user_items_ids = $mybb->get_input('shop_items', MyBB::INPUT_ARRAY);

    if (empty($hook_arguments['user_data']['newpoints_shop_total_items']) || !$selected_user_items_ids) {
        return $hook_arguments;
    }

    language_load('shop');

    $user_id = (int)$hook_arguments['user_data']['uid'];

    $user_points_refund = get_setting('quick_edit_shop_delete_refund');

    $item_stock_increase = get_setting('quick_edit_shop_delete_stock_increase');

    $current_user_id = (int)$mybb->user['uid'];

    foreach ($selected_user_items_ids as $user_item_id) {
        $user_item_id = (int)$user_item_id;

        foreach (
            user_items_get(
                ["user_id='{$user_id}'", "user_item_id='{$user_item_id}'"],
                ['user_item_id', 'item_id', 'item_price'],
                ['limit' => 1]
            ) as $user_item_data
        ) {
            $item_id = (int)$user_item_data['item_id'];

            $item_price = $log_type = 0;

            $item_price = (float)$user_item_data['item_price'];

            user_item_delete($user_item_id);

            if ($user_points_refund && !empty($item_price)) {
                $log_type = LOGGING_TYPE_INCOME;

                points_add_simple($user_id, $item_price);
            }

            log_add(
                'shop_quick_item_delete',
                '',
                $hook_arguments['user_data']['username'] ?? '',
                $user_id,
                $item_price,
                $item_id,
                $current_user_id,
                $user_item_id,
                $log_type
            );

            $item_data = item_get(["iid='{$item_id}'"], ['stock']);

            if ($item_stock_increase && !empty($item_data)) {
                item_update(['stock' => $item_data['stock'] + 1], $item_id);
            }
        }
    }

    return $hook_arguments;
}

function newpoints_quick_edit_end(array &$hook_arguments): array
{
    if (!can_manage_quick_edit() || empty($hook_arguments['user_data']['newpoints_shop_total_items'])) {
        return $hook_arguments;
    }

    $user_id = $hook_arguments['user_data']['uid'];

    $user_items_objects = user_items_get(
        ["user_id='{$user_id}'"],
        ['user_item_id', 'item_id'],
        ['order_by' => 'user_item_stamp', 'order_dir' => 'desc']
    );

    $items_ids = implode("','", array_map('intval', array_unique(array_column($user_items_objects, 'item_id'))));

    $post_items_cache = items_get(["iid IN ('{$items_ids}')", "visible='1'"], ['iid', 'name', 'icon']);

    if (!empty($user_items_objects)) {
        global $mybb, $db, $lang;

        language_load('shop');

        $alternative_background = &$hook_arguments['alternative_background'];

        $shop_items = '';

        $url_params = ['action' => get_setting('shop_action_name'), 'view' => 'item'];

        $tab_index = 20;

        foreach ($user_items_objects as $user_item_id => $user_item_data) {
            $url_params['item_id'] = $item_id = (int)$user_item_data['item_id'];

            if (empty($post_items_cache[$item_id])) {
                break;
            }

            $item_data = $post_items_cache[$item_id];

            $view_item_url = url_handler_build($url_params);

            $item_icon = htmlspecialchars_uni(
                $mybb->get_asset_url($item_data['icon'] ?? 'images/newpoints/default.png')
            );

            $item_name = htmlspecialchars_uni($item_data['name']);

            $item_icon = eval(templates_get('quick_edit_row_item_icon'));

            $shop_items .= eval(templates_get('quick_edit_row_item'));

            ++$tab_index;
        }

        $hook_arguments['additional_rows'][] = eval(templates_get('quick_edit_row'));

        $alternative_background = alt_trow();
    }

    return $hook_arguments;
}
