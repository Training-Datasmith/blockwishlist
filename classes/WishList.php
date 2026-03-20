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
class Wish_List extends Object_Model
{
    /** @var int Wishlist ID */
    public $id;
    /** @var int Customer ID */
    public $id_customer;
    /** @var int Token */
    public $token;
    /** @var string Name */
    public $name;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var int Object last modification date */
    public $id_shop;
    /** @var int Object last modification date */
    public $id_shop_group;
    /** @var int default */
    public $default;
    /**
     * @see ObjectModel::$definition
     *
     * @var array<string, string|array<string, array<string, mixed>>>
     */
    public static $definition = ['table' => 'wishlist', 'primary' => 'id_wishlist', 'fields' => ['id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'token' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'default' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool']]];
    /**
     * Get Customers having a wishlist
     *
     * @return array Results
     */
    public static function get_customers()
    {
        $cache_id = 'WishList::getCustomers';
        if (false === Cache::is_stored($cache_id)) {
            $result = Db::get_instance((bool) _PS_USE_SQL_SLAVE_)->execute_s('
                SELECT c.`id_customer`, c.`firstname`, c.`lastname`
                    FROM `' . _DB_PREFIX_ . 'wishlist` w
                INNER JOIN `' . _DB_PREFIX_ . 'customer` c ON c.`id_customer` = w.`id_customer`
                ORDER BY c.`firstname` ASC');
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return true if wishlist exists else false
     *
     * @param int $id_wishlist
     * @param int $id_customer
     *
     * @return bool exists
     */
    public static function exists($id_wishlist, $id_customer)
    {
        $result = Db::get_instance()->get_row('
            SELECT 1
            FROM `' . _DB_PREFIX_ . 'wishlist`
            WHERE `id_wishlist` = ' . (int) $id_wishlist . '
            AND `id_customer` = ' . (int) $id_customer . '
            AND `id_shop` = ' . (int) Context::get_context()->shop->id);
        return (bool) $result;
    }
    /**
     * Set current WishList as default
     *
     * @return bool
     */
    public function set_default()
    {
        if ($default = static::get_default($this->id_customer)) {
            Db::get_instance()->update('wishlist', ['default' => '0'], 'id_wishlist = ' . (int) $default);
        }
        return Db::get_instance()->update('wishlist', ['default' => '1'], 'id_wishlist = ' . (int) $this->id);
    }
    /**
     * Return if there is a default already set
     *
     * @param int $id_customer
     *
     * @return bool
     */
    public static function is_default($id_customer)
    {
        return (bool) Db::get_instance()->get_value('SELECT 1 FROM `' . _DB_PREFIX_ . 'wishlist` WHERE `id_customer` = ' . (int) $id_customer . ' AND `default` = 1');
    }
    /**
     * @param int $id_customer
     *
     * @return int
     */
    public static function get_default($id_customer)
    {
        return (int) Db::get_instance()->get_value('SELECT `id_wishlist` FROM `' . _DB_PREFIX_ . 'wishlist` WHERE `id_customer` = ' . (int) $id_customer . ' AND `default` = 1');
    }
    /**
     * Add product to ID wishlist
     *
     * @param int $id_wishlist
     * @param int $id_customer
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $quantity
     *
     * @return bool succeed
     */
    public static function add_product($id_wishlist, $id_customer, $id_product, $id_product_attribute, $quantity)
    {
        $result = Db::get_instance()->get_row('
            SELECT wp.`quantity`
                FROM `' . _DB_PREFIX_ . 'wishlist_product` wp
            JOIN `' . _DB_PREFIX_ . 'wishlist` w ON (w.`id_wishlist` = wp.`id_wishlist`)
            WHERE wp.`id_wishlist` = ' . (int) $id_wishlist . '
            AND w.`id_customer` = ' . (int) $id_customer . '
            AND wp.`id_product` = ' . (int) $id_product . '
            AND wp.`id_product_attribute` = ' . (int) $id_product_attribute);
        if (!empty($result)) {
            if ((int) $result['quantity'] + (int) $quantity <= 0) {
                return Wish_List::remove_product($id_wishlist, $id_customer, $id_product, $id_product_attribute);
            }
            // TODO: use a method for this like updateProduct ?
            return Db::get_instance()->update('wishlist_product', ['quantity' => (int) $quantity + (int) $result['quantity']], '`id_wishlist` = ' . (int) $id_wishlist . ' AND `id_product` = ' . (int) $id_product . ' AND `id_product_attribute` = ' . (int) $id_product_attribute);
        }
        return Db::get_instance()->insert('wishlist_product', ['id_wishlist' => (int) $id_wishlist, 'id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute, 'quantity' => (int) $quantity, 'priority' => 1]);
    }
    /**
     * Remove product from wishlist
     *
     * @param int $id_wishlist
     * @param int $id_customer
     * @param int $id_product
     * @param int $id_product_attribute
     *
     * @return bool
     */
    public static function remove_product($id_wishlist, $id_customer, $id_product, $id_product_attribute)
    {
        $result = Db::get_instance()->get_row('
            SELECT w.`id_wishlist`, wp.`id_wishlist_product`
            FROM `' . _DB_PREFIX_ . 'wishlist` w
            LEFT JOIN `' . _DB_PREFIX_ . 'wishlist_product` wp ON (wp.`id_wishlist` = w.`id_wishlist`)
            WHERE `id_customer` = ' . (int) $id_customer . '
            AND w.`id_wishlist` = ' . (int) $id_wishlist);
        if (empty($result)) {
            return false;
        }
        // Delete product in wishlist_product_cart
        Db::get_instance()->delete('wishlist_product_cart', 'id_wishlist_product = ' . (int) $result['id_wishlist_product']);
        return Db::get_instance()->delete('wishlist_product', 'id_wishlist = ' . (int) $id_wishlist . ' AND id_product = ' . (int) $id_product . ' AND id_product_attribute = ' . (int) $id_product_attribute);
    }
    /**
     * @param int|null $id_product
     * @param int|null $id_product_attribute
     *
     * @return bool
     */
    public static function remove_product_from_wishlist($id_product = null, $id_product_attribute = null)
    {
        if ($id_product === null && $id_product_attribute === null) {
            return false;
        }
        return Db::get_instance()->delete('wishlist_product', ($id_product ? 'id_product = ' . (int) $id_product : '') . ($id_product && $id_product_attribute ? ' AND ' : '') . ($id_product_attribute ? ' id_product_attribute = ' . (int) $id_product_attribute : ''));
    }
    public static function remove_non_existing_product_attributes_from_wishlist(): void
    {
        $db_query = new Db_Query();
        $db_query->select('wp.id_product_attribute');
        $db_query->from('wishlist_product', 'wp');
        $db_query->left_join('product_attribute', 'pa', 'wp.id_product_attribute = pa.id_product_attribute');
        $db_query->where('pa.id_product_attribute IS NULL');
        $product_attributes = Db::get_instance()->execute_s($db_query);
        foreach ($product_attributes as $product_attribute) {
            self::remove_product_from_wishlist(null, (int) $product_attribute['id_product_attribute']);
        }
    }
    /**
     * Update product to wishlist
     *
     * @param int $id_wishlist
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $priority
     * @param int $quantity
     *
     * @return bool succeed
     */
    public static function update_product($id_wishlist, $id_product, $id_product_attribute, $priority, $quantity)
    {
        if ($priority < 0 || $priority > 2) {
            return false;
        }
        return Db::get_instance()->update('wishlist_product', ['priority' => (int) $priority, 'quantity' => (int) $quantity], 'id_wishlist = ' . (int) $id_wishlist . 'id_product` = ' . (int) $id_product . 'id_product_attribute` = ' . (int) $id_product_attribute);
    }
    /**
     * Get all Wishlists by Customer ID
     *
     * @param int $id_customer
     *
     * @return array Results
     */
    public static function get_all_wishlists_by_id_customer($id_customer)
    {
        $shop_restriction = '';
        if (Shop::get_context_shop_id()) {
            $shop_restriction = 'AND w.id_shop = ' . (int) Shop::get_context_shop_id();
        } elseif (Shop::get_context_shop_group_id()) {
            $shop_restriction = 'AND w.id_shop_group = ' . (int) Shop::get_context_shop_group_id();
        }
        $sql = sprintf('SELECT w.`id_wishlist`, (
                SELECT COUNT(wp.id_wishlist_product)
                FROM `%1$swishlist_product` wp
                    INNER JOIN `%1$sproduct` p ON p.`id_product` = wp.`id_product`
                    %2$s
                WHERE wp.id_wishlist = w.id_wishlist
            ) AS nbProducts, w.`name`, w.`default`, w.`token`
            FROM `%1$swishlist` w
            WHERE w.`id_customer` = %3$d %4$s
            ORDER BY w.`default` DESC, w.`name` ASC', _DB_PREFIX_, Shop::add_sql_association('product', 'p', true, 'product_shop.active = 1'), (int) $id_customer, $shop_restriction);
        return Db::get_instance((bool) _PS_USE_SQL_SLAVE_)->execute_s($sql);
    }
    /**
     * Get products by Wishlist
     *
     * @param int $id_wishlist
     *
     * @return array|false Results
     */
    public static function get_products_by_wishlist($id_wishlist)
    {
        $wishlist_products = Db::get_instance()->execute_s('
            SELECT `id_product`, `id_product_attribute`, `quantity`
            FROM `' . _DB_PREFIX_ . 'wishlist_product`
            WHERE `id_wishlist` = ' . (int) $id_wishlist . '
            And quantity > 0');
        if (!empty($wishlist_products)) {
            return $wishlist_products;
        }
        return false;
    }
    /**
     * Get Wishlist products by Customer ID
     *
     * @param int $id_wishlist
     * @param int $id_customer
     * @param int $id_lang
     * @param int|null $id_product
     * @param bool $quantity
     *
     * @return array Results
     */
    public static function get_product_by_id_customer($id_wishlist, $id_customer, $id_lang, $id_product = null, $quantity = false)
    {
        $products = Db::get_instance()->execute_s('
            SELECT wp.`id_product`, wp.`quantity` as wishlist_quantity, p.`quantity` AS product_quantity, pl.`name`, wp.`id_product_attribute`, wp.`priority`, pl.link_rewrite, cl.link_rewrite AS category_rewrite
            FROM `' . _DB_PREFIX_ . 'wishlist_product` wp
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = wp.`id_product`
            ' . Shop::add_sql_association('product', 'p') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = wp.`id_product`' . Shop::add_sql_restriction_on_lang('pl') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'wishlist` w ON w.`id_wishlist` = wp.`id_wishlist`
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON cl.`id_category` = product_shop.`id_category_default` AND cl.id_lang=' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl') . '
            WHERE w.`id_customer` = ' . (int) $id_customer . '
            AND pl.`id_lang` = ' . (int) $id_lang . '
            AND wp.`id_wishlist` = ' . (int) $id_wishlist . (empty($id_product) === false ? ' AND wp.`id_product` = ' . (int) $id_product : '') . ($quantity == true ? ' AND wp.`quantity` != 0' : '') . '
            GROUP BY p.id_product, wp.id_product_attribute');
        if (empty($products)) {
            return [];
        }
        for ($i = 0; $i < sizeof($products); ++$i) {
            if (isset($products[$i]['id_product_attribute']) && Validate::is_unsigned_int($products[$i]['id_product_attribute'])) {
                $result = Db::get_instance()->execute_s('
                SELECT al.`name` AS attribute_name, pa.`quantity` AS "attribute_quantity"
                FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                ' . Shop::add_sql_association('product_attribute', 'pa') . '
                WHERE pac.`id_product_attribute` = ' . (int) $products[$i]['id_product_attribute']);
                $products[$i]['attributes_small'] = '';
                if ($result) {
                    foreach ($result as $row) {
                        $products[$i]['attributes_small'] .= $row['attribute_name'] . ', ';
                    }
                }
                $products[$i]['attributes_small'] = rtrim($products[$i]['attributes_small'], ', ');
                if (isset($result[0])) {
                    $products[$i]['attribute_quantity'] = $result[0]['attribute_quantity'];
                }
            } else {
                $products[$i]['attribute_quantity'] = $products[$i]['product_quantity'];
            }
        }
        return $products;
    }
    /**
     * Add bought product
     *
     * @param int $id_wishlist
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $id_cart
     * @param int $quantity
     *
     * @return bool succeed
     */
    public static function add_bought_product($id_wishlist, $id_product, $id_product_attribute, $id_cart, $quantity)
    {
        $result = Db::get_instance()->get_row('
            SELECT `quantity`, `id_wishlist_product`
            FROM `' . _DB_PREFIX_ . 'wishlist_product` wp
            WHERE `id_wishlist` = ' . (int) $id_wishlist . '
            AND `id_product` = ' . (int) $id_product . '
            AND `id_product_attribute` = ' . (int) $id_product_attribute);
        if (empty($result) || $result['quantity'] - $quantity < 0 || $quantity > $result['quantity']) {
            return false;
        }
        Db::get_instance()->execute_s('
            SELECT *
            FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`
            WHERE `id_wishlist_product`=' . (int) $result['id_wishlist_product'] . ' AND `id_cart`=' . (int) $id_cart);
        if (Db::get_instance()->num_rows() > 0) {
            $result2 = Db::get_instance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'wishlist_product_cart`
                SET `quantity`=`quantity` + ' . (int) $quantity . '
                WHERE `id_wishlist_product`=' . (int) $result['id_wishlist_product'] . ' AND `id_cart`=' . (int) $id_cart);
        } else {
            $result2 = Db::get_instance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'wishlist_product_cart`
                (`id_wishlist_product`, `id_cart`, `quantity`, `date_add`) VALUES(
                ' . (int) $result['id_wishlist_product'] . ',
                ' . (int) $id_cart . ',
                ' . (int) $quantity . ',
                \'' . p_sql(date('Y-m-d H:i:s')) . '\')');
        }
        if ($result2 === false) {
            return false;
        }
        return true;
    }
    /**
     * @param int $id_customer
     * @param int $idShop
     *
     * @return array|false
     */
    public static function get_all_product_by_customer($id_customer, $id_shop)
    {
        $result = Db::get_instance()->execute_s('
            SELECT  `id_product`, `id_product_attribute`, w.`id_wishlist`, wp.`quantity`
            FROM `' . _DB_PREFIX_ . 'wishlist_product` wp
            LEFT JOIN `' . _DB_PREFIX_ . 'wishlist` w ON (w.`id_wishlist` = wp.`id_wishlist`)
            WHERE w.`id_customer` = ' . (int) $id_customer . '
            AND w.id_shop = ' . (int) $id_shop . '
            AND wp.`quantity` > 0 ');
        if (empty($result)) {
            return false;
        }
        return $result;
    }
    /**
     * Get ID wishlist by Token
     *
     * @param string $token
     *
     * @return array Results
     *
     * @throws PrestaShopException
     */
    public static function get_by_token($token)
    {
        if (empty($token) || false === Validate::is_message($token)) {
            throw new Presta_Shop_Exception('Invalid token');
        }
        return Db::get_instance((bool) _PS_USE_SQL_SLAVE_)->get_row('
            SELECT w.`id_wishlist`, w.`name`, w.`id_customer`, c.`firstname`, c.`lastname`
            FROM `' . _DB_PREFIX_ . 'wishlist` w
            INNER JOIN `' . _DB_PREFIX_ . 'customer` c ON c.`id_customer` = w.`id_customer`
            WHERE `token` = \'' . p_sql($token) . '\'');
    }
    public static function refresh_wish_list($id_wishlist): void
    {
        $old_carts = Db::get_instance((bool) _PS_USE_SQL_SLAVE_)->execute_s('
        SELECT wp.id_product, wp.id_product_attribute, wpc.id_cart, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(wpc.date_add) AS timecart
        FROM `' . _DB_PREFIX_ . 'wishlist_product_cart` wpc
        JOIN `' . _DB_PREFIX_ . 'wishlist_product` wp ON (wp.id_wishlist_product = wpc.id_wishlist_product)
        JOIN `' . _DB_PREFIX_ . 'cart` c ON  (c.id_cart = wpc.id_cart)
        JOIN `' . _DB_PREFIX_ . 'cart_product` cp ON (wpc.id_cart = cp.id_cart)
        LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_cart = c.id_cart)
        WHERE (wp.id_wishlist=' . (int) $id_wishlist . ' AND o.id_cart IS NULL)
        HAVING timecart  >= 3600*6');
        if (!empty($old_carts)) {
            foreach ($old_carts as $old_cart) {
                Db::get_instance()->execute('
                    DELETE FROM `' . _DB_PREFIX_ . 'cart_product`
                    WHERE id_cart=' . (int) $old_cart['id_cart'] . ' AND id_product=' . (int) $old_cart['id_product'] . ' AND id_product_attribute=' . (int) $old_cart['id_product_attribute']);
            }
        }
        $freshwish = Db::get_instance()->execute_s('
            SELECT  wpc.id_cart, wpc.id_wishlist_product
            FROM `' . _DB_PREFIX_ . 'wishlist_product_cart` wpc
            JOIN `' . _DB_PREFIX_ . 'wishlist_product` wp ON (wpc.id_wishlist_product = wp.id_wishlist_product)
            JOIN `' . _DB_PREFIX_ . 'cart` c ON (c.id_cart = wpc.id_cart)
            LEFT JOIN `' . _DB_PREFIX_ . 'cart_product` cp ON (cp.id_cart = wpc.id_cart AND cp.id_product = wp.id_product AND cp.id_product_attribute = wp.id_product_attribute)
            WHERE (wp.id_wishlist = ' . (int) $id_wishlist . ' AND ((cp.id_product IS NULL AND cp.id_product_attribute IS NULL)))
            ');
        $res = Db::get_instance()->execute_s('
            SELECT wp.id_wishlist_product, cp.quantity AS cart_quantity, wpc.quantity AS wish_quantity, wpc.id_cart
            FROM `' . _DB_PREFIX_ . 'wishlist_product_cart` wpc
            JOIN `' . _DB_PREFIX_ . 'wishlist_product` wp ON (wp.id_wishlist_product = wpc.id_wishlist_product)
            JOIN `' . _DB_PREFIX_ . 'cart` c ON (c.id_cart = wpc.id_cart)
            JOIN `' . _DB_PREFIX_ . 'cart_product` cp ON (cp.id_cart = wpc.id_cart AND cp.id_product = wp.id_product AND cp.id_product_attribute = wp.id_product_attribute)
            WHERE wp.id_wishlist=' . (int) $id_wishlist);
        if (!empty($res)) {
            foreach ($res as $refresh) {
                if ($refresh['wish_quantity'] > $refresh['cart_quantity']) {
                    Db::get_instance()->execute('
                        UPDATE `' . _DB_PREFIX_ . 'wishlist_product`
                        SET `quantity`= `quantity` + ' . ((int) $refresh['wish_quantity'] - (int) $refresh['cart_quantity']) . '
                        WHERE id_wishlist_product=' . (int) $refresh['id_wishlist_product']);
                    Db::get_instance()->execute('
                        UPDATE `' . _DB_PREFIX_ . 'wishlist_product_cart`
                        SET `quantity`=' . (int) $refresh['cart_quantity'] . '
                        WHERE id_wishlist_product=' . (int) $refresh['id_wishlist_product'] . ' AND id_cart=' . (int) $refresh['id_cart']);
                }
            }
        }
        if (!empty($freshwish)) {
            foreach ($freshwish as $prodcustomer) {
                Db::get_instance()->execute('
                    UPDATE `' . _DB_PREFIX_ . 'wishlist_product` SET `quantity`=`quantity` +
                    (
                        SELECT `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`
                        WHERE `id_wishlist_product`=' . (int) $prodcustomer['id_wishlist_product'] . ' AND `id_cart`=' . (int) $prodcustomer['id_cart'] . '
                    )
                    WHERE `id_wishlist_product`=' . (int) $prodcustomer['id_wishlist_product'] . ' AND `id_wishlist`=' . (int) $id_wishlist);
                Db::get_instance()->execute('
                    DELETE FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`
                    WHERE `id_wishlist_product`=' . (int) $prodcustomer['id_wishlist_product'] . ' AND `id_cart`=' . (int) $prodcustomer['id_cart']);
            }
        }
    }
    /**
     * Increment counter
     *
     * @param int $id_wishlist
     *
     * @return bool succeed
     */
    public static function inc_counter($id_wishlist)
    {
        $counter = Wish_List::get_wishlist_counter((int) $id_wishlist);
        ++$counter;
        return Db::get_instance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'wishlist` SET
            `counter` = ' . $counter . '
            WHERE `id_wishlist` = ' . (int) $id_wishlist);
    }
    /**
     * @param int $id_wishlist
     *
     * @return int
     */
    public static function get_wishlist_counter($id_wishlist)
    {
        return (int) Db::get_instance()->get_value('
            SELECT `counter`
            FROM `' . _DB_PREFIX_ . 'wishlist`
            WHERE `id_wishlist` = ' . (int) $id_wishlist);
    }
    /**
     * Get Wishlists by Customer ID
     *
     * @param int $id_customer
     *
     * @return array Results
     */
    public static function get_by_id_customer($id_customer)
    {
        $shop_restriction = '';
        if (Shop::get_context_shop_id()) {
            $shop_restriction = 'AND id_shop = ' . (int) Shop::get_context_shop_id();
        } elseif (Shop::get_context_shop_group_id()) {
            $shop_restriction = 'AND id_shop_group = ' . (int) Shop::get_context_shop_group_id();
        }
        $cache_id = 'WhishList::getByIdCustomer_' . (int) $id_customer . '-' . (int) Shop::get_context_shop_id() . '-' . (int) Shop::get_context_shop_group_id();
        if (!Cache::is_stored($cache_id)) {
            $result = Db::get_instance()->execute_s('
                SELECT w.`id_wishlist`, w.`name`, w.`token`, w.`date_add`, w.`date_upd`, w.`counter`, w.`default`
                FROM `' . _DB_PREFIX_ . 'wishlist` w
                WHERE `id_customer` = ' . (int) $id_customer . '
                ' . $shop_restriction . '
                ORDER BY w.`name` ASC');
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
}