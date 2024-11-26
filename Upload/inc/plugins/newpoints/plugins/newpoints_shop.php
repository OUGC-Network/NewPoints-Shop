<?php
/***************************************************************************
 *
 *    NewPoints Shop plugin (/inc/plugins/newpoints/plugins/newpoints_shop.php)
 *    Author: Diogo Parrinha
 *    Copyright: © 2014 Diogo Parrinha
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

use function Newpoints\Shop\Admin\plugin_activation;
use function Newpoints\Shop\Admin\plugin_information;
use function Newpoints\Shop\Admin\plugin_is_installed;
use function Newpoints\Shop\Admin\plugin_installation;
use function Newpoints\Shop\Admin\plugin_uninstallation;
use function Newpoints\Core\add_hooks;

use const Newpoints\Shop\ROOT;
use const Newpoints\ROOT_PLUGINS;

defined('IN_MYBB') || die('Direct initialization of this file is not allowed.');

define('Newpoints\Shop\ROOT', ROOT_PLUGINS . '/ougc/Shop');

require_once ROOT . '/core.php';

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';

    require_once ROOT . '/hooks/admin.php';

    add_hooks('Newpoints\Shop\Hooks\Admin');
} else {
    require_once ROOT . '/hooks/forum.php';

    add_hooks('Newpoints\Shop\Hooks\Forum');
}

require_once ROOT . '/hooks/shared.php';

add_hooks('Newpoints\Shop\Hooks\Share');

function newpoints_shop_info(): array
{
    return plugin_information();
}

function newpoints_shop_install(): bool
{
    return plugin_installation();
}

function newpoints_shop_is_installed(): bool
{
    return plugin_is_installed();
}

function newpoints_shop_uninstall(): bool
{
    return plugin_uninstallation();
}

function newpoints_shop_activate(): bool
{
    return plugin_activation();
}