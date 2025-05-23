<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/hooks/forum.php)
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

namespace Newpoints\Shop\Hooks\Forum;

use MyBB;

use function Newpoints\Core\alert_send;
use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\log_add;
use function Newpoints\Core\main_file_name;
use function Newpoints\Core\page_build_cancel_confirmation;
use function Newpoints\Core\page_build_purchase_confirmation;
use function Newpoints\Core\points_add_simple;
use function Newpoints\Core\points_format;
use function Newpoints\Core\points_subtract;
use function Newpoints\Core\post_parser_parse_message;
use function Newpoints\Core\private_message_send;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\url_handler_build;
use function Newpoints\Core\users_get_by_username;
use function Newpoints\Shop\Core\cache_update;
use function Newpoints\Shop\Core\can_manage_quick_edit;
use function Newpoints\Shop\Core\category_delete;
use function Newpoints\Shop\Core\category_get;
use function Newpoints\Shop\Core\category_insert;
use function Newpoints\Shop\Core\category_update;
use function Newpoints\Shop\Core\item_delete;
use function Newpoints\Shop\Core\item_get;
use function Newpoints\Shop\Core\item_insert;
use function Newpoints\Shop\Core\item_update;
use function Newpoints\Shop\Core\item_upload_icon;
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
use const Newpoints\DECIMAL_DATA_TYPE_STEP;
use const Newpoints\Shop\Core\GROUPS_PERMISSION_ALL;
use const Newpoints\Shop\ROOT;

function global_intermediate(): bool
{
    global $mybb;

    if (get_setting('shop_enable_dvz_stream') && isset($mybb->settings['dvz_stream_active_streams'])) {
        $mybb->settings['dvz_stream_active_streams'] .= ',newpoints_shop_purchase,newpoints_shop_send,newpoints_shop_sell';
    }

    return true;
}

function xmlhttp09(): bool
{
    global $mybb;

    if (get_setting('shop_enable_dvz_stream') && isset($mybb->settings['dvz_stream_active_streams'])) {
        $mybb->settings['dvz_stream_active_streams'] .= ',newpoints_shop_purchase,newpoints_shop_send,newpoints_shop_sell';
    }

    return true;
}

