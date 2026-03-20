<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
namespace Presta_Shop\Module\Block_Wish_List\Database;

use Block_Wish_List;
use Configuration;
use Db;
use Language;
use Symfony\Contracts\Translation\Translator_Interface;
use Tab;
class Install
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    public function __construct(Translator_Interface $translator)
    {
        $this->translator = $translator;
    }
    public function run(): bool
    {
        return $this->install_tables() && $this->install_configuration() && $this->install_tabs();
    }
    public function install_tables()
    {
        $sql = [];
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wishlist` (
          `id_wishlist` int(10) unsigned NOT NULL auto_increment,
          `id_customer` int(10) unsigned NOT NULL,
          `id_shop` int(10) unsigned default 1,
          `id_shop_group` int(10) unsigned default 1,
          `token` varchar(64) NOT NULL,
          `name` varchar(64) NOT NULL,
          `counter` int(10) unsigned NULL,
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          `default` int(10) unsigned default 0,
          PRIMARY KEY  (`id_wishlist`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wishlist_product` (
          `id_wishlist_product` int(10) NOT NULL auto_increment,
          `id_wishlist` int(10) unsigned NOT NULL,
          `id_product` int(10) unsigned NOT NULL,
          `id_product_attribute` int(10) unsigned NOT NULL,
          `quantity` int(10) unsigned NOT NULL,
          `priority` int(10) unsigned NOT NULL,
          PRIMARY KEY  (`id_wishlist_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wishlist_product_cart` (
          `id_wishlist_product` int(10) unsigned NOT NULL,
          `id_cart` int(10) unsigned NOT NULL,
          `quantity` int(10) unsigned NOT NULL,
          `date_add` datetime NOT NULL
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'blockwishlist_statistics` (
            `id_statistics` int(10) unsigned NOT NULL auto_increment,
            `id_cart` int(10) unsigned default NULL,
            `id_product` int(10) unsigned NOT NULL,
            `id_product_attribute` int(10) unsigned NOT NULL,
            `date_add` datetime NOT NULL,
            `id_shop` int(10) unsigned default 1,
            PRIMARY KEY  (`id_statistics`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $result = true;
        foreach ($sql as $query) {
            $result = $result && Db::get_instance()->execute($query);
        }
        return $result;
    }
    public function install_configuration(): bool
    {
        $page_name = $default_name = $create_button_label = [];
        foreach (Language::get_languages() as $lang) {
            $page_name[$lang['id_lang']] = $this->translator->trans('My wishlists', [], 'Modules.Blockwishlist.Admin', $lang['locale']);
            $default_name[$lang['id_lang']] = $this->translator->trans('My wishlist', [], 'Modules.Blockwishlist.Admin', $lang['locale']);
            $create_button_label[$lang['id_lang']] = $this->translator->trans('Create new list', [], 'Modules.Blockwishlist.Admin', $lang['locale']);
        }
        return Configuration::update_value('blockwishlist_WishlistPageName', $page_name) && Configuration::update_value('blockwishlist_WishlistDefaultTitle', $default_name) && Configuration::update_value('blockwishlist_CreateButtonLabel', $create_button_label);
    }
    public function install_tabs()
    {
        $install_tab_completed = true;
        foreach (Block_Wish_List::MODULE_ADMIN_CONTROLLERS as $controller) {
            if (Tab::get_id_from_class_name($controller['class_name'])) {
                continue;
            }
            $tab = new Tab();
            $tab->class_name = $controller['class_name'];
            $tab->active = $controller['visible'];
            foreach (Language::get_languages() as $lang) {
                $tab->name[$lang['id_lang']] = $this->translator->trans($controller['name'], [], 'Modules.BlockWishList.Admin', $lang['locale']);
            }
            $tab->id_parent = Tab::get_id_from_class_name($controller['parent_class_name']);
            $tab->module = 'blockwishlist';
            $install_tab_completed = $install_tab_completed && $tab->add();
        }
        return $install_tab_completed;
    }
}