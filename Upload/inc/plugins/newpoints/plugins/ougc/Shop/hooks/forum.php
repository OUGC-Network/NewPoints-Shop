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
use postParser;

use function Newpoints\Core\get_setting;
use function Newpoints\Core\language_load;
use function Newpoints\Core\log_add;
use function Newpoints\Core\main_file_name;
use function Newpoints\Core\points_add;
use function Newpoints\Core\points_format;
use function Newpoints\Core\private_message_send;
use function Newpoints\Core\rules_get_group_rate;
use function Newpoints\Core\rules_group_get;
use function Newpoints\Core\run_hooks;
use function Newpoints\Core\url_handler_build;
use function Newpoints\Shop\Core\category_get;
use function Newpoints\Shop\Core\item_get;
use function Newpoints\Shop\Core\templates_get;

function newpoints_terminate(): bool
{
    global $mybb;

    $action_name = get_setting('shop_action_name');

    if ($mybb->get_input('action') !== $action_name) {
        return false;
    }

    $per_page = get_setting('shop_per_page');

    if ($per_page < 1) {
        $per_page = 10;
    }

    $hook_arguments = [
        'per_page' => &$per_page
    ];

    global $db, $lang, $theme, $header, $templates, $headerinclude, $footer, $options, $inline_errors;

    $formUrl = url_handler_build([
        'action' => $action_name
    ]);

    language_load('shop');

    if ($mybb->request_method === 'post') {
        add_breadcrumb($lang->newpoints_shop, url_handler_build(['action' => 'shop']));

        verify_post_check($mybb->get_input('my_post_key'));

        run_hooks('do_shop_start');

        switch ($mybb->get_input('shop_action')) {
            case 'buy':

                $hook_arguments = run_hooks('shop_buy_start', $hook_arguments);

                // check if the item exists
                if (!($item = item_get($mybb->get_input('iid', MyBB::INPUT_INT)))) {
                    error($lang->newpoints_shop_invalid_item);
                }

                // check if the item is assigned to category
                if (!($cat = category_get((int)$item['cid']))) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                // check if we have permissions to view the parent category
                if (!is_member($cat['usergroups'])) {
                    error_no_permission();
                }

                if ($item['visible'] == 0 || $cat['visible'] == 0) {
                    error_no_permission();
                }

                // check group rules - primary group check
                $rule_group = rules_group_get((int)$mybb->user['usergroup']);
                if (!$rule_group) {
                    $rule_group['items_rate'] = 1.0;
                } // no rule set so default income rate is 1

                // if the group items rate is 0, the price of the item is 0
                if (!(float)$rule_group['items_rate']) {
                    $item['price'] = 0;
                } else {
                    $item['price'] = $item['price'] * (float)$rule_group['items_rate'];
                }

                if ((float)$item['price'] > (float)$mybb->user['newpoints']) {
                    $errors[] = $lang->newpoints_shop_not_enough;
                }

                if ($item['infinite'] != 1 && $item['stock'] <= 0) {
                    $errors[] = $lang->newpoints_shop_out_of_stock;
                }

                if ($item['limit'] != 0) {
                    // Get how many items of this type we have in our inventory
                    $myitems = my_unserialize($mybb->user['newpoints_items']);
                    if (!$myitems) {
                        $myitems = [];
                    }

                    // If more than or equal to $item['limit'] -> FAILED
                    if (count(array_keys($myitems, $item['iid'])) >= $item['limit']) {
                        $errors[] = $lang->newpoints_shop_limit_reached;
                    }
                }

                if (!empty($errors)) {
                    $inline_errors = inline_error($errors, $lang->newpoints_shop_inline_errors);
                    $mybb->input = [];
                    $mybb->input['action'] = 'shop';
                } else {
                    $myitems = my_unserialize($mybb->user['newpoints_items']);
                    if (!$myitems) {
                        $myitems = [];
                    }
                    $myitems[] = $item['iid'];
                    $db->update_query(
                        'users',
                        ['newpoints_items' => my_serialize($myitems)],
                        'uid=\'' . $mybb->user['uid'] . '\''
                    );

                    // update stock
                    if ($item['infinite'] != 1) {
                        $db->update_query(
                            'newpoints_shop_items',
                            ['stock' => $item['stock'] - 1],
                            'iid=\'' . $item['iid'] . '\''
                        );
                    }

                    // get money from user
                    points_add((int)$mybb->user['uid'], -(float)$item['price']);

                    if (!empty($item['pm']) || get_setting('shop_pm_default')) {
                        // send PM if item has private message
                        if ($item['pm'] == '' && get_setting('shop_pm_default')) {
                            $item['pm'] = str_replace(
                                ['{itemname}', '{itemid}'],
                                [$item['name'], $item['iid']],
                                get_setting('shop_pm_default')
                            );
                        }

                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_bought_item_pm_subject,
                                'message' => $item['pm'],
                                'touid' => $mybb->user['uid'],
                                'receivepms' => 1
                            ],
                            -1
                        );
                    }

                    if (!empty($item['pmadmin']) || get_setting('shop_pmadmins')) {
                        // send PM if item has private message
                        if ($item['pmadmin'] == '' && get_setting('shop_pm_default')) {
                            $item['pmadmin'] = str_replace(['{itemname}', '{itemid}'],
                                [$item['name'], $item['iid']],
                                get_setting('shop_pmadmin_default'));
                        }

                        private_message_send(
                            [
                                'subject' => $lang->newpoints_shop_bought_item_pmadmin_subject,
                                'message' => $item['pmadmin'],
                                'touid' => [explode(',', get_setting('shop_pmadmins'))],
                                'receivepms' => 1
                            ],
                            (int)$mybb->user['uid']
                        );
                    }

                    $item = run_hooks('shop_buy_end', $item);

                    // log purchase
                    log_add(
                        'shop_purchase',
                        $lang->sprintf($lang->newpoints_shop_purchased_log, $item['iid'], $item['price'])
                    );

                    redirect(
                        $mybb->settings['bburl'] . '/' . $formUrl,
                        $lang->newpoints_shop_item_bought,
                        $lang->newpoints_shop_item_bought_title
                    );
                }
                break;
            case 'send':
                run_hooks('shop_send_start');

                // check if the item exists
                if (!($item = item_get($mybb->get_input('iid', MyBB::INPUT_INT)))) {
                    error($lang->newpoints_shop_invalid_item);
                }

                // check if the item is assigned to category
                if (!($cat = category_get((int)$item['cid']))) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                // check if we have permissions to view the parent category
                if (!is_member($cat['usergroups'])) {
                    error_no_permission();
                }

                if ($item['visible'] == 0 || $cat['visible'] == 0) {
                    error_no_permission();
                }

                $myitems = my_unserialize($mybb->user['newpoints_items']);
                if (!$myitems) {
                    error($lang->newpoints_shop_inventory_empty);
                }

                // make sure we own the item
                $key = array_search($item['iid'], $myitems);
                if ($key === false) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                $lang->newpoints_shop_action = $lang->newpoints_shop_send_item;
                $item['name'] = htmlspecialchars_uni($item['name']);

                global $shop_action, $data, $colspan;
                $colspan = 2;
                $shop_action = 'do_send';
                $fields = '<input type="hidden" name="iid" value="' . $item['iid'] . '">';
                $data = "<td class=\"trow1\" width=\"50%\"><strong>" . $lang->newpoints_shop_send_item_username . ':</strong><br /><small>' . $lang->newpoints_shop_send_item_message . "</small></td><td class=\"trow1\" width=\"50%\"><input type=\"text\" class=\"textbox\" name=\"username\" value=\"\"></td>";

                run_hooks('shop_send_end');

                $page = eval(templates_get('do_action'));

                output_page($page);
                break;
            case 'do_send':
                run_hooks('shop_do_send_start');

                // check if the item exists
                if (!($item = item_get($mybb->get_input('iid', MyBB::INPUT_INT)))) {
                    error($lang->newpoints_shop_invalid_item);
                }

                // check if the item is assigned to category
                if (!($cat = category_get((int)$item['cid']))) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                // check if we have permissions to view the parent category
                if (!is_member($cat['usergroups'])) {
                    error_no_permission();
                }

                if ($item['visible'] == 0 || $cat['visible'] == 0) {
                    error_no_permission();
                }

                $myitems = my_unserialize($mybb->user['newpoints_items']);
                if (!$myitems) {
                    error($lang->newpoints_shop_inventory_empty);
                }

                // make sure we own the item
                $key = array_search($item['iid'], $myitems);
                if ($key === false) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                $username = trim($mybb->get_input('username'));
                if (!($user = newpoints_getuser_byname($username))) {
                    error($lang->newpoints_shop_invalid_user);
                } else {
                    if ($user['uid'] == $mybb->user['uid']) {
                        error($lang->newpoints_shop_cant_send_item_self);
                    }

                    // send item to the selected user
                    $useritems = my_unserialize($user['newpoints_items']);
                    if (!$useritems) {
                        $useritems = [];
                    }
                    $useritems[] = $item['iid'];
                    $db->update_query(
                        'users',
                        ['newpoints_items' => my_serialize($useritems)],
                        'uid=\'' . $user['uid'] . '\''
                    );

                    // remove item from our inventory
                    unset($myitems[$key]);
                    sort($myitems);
                    $db->update_query(
                        'users',
                        ['newpoints_items' => my_serialize($myitems)],
                        'uid=\'' . $mybb->user['uid'] . '\''
                    );

                    run_hooks('shop_do_send_end');

                    // send pm to user
                    private_message_send(
                        [
                            'subject' => $lang->newpoints_shop_item_received_title,
                            'message' => $lang->sprintf(
                                $lang->newpoints_shop_item_received,
                                htmlspecialchars_uni($mybb->user['username']),
                                htmlspecialchars_uni($item['name'])
                            ),
                            'touid' => $user['uid'],
                            'receivepms' => 1
                        ],
                        -1
                    );

                    // log
                    log_add(
                        'shop_send',
                        $lang->sprintf(
                            $lang->newpoints_shop_sent_log,
                            $item['iid'],
                            $user['uid'],
                            $user['username']
                        )
                    );

                    $my_items_url = url_handler_build([
                        'action' => $action_name,
                        'shop_action' => 'myitems'
                    ]);

                    redirect(
                        $mybb->settings['bburl'] . '/' . $my_items_url,
                        $lang->newpoints_shop_item_sent,
                        $lang->newpoints_shop_item_sent_title
                    );
                }
                break;
            case 'sell':
                run_hooks('shop_sell_start');

                // check if the item exists
                if (!($item = item_get($mybb->get_input('iid', MyBB::INPUT_INT)))) {
                    error($lang->newpoints_shop_invalid_item);
                }

                // check if the item is assigned to category
                if (!($cat = category_get((int)$item['cid']))) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                // check if we have permissions to view the parent category
                if (!is_member($cat['usergroups'])) {
                    error_no_permission();
                }

                if ($item['visible'] == 0 || $cat['visible'] == 0) {
                    error_no_permission();
                }

                $myitems = my_unserialize($mybb->user['newpoints_items']);
                if (!$myitems) {
                    error($lang->newpoints_shop_inventory_empty);
                }

                // make sure we own the item
                $key = array_search($item['iid'], $myitems);
                if ($key === false) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                $lang->newpoints_shop_action = $lang->newpoints_shop_sell_item;
                $item['name'] = htmlspecialchars_uni($item['name']);

                global $shop_action, $data, $colspan;
                $colspan = 1;
                $shop_action = 'do_sell';
                $fields = '<input type="hidden" name="iid" value="' . $item['iid'] . '">';
                $data = "<td class=\"trow1\" width=\"100%\">" . $lang->sprintf(
                        $lang->newpoints_shop_sell_item_confirm,
                        htmlspecialchars_uni($item['name']),
                        points_format(
                            (float)$item['price'] * get_setting('shop_percent')
                        )
                    ) . '</td>';

                run_hooks('shop_sell_end');

                $page = eval(templates_get('do_action'));

                output_page($page);
                break;
            case 'do_sell':
                run_hooks('shop_do_sell_start');

                // check if the item exists
                if (!($item = item_get($mybb->get_input('iid', MyBB::INPUT_INT)))) {
                    error($lang->newpoints_shop_invalid_item);
                }

                // check if the item is assigned to category
                if (!($cat = category_get((int)$item['cid']))) {
                    error($lang->newpoints_shop_invalid_cat);
                }

                // check if we have permissions to view the parent category
                if (!is_member($cat['usergroups'])) {
                    error_no_permission();
                }

                if ($item['visible'] == 0 || $cat['visible'] == 0) {
                    error_no_permission();
                }

                $myitems = my_unserialize($mybb->user['newpoints_items']);
                if (!$myitems) {
                    error($lang->newpoints_shop_inventory_empty);
                }

                // make sure we own the item
                $key = array_search($item['iid'], $myitems);
                if ($key === false) {
                    error($lang->newpoints_shop_selected_item_not_owned);
                }

                // remove item from our inventory
                unset($myitems[$key]);
                sort($myitems);
                $db->update_query(
                    'users',
                    ['newpoints_items' => my_serialize($myitems)],
                    'uid=\'' . $mybb->user['uid'] . '\''
                );

                // update stock
                if ($item['infinite'] != 1) {
                    $db->update_query(
                        'newpoints_shop_items',
                        ['stock' => $item['stock'] + 1],
                        'iid=\'' . $item['iid'] . '\''
                    );
                }

                points_add(
                    (int)$mybb->user['uid'],
                    (float)$item['price'] * get_setting('shop_percent')
                );

                run_hooks('shop_do_sell_end');

                // log
                log_add(
                    'shop_sell',
                    $lang->sprintf(
                        $lang->newpoints_shop_sell_log,
                        $item['iid'],
                        (float)$item['price'] * get_setting('shop_percent')
                    )
                );

                $my_items_url = url_handler_build([
                    'action' => $action_name,
                    'shop_action' => 'myitems'
                ]);

                redirect(
                    $mybb->settings['bburl'] . '/' . $my_items_url,
                    $lang->newpoints_shop_item_sell,
                    $lang->newpoints_shop_item_sell_title
                );
                break;
            default:
                error_no_permission();
        }

        run_hooks('do_shop_end');
    }

    add_breadcrumb($lang->newpoints_shop, url_handler_build(['action' => 'shop']));

    run_hooks('shop_start');

    if ($mybb->get_input('shop_action') == 'view') {
        // check if the item exists
        if (!($item = item_get($mybb->get_input('iid', MyBB::INPUT_INT)))) {
            error($lang->newpoints_shop_invalid_item);
        }

        // check if the item is assigned to category
        if (!($cat = category_get((int)$item['cid']))) {
            error($lang->newpoints_shop_invalid_cat);
        }

        // check if we have permissions to view the parent category
        if (!is_member($cat['usergroups'])) {
            error_no_permission();
        }

        if ($item['visible'] == 0 || $cat['visible'] == 0) {
            error_no_permission();
        }

        $item['name'] = htmlspecialchars_uni($item['name']);
        $item['description'] = htmlspecialchars_uni($item['description']);

        // check group rules - primary group check
        $rule_group = rules_group_get((int)$mybb->user['usergroup']);
        if (!$rule_group) {
            $rule_group['items_rate'] = 1.0;
        } // no rule set so default income rate is 1

        // if the group items rate is 0, the price of the item is 0
        if (!(float)$rule_group['items_rate']) {
            $item['price'] = 0;
        } else {
            $item['price'] = $item['price'] * (float)$rule_group['items_rate'];
        }

        $item['price'] = points_format((float)$item['price']);
        if ($item['price'] > $mybb->user['newpoints']) {
            $item['price'] = '<span style="color: #FF0000;">' . $item['price'] . '</span>';
        }

        // build icon
        if ($item['icon'] != '') {
            $item['icon'] = htmlspecialchars_uni($item['icon']);
            $item['icon'] = '<img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '">';
        } else {
            $item['icon'] = '<img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png">';
        }

        if ($item['infinite'] == 1) {
            $item['stock'] = $lang->newpoints_shop_infinite;
        } else {
            $item['stock'] = (int)$item['stock'];
        }

        if ($item['sendable'] == 1) {
            $item['sendable'] = $lang->newpoints_shop_yes;
        } else {
            $item['sendable'] = $lang->newpoints_shop_no;
        }

        if ($item['sellable'] == 1) {
            $item['sellable'] = $lang->newpoints_shop_yes;
        } else {
            $item['sellable'] = $lang->newpoints_shop_no;
        }

        $buy_item_url = url_handler_build([
            'action' => get_setting('shop_action_name'),
            'shop_action' => 'buy'
        ]);

        $page = eval(templates_get('view_item'));
    } elseif ($mybb->get_input('shop_action') == 'myitems') {
        $uid = $mybb->get_input('uid', MyBB::INPUT_INT);
        $uidpart = '';
        if ($uid > 0) {
            $user = get_user($uid);
            // we're viewing someone else's inventory
            if (!empty($user)) {
                // we can't view others inventories if we don't have enough previleges
                if (!get_setting(
                        'shop_viewothers'
                    ) && $mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $uid) {
                    error_no_permission();
                }

                $myitems = my_unserialize($user['newpoints_items']);
                $lang->newpoints_shop_myitems = $lang->sprintf(
                    $lang->newpoints_shop_items_username,
                    htmlspecialchars_uni($user['username'])
                );
                $uidpart = '&amp;uid=' . $uid; // we need this for pagination
            } else {
                $myitems = my_unserialize($mybb->user['newpoints_items']);
            }
        } else {
            $myitems = my_unserialize($mybb->user['newpoints_items']);
        }
        $items = '';
        $newrow = true;
        $invert_bgcolor = alt_trow();

        if (!get_setting('shop_sendable')) {
            $sendable = false;
        } else {
            $sendable = true;
        }

        if (!get_setting('shop_sellable')) {
            $sellable = false;
        } else {
            $sellable = true;
        }

        require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser = new postParser();

        $parser_options = [
            'allow_mycode' => 1,
            'allow_smilies' => 1,
            'allow_imgcode' => 0,
            'allow_html' => 0,
            'filter_badwords' => 1
        ];

        $multipage = '';

        if (!empty($myitems)) {
            if ($mybb->get_input('page', MyBB::INPUT_INT) > 1) {
                $start = ($mybb->get_input('page', MyBB::INPUT_INT) * $per_page) - $per_page;
            } else {
                $mybb->input['page'] = 1;
                $start = 0;
            }

            // total items
            $total_rows = $db->fetch_field(
                $db->simple_select(
                    'newpoints_shop_items',
                    'COUNT(iid) as items',
                    'visible=1 AND iid IN (' . implode(',', array_unique($myitems)) . ')'
                ),
                'items'
            );

            if ($total_rows > $per_page) {
                if ($uidpart) {
                    $my_items_url = url_handler_build([
                        'action' => $action_name,
                        'shop_action' => 'myitems',
                        'uid' => $uid
                    ]);
                } else {
                    $my_items_url = url_handler_build([
                        'action' => $action_name,
                        'shop_action' => 'myitems'
                    ]);
                }

                $multipage = (string)multipage(
                    $total_rows,
                    $per_page,
                    $mybb->get_input('page', MyBB::INPUT_INT),
                    $mybb->settings['bburl'] . '/' . $my_items_url
                );
            }

            $query = $db->simple_select(
                'newpoints_shop_items',
                '*',
                'visible=1 AND iid IN (' . implode(',', array_unique($myitems)) . ')',
                ['limit' => "{$start}, {$per_page}"]
            );
            while ($item = $db->fetch_array($query)) {
                if ($sellable === true && $item['sellable']) {
                    if ($sendable === true && $item['sendable']) {
                        $tdstart = '<td width="50%">';
                    } else {
                        $tdstart = '<td width="100%">';
                    }

                    $sell = $tdstart . '<form action="' . $formUrl . '" method="POST"><input type="hidden" name="action" value="' . $action_name . '"><input type="hidden" name="shop_action" value="sell"><input type="hidden" name="iid" value="' . $item['iid'] . '"><input type="hidden" name="my_post_key" value="' . $mybb->post_code . '"><input type="submit" name="submit" value="' . $lang->newpoints_shop_sell . '"></form></td>';
                } else {
                    $sell = '';
                }

                if ($sendable === true && $item['sendable']) {
                    if ($sell == '') {
                        $tdstart = '<td width="100%">';
                    } else {
                        $tdstart = '<td width="50%">';
                    }

                    $send = $tdstart . '<form action="' . $formUrl . '" method="POST"><input type="hidden" name="action" value="' . $action_name . '"><input type="hidden" name="shop_action" value="send"><input type="hidden" name="iid" value="' . $item['iid'] . '"><input type="hidden" name="my_post_key" value="' . $mybb->post_code . '"><input type="submit" name="submit" value="' . $lang->newpoints_shop_send . '"></form></td>';
                } else {
                    $send = '';
                }

                if (!$send && !$sell) {
                    $send = $lang->newpoints_shop_no_options;
                }

                $item['description'] = $parser->parse_message($item['description'], $parser_options);

                // check group rules - primary group check
                $rule_group = rules_group_get((int)$mybb->user['usergroup']);
                if (!$rule_group) {
                    $rule_group['items_rate'] = 1.0;
                } // no rule set so default income rate is 1

                // if the group items rate is 0, the price of the item is 0
                if (!(float)$rule_group['items_rate']) {
                    $item['price'] = 0;
                } else {
                    $item['price'] = $item['price'] * (float)$rule_group['items_rate'];
                }

                $item['price'] = points_format((float)$item['price']);
                $item['quantity'] = count(array_keys($myitems, $item['iid']));

                // build icon
                if ($item['icon'] != '') {
                    $item['icon'] = htmlspecialchars_uni($item['icon']);
                    $item['icon'] = '<img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '" style="width: 24px; height: 24px">';
                } else {
                    $item['icon'] = '<img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png">';
                }

                $alternative_background = alt_trow();
                $invert_bgcolor = alt_trow();
                $item = run_hooks('shop_myitems_item', $item);

                $view_item_url = url_handler_build([
                    'action' => $action_name,
                    'shop_action' => 'view',
                    'iid' => $item['iid']
                ]);

                $items .= eval(templates_get('myitems_item'));
            }

            if (!$items) {
                $items = eval(templates_get('myitems_no_items'));
            } else {
                $items .= eval(templates_get('myitems_item_empty'));
            }
        } else {
            $items = eval(templates_get('myitems_no_items'));
        }

        $shop_url = url_handler_build(['action' => $action_name]);

        $page = eval(templates_get('myitems'));
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

        $categories_code = '';

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

                    $item_description = htmlspecialchars_uni($item_data['description']);

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

                    $view_item_url = url_handler_build([
                        'action' => $action_name,
                        'shop_action' => 'view',
                        'iid' => $item_id
                    ]);

                    $buy_item_url = url_handler_build([
                        'action' => $action_name,
                        'shop_action' => 'buy',
                        'iid' => $item_id
                    ]);

                    $items_rows .= eval(templates_get('item'));
                }
            }

            $pagination = '';

            if ($category_data['total_items'] > $per_page) {
                $pagination_input_array["page{$category_id}"] = '{page}';

                $pagination = multipage(
                    $category_data['total_items'],
                    $per_page,
                    $mybb->get_input("page{$category_id}", MyBB::INPUT_INT),
                    url_handler_build($pagination_input_array, false, false)
                );

                $pagination = eval(templates_get('category_pagination'));

                $pagination_input_array["page{$category_id}"] = $mybb->get_input("page{$category_id}", MyBB::INPUT_INT);
            } else {
                unset($pagination_input_array["page{$category_id}"]);
            }

            if (!$items_rows) {
                $items_rows = eval(templates_get('no_items'));
            }

            $my_items_url = url_handler_build(['action' => $action_name, 'shop_action' => 'myitems']);

            $categories_code .= eval(templates_get('category'));
        }

        if (!$categories_code) {
            $categories_code = eval(templates_get('no_cats'));
        }

        $page = eval(templates_get('page'));
    }

    $page = run_hooks('shop_end', $page);

    output_page($page);

    exit;
}

