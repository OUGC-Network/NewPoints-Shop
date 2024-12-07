<?php

/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/ougc/Shop/admin.php)
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

namespace Newpoints\Shop\Admin;

use function Newpoints\Admin\db_verify_columns;
use function Newpoints\Admin\db_verify_columns_exists;
use function Newpoints\Admin\db_verify_tables;
use function Newpoints\Admin\db_verify_tables_exists;
use function Newpoints\Admin\plugin_library_load;
use function Newpoints\Core\language_load;
use function Newpoints\Core\log_remove;
use function Newpoints\Core\plugins_version_delete;
use function Newpoints\Core\plugins_version_get;
use function Newpoints\Core\plugins_version_update;
use function Newpoints\Core\rules_rebuild_cache;
use function Newpoints\Core\settings_rebuild;
use function Newpoints\Core\settings_remove;
use function Newpoints\Core\templates_rebuild;
use function Newpoints\Core\templates_remove;

const TABLES_DATA = [
    'newpoints_shop_categories' => [
        'cid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'description' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'visible' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 1
        ],
        'icon' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'usergroups' => [
            'type' => 'VARCHAR',
            'size' => 5,
            'default' => ''
        ],
        'disporder' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'items' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'expanded' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
    ],
    'newpoints_shop_items' => [
        'iid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'cid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'description' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'price' => [
            'type' => 'DECIMAL',
            'size' => '16,2',
            'unsigned' => true,
            'default' => 0
        ],
        'icon' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'visible' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 1
        ],
        'disporder' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'infinite' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'limit' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'stock' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'sendable' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 1
        ],
        'sellable' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 1
        ],
        'pm' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'pmadmin' => [
            'type' => 'TEXT',
            'null' => true
        ],
    ]
];

const FIELDS_DATA = [
    'users' => [
        'newpoints_items' => [
            'type' => 'TEXT',
            'null' => true
        ],
    ],
    'newpoints_grouprules' => [
        'items_rate' => [
            'type' => 'FLOAT',
            'unsigned' => true,
            'default' => 1
        ]
    ]
];

function plugin_information(): array
{
    return [
        'name' => 'Shop',
        'description' => 'Integrates a shop system with NewPoints.',
        'website' => 'https://ougc.network',
        'author' => 'Diogo Parrinha',
        'authorsite' => 'https://ougc.network',
        'version' => '1.9.4',
        'versioncode' => 1940,
        'compatibility' => '3*'
    ];
}

function plugin_activation(): bool
{
    global $cache;

    language_load('shop');

    $current_version = plugins_version_get('awards_market');

    $new_version = (int)plugin_information()['versioncode'];

    db_verify_tables(TABLES_DATA);

    db_verify_columns(FIELDS_DATA);

    rules_rebuild_cache();

    /*~*~* RUN UPDATES START *~*~*/

    /*~*~* RUN UPDATES END *~*~*/

    plugins_version_update('newpoints_shop', $new_version);

    return true;
}

function plugin_installation(): bool
{
    db_verify_tables(TABLES_DATA);

    db_verify_columns(FIELDS_DATA);

    return true;
}

function plugin_is_installed(): bool
{
    return db_verify_tables_exists(TABLES_DATA) && db_verify_columns_exists(TABLES_DATA) && db_verify_columns_exists(
            FIELDS_DATA
        );
}

function plugin_uninstallation(): bool
{
    global $db;

    log_remove(['shop_purchase', 'shop_send', 'shop_sell']);

    foreach (TABLES_DATA as $table_name => $table_columns) {
        $db->drop_table($table_name);
    }

    foreach (FIELDS_DATA as $table_name => $table_columns) {
        if ($db->table_exists($table_name)) {
            foreach ($table_columns as $field_name => $field_data) {
                if ($db->field_exists($field_name, $table_name)) {
                    $db->drop_column($table_name, $field_name);
                }
            }
        }
    }

    settings_remove(
        [
            'sendable',
            'sellable',
            'lastpurchases',
            'percent',
            'viewothers',
            'itemsprofile',
            'itemspostbit'
        ],
        'newpoints_shop'
    );

    templates_remove([
        'page',
        'category',
        'item',
        'no_items',
        'no_cats',
        'myitems',
        'myitems_item',
        'myitems_no_items',
        'do_action',
        'v',
        'stats_purchase',
        'stats_nopurchase',
        'myitems_item_empty',
        'profile',
        'view_item',
        'postbit',
    ], 'newpoints_shop_');

    plugins_version_delete('newpoints_shop');

    return true;
}

function redirect(string $message, bool $error = false, string $action = '')
{
    if (!$message) {
        return;
    }

    $parameters = '';

    if ($action) {
        $parameters = '&amp;action=' . $action;
    }

    if ($error) {
        flash_message($message, 'error');
    } else {
        flash_message($message, 'success');
    }

    admin_redirect('index.php?module=newpoints-shop' . $parameters);
}