<p align="center">
    <a href="" rel="noopener">
        <img width="700" height="400" src="https://github.com/user-attachments/assets/e569462f-29b1-4222-b5a3-7fef70a02642" alt="Project logo">
    </a>
</p>

<h3 align="center">NewPoints Shop</h3>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![GitHub Issues](https://img.shields.io/github/issues/OUGC-Network/NewPoints-Shop.svg)](./issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/OUGC-Network/NewPoints-Shop.svg)](./pulls)
[![License](https://img.shields.io/badge/license-GPL-blue)](/LICENSE)

</div>

---

<p align="center"> Integrates a shop system with NewPoints.
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
        - [Update from 1.9](#update_19)
    - [Template Modifications](#template_modifications)
    - [Uploads Directory Permissions](#directory_permissions)
- [Settings](#settings)
- [Templates](#templates)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

Integrates a shop system with NewPoints.

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7
- [MyBB-PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) >= 13

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── images
   │ ├── newpoints
   │ │ ├── default.png
   │ ├── languages
   │ │ ├── english
   │ │ │ ├── newpoints.lang.php
   │ │ │ ├── admin
   │ │ │ │ ├── newpoints.lang.php
   │ │ │ │ ├── newpoints_module_meta.lang.php
   ├── inc
   │ ├── plugins
   │ │ ├── newpoints
   │ │ │ ├── languages
   │ │ │ │ ├── english
   │ │ │ │ │ ├── admin
   │ │ │ │ │ │ ├── newpoints_shop.lang.php
   │ │ │ │ │ ├── newpoints_shop.lang.php
   │ │ │ ├── plugins
   │ │ │ │ │ ├── Shop
   │ │ │ │ │ │ ├── hooks
   │ │ │ │ │ │ │ ├── admin.php
   │ │ │ │ │ │ │ ├── forum.php
   │ │ │ │ │ │ │ ├── shared.php
   │ │ │ │ │ │ ├── settings
   │ │ │ │ │ │ │ ├── quick_edit.json
   │ │ │ │ │ │ │ ├── shop.json
   │ │ │ │ │ │ ├── templates
   │ │ │ │ │ │ │ ├── category.html
   │ │ │ │ │ │ │ ├── category_add_edit_form.html
   │ │ │ │ │ │ │ ├── category_add_edit_form_category.html
   │ │ │ │ │ │ │ ├── category_add_edit_form_category_option.html
   │ │ │ │ │ │ │ ├── category_add_edit_form_upload.html
   │ │ │ │ │ │ │ ├── category_empty.html
   │ │ │ │ │ │ │ ├── category_icon.html
   │ │ │ │ │ │ │ ├── category_links.html
   │ │ │ │ │ │ │ ├── category_pagination.html
   │ │ │ │ │ │ │ ├── category_thead_options.html
   │ │ │ │ │ │ │ ├── category_thead_purchase.html
   │ │ │ │ │ │ │ ├── confirm_purchase.html
   │ │ │ │ │ │ │ ├── confirm_purchase_icon.html
   │ │ │ │ │ │ │ ├── confirm_sell.html
   │ │ │ │ │ │ │ ├── confirm_send.html
   │ │ │ │ │ │ │ ├── css.html
   │ │ │ │ │ │ │ ├── item.html
   │ │ │ │ │ │ │ ├── item_add_edit_form.html
   │ │ │ │ │ │ │ ├── item_add_edit_form_category.html
   │ │ │ │ │ │ │ ├── item_add_edit_form_category_option.html
   │ │ │ │ │ │ │ ├── item_add_edit_form_upload.html
   │ │ │ │ │ │ │ ├── item_icon.html
   │ │ │ │ │ │ │ ├── item_purchase.html
   │ │ │ │ │ │ │ ├── item_row_options.html
   │ │ │ │ │ │ │ ├── my_items_content.html
   │ │ │ │ │ │ │ ├── my_items_empty.html
   │ │ │ │ │ │ │ ├── my_items_row.html
   │ │ │ │ │ │ │ ├── my_items_row_icon.html
   │ │ │ │ │ │ │ ├── my_items_row_options.html
   │ │ │ │ │ │ │ ├── my_items_row_options_sell.html
   │ │ │ │ │ │ │ ├── my_items_row_options_send.html
   │ │ │ │ │ │ │ ├── no_cats.html
   │ │ │ │ │ │ │ ├── page_button_add_category.html
   │ │ │ │ │ │ │ ├── page_button_my_items.html
   │ │ │ │ │ │ │ ├── post.html
   │ │ │ │ │ │ │ ├── post_icon.html
   │ │ │ │ │ │ │ ├── post_view_all.html
   │ │ │ │ │ │ │ ├── profile.html
   │ │ │ │ │ │ │ ├── profile_icon.html
   │ │ │ │ │ │ │ ├── profile_view_all.html
   │ │ │ │ │ │ │ ├── quick_edit_row.html
   │ │ │ │ │ │ │ ├── quick_edit_row_item.html
   │ │ │ │ │ │ │ ├── quick_edit_row_item_icon.html
   │ │ │ │ │ │ │ ├── stats.html
   │ │ │ │ │ │ │ ├── stats_empty.html
   │ │ │ │ │ │ │ ├── stats_row.html
   │ │ │ │ │ │ │ ├── view_item.html
   │ │ │ │ │ │ │ ├── view_item_icon.html
   │ │ │ │ │ │ │ ├── view_item_purchase.html
   │ │ │ │ ├── newpoints_shop.php
   ├── uploads
   │ ├── shop
   │ │ ├── index.html
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from the [MyBB Extend](https://community.mybb.com/mods.php) site or
   from the [repository releases](https://github.com/OUGC-Network/NewPoints-Shop/releases/latest).
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.
4. Browse to _NewPoints_ to manage NewPoints modules.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.
4. Browse to _NewPoints_ to manage NewPoints modules.

#### Updating from 1.9 (NewPoints 2) <a name = "update_19"></a>

If you are updating the Shop from version 1.9 (NewPoints 2), you will need to follow the next steps.

1. Follow all steps from the [Updating](#update) section.
2. Browse to _Tools & Maintenance » Recount & Rebuild » Recount & Rebuild_ and run the _Rebuild NewPoints Shop User
   Items_ rebuild tool.
3. This will deplete the old `newpoints_items` column from the users table and convert (insert) user items into the new
   table (store) format.
4. Because the old system did not store the item bought time, all existing items will have the same bought time.

### Template Modifications <a name = "template_modifications"></a>

To display NewPoints data it is required that you edit the following template for each of your themes.

1. Place `{$post['newpoints_shop_items']}` in the `postbit` or `postbit_classic` templates to display the user items in
   posts.
2. Place `{$post['newpoints_shop_items_count']}` in the `postbit` or `postbit_classic` templates to display the user
   total items number in posts.
3. Place `{$newpoints_shop_profile}` in the `member_profile` template to display the user items in

### Uploads Directory Permissions <a name = "directory_permissions"></a>

It is necessary to set the correct permissions for the uploads directory set in the `Uploads Path` setting to `777`.

[Go up to Table of Contents](#table_of_contents)

## 🛠 Settings <a name = "settings"></a>

Below you can find a description of the plugin settings.

### Main Settings

- **Manage Groups** `select`
    - _Select the groups that can manage the shop._
- **Action Name** `text`
    - _Select the action input name to use for this feature._
- **Pagination Per Page Items** `number`
    - _Number of items to display per page in the signature market._
- **Menu Order** `number`
    - _Order in the Newpoints menu item._
- **Uploads Path** `text`
    - _Type the path where the category and item images will be uploaded._
- **Uploads Dimensions** `text` Default: `32|32`
    - _Type the maximum dimensions for the category and item images._
- **Uploads Size** `text` Default: `50`
    - _Type the maximum size in bytes for the category and item images._

[Go up to Table of Contents](#table_of_contents)

## 📐 Templates <a name = "templates"></a>

The following is a list of templates available for this plugin.

- `newpoints_shop_category`
    - _front end_;
- `newpoints_shop_category_add_edit_form`
    - _front end_;
- `newpoints_shop_category_add_edit_form_category`
    - _front end_;
- `newpoints_shop_category_add_edit_form_category_option`
    - _front end_;
- `newpoints_shop_category_add_edit_form_upload`
    - _front end_;
- `newpoints_shop_category_empty`
    - _front end_;
- `newpoints_shop_category_icon`
    - _front end_;
- `newpoints_shop_category_links`
    - _front end_;
- `newpoints_shop_category_pagination`
    - _front end_;
- `newpoints_shop_category_thead_options`
    - _front end_;
- `newpoints_shop_category_thead_purchase`
    - _front end_;
- `newpoints_shop_confirm_purchase`
    - _front end_;
- `newpoints_shop_confirm_purchase_icon`
    - _front end_;
- `newpoints_shop_confirm_sell`
    - _front end_;
- `newpoints_shop_confirm_send`
    - _front end_;
- `newpoints_shop_css`
    - _front end_;
- `newpoints_shop_item`
    - _front end_;
- `newpoints_shop_item_add_edit_form`
    - _front end_;
- `newpoints_shop_item_add_edit_form_category`
    - _front end_;
- `newpoints_shop_item_add_edit_form_category_option`
    - _front end_;
- `newpoints_shop_item_add_edit_form_upload`
    - _front end_;
- `newpoints_shop_item_icon`
    - _front end_;
- `newpoints_shop_item_purchase`
    - _front end_;
- `newpoints_shop_item_row_options`
    - _front end_;
- `newpoints_shop_my_items_content`
    - _front end_;
- `newpoints_shop_my_items_empty`
    - _front end_;
- `newpoints_shop_my_items_row`
    - _front end_;
- `newpoints_shop_my_items_row_icon`
    - _front end_;
- `newpoints_shop_my_items_row_options`
    - _front end_;
- `newpoints_shop_my_items_row_options_sell`
    - _front end_;
- `newpoints_shop_my_items_row_options_send`
    - _front end_;
- `newpoints_shop_no_cats`
    - _front end_;
- `newpoints_shop_page_button_add_category`
    - _front end_;
- `newpoints_shop_page_button_my_items`
    - _front end_;
- `newpoints_shop_post`
    - _front end_;
- `newpoints_shop_post_icon`
    - _front end_;
- `newpoints_shop_post_view_all`
    - _front end_;
- `newpoints_shop_profile`
    - _front end_;
- `newpoints_shop_profile_icon`
    - _front end_;
- `newpoints_shop_profile_view_all`
    - _front end_;
- `newpoints_shop_quick_edit_row`
    - _front end_;
- `newpoints_shop_quick_edit_row_item`
    - _front end_;
- `newpoints_shop_quick_edit_row_item_icon`
    - _front end_;
- `newpoints_shop_stats`
    - _front end_;
- `newpoints_shop_stats_empty`
    - _front end_;
- `newpoints_shop_stats_row`
    - _front end_;
- `newpoints_shop_view_item`
    - _front end_;
- `newpoints_shop_view_item_icon`
    - _front end_;
- `newpoints_shop_view_item_purchase`
    - _front end_;

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

See also the list of [contributors](https://github.com/OUGC-Network/NewPoints-Shop/contributors) who participated in
this
project.

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-159249.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)