function newpoints_stats_start()
{
    global $mybb, $db, $templates, $cache, $theme, $newpoints_shop_lastpurchases, $last_purchases, $lang;

    // load language
    language_load('shop');
    $last_purchases = '';

    // build stats table
    $query = $db->simple_select(
        'newpoints_log',
        '*',
        'action=\'shop_purchase\'',
        [
            'order_by' => 'date',
            'order_dir' => 'DESC',
            'limit' => (int)get_setting('shop_lastpurchases')
        ]
    );
    while ($purchase = $db->fetch_array($query)) {
        $alternative_background = alt_trow();
        $data = explode('-', $purchase['data']);

        $item = item_get((int)$data[0]);
        $purchase['item'] = htmlspecialchars_uni($item['name']);

        $link = build_profile_link(htmlspecialchars_uni($purchase['username']), (int)$purchase['uid']);
        $purchase['user'] = $link;

        $purchase['date'] = my_date($mybb->settings['dateformat'], (int)$purchase['date'], '', false);

        $last_purchases .= eval(templates_get('stats_purchase'));
    }

    if (!$last_purchases) {
        $last_purchases = eval(templates_get('stats_nopurchase'));
    }

    $newpoints_shop_lastpurchases = eval(templates_get('stats'));
}

function newpoints_shopmember_profile_end_profile()
{
    global $mybb, $lang, $db, $memprofile, $templates, $newpoints_shop_profile;

    if (!get_setting('shop_itemsprofile')) {
        $newpoints_shop_profile = '';
        return;
    }

    global $shop_items;

    $shop_items = '';
    if (empty($memprofile['newpoints_items'])) {
        $shop_items = $lang->newpoints_shop_user_no_items;
    } else {
        $items = unserialize($memprofile['newpoints_items']);
        if (!empty($items)) {
            // do not show multiple icons of the same item if we own more than one
            $query = $db->simple_select(
                'newpoints_shop_items',
                'iid,name,icon',
                'visible=1 AND iid IN (' . implode(',', array_unique($items)) . ')',
                ['limit' => get_setting('shop_itemsprofile')]
            );
            while ($item = $db->fetch_array($query)) {
                $view_item_url = url_handler_build([
                    'action' => get_setting('shop_action_name'),
                    'shop_action' => 'view',
                    'iid' => $item['iid']
                ]);

                if ($item['icon'] != '') {
                    $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/' . $view_item_url . '"><img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '" title="' . htmlspecialchars_uni(
                            $item['name']
                        ) . '" style="width: 24px; height: 24px"></a> ';
                } else {
                    $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/' . $view_item_url . '"><img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png" title="' . htmlspecialchars_uni(
                            $item['name']
                        ) . '"></a> ';
                }
            }
        } else {
            $shop_items = $lang->newpoints_shop_user_no_items;
        }
    }

    $my_items_url = url_handler_build([
        'action' => get_setting('shop_action_name'),
        'shop_action' => 'myitems',
        'uid' => $memprofile['uid']
    ]);

    $newpoints_shop_profile = eval(templates_get('profile'));
}

