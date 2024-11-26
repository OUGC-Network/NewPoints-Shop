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
use function Newpoints\Core\points_add;
use function Newpoints\Core\points_format;
use function Newpoints\Core\rules_group_get;
use function Newpoints\Core\run_hooks;
use function Newpoints\Shop\Core\category_get;
use function Newpoints\Shop\Core\item_get;
use function Newpoints\Shop\Core\templates_get;

function newpoints_start()
{
    global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;

    if (!$mybb->user['uid']) {
        return;
    }

    language_load('shop');

    add_breadcrumb($lang->newpoints_shop, \Newpoints\Core\url_handler_build(['action' => 'shop']));

    if ($mybb->get_input('action') === 'do_shop') {
        verify_post_check($mybb->get_input('postcode'));

        run_hooks('do_shop_start');

        switch ($mybb->get_input('shop_action')) {
            case 'buy':

                run_hooks('shop_buy_start');

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
                $grouprules = rules_group_get((int)$mybb->user['usergroup']);
                if (!$grouprules) {
                    $grouprules['items_rate'] = 1.0;
                } // no rule set so default income rate is 1

                // if the group items rate is 0, the price of the item is 0
                if (!(float)$grouprules['items_rate']) {
                    $item['price'] = 0;
                } else {
                    $item['price'] = $item['price'] * (float)$grouprules['items_rate'];
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

                        \Newpoints\Core\private_message_send(
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

                        \Newpoints\Core\private_message_send(
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
                        $mybb->settings['bburl'] . '/newpoints.php?action=shop',
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
                    \Newpoints\Core\private_message_send(
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
                        $lang->sprintf($lang->newpoints_shop_sent_log, $item['iid'], $user['uid'], $user['username'])
                    );

                    redirect(
                        $mybb->settings['bburl'] . '/newpoints.php?action=shop&amp;shop_action=myitems',
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

                redirect(
                    $mybb->settings['bburl'] . '/newpoints.php?action=shop&amp;shop_action=myitems',
                    $lang->newpoints_shop_item_sell,
                    $lang->newpoints_shop_item_sell_title
                );
                break;

            default:
                error_no_permission();
        }

        run_hooks('do_shop_end');
    }

    // shop page
    if ($mybb->get_input('action') === 'shop') {
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
            $grouprules = rules_group_get((int)$mybb->user['usergroup']);
            if (!$grouprules) {
                $grouprules['items_rate'] = 1.0;
            } // no rule set so default income rate is 1

            // if the group items rate is 0, the price of the item is 0
            if (!(float)$grouprules['items_rate']) {
                $item['price'] = 0;
            } else {
                $item['price'] = $item['price'] * (float)$grouprules['items_rate'];
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
                // pagination
                $per_page = 10;
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
                    $multipage = (string)multipage(
                        $total_rows,
                        $per_page,
                        $mybb->get_input('page', MyBB::INPUT_INT),
                        $mybb->settings['bburl'] . '/newpoints.php?action=shop&shop_action=myitems' . $uidpart
                    );
                }

                $query = $db->simple_select(
                    'newpoints_shop_items',
                    '*',
                    'visible=1 AND iid IN (' . implode(',', array_unique($myitems)) . ')',
                    ['limit' => "{$start}, {$per_page}"]
                );
                while ($item = $db->fetch_array($query)) {
                    if ($newrow === true) {
                        $trstart = '<tr>';
                        $trend = '';
                        $newrow = false;
                    } elseif ($newrow === false) {
                        $trstart = '';
                        $trend = '</tr>';
                        $newrow = true;
                    }

                    if ($sellable === true && $item['sellable']) {
                        if ($sendable === true && $item['sendable']) {
                            $tdstart = '<td width="50%">';
                        } else {
                            $tdstart = '<td width="100%">';
                        }

                        $sell = $tdstart . '<form action="newpoints.php" method="POST"><input type="hidden" name="action" value="do_shop"><input type="hidden" name="shop_action" value="sell"><input type="hidden" name="iid" value="' . $item['iid'] . '"><input type="hidden" name="postcode" value="' . $mybb->post_code . '"><input type="submit" name="submit" value="' . $lang->newpoints_shop_sell . '"></form></td>';
                    } else {
                        $sell = '';
                    }

                    if ($sendable === true && $item['sendable']) {
                        if ($sell == '') {
                            $tdstart = '<td width="100%">';
                        } else {
                            $tdstart = '<td width="50%">';
                        }

                        $send = $tdstart . '<form action="newpoints.php" method="POST"><input type="hidden" name="action" value="do_shop"><input type="hidden" name="shop_action" value="send"><input type="hidden" name="iid" value="' . $item['iid'] . '"><input type="hidden" name="postcode" value="' . $mybb->post_code . '"><input type="submit" name="submit" value="' . $lang->newpoints_shop_send . '"></form></td>';
                    } else {
                        $send = '';
                    }

                    if (!$send && !$sell) {
                        $send = $lang->newpoints_shop_no_options;
                    }

                    $item['description'] = $parser->parse_message($item['description'], $parser_options);

                    // check group rules - primary group check
                    $grouprules = rules_group_get((int)$mybb->user['usergroup']);
                    if (!$grouprules) {
                        $grouprules['items_rate'] = 1.0;
                    } // no rule set so default income rate is 1

                    // if the group items rate is 0, the price of the item is 0
                    if (!(float)$grouprules['items_rate']) {
                        $item['price'] = 0;
                    } else {
                        $item['price'] = $item['price'] * (float)$grouprules['items_rate'];
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

                    $bgcolor = alt_trow();
                    $invert_bgcolor = alt_trow();
                    $item = run_hooks('shop_myitems_item', $item);

                    $items .= $trstart . eval(templates_get('myitems_item')) . $trend;
                }

                if (!$items) {
                    $items = eval(templates_get('myitems_no_items'));
                } elseif ($newrow === false) // we haven't closed the row, that means there's a missing td
                {
                    $items .= eval(templates_get('myitems_item_empty')) . '</tr>';

                    $newrow = true;
                }
            } else {
                $items = eval(templates_get('myitems_no_items'));
            }

            $page = eval(templates_get('myitems'));
        } else {
            // check group rules - primary group check
            $grouprules = rules_group_get((int)$mybb->user['usergroup']);
            if (!$grouprules) {
                $grouprules['items_rate'] = 1.0;
            } // no rule set so default income rate is 1

            // if the group items rate is 0, the price of the item is 0
            $itemsrate = (float)$grouprules['items_rate'];

            global $cats, $items;

            // get categories
            $query = $db->simple_select(
                'newpoints_shop_categories',
                '*',
                '',
                ['order_by' => 'disporder', 'order_dir' => 'ASC']
            );
            while ($cat = $db->fetch_array($query)) {
                $categories[$cat['cid']] = $cat;
            }

            // get items and store them in their categories
            $query = $db->simple_select(
                'newpoints_shop_items',
                '*',
                'visible=1 AND cid>0',
                ['order_by' => 'disporder', 'order_dir' => 'ASC']
            );
            while ($item = $db->fetch_array($query)) {
                $items_array[$item['cid']][$item['iid']] = $item;
            }

            $cats = '';
            $bgcolor = '';
            $bgcolor = alt_trow();

            // build items and categories
            if (!empty($categories)) {
                foreach ($categories as $cid => $category) {
                    $items = '';

                    if ($category['items'] > 0 && !empty($items_array[$category['cid']])) {
                        foreach ($items_array as $cid => $member) {
                            if ($cid != $category['cid']) {
                                continue;
                            }

                            $bgcolor = alt_trow();
                            foreach ($member as $iid => $item) {
                                // skip hidden items
                                if ($item['visible'] == 0) {
                                    continue;
                                }

                                if ($item['infinite'] == 1) {
                                    $item['stock'] = $lang->newpoints_shop_infinite;
                                }

                                if ($item['price'] > $mybb->user['newpoints']) {
                                    $enough_money = false;
                                } else {
                                    $enough_money = true;
                                }

                                $item['name'] = htmlspecialchars_uni($item['name']);
                                $item['description'] = htmlspecialchars_uni($item['description']);
                                $item['price'] = points_format((float)$item['price'] * $itemsrate);

                                // build icon
                                if ($item['icon'] != '') {
                                    $item['icon'] = htmlspecialchars_uni($item['icon']);
                                    $item['icon'] = '<img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '" style="width: 24px; height: 24px">';
                                } else {
                                    $item['icon'] = '<img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png">';
                                }

                                if (!$enough_money) {
                                    $item['price'] = '<span style="color: #FF0000;">' . $item['price'] . '</span>';
                                }

                                $item = run_hooks('shop_item', $item);

                                $items .= eval(templates_get('item'));
                            }
                        }
                    } else {
                        $items = eval(templates_get('no_items'));
                    }

                    // if it's not visible, don't show it
                    if ($category['visible'] == 0) {
                        continue;
                    }

                    // check if we have permissions to view the category
                    if (!is_member($category['usergroups'])) {
                        continue;
                    }

                    // Expanded by default feature
                    global $extdisplay, $expcolimage, $expdisplay, $expaltext, $icon;

                    $expdisplay = '';
                    if ((int)$category['expanded'] == 0) {
                        $expcolimage = 'collapse_collapsed.png';
                        $expdisplay = 'display: none;';
                        $expaltext = '[+]';
                    } else {
                        $expcolimage = 'collapse.png';
                        $expaltext = '[-]';
                    }

                    // build icon
                    if ($category['icon'] != '') {
                        $category['icon'] = htmlspecialchars_uni($category['icon']);
                        $category['icon'] = '<img src="' . $mybb->settings['bburl'] . '/' . $category['icon'] . '" style="vertical-align:middle; width: 24px; height: 24px">';
                    }

                    // sanitize html
                    $category['description'] = htmlspecialchars_uni($category['description']);
                    $category['name'] = htmlspecialchars_uni($category['name']);

                    $category = run_hooks('shop_category', $category);

                    $cats .= eval(templates_get('category'));
                }
            } else {
                $cats = eval(templates_get('no_cats'));
            }

            $page = eval(templates_get('page'));
        }

        $page = run_hooks('shop_end', $page);

        // output page
        output_page($page);
    }
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
        $bgcolor = alt_trow();
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
                if ($item['icon'] != '') {
                    $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/newpoints.php?action=shop&amp;shop_action=view&amp;iid=' . $item['iid'] . '"><img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '" title="' . htmlspecialchars_uni(
                            $item['name']
                        ) . '" style="width: 24px; height: 24px"></a> ';
                } else {
                    $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/newpoints.php?action=shop&amp;shop_action=view&amp;iid=' . $item['iid'] . '"><img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png" title="' . htmlspecialchars_uni(
                            $item['name']
                        ) . '"></a> ';
                }
            }
        } else {
            $shop_items = $lang->newpoints_shop_user_no_items;
        }
    }

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

        if ($item['icon'] != '') {
            $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/newpoints.php?action=shop&amp;shop_action=view&amp;iid=' . $iid . '"><img src="' . $mybb->settings['bburl'] . '/' . $item['icon'] . '" title="' . htmlspecialchars_uni(
                    $item['name']
                ) . '" style="width: 24px; height: 24px"></a> ';
        } else {
            $shop_items .= '<a href="' . $mybb->settings['bburl'] . '/newpoints.php?action=shop&amp;shop_action=view&amp;iid=' . $iid . '"><img src="' . $mybb->settings['bburl'] . '/images/newpoints/default.png" title="' . htmlspecialchars_uni(
                    $item['name']
                ) . '"></a> ';
        }

        $count++;

        if ($count > (int)get_setting('shop_itemspostbit')) {
            break;
        }
    }

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

    $menu[] = [
        'action' => 'shop',
        'lang_string' => 'newpoints_shop'
    ];

    return $menu;
}