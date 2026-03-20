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
class Statistics extends Object_Model
{
    /** @var int ID */
    public $id_statistics;
    /** @var int id_product */
    public $id_product;
    /** @var int id_product_attribute */
    public $id_product_attribute;
    /** @var string date_add */
    public $date_add;
    /** @var int|null date_add */
    public $id_cart;
    /** @var int ID */
    public $id_shop;
    /**
     * @see ObjectModel::$definition
     *
     * @var array<string, string|array<string, array<string, mixed>>>
     */
    public static $definition = ['table' => 'blockwishlist_statistics', 'primary' => 'id_statistics', 'fields' => ['id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false], 'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'date_add' => ['type' => self::TYPE_DATE, 'required' => true], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true]]];
    /**
     * @param int|null $id_product
     * @param int|null $id_product_attribute
     *
     * @return bool
     */
    public static function remove_product_from_statistics($id_product = null, $id_product_attribute = null)
    {
        if ($id_product === null && $id_product_attribute === null) {
            return false;
        }
        return Db::get_instance()->delete('blockwishlist_statistics', ($id_product ? 'id_product = ' . (int) $id_product : '') . ($id_product && $id_product_attribute ? ' AND ' : '') . ($id_product_attribute ? ' id_product_attribute = ' . (int) $id_product_attribute : ''));
    }
    public static function remove_non_existing_product_attributes_from_statistics(): void
    {
        $db_query = new Db_Query();
        $db_query->select('bws.id_product_attribute');
        $db_query->from('blockwishlist_statistics', 'bws');
        $db_query->left_join('product_attribute', 'pa', 'bws.id_product_attribute = pa.id_product_attribute');
        $db_query->where('pa.id_product_attribute IS NULL');
        $product_attributes = Db::get_instance()->execute_s($db_query);
        foreach ($product_attributes as $product_attribute) {
            self::remove_product_from_statistics(null, (int) $product_attribute['id_product_attribute']);
        }
    }
}