function member_profile_start()
{
    global $lang;

    // load language
    language_load('shop');
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
    global $mybb, $lang, $db, $templates;

    $post['newpoints_shop_items'] = '';

    if (!get_setting('shop_itemspostbit')) {
        return $post;
    }

    if (empty($post['newpoints_items'])) {
        return $post;
    }

    $items = unserialize($post['newpoints_items']);

    if (empty($items)) {
        return $post;
    }

    // load language
    language_load('shop');

    static $postbit_items_cache; // we need to cache all items' icons and names to use less queries

    if (!isset($postbit_items_cache) || !is_array($postbit_items_cache)) {
        $postbit_items_cache = [];
        $query = $db->simple_select('newpoints_shop_items', 'iid,name,icon', 'visible=1');
        while ($item = $db->fetch_array($query)) {
            $postbit_items_cache[$item['iid']] = ['name' => $item['name'], 'icon' => $item['icon']];
        }
    }

    if (empty($postbit_items_cache)) {
        return $post;
    }

    $shop_items = '';
    $count = 1;

    $items = array_unique($items);

    foreach ($postbit_items_cache as $iid => $item) {
        if (!in_array($iid, $items)) {
            continue;
        }

        $view_item_url = url_handler_build([
            'action' => get_setting('shop_action_name'),
            'shop_action' => 'view',
            'iid' => $iid
        ]);

        if ($item['icon'] != '') {
            $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/' . $view_item_url . '"><img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '" title="' . htmlspecialchars_uni(
                    $item['name']
                ) . '" style="width: 24px; height: 24px"></a> ';
        } else {
            $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/' . $view_item_url . '"><img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png" title="' . htmlspecialchars_uni(
                    $item['name']
                ) . '"></a> ';
        }

        $count++;

        if ($count > (int)get_setting('shop_itemspostbit')) {
            break;
        }
    }

    $my_items_url = url_handler_build([
        'action' => get_setting('shop_action_name'),
        'shop_action' => 'myitems',
        'uid' => $post['uid']
    ]);

    $post['newpoints_shop_items'] = eval(templates_get('postbit'));

    if ($shop_items != '') {
        $post['newpoints_shop_items_count'] = count($items);
    } else {
        $post['newpoints_shop_items_count'] = '0';
    }

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
    $hook_arguments['db_fields'][] = 'newpoints_items';

    return $hook_arguments;
}