function newpoints_global_start(array &$hook_arguments): array
{
    $hook_arguments['newpoints.php'] = array_merge($hook_arguments['newpoints.php'], [
        'newpoints_shop_category',
        'newpoints_shop_category_add_edit_form',
        'newpoints_shop_category_add_edit_form_category',
        'newpoints_shop_category_add_edit_form_category_option',
        'newpoints_shop_category_add_edit_form_upload',
        'newpoints_shop_category_empty',
        'newpoints_shop_category_icon',
        'newpoints_shop_category_links',
        'newpoints_shop_category_pagination',
        'newpoints_shop_category_thead_options',
        'newpoints_shop_category_thead_purchase',
        'newpoints_shop_confirm_purchase',
        'newpoints_shop_confirm_purchase_icon',
        'newpoints_shop_confirm_sell',
        'newpoints_shop_confirm_send',
        'newpoints_shop_css',
        'newpoints_shop_item',
        'newpoints_shop_item_add_edit_form',
        'newpoints_shop_item_add_edit_form_category',
        'newpoints_shop_item_add_edit_form_category_option',
        'newpoints_shop_item_add_edit_form_upload',
        'newpoints_shop_item_icon',
        'newpoints_shop_item_image',
        'newpoints_shop_item_purchase',
        'newpoints_shop_item_row_options',
        'newpoints_shop_my_items_content',
        'newpoints_shop_my_items_empty',
        'newpoints_shop_my_items_row',
        'newpoints_shop_my_items_row_icon',
        'newpoints_shop_my_items_row_options',
        'newpoints_shop_my_items_row_options_sell',
        'newpoints_shop_my_items_row_options_send',
        'newpoints_shop_no_cats',
        'newpoints_shop_nopurchase',
        'newpoints_shop_page_button_add_category',
        'newpoints_shop_page_button_my_items',
        'newpoints_shop_purchase',
        'newpoints_shop_quick_edit_row',
        'newpoints_shop_quick_edit_row_item',
        'newpoints_shop_quick_edit_row_item_icon',
        'newpoints_shop_stats',
        'newpoints_shop_stats_empty',
        'newpoints_shop_stats_row',
        'newpoints_shop_view_item',
        'newpoints_shop_view_item_icon',
        'newpoints_shop_view_item_purchase',
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

    $hook_arguments['global'] = array_merge($hook_arguments['global'], [
        'newpoints_shop_item_image',
        'newpoints_shop_stream_item',
    ]);

    return $hook_arguments;
}

function newpoints_logs_log_row(): bool
{
    global $log_data;

    if (!in_array($log_data['action'], [
        'shop_purchase',
        'shop_send',
        'shop_item_received',
        'shop_sell',
        'shop_quick_item_delete',
    ])) {
        return false;
    }

    global $mybb, $lang;
    global $log_action, $log_primary, $log_secondary, $log_tertiary;

    language_load('shop');

    if ($log_data['action'] === 'shop_purchase') {
        $log_action = $lang->newpoints_shop_page_logs_shop_purchase;
    }

    if ($log_data['action'] === 'shop_send') {
        $log_action = $lang->newpoints_shop_page_logs_shop_send;
    }

    if ($log_data['action'] === 'shop_item_received') {
        $log_action = $lang->newpoints_shop_page_logs_shop_item_received;
    }

    if ($log_data['action'] === 'shop_sell') {
        $log_action = $lang->newpoints_shop_page_logs_shop_sell;
    }

    if ($log_data['action'] === 'shop_quick_item_delete') {
        $log_action = $lang->newpoints_shop_page_logs_shop_quick_item_delete;
    }

    $user_item_id = (int)$log_data['log_primary_id'];

    $log_primary = $lang->sprintf(
        $lang->newpoints_shop_page_logs_shop_table_log_user_item_id,
        my_number_format($user_item_id)
    );

    $item_id = (int)$log_data['log_secondary_id'];

    $item_data = item_get(["iid='{$item_id}'"], ['name', 'icon']);

    if ($item_data) {
        $action_name = get_setting('shop_action_name');

        $upload_path = (string)get_setting('shop_upload_path');

        $item_name = htmlspecialchars_uni($item_data['name']);

        $item_icon = $mybb->get_asset_url(
            !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
        );

        $view_item_url = url_handler_build([
            'action' => $action_name,
            'view' => 'item',
            'item_id' => $item_id
        ]);

        $log_secondary = eval(templates_get('view_item_icon'));
    }

    if ($log_data['action'] === 'shop_send') {
        $recipient_user_id = (int)$log_data['log_tertiary_id'];

        $user_data = get_user($recipient_user_id);

        $log_tertiary = $lang->sprintf(
            $lang->newpoints_shop_page_logs_shop_table_log_user_recipient,
            build_profile_link(
                format_name(
                    htmlspecialchars_uni($user_data['username']),
                    (int)$user_data['usergroup'],
                    (int)$user_data['displaygroup']
                ),
                (int)$user_data['uid']
            )
        );
    }

    if ($log_data['action'] === 'shop_item_received') {
        $sender_user_id = (int)$log_data['log_tertiary_id'];

        $user_data = get_user($sender_user_id);

        $log_tertiary = $lang->sprintf(
            $lang->newpoints_shop_page_logs_shop_table_log_user,
            build_profile_link(
                format_name(
                    htmlspecialchars_uni($user_data['username']),
                    (int)$user_data['usergroup'],
                    (int)$user_data['displaygroup']
                ),
                (int)$user_data['uid']
            )
        );
    }

    if ($log_data['action'] === 'shop_sell') {
        //$items_rate = (float)$log_data['log_tertiary_id'];
        //todo, remove this log id as floats are not supported
    }

    if ($log_data['action'] === 'shop_sell') {
        $moderator_user_id = (int)$log_data['log_tertiary_id'];

        $user_data = get_user($moderator_user_id);

        $log_tertiary = $lang->sprintf(
            $lang->newpoints_shop_page_logs_shop_table_log_moderator,
            build_profile_link(
                format_name(
                    htmlspecialchars_uni($user_data['username']),
                    (int)$user_data['usergroup'],
                    (int)$user_data['displaygroup']
                ),
                (int)$user_data['uid']
            )
        );
    }

    return true;
}

function newpoints_logs_end(): bool
{
    global $lang;
    global $action_types;

    language_load('shop');

    foreach ($action_types as $key => &$action_type) {
        if ($key === 'shop_purchase') {
            $action_type = $lang->newpoints_shop_page_logs_shop_purchase;
        }

        if ($key === 'shop_send') {
            $action_type = $lang->newpoints_shop_page_logs_shop_send;
        }

        if ($key === 'shop_item_received') {
            $action_type = $lang->newpoints_shop_page_logs_shop_item_received;
        }

        if ($key === 'shop_sell') {
            $action_type = $lang->newpoints_shop_page_logs_shop_sell;
        }

        if ($key === 'shop_quick_item_delete') {
            $action_type = $lang->newpoints_shop_page_logs_shop_quick_item_delete;
        }
    }

    return true;
}

function newpoints_terminate(): bool
{
    global $mybb;

    if ($mybb->get_input('action') !== get_setting('shop_action_name') ||
        empty($mybb->usergroup['newpoints_shop_can_view'])) {
        return false;
    }

    $is_moderator = (bool)is_member(get_setting('shop_manage_groups'));

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
    global $page_title, $newpoints_pagination, $newpoints_menu, $newpoints_errors, $newpoints_additional, $newpoints_content, $newpoints_file;

    $footer .= eval(templates_get('css'));

    $form_url = url_handler_build([
        'action' => $action_name
    ]);

    language_load('shop');

    $current_user_id = (int)$mybb->user['uid'];

    $errors = [];

    $hook_arguments['errors'] = &$errors;

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        add_breadcrumb($lang->newpoints_shop, url_handler_build(['action' => 'shop']));

        $hook_arguments = run_hooks('shop_post_start', $hook_arguments);

        switch ($mybb->get_input('view')) {
            case 'purchase':
                if (empty($mybb->usergroup['newpoints_shop_can_purchase'])) {
                    error_no_permission();
                }

                $hook_arguments = run_hooks('shop_purchase_start', $hook_arguments);

                $items_rate = (float)$mybb->usergroup['newpoints_rate_shop_purchase'];

                $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);

                $where_clauses = ["iid='{$item_id}'"];

                if (!$is_moderator) {
                    $where_clauses[] = "visible='1'";
                }

                $item_data = item_get(
                    $where_clauses,
                    [
                        'iid',
                        'cid',
                        'name',
                        'description',
                        'price',
                        'icon',
                        'infinite',
                        'stock',
                        'pm',
                        'pmadmin'
                    ]
                );

                $hook_arguments['item_data'] = &$item_data;

                if (!$item_data) {
                    error($lang->newpoints_shop_invalid_item);
                }

                $where_clauses = ["cid='{$item_data['cid']}'"];

                if (!$is_moderator) {
                    $where_clauses[] = "visible='1'";
                }

                $category_data = category_get(
                    $where_clauses,
                    ['usergroups'],
                    ['limit' => 1]
                );

                if (!$category_data) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                if (!is_member($category_data['usergroups'])) {
                    error_no_permission();
                }

                $item_name = htmlspecialchars_uni($item_data['name']);

                $item_description = post_parser_parse_message($item_data['description'], ['allow_imgcode' => false]);

                $item_price = (float)$item_data['price'] * ($items_rate / 100);

                if ($item_price > $mybb->user['newpoints']) {
                    $errors[] = $lang->newpoints_shop_not_enough;
                }

                if (empty($item_data['infinite']) && $item_data['stock'] < 1) {
                    $errors[] = $lang->newpoints_shop_out_of_stock;
                }

                if (!empty($item_data['user_limit'])) {
                    $total_user_items = user_items_get(
                        ["item_id='{$item_id}'"],
                        ['COUNT(user_item_id) AS total_user_items'],
                        ['limit' => 1]
                    );

                    $total_user_items = (int)($total_user_items['total_user_items'] ?? 0);

                    if ($total_user_items >= $item_data['user_limit']) {
                        $errors[] = $lang->newpoints_shop_user_limit_reached;
                    }
                }

                $hook_arguments = run_hooks('shop_purchase_intermediate', $hook_arguments);

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

                    $upload_path = (string)get_setting('shop_upload_path');

                    $item_icon = $mybb->get_asset_url(
                        !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
                    );

                    $private_message = $item_data['pm'] ?? get_setting('shop_pm_default');

                    if (!empty($private_message)) {
                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_bought_item_pm_subject,
                                'message' => str_replace(
                                    [
                                        '{itemname}',
                                        '{itemid}',
                                        '{user_name}',
                                        '{item_name}',
                                        '{item_id}',
                                        '{item_image}'
                                    ],
                                    [
                                        $item_data['name'],
                                        $item_data['iid'],
                                        $mybb->user['username'],
                                        $item_data['name'],
                                        $item_data['iid'],
                                        $item_icon
                                    ],
                                    $private_message
                                ),
                                'touid' => $current_user_id,
                                'receivepms' => 1
                            ],
                            -1
                        );
                    }

                    $private_message_admin = $item_data['pmadmin'] ?? get_setting('shop_pmadmin_default');

                    $private_message_admin_user_ids = array_filter(
                        array_map('intval', explode(',', get_setting('shop_pmadmins')))
                    );

                    if (!empty($private_message_admin) && $private_message_admin_user_ids) {
                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_bought_item_pmadmin_subject,
                                'message' => str_replace(
                                    [
                                        '{itemname}',
                                        '{itemid}',
                                        '{user_name}',
                                        '{item_name}',
                                        '{item_id}',
                                        '{item_image}'
                                    ],
                                    [
                                        $item_data['name'],
                                        $item_data['iid'],
                                        $mybb->user['username'],
                                        $item_data['name'],
                                        $item_data['iid'],
                                        $item_icon
                                    ],
                                    $private_message_admin
                                ),
                                'touid' => $private_message_admin_user_ids,
                                'receivepms' => 1
                            ],
                            $current_user_id
                        );
                    }

                    $hook_arguments = run_hooks('shop_purchase_end', $hook_arguments);

                    user_update_details($current_user_id);

                    redirect(
                        $mybb->settings['bburl'] . '/' . $form_url,
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

                    if ($item_price > $mybb->user['newpoints']) {
                        $price_class = 'insufficient_funds';
                    } else {
                        $price_class = 'sufficient_funds';
                    }

                    $item_price = points_format($item_price);

                    $upload_path = (string)get_setting('shop_upload_path');

                    $item_icon = $mybb->get_asset_url(
                        !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
                    );

                    $view_item_url = url_handler_build([
                        'action' => $action_name,
                        'view' => 'item',
                        'item_id' => $item_id
                    ]);

                    $item_icon = eval(templates_get('confirm_purchase_icon'));

                    page_build_purchase_confirmation(
                        $lang->newpoints_shop_confirm_purchase_description,
                        'item_id',
                        $item_id,
                        'purchase',
                        eval(templates_get('confirm_purchase'))
                    );
                }

                break;
            case 'send':
                $hook_arguments = run_hooks('shop_send_start', $hook_arguments);

                $user_item_id = $mybb->get_input('user_item_id', MyBB::INPUT_INT);

                if ($user_item_id) {
                    $user_items_data = user_items_get(
                        ["user_id='{$current_user_id}'", "user_item_id='{$user_item_id}'"],
                        ['item_id'],
                        ['limit' => 1]
                    );

                    $item_id = (int)($user_items_data['item_id'] ?? 0);
                } else {
                    $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);
                }

                $where_clauses = ["iid='{$item_id}'"];

                if (!$is_moderator) {
                    $where_clauses[] = "visible='1'";
                }

                $item_data = item_get($where_clauses, ['cid', 'name']);

                $hook_arguments['item_data'] = &$item_data;

                if (empty($item_data)) {
                    error($lang->newpoints_shop_invalid_item);
                }

                $where_clauses = ["cid='{$item_data['cid']}'"];

                if (!$is_moderator) {
                    $where_clauses[] = "visible='1'";
                }

                $category_data = category_get($where_clauses, ['usergroups'], ['limit' => 1]);

                $hook_arguments['category_data'] = &$category_data;

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
                        [],
                        ['limit' => 1]
                    );

                    $user_item_id = (int)($user_items_objects['user_item_id'] ?? 0);
                }

                $hook_arguments['user_item_id'] = &$user_item_id;

                if (empty($user_item_id)) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                $lang->newpoints_page_confirm_table_purchase_title = $lang->newpoints_shop_confirm_send_title;

                $lang->newpoints_page_confirm_table_purchase_button = $lang->newpoints_shop_confirm_send_button;

                $user_name = '';

                $user_name = $mybb->get_input('username');

                $hook_arguments = run_hooks('shop_send_intermediate', $hook_arguments);

                if (isset($mybb->input['confirm'])) {
                    $user_data = users_get_by_username($user_name);

                    if (empty($user_data)) {
                        $errors[] = $lang->newpoints_shop_invalid_user;
                    }

                    if ((int)$user_data['uid'] === $current_user_id) {
                        $errors[] = $lang->newpoints_shop_cant_send_item_self;
                    }

                    $hook_arguments = run_hooks('shop_post_send_start', $hook_arguments);

                    if (empty($errors)) {
                        $user_id = (int)$user_data['uid'];

                        $hook_arguments = run_hooks('shop_post_send_start', $hook_arguments);

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

                        $log_id = log_add(
                            'shop_item_received',
                            '',
                            $user_data['username'] ?? '',
                            $user_id,
                            0,
                            $user_item_id,
                            $item_id,
                            $current_user_id
                        );

                        alert_send($user_id, (int)$log_id, 'shop', 'item_received');

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

                        $hook_arguments = run_hooks('shop_post_send_end', $hook_arguments);

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

                $hook_arguments = run_hooks('shop_send_end', $hook_arguments);

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
                $hook_arguments = run_hooks('shop_sell_start', $hook_arguments);

                $user_item_id = $mybb->get_input('user_item_id', MyBB::INPUT_INT);

                if (!empty($user_item_id)) {
                    $user_items_objects = user_items_get(
                        ["user_id='{$current_user_id}'", "user_item_id='{$user_item_id}'"],
                        ['item_id', 'item_price'],
                        ['limit' => 1]
                    );

                    $item_id = (int)($user_items_objects['item_id'] ?? 0);

                    $item_price = (float)($user_items_objects['item_price'] ?? 0);
                } else {
                    $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);
                }

                $where_clauses = ["iid='{$item_id}'"];

                if (!$is_moderator) {
                    $where_clauses[] = "visible='1'";
                }

                $item_data = item_get($where_clauses, ['cid', 'name', 'stock', 'price']);

                if (empty($item_data)) {
                    error($lang->newpoints_shop_invalid_item);
                }

                $hook_arguments['item_data'] = &$item_data;

                if (!isset($item_price)) {
                    $item_price = (float)$item_data['price'];
                }

                if (empty($user_item_id)) {
                    $user_items_objects = user_items_get(
                        ["user_id='{$current_user_id}'", "item_id='{$item_id}'"],
                        ['user_item_id', 'item_price'],
                        ['limit' => 1]
                    );

                    $user_item_id = (int)($user_items_objects['user_item_id'] ?? 0);

                    $item_price = (float)($user_items_objects['item_price'] ?? 0);
                }

                if (empty($user_item_id)) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                $items_rate = (int)$mybb->usergroup['newpoints_rate_shop_sell'];

                $item_price = $item_price * ($items_rate / 100);

                $where_clauses = ["cid='{$item_data['cid']}'"];

                if (!$is_moderator) {
                    $where_clauses[] = "visible='1'";
                }

                $category_data = category_get($where_clauses, ['usergroups'], ['limit' => 1]);

                $hook_arguments['category_data'] = &$category_data;

                if (!$category_data) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                if (!is_member($category_data['usergroups'])) {
                    error_no_permission();
                }

                $item_name = htmlspecialchars_uni($item_data['name']);

                $lang->newpoints_page_confirm_table_purchase_title = $lang->newpoints_shop_confirm_sell_title;

                $lang->newpoints_page_confirm_table_purchase_button = $lang->newpoints_shop_confirm_sell_button;

                $user_name = '';

                $user_name = $mybb->get_input('username');

                $hook_arguments = run_hooks('shop_sell_intermediate', $hook_arguments);

                if (isset($mybb->input['confirm'])) {
                    if (empty($errors)) {
                        $hook_arguments = run_hooks('shop_post_sell_start', $hook_arguments);

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
                            $user_item_id,
                            $item_id,
                            $items_rate,
                            LOGGING_TYPE_INCOME
                        );

                        user_update_details($current_user_id);

                        $my_items_url = url_handler_build([
                            'action' => $action_name,
                            'view' => 'my_items'
                        ]);

                        $hook_arguments = run_hooks('shop_post_sell_end', $hook_arguments);

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

                $hook_arguments = run_hooks('shop_sell_end', $hook_arguments);

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

        $hook_arguments = run_hooks('shop_post_end', $hook_arguments);
    }

    user_update_details(1);

    add_breadcrumb($lang->newpoints_shop, url_handler_build(['action' => 'shop']));

    $hook_arguments = run_hooks('shop_start', $hook_arguments);

    $newpoints_buttons = '';

    if (in_array($mybb->get_input('view'), ['edit_category', 'add_category'])) {
        if (!$is_moderator) {
            error_no_permission();
        }

        $is_add_page = $mybb->get_input('view') === 'add_category';

        $query_fields_categories = [
            'name',
            'description',
            'visible',
            'icon',
            'usergroups',
            'disporder'
        ];

        $hook_arguments['query_fields_categories'] = &$query_fields_categories;

        $hook_arguments = run_hooks('shop_add_edit_category_start', $hook_arguments);

        if ($is_add_page) {
            $page_title = $lang->newpoints_shop_add_category;

            $table_title = $lang->newpoints_shop_add_category_table_title;
        } else {
            $page_title = $lang->newpoints_shop_edit_category;

            $table_title = $lang->newpoints_shop_edit_category_table_title;
        }

        $category_id = $mybb->get_input('category_id', MyBB::INPUT_INT);

        $category_data = category_get(
            ["cid='{$category_id}'"],
            $query_fields_categories,
            ['limit' => 1]
        );

        $hook_arguments['category_data'] = &$category_data;

        if (!$category_data && !$is_add_page) {
            error_no_permission();
        }

        if ($mybb->request_method === 'post') {
            verify_post_check($mybb->get_input('my_post_key'));

            $insert_data = [
                'name' => $mybb->get_input('name'),
                'description' => $mybb->get_input('description'),
                'visible' => $mybb->get_input('is_visible', MyBB::INPUT_INT),
                'disporder' => $mybb->get_input('display_order', MyBB::INPUT_INT),
            ];

            $insert_data['usergroups'] = array_flip(
                array_map('intval', $mybb->get_input('group_ids', MyBB::INPUT_ARRAY))
            );

            if (isset($insert_data['usergroups'][GROUPS_PERMISSION_ALL])) {
                $insert_data['usergroups'] = GROUPS_PERMISSION_ALL;
            } else {
                $insert_data['usergroups'] = implode(',', array_keys($insert_data['usergroups']));
            }

            if (my_strlen($insert_data['name']) > 100 || my_strlen($insert_data['name']) < 1) {
                $errors[] = $lang->newpoints_shop_error_invalid_item_name;
            }

            if (empty($errors) && !empty($_FILES['icon_file']['name'])) {
                $upload = item_upload_icon($_FILES['icon_file']);
            }

            $hook_arguments['insert_data'] = &$insert_data;

            $hook_arguments = run_hooks('shop_post_add_edit_category_intermediate', $hook_arguments);

            if (empty($errors)) {
                if (!empty($upload['file_name'])) {
                    $insert_data['icon'] = $db->escape_string($upload['file_name']);
                }

                $hook_arguments = run_hooks('shop_post_add_edit_category_end', $hook_arguments);

                if ($is_add_page) {
                    category_insert($insert_data);

                    cache_update();

                    redirect(
                        $mybb->settings['bburl'] . '/' . $form_url,
                        $lang->newpoints_shop_redirect_category_add
                    );
                } else {
                    category_update($insert_data, $category_id);

                    cache_update();

                    redirect(
                        $mybb->settings['bburl'] . '/' . $form_url,
                        $lang->newpoints_shop_redirect_category_update
                    );
                }
            }

            if (!empty($errors)) {
                $newpoints_errors = inline_error($errors);
            }

            $category_data = array_merge($category_data, $insert_data);
        }

        $groups_select = (function (array $selected_category) use (
            $category_data,
            $is_moderator,
            $mybb,
            $lang
        ): string {
            $group_id = -1;

            $group_title = $lang->newpoints_shop_edit_category_table_category_category_all;

            $group_selected = '';

            if (in_array($group_id, $selected_category)) {
                $group_selected = 'selected="selected"';
            }

            $select_options = eval(templates_get('category_add_edit_form_category_option'));

            foreach (
                $mybb->cache->read('usergroups') as $group_data
            ) {
                $group_id = (int)$group_data['gid'];

                $group_title = htmlspecialchars_uni($group_data['title']);

                $group_selected = '';

                if (in_array($group_id, $selected_category)) {
                    $group_selected = 'selected="selected"';
                }

                $select_options .= eval(templates_get('category_add_edit_form_category_option'));
            }

            return eval(templates_get('category_add_edit_form_category'));
        })(
            explode(',', $category_data['usergroups'] ?? '')
        );

        $category_name = htmlspecialchars_uni($category_data['name'] ?? '');

        $category_description = htmlspecialchars_uni($category_data['description'] ?? '');

        $category_display_order = (int)($category_data['disporder'] ?? 0);

        $category_checked_element_is_visible_yes = $category_checked_element_is_visible_no = '';

        if (!empty($category_data['visible'])) {
            $category_checked_element_is_visible_yes = 'checked="checked"';
        } else {
            $category_checked_element_is_visible_no = 'checked="checked"';
        }

        $alternative_background = alt_trow(true);

        $hook_arguments['alternative_background'] = &$alternative_background;

        $icon_file_row = '';

        if (!$is_add_page) {
            $alternative_background = alt_trow();

            $icon_file_row = eval(templates_get('category_add_edit_form_upload'));
        }

        $category_extra_rows = [];

        $hook_arguments['category_extra_rows'] = &$category_extra_rows;

        $hook_arguments = run_hooks('shop_add_edit_category_end', $hook_arguments);

        $category_extra_rows = implode('', $category_extra_rows);

        $button_text = $lang->newpoints_shop_edit_category_button_update;

        if ($is_add_page) {
            $button_text = $lang->newpoints_shop_add_category_button_create;
        }

        $newpoints_content = eval(templates_get('category_add_edit_form'));

        $page = eval(\Newpoints\Core\templates_get('page'));
    } elseif ($mybb->get_input('view') === 'delete_category') {
        if (!$is_moderator) {
            error_no_permission();
        }

        $hook_arguments = run_hooks('shop_delete_category_start', $hook_arguments);

        $category_id = $mybb->get_input('category_id', MyBB::INPUT_INT);

        $category_data = category_get(["cid='{$category_id}'"], [], ['limit' => 1]);

        $hook_arguments['category_data'] = &$category_data;

        if (!$category_data) {
            error_no_permission();
        }

        if (!empty($mybb->input['confirm'])) {
            $hook_arguments = run_hooks('shop_delete_category_confirm', $hook_arguments);

            category_delete($category_id);

            redirect(
                $mybb->settings['bburl'] . '/' . $form_url,
                $lang->newpoints_shop_redirect_item_delete
            );
        }

        $lang->newpoints_page_confirm_table_cancel_title = $lang->newpoints_shop_confirm_category_delete_title;

        $lang->newpoints_page_confirm_table_cancel_button = $lang->newpoints_shop_confirm_category_delete_button;

        page_build_cancel_confirmation(
            'category_id',
            $category_id,
            $lang->newpoints_page_confirm_category_delete_text,
            'delete_category'
        );
    } elseif ($mybb->get_input('view') === 'delete_item') {
        if (!$is_moderator) {
            error_no_permission();
        }

        $hook_arguments = run_hooks('shop_delete_item_start', $hook_arguments);

        $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);

        $item_data = item_get(["iid='{$item_id}'"]);

        $hook_arguments['item_data'] = &$item_data;

        if (!$item_data) {
            error_no_permission();
        }

        if (!empty($mybb->input['confirm'])) {
            $hook_arguments = run_hooks('shop_delete_item_confirm', $hook_arguments);

            item_delete($item_id);

            redirect(
                $mybb->settings['bburl'] . '/' . $form_url,
                $lang->newpoints_shop_redirect_item_delete
            );
        }

        $lang->newpoints_page_confirm_table_cancel_title = $lang->newpoints_shop_confirm_item_delete_title;

        $lang->newpoints_page_confirm_table_cancel_button = $lang->newpoints_shop_confirm_item_delete_button;

        page_build_cancel_confirmation(
            'item_id',
            $item_id,
            $lang->newpoints_page_confirm_item_delete_text,
            'delete_item'
        );
    } elseif (in_array($mybb->get_input('view'), ['edit_item', 'add_item'])) {
        if (!$is_moderator) {
            error_no_permission();
        }

        $is_add_page = $mybb->get_input('view') === 'add_item';

        $query_fields_items = [
            'cid',
            'name',
            'description',
            'price',
            'icon',
            'visible',
            'disporder',
            'infinite',
            'user_limit',
            'stock',
            'sendable',
            'sellable',
            'pm',
            'pmadmin'
        ];

        $query_fields_category = [
            'name',
            'description',
            'visible',
            'icon',
            'usergroups',
            'disporder'
        ];

        $hook_arguments['query_fields_items'] = &$query_fields_items;

        $hook_arguments['query_fields_category'] = &$query_fields_category;

        $hook_arguments = run_hooks('shop_add_edit_item_start', $hook_arguments);

        if ($is_add_page) {
            $page_title = $lang->newpoints_shop_add_item;

            $table_title = $lang->newpoints_shop_add_item_table_title;
        } else {
            $page_title = $lang->newpoints_shop_edit_item;

            $table_title = $lang->newpoints_shop_edit_item_table_title;
        }

        $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);

        $item_data = item_get(
            ["iid='{$item_id}'"],
            $query_fields_items
        );

        $hook_arguments['item_data'] = &$item_data;

        if (!$item_data && !$is_add_page) {
            error_no_permission();
        }

        if ($is_add_page) {
            $category_id = $mybb->get_input('category_id', MyBB::INPUT_INT);
        } else {
            $category_id = (int)$item_data['cid'];
        }

        $category_data = category_get(
            ["cid='{$category_id}'"],
            $query_fields_category,
            ['limit' => 1]
        );

        $hook_arguments['category_data'] = &$category_data;

        if (!$category_data) {
            error_no_permission();
        }

        if ($mybb->request_method === 'post') {
            verify_post_check($mybb->get_input('my_post_key'));

            $insert_data = [
                'cid' => $category_id,
                'name' => $mybb->get_input('name'),
                'description' => $mybb->get_input('description'),
                'price' => $mybb->get_input('price', MyBB::INPUT_FLOAT),
                'visible' => $mybb->get_input('is_visible', MyBB::INPUT_INT),
                'disporder' => $mybb->get_input('display_order', MyBB::INPUT_INT),
                'infinite' => $mybb->get_input('infinite', MyBB::INPUT_INT),
                'user_limit' => $mybb->get_input('user_limit', MyBB::INPUT_INT),
                'stock' => $mybb->get_input('stock', MyBB::INPUT_INT),
                'sendable' => $mybb->get_input('can_be_sent', MyBB::INPUT_INT),
                'sellable' => $mybb->get_input('can_be_sold', MyBB::INPUT_INT),
                'pm' => $mybb->get_input('private_message'),
                'pmadmin' => $mybb->get_input('private_message_admin'),
            ];

            if (my_strlen($insert_data['name']) > 100 || my_strlen($insert_data['name']) < 1) {
                $errors[] = $lang->newpoints_shop_error_invalid_item_name;
            }

            if (my_strlen($insert_data['price']) < 0) {
                $errors[] = $lang->newpoints_shop_error_invalid_item_icon;
            }

            if (!category_get(["cid='{$insert_data['cid']}'"], [], ['limit' => 1])) {
                $errors[] = $lang->newpoints_shop_error_invalid_item_category;
            }

            if (empty($errors) && !empty($_FILES['icon_file']['name'])) {
                $upload = item_upload_icon($_FILES['icon_file'], $item_id);
            }

            $hook_arguments['insert_data'] = &$insert_data;

            $hook_arguments = run_hooks('shop_post_add_edit_item_intermediate', $hook_arguments);

            if (empty($errors)) {
                if (!empty($upload['file_name'])) {
                    $insert_data['icon'] = $db->escape_string($upload['file_name']);
                }

                $hook_arguments = run_hooks('shop_post_add_edit_item_end', $hook_arguments);

                $form_url = url_handler_build([
                    'action' => $action_name,
                    "page{$category_id}" => $mybb->get_input("page{$category_id}", MyBB::INPUT_INT)
                ]);

                if ($is_add_page) {
                    item_insert($insert_data);

                    cache_update();

                    redirect(
                        $mybb->settings['bburl'] . '/' . $form_url,
                        $lang->newpoints_shop_redirect_item_add
                    );
                } else {
                    item_update($insert_data, $item_id);

                    cache_update();

                    redirect(
                        $mybb->settings['bburl'] . '/' . $form_url,
                        $lang->newpoints_shop_redirect_item_update
                    );
                }
            }

            if (!empty($errors)) {
                $newpoints_errors = inline_error($errors);
            }

            $item_data = array_merge($item_data, $insert_data);
        }

        $categories_select = (function (int $selected_category) use ($item_data, $is_moderator): string {
            $select_options = '';

            $where_clauses = [];

            if (!$is_moderator) {
                $where_clauses[] = "visible='1'";
            }

            foreach (
                category_get(
                    $where_clauses,
                    ['name'],
                    ['order_by' => 'name']
                ) as $category_id => $category_data
            ) {
                $category_name = htmlspecialchars_uni($category_data['name']);

                $category_selected = '';

                if ($category_id === $selected_category) {
                    $category_selected = 'selected="selected"';
                }

                $select_options .= eval(templates_get('item_add_edit_form_category_option'));
            }

            return eval(templates_get('item_add_edit_form_category'));
        })(
            $category_id
        );

        $item_name = htmlspecialchars_uni($item_data['name'] ?? '');

        $item_description = htmlspecialchars_uni($item_data['description'] ?? '');

        $item_private_message = htmlspecialchars_uni($item_data['pm'] ?? '');

        $item_private_message_admin = htmlspecialchars_uni($item_data['pmadmin'] ?? '');

        $item_display_order = (int)($item_data['disporder'] ?? 0);

        $item_stock = (int)($item_data['stock'] ?? 0);

        $item_user_limit = (int)($item_data['user_limit'] ?? 0);

        $item_price = (float)($item_data['price'] ?? 0);

        $item_checked_element_infinite_yes = $item_checked_element_infinite_no = $item_checked_element_is_visible_yes = $item_checked_element_is_visible_no = $item_checked_element_can_be_sent_yes = $item_checked_element_can_be_sent_no = $item_checked_element_can_be_sold_yes = $item_checked_element_can_be_sold_no = '';

        if (!empty($item_data['infinite'])) {
            $item_checked_element_infinite_yes = 'checked="checked"';
        } else {
            $item_checked_element_infinite_no = 'checked="checked"';
        }

        if (!empty($item_data['visible'])) {
            $item_checked_element_is_visible_yes = 'checked="checked"';
        } else {
            $item_checked_element_is_visible_no = 'checked="checked"';
        }

        if (!empty($item_data['sendable'])) {
            $item_checked_element_can_be_sent_yes = 'checked="checked"';
        } else {
            $item_checked_element_can_be_sent_no = 'checked="checked"';
        }

        if (!empty($item_data['sellable'])) {
            $item_checked_element_can_be_sold_yes = 'checked="checked"';
        } else {
            $item_checked_element_can_be_sold_no = 'checked="checked"';
        }

        $alternative_background = alt_trow(true);

        $hook_arguments['alternative_background'] = &$alternative_background;

        $icon_file_row = '';

        if (!$is_add_page) {
            $alternative_background = alt_trow(true);

            $icon_file_row = eval(templates_get('item_add_edit_form_upload'));
        }

        $item_extra_rows = [];

        $hook_arguments['item_extra_rows'] = &$item_extra_rows;

        $hook_arguments = run_hooks('shop_add_edit_item_end', $hook_arguments);

        $item_extra_rows = implode('', $item_extra_rows);

        $step = DECIMAL_DATA_TYPE_STEP;

        $category_page = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);

        $button_text = $lang->newpoints_shop_edit_item_button_update;

        if ($is_add_page) {
            $button_text = $lang->newpoints_shop_edit_item_button_create;
        }

        $newpoints_content = eval(templates_get('item_add_edit_form'));

        $page = eval(\Newpoints\Core\templates_get('page'));
    } elseif ($mybb->get_input('view') === 'item') {
        $hook_arguments = run_hooks('shop_item_start', $hook_arguments);

        $items_rate = (float)$mybb->usergroup['newpoints_rate_shop_purchase'];

        $item_id = $mybb->get_input('item_id', MyBB::INPUT_INT);

        $where_clause = ["iid='{$item_id}'"];

        if (!$is_moderator) {
            $where_clause[] = "visible='1'";
        }

        $item_data = item_get(
            $where_clause,
            ['cid', 'name', 'description', 'price', 'icon', 'infinite', 'stock', 'sendable', 'sellable']
        );

        $hook_arguments['item_data'] = &$item_data;

        if (!$item_data) {
            error($lang->newpoints_shop_invalid_item);
        }

        $category_id = (int)$item_data['cid'];

        $where_clauses = ["cid='{$category_id}'"];

        if (!$is_moderator) {
            $where_clauses[] = "visible='1'";
        }

        $category_data = category_get($where_clauses, ['usergroups'], ['limit' => 1]);

        $hook_arguments['category_data'] = &$category_data;

        $hook_arguments = run_hooks('shop_item_intermediate', $hook_arguments);

        if (empty($category_data)) {
            error($lang->newpoints_shop_invalid_cat);
        }

        if (!is_member($category_data['usergroups'])) {
            error_no_permission();
        }

        $item_name = htmlspecialchars_uni($item_data['name']);

        $item_description = post_parser_parse_message($item_data['description'], ['allow_imgcode' => false]);

        $item_price = (float)$item_data['price'] * ($items_rate / 100);

        if ($item_price > $mybb->user['newpoints']) {
            $item_price_class = 'insufficient_funds';
        } else {
            $item_price_class = 'sufficient_funds';
        }

        if ($item_price > $mybb->user['newpoints']) {
            $price_class = 'insufficient_funds';
        } else {
            $price_class = 'sufficient_funds';
        }

        $item_price = points_format($item_price);

        $upload_path = (string)get_setting('shop_upload_path');

        $item_icon = $mybb->get_asset_url(
            !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
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

        $item_purchase_row = '';

        if (!empty($mybb->usergroup['newpoints_shop_can_purchase'])) {
            $purchase_item_url = url_handler_build([
                'action' => $action_name,
                'view' => 'purchase'
            ]);

            $item_purchase_row = eval(templates_get('view_item_purchase'));
        }

        $page_title = $lang->newpoints_shop_view_item;

        $hook_arguments = run_hooks('shop_item_end', $hook_arguments);

        $newpoints_content = eval(templates_get('view_item'));

        $page = eval(\Newpoints\Core\templates_get('page'));
    } elseif ($mybb->get_input('view') === 'my_items') {
        $hook_arguments = run_hooks('shop_my_items_start', $hook_arguments);

        $user_id = $mybb->get_input('uid', MyBB::INPUT_INT);

        if (empty($user_id)) {
            $user_id = $current_user_id;
        }

        $url_params = ['action' => $action_name, 'view' => 'item'];

        $user_data = get_user($user_id);

        $hook_arguments['user_data'] = &$user_data;

        if (!$user_data || $user_id !== $current_user_id && empty($mybb->usergroup['newpoints_shop_can_view_inventories'])) {
            error_no_permission();
        }

        $lang->newpoints_shop_myitems = $lang->sprintf(
            $lang->newpoints_shop_items_username,
            htmlspecialchars_uni($user_data['username'])
        );

        $visible_items_ids = items_get_visible();

        $visible_items_ids = implode("','", $visible_items_ids);

        $where_clauses = ["user_id='{$user_id}'", "item_id IN ('{$visible_items_ids}')"];

        if (!$is_moderator) {
            $where_clauses[] = "is_visible='1'";
        }

        $total_user_items = count(
            user_items_get(
                $where_clauses,
                ['item_id', 'COUNT(DISTINCT user_item_id) AS total_user_items'],
                ['group_by' => 'item_id'],
                true
            )
        );

        $hook_arguments['total_user_items'] = &$total_user_items;

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
            ['item_id', 'COUNT(DISTINCT user_item_id) AS total_user_items'],
            [
                'group_by' => ' item_id',
                'order_by' => 'item_id',
                'order_dir' => 'desc',
                'limit' => $per_page,
                'limit_start' => $start
            ],
            true
        );

        $hook_arguments['user_items_objects'] = &$user_items_objects;

        $items_ids = implode("','", array_map('intval', array_unique(array_column($user_items_objects, 'item_id'))));

        $query_fields_items = [
            'iid',
            'cid',
            'name',
            'description',
            'price',
            'icon',
            'sendable',
            'sellable'
        ];

        $query_fields_category = [
            'name',
            'description',
            'visible',
            'icon',
            'usergroups',
            'disporder'
        ];

        $hook_arguments['query_fields_items'] = &$query_fields_items;

        $hook_arguments['query_fields_category'] = &$query_fields_category;

        $hook_arguments = run_hooks('shop_my_items_intermediate', $hook_arguments);

        $post_items_cache = items_get(
            ["iid IN ('{$items_ids}')", "visible='1'"],
            $query_fields_items
        );

        $shop_items = '';

        $hook_arguments['shop_items'] = &$shop_items;

        $alternative_background = alt_trow(true);

        $inverted_background = alt_trow();

        $items_rate = (float)$mybb->usergroup['newpoints_rate_shop_purchase'];

        $category_ids = [];

        foreach ($user_items_objects as $user_item_data) {
            $category_ids[] = (int)$post_items_cache[$user_item_data['item_id']]['cid'];
        }

        $category_ids = implode("','", $category_ids);

        $categories_cache = category_get(
            ["cid IN ('{$category_ids}')"],
            $query_fields_category
        );

        foreach ($user_items_objects as $user_item_data) {
            $item_id = $url_params['item_id'] = (int)$user_item_data['item_id'];

            if (empty($post_items_cache[$item_id])) {
                continue;
            }

            $item_data = $post_items_cache[$item_id];

            $hook_arguments['item_data'] = &$item_data;

            $category_id = (int)$item_data['cid'];

            $category_data = $categories_cache[$category_id];

            $hook_arguments['category_id'] = &$category_id;

            $hook_arguments['category_data'] = &$category_data;

            $button_extra = [];

            $hook_arguments['button_extra'] = &$button_extra;

            $hook_arguments = run_hooks('shop_my_items_item_start', $hook_arguments);

            $button_extra = implode('', $button_extra);

            $option_buttons = $button_sell = $button_send = '';

            if (!empty($mybb->usergroup['newpoints_shop_can_send']) && !empty($item_data['sendable'])) {
                $button_send = eval(templates_get('my_items_row_options_send'));
            }

            if (!empty($mybb->usergroup['newpoints_shop_can_sell']) && !empty($item_data['sellable'])) {
                $button_sell = eval(templates_get('my_items_row_options_sell'));
            }

            if ($button_send || $button_sell || $button_extra) {
                $option_buttons = eval(templates_get('my_items_row_options'));
            }

            $item_name = htmlspecialchars_uni($item_data['name']);

            $item_description = post_parser_parse_message($item_data['description'], ['allow_imgcode' => false]);

            $upload_path = (string)get_setting('shop_upload_path');

            $item_icon = $mybb->get_asset_url(
                !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
            );

            $view_item_url = url_handler_build($url_params);

            $item_icon = eval(templates_get('my_items_row_icon'));

            $item_price = (float)$item_data['price'] * ($items_rate / 100);

            if ($item_price > $mybb->user['newpoints']) {
                $price_class = 'insufficient_funds';
            } else {
                $price_class = 'sufficient_funds';
            }

            $item_price = points_format($item_price);

            $item_stock = my_number_format($user_item_data['total_user_items']);

            $view_item_url = url_handler_build([
                'action' => $action_name,
                'view' => 'item',
                'item_id' => $item_id
            ]);

            $item_extra_info = [];

            $hook_arguments['item_extra_info'] = &$item_extra_info;

            $hook_arguments = run_hooks('shop_my_items_item_end', $hook_arguments);

            $item_extra_info = implode('', $item_extra_info);

            $shop_items .= eval(templates_get('my_items_row'));

            $alternative_background = alt_trow();

            $inverted_background = alt_trow();
        }

        unset($url_params['item_id']);

        $hook_arguments = run_hooks('shop_my_items_end', $hook_arguments);

        if (!$shop_items) {
            $shop_items = eval(templates_get('my_items_empty'));
        }

        $newpoints_content = eval(templates_get('my_items_content'));

        $page = eval(\Newpoints\Core\templates_get('page'));
    } else {
        $page_title = $lang->newpoints_shop;

        $items_rate = (float)$mybb->usergroup['newpoints_rate_shop_purchase'];

        global $cats, $items;

        $where_clauses = [];

        if (!$is_moderator) {
            $where_clauses[] = "visible='1'";
        }

        $query = $db->simple_select(
            'newpoints_shop_categories',
            'cid, name, description, visible, icon, usergroups, disporder',
            implode(' AND ', $where_clauses),
            ['order_by' => 'disporder', 'order_dir' => 'ASC']
        );

        $categories_cache = [];

        $hook_arguments['items_rate'] = &$items_rate;

        $hook_arguments['categories_cache'] = &$categories_cache;

        $upload_path = (string)get_setting('shop_upload_path');

        while ($category_data = $db->fetch_array($query)) {
            $categories_cache[] = [
                'category_id' => (int)$category_data['cid'],
                'category_name' => (string)$category_data['name'],
                'category_description' => (string)$category_data['description'],
                'is_visible' => (bool)$category_data['visible'],
                'icon_url' => "{$upload_path}/{$category_data['icon']}",
                'allowed_groups' => (string)$category_data['usergroups'],
                'total_items' => (function () use ($category_data, $is_moderator): int {
                    $category_id = (int)$category_data['cid'];

                    $where_clauses = ["cid='{$category_id}'"];

                    if (!$is_moderator) {
                        $where_clauses[] = "visible='1'";
                    }

                    global $db;

                    $query = $db->simple_select(
                        'newpoints_shop_items',
                        'COUNT(iid) AS total_items',
                        implode(' AND ', $where_clauses)
                    );

                    return (int)$db->fetch_field($query, 'total_items');
                })(),
                //'is_expanded' => (bool)$category_data['expanded'],
                'get_items' => function (int $category_id, array $category_data) use (
                    $per_page,
                    $upload_path,
                    $is_moderator
                ): array {
                    global $mybb, $db;

                    $where_clauses = ["cid='{$category_id}'"];

                    if (!$is_moderator) {
                        $where_clauses[] = "visible='1'";
                    }

                    if ($mybb->get_input("page{$category_id}", MyBB::INPUT_INT) > 0) {
                        $start_row = ($mybb->get_input("page{$category_id}", MyBB::INPUT_INT) - 1) * $per_page;

                        $total_pages = ceil($category_data['total_items'] / $per_page);

                        if ($mybb->get_input("page{$category_id}", MyBB::INPUT_INT) > $total_pages) {
                            $start_row = 0;

                            $mybb->input["page{$category_id}"] = 1;
                        }
                    } else {
                        $start_row = 0;

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
                            'limit_start' => $start_row
                        ]
                    );

                    $items_objects = [];

                    while ($item_data = $db->fetch_array($query)) {
                        $item_id = (int)$item_data['iid'];

                        $items_objects[$item_id] = [
                            'item_id' => $item_id,
                            'item_name' => (string)$item_data['name'],
                            'item_description' => (string)$item_data['description'],
                            'item_price' => (float)$item_data['price'],
                            'icon_url' => !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : '',
                            'is_visible' => (bool)$item_data['visible'],
                            'is_infinite' => (bool)$item_data['infinite'],
                            'item_stock' => (int)$item_data['stock'],
                            'allowed_groups' => -1
                        ];
                    }

                    return $items_objects;
                },
                'category_type' => 'shop',
                'display_options' => true,
                'force_display' => true
            ];
        }

        $hook_arguments = run_hooks('shop_main_start', $hook_arguments);

        global $extdisplay, $expcolimage, $expdisplay, $expaltext;

        $newpoints_content = '';

        $pagination_input_array = ['action' => $action_name];

        foreach ($categories_cache as $category_data) {
            $category_id = $category_data['category_id'];

            if (isset($mybb->input["page{$category_id}"])) {
                $pagination_input_array["page{$category_id}"] = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);
            }
        }

        global $collapse, $collapsed, $collapsedimg;

        foreach ($categories_cache as $category_data) {
            $hook_arguments['category_data'] = &$category_data;

            $hook_arguments = run_hooks('shop_main_category_start', $hook_arguments);

            $total_items = $category_data['total_items'];

            if (
                (!$is_moderator && empty($category_data['is_visible'])) ||
                (!$is_moderator && !is_member($category_data['allowed_groups'])) ||
                !$total_items && empty($category_data['force_display'])
            ) {
                continue;
            }

            $category_id = (int)$category_data['category_id'];

            $collapsed_name = "category_{$category_id}";

            $collapsedimg[$collapsed_name] = $collapsedimg[$collapsed_name] ?? '';

            $collapsed_image = $collapsedimg[$collapsed_name];

            $expanded_display = $collapsed["{$collapsed_name}_e"] = $collapsed["{$collapsed_name}_e"] ?? '';

            $expanded_alternative_text = !empty($collapsed["{$collapsed_name}_e"]) ? $lang->expcol_expand : $lang->expcol_collapse;

            $category_description = htmlspecialchars_uni($category_data['category_description']);

            $category_name = htmlspecialchars_uni($category_data['category_name']);

            $category_icon = '';

            if (!empty($category_data['icon_url'])) {
                $category_icon = $mybb->get_asset_url($category_data['icon_url']);

                $category_icon = eval(templates_get('category_icon'));
            }

            $items_rows = '';

            $alternative_background = alt_trow();

            $category_type = $category_data['category_type'] ?? 'shop';

            $column_span = 5;

            if ($is_moderator) {
                ++$column_span;
            }

            $category_extra_columns = [];

            $hook_arguments['category_extra_columns'] = &$category_extra_columns;

            if (!empty($mybb->usergroup['newpoints_shop_can_purchase'])) {
                ++$column_span;

                $category_extra_columns[] = eval(templates_get('category_thead_purchase'));
            }

            if ($is_moderator) {
                ++$column_span;

                $category_extra_columns[] = eval(templates_get('category_thead_options'));
            }

            $category_extra_columns = implode('', $category_extra_columns);

            if ($total_items > 0 && is_callable($category_data['get_items'])) {
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
                        $item_stock = my_number_format($item_data['item_stock']);
                    }

                    $item_name = htmlspecialchars_uni($item_data['item_name']);

                    $item_description = post_parser_parse_message(
                        $item_data['item_description'],
                        ['allow_imgcode' => false]
                    );

                    $item_price = (float)$item_data['item_price'] * ($items_rate / 100);

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

                    $purchase_item_url = url_handler_build([
                        'action' => $action_name,
                        'view' => 'purchase',
                        'item_id' => $item_id
                    ]);

                    $item_purchase_row = '';

                    $item_extra_columns = [];

                    $hook_arguments['item_extra_columns'] = &$item_extra_columns;

                    if (!empty($mybb->usergroup['newpoints_shop_can_purchase'])) {
                        $purchase_item_url = url_handler_build([
                            'action' => $action_name,
                            'view' => 'purchase'
                        ]);

                        $item_extra_columns[] = eval(templates_get('item_purchase'));
                    }

                    if ($is_moderator) {
                        $item_edit_url = url_handler_build([
                            'action' => $action_name,
                            'view' => 'edit_item',
                            'item_id' => $item_id
                        ]);

                        $category_page = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);

                        $item_extra_columns[] = eval(templates_get('item_row_options'));
                    }

                    $item_extra_columns = implode('', $item_extra_columns);

                    $items_rows .= eval(templates_get('item'));
                }
            }

            $pagination = '';

            if ($total_items > $per_page) {
                $pagination_input_array["page{$category_id}"] = '{page}';

                $pagination = multipage(
                    $total_items,
                    $per_page,
                    $mybb->get_input("page{$category_id}", MyBB::INPUT_INT),
                    url_handler_build($pagination_input_array, false, false)
                );

                if ($pagination) {
                    $pagination = eval(templates_get('category_pagination'));
                }

                $pagination_input_array["page{$category_id}"] = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);
            } else {
                unset($pagination_input_array["page{$category_id}"]);
            }

            if (!$items_rows) {
                $items_rows = eval(templates_get('category_empty'));
            }

            $option_links = '';

            if ($is_moderator && !empty($category_data['display_options'])) {
                $edit_category_url = url_handler_build([
                    'action' => $action_name,
                    'view' => 'edit_category',
                    'category_id' => $category_id
                ]);

                $delete_category_url = url_handler_build([
                    'action' => $action_name,
                    'view' => 'delete_category',
                    'category_id' => $category_id
                ]);

                $add_item_url = url_handler_build([
                    'action' => $action_name,
                    'view' => 'add_item',
                    'category_id' => $category_id
                ]);

                $option_links .= eval(templates_get('category_links'));
            }

            $newpoints_content .= eval(templates_get('category'));
        }

        if (!$newpoints_content) {
            $newpoints_content = eval(templates_get('no_cats'));
        }

        $my_items_url = url_handler_build(['action' => $action_name, 'view' => 'my_items']);

        $newpoints_buttons .= eval(templates_get('page_button_my_items'));

        if ($is_moderator) {
            $delete_category_url = url_handler_build(
                ['action' => $action_name, 'view' => 'add_category']
            );

            $newpoints_buttons .= eval(templates_get('page_button_add_category'));
        }

        $newpoints_pagination = '';

        $page = eval(\Newpoints\Core\templates_get('page'));
    }

    $hook_arguments['page'] = &$page;

    $hook_arguments = run_hooks('shop_end', $hook_arguments);

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

            $upload_path = (string)get_setting('shop_upload_path');

            $item_icon = $mybb->get_asset_url(
                !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
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

        $upload_path = (string)get_setting('shop_upload_path');

        $item_icon = $mybb->get_asset_url(
            !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
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
    global $mybb;

    if (!empty($mybb->usergroup['newpoints_shop_can_view'])) {
        language_load('shop');

        $menu[get_setting('shop_menu_order')] = [
            'action' => get_setting('shop_action_name'),
            'lang_string' => 'newpoints_shop_menu_title',
        ];
    }

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

        $user_item_data = user_items_get(
            ["user_id='{$user_id}'", "user_item_id='{$user_item_id}'"],
            ['user_item_id', 'item_id', 'item_price'],
            ['limit' => 1]
        );

        $item_id = (int)$user_item_data['item_id'];

        $item_price = $log_type = 0;

        $item_price = (float)$user_item_data['item_price'];

        user_item_delete($user_item_id);

        if ($user_points_refund && !empty($item_price)) {
            $log_type = LOGGING_TYPE_INCOME;

            points_add_simple($user_id, $item_price);
        }

        $log_id = log_add(
            'shop_quick_item_delete',
            '',
            $hook_arguments['user_data']['username'] ?? '',
            $user_id,
            $item_price,
            $user_item_id,
            $item_id,
            $current_user_id,
            $log_type
        );

        alert_send($user_id, (int)$log_id, 'shop', 'item_deleted');

        $item_data = item_get(["iid='{$item_id}'"], ['stock']);

        if ($item_stock_increase && !empty($item_data)) {
            item_update(['stock' => $item_data['stock'] + 1], $item_id);
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

            $upload_path = (string)get_setting('shop_upload_path');

            $item_icon = $mybb->get_asset_url(
                !empty($item_data['icon']) ? "{$upload_path}/{$item_data['icon']}" : 'images/newpoints/default.png'
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

function newpoints_my_alerts_language_load(array &$hook_arguments): array
{
    language_load('shop');

    return $hook_arguments;
}

function newpoints_my_alerts_init(array &$hook_arguments): array
{
    $hook_arguments['newpoints_my_alerts_formatters'][] = [
        'plugin_code' => 'shop',
        'alert_types' => ['item_received', 'item_deleted'],
        'formatters_directory' => ROOT . '/alert_formatters/',
        'namespace' => 'Newpoints\Shop\MyAlerts\Formatters\\'
    ];

    return $hook_arguments;
}

function fetch_wol_activity_end(array &$hook_parameters): array
{
    if (my_strpos($hook_parameters['location'], main_file_name()) === false ||
        my_strpos($hook_parameters['location'], 'action=' . get_setting('shop_action_name')) === false) {
        return $hook_parameters;
    }

    $hook_parameters['activity'] = 'newpoints_shop';

    return $hook_parameters;
}

function build_friendly_wol_location_end(array $hook_parameters): array
{
    global $mybb, $lang;

    language_load('shop');

    switch ($hook_parameters['user_activity']['activity']) {
        case 'newpoints_shop':
            $hook_parameters['location_name'] = $lang->sprintf(
                $lang->newpoints_shop_wol_location,
                $mybb->settings['bburl'],
                url_handler_build(['action' => get_setting('shop_action_name')])
            );
            break;
    }

    return $hook_parameters;
}