function newpoints_quick_edit_post_start(array &$hook_arguments): array
{
    global $mybb, $lang, $db;

    $items_input = $mybb->get_input('items', MyBB::INPUT_ARRAY);

    if (empty($hook_arguments['user_data']['newpoints_items']) || !$items_input) {
        return $hook_arguments;
    }

    language_load('shop');

    $user_items = my_unserialize($hook_arguments['user_data']['newpoints_items']);

    $removed_items_ids = [];

    foreach ($items_input as $item_id) {
        $item_id = (int)$item_id;

        if (!($check_item = item_get($item_id))) {
            error($lang->newpoints_shop_invalid_item);
        } elseif (!item_get($item_id)) {
            error($lang->newpoints_shop_invalid_cat);
        } elseif (!empty($user_items)) {
            $item_id = (int)$check_item['iid'];

            $key = array_search($item_id, $user_items);

            if ($key === false) {
                error($lang->newpoints_shop_invalid_item);
            } else {
                unset($user_items[$key]);

                $removed_items_ids[] = $item_id;
            }
        }
    }

    sort($user_items);

    $user_id = (int)$hook_arguments['user_data']['uid'];

    $db->update_query('users', ['newpoints_items' => my_serialize($user_items)], "uid='{$user_id}'");

    if (get_setting('shop_quick_edit_stock_increase')) {
        foreach ($removed_items_ids as $item_id) {
            $check_item = item_get($item_id);

            $db->update_query(
                'newpoints_shop_items',
                ['stock' => $check_item['stock'] + 1],
                "iid='{$item_id}'"
            );
        }
    }

    $removed_items_ids = implode(',', $removed_items_ids);

    log_add(
        'quickedit',
        "uid:{$user_id};items_ids:{$removed_items_ids}",
        $mybb->user['username'] ?? '',
        (int)$mybb->user['uid']
    );

    return $hook_arguments;
}

function newpoints_quick_edit_end(array &$hook_arguments): array
{
    if (empty($hook_arguments['user_data']['newpoints_items'])) {
        return $hook_arguments;
    }

    $user_items = my_unserialize($hook_arguments['user_data']['newpoints_items']);

    $shop_items = '';

    if (!empty($user_items)) {
        global $mybb, $db, $lang;

        language_load('shop');

        $query = $db->simple_select(
            'newpoints_shop_items',
            'iid, name, icon',
            'visible=1 AND iid IN (' . implode(',', array_unique($user_items)) . ')',
            ['order_by' => 'disporder']
        );

        $newpointsFile = main_file_name();

        while ($item_data = $db->fetch_array($query)) {
            $item_id = (int)$item_data['iid'];

            $item_name = htmlspecialchars_uni($item_data['name']);

            $item_icon = htmlspecialchars_uni($item_data['icon']);

            $item_data['icon'] = htmlspecialchars_uni(
                (!empty($item_data['icon']) ? $item_data['icon'] : 'images/newpoints/default.png')
            );

            $tabindex = $item_id + 10;

            $shop_items .= eval(templates_get('quick_edit_row_item'));
        }

        $hook_arguments['additional_rows'] .= eval(templates_get('quick_edit_row'));

        $hook_arguments['alternative_background'] = alt_trow();
    }

    return $hook_arguments;
}
