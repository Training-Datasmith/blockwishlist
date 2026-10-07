<?php
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

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\BlockWishList\Database\Install;

abstract class WishlistDatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        Cache::clean();
        $_GET = [];
        $_POST = [];
        $this->setShopContext(1, 1);

        $installed = (new Install(new TestTranslator()))->installTables();
        $this->assertTrue($installed, Db::getInstance()->getLastError());
        $this->createAuxiliaryTables();
        $this->truncateAll();
    }

    protected function setShopContext($shopId, $shopGroupId)
    {
        Shop::$contextShopId = $shopId;
        Shop::$contextShopGroupId = $shopGroupId;

        $context = Context::getContext();
        if (!is_object($context->shop)) {
            $context->shop = new stdClass();
        }
        $context->shop->id = $shopId;
        $context->shop->id_shop_group = $shopGroupId;
        $context->currency = (object) ['iso_code' => 'EUR'];
        $context->language = (object) ['id' => 1, 'locale' => 'en-US'];
        $context->link = (object) [];
        $context->cart = (object) ['id' => 1];
    }

    protected function execute($sql)
    {
        $this->assertTrue(Db::getInstance()->execute($sql), Db::getInstance()->getLastError());
    }

    protected function rows($sql)
    {
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $this->fail(Db::getInstance()->getLastError());
        }

        return $rows;
    }

    protected function insertCustomer($firstname, $lastname)
    {
        $this->assertTrue(Db::getInstance()->insert('customer', [
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]), Db::getInstance()->getLastError());

        return (int) Db::getInstance()->Insert_ID();
    }

    protected function insertWishlist($idCustomer, $name, array $extra = [])
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'id_customer' => $idCustomer,
            'id_shop' => 1,
            'id_shop_group' => 1,
            'token' => 'token-' . $name,
            'name' => $name,
            'counter' => 0,
            'date_add' => $now,
            'date_upd' => $now,
            'default' => 0,
        ], $extra);

        $this->assertTrue(Db::getInstance()->insert('wishlist', $data), Db::getInstance()->getLastError());

        return (int) Db::getInstance()->Insert_ID();
    }

    protected function insertWishlistProduct($idWishlist, $idProduct, $idProductAttribute, $quantity, $priority = 1)
    {
        $this->assertTrue(Db::getInstance()->insert('wishlist_product', [
            'id_wishlist' => $idWishlist,
            'id_product' => $idProduct,
            'id_product_attribute' => $idProductAttribute,
            'quantity' => $quantity,
            'priority' => $priority,
        ]), Db::getInstance()->getLastError());

        return (int) Db::getInstance()->Insert_ID();
    }

    protected function insertCatalogProduct($name, $quantity = 10, $active = 1, $shopId = 1, $langId = 1, $categoryId = 2)
    {
        $this->assertTrue(Db::getInstance()->insert('product', [
            'quantity' => $quantity,
        ]), Db::getInstance()->getLastError());
        $idProduct = (int) Db::getInstance()->Insert_ID();

        $this->assertTrue(Db::getInstance()->insert('product_shop', [
            'id_product' => $idProduct,
            'id_shop' => $shopId,
            'active' => $active,
            'id_category_default' => $categoryId,
            'visibility' => 'both',
        ]), Db::getInstance()->getLastError());

        $this->assertTrue(Db::getInstance()->insert('product_lang', [
            'id_product' => $idProduct,
            'id_lang' => $langId,
            'id_shop' => $shopId,
            'name' => $name,
            'link_rewrite' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)),
        ]), Db::getInstance()->getLastError());

        $this->ensureCategoryLang($categoryId, $langId, $shopId);

        return $idProduct;
    }

    protected function ensureCategoryLang($categoryId, $langId = 1, $shopId = 1)
    {
        $existing = Db::getInstance()->getValue(
            'SELECT `id_category` FROM `' . _DB_PREFIX_ . 'category_lang` WHERE `id_category` = ' . (int) $categoryId
            . ' AND `id_lang` = ' . (int) $langId . ' AND `id_shop` = ' . (int) $shopId
        );
        if ($existing) {
            return;
        }

        $this->assertTrue(Db::getInstance()->insert('category_lang', [
            'id_category' => $categoryId,
            'id_lang' => $langId,
            'id_shop' => $shopId,
            'link_rewrite' => 'category-' . $categoryId,
        ]), Db::getInstance()->getLastError());
    }

    protected function insertCombination($idProduct, $attributeName, $quantity = 3, $shopId = 1, $langId = 1)
    {
        $this->assertTrue(Db::getInstance()->insert('attribute_group', [
            'position' => 0,
        ]), Db::getInstance()->getLastError());
        $groupId = (int) Db::getInstance()->Insert_ID();

        $this->assertTrue(Db::getInstance()->insert('attribute', [
            'id_attribute_group' => $groupId,
        ]), Db::getInstance()->getLastError());
        $attributeId = (int) Db::getInstance()->Insert_ID();

        $this->assertTrue(Db::getInstance()->insert('attribute_lang', [
            'id_attribute' => $attributeId,
            'id_lang' => $langId,
            'name' => $attributeName,
        ]), Db::getInstance()->getLastError());
        $this->assertTrue(Db::getInstance()->insert('attribute_group_lang', [
            'id_attribute_group' => $groupId,
            'id_lang' => $langId,
            'name' => 'Group',
        ]), Db::getInstance()->getLastError());
        $this->assertTrue(Db::getInstance()->insert('product_attribute', [
            'id_product' => $idProduct,
            'quantity' => $quantity,
        ]), Db::getInstance()->getLastError());
        $combinationId = (int) Db::getInstance()->Insert_ID();
        $this->assertTrue(Db::getInstance()->insert('product_attribute_shop', [
            'id_product_attribute' => $combinationId,
            'id_shop' => $shopId,
            'id_product' => $idProduct,
        ]), Db::getInstance()->getLastError());
        $this->assertTrue(Db::getInstance()->insert('product_attribute_combination', [
            'id_product_attribute' => $combinationId,
            'id_attribute' => $attributeId,
        ]), Db::getInstance()->getLastError());

        return $combinationId;
    }

    protected function insertStatistic($idProduct, $idProductAttribute = 0, $idShop = 1, $idCart = 0, $dateExpression = null)
    {
        if (null === $dateExpression) {
            $dateExpression = '\'' . pSQL(date('Y-m-d H:i:s')) . '\'';
        }

        $this->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'blockwishlist_statistics` (`id_cart`, `id_product`, `id_product_attribute`, `date_add`, `id_shop`) VALUES ('
            . (int) $idCart . ', ' . (int) $idProduct . ', ' . (int) $idProductAttribute . ', ' . $dateExpression . ', ' . (int) $idShop . ')'
        );
    }

    protected function insertOrder($idCart, $paid = 0, $shipped = 0, $productId = null, $attributeId = 0)
    {
        $this->assertTrue(Db::getInstance()->insert('order_state', [
            'paid' => $paid,
            'shipped' => $shipped,
        ]), Db::getInstance()->getLastError());
        $stateId = (int) Db::getInstance()->Insert_ID();

        $this->assertTrue(Db::getInstance()->insert('orders', [
            'id_cart' => $idCart,
        ]), Db::getInstance()->getLastError());
        $orderId = (int) Db::getInstance()->Insert_ID();

        $this->assertTrue(Db::getInstance()->insert('order_history', [
            'id_order' => $orderId,
            'id_order_state' => $stateId,
        ]), Db::getInstance()->getLastError());

        if (null !== $productId) {
            $this->assertTrue(Db::getInstance()->insert('order_detail', [
                'id_order' => $orderId,
                'product_id' => $productId,
                'product_attribute_id' => $attributeId,
            ]), Db::getInstance()->getLastError());
        }

        return $orderId;
    }

    protected function insertCart()
    {
        $this->assertTrue(Db::getInstance()->insert('cart', [
            'id_shop' => 1,
        ]), Db::getInstance()->getLastError());

        return (int) Db::getInstance()->Insert_ID();
    }

    protected function insertWishlistProductCart($idWishlistProduct, $idCart, $quantity, $dateExpression = 'NOW()')
    {
        $this->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'wishlist_product_cart` (`id_wishlist_product`, `id_cart`, `quantity`, `date_add`) VALUES ('
            . (int) $idWishlistProduct . ', ' . (int) $idCart . ', ' . (int) $quantity . ', ' . $dateExpression . ')'
        );
    }

    private function createAuxiliaryTables()
    {
        $engine = _MYSQL_ENGINE_;
        $prefix = _DB_PREFIX_;
        $statements = [
            "CREATE TABLE IF NOT EXISTS `{$prefix}customer` (
                `id_customer` int unsigned NOT NULL AUTO_INCREMENT,
                `firstname` varchar(255) NOT NULL,
                `lastname` varchar(255) NOT NULL,
                PRIMARY KEY (`id_customer`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}product` (
                `id_product` int unsigned NOT NULL AUTO_INCREMENT,
                `quantity` int NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_product`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}product_shop` (
                `id_product` int unsigned NOT NULL,
                `id_shop` int unsigned NOT NULL,
                `active` tinyint(1) NOT NULL DEFAULT 1,
                `id_category_default` int unsigned NOT NULL DEFAULT 2,
                `visibility` varchar(16) NOT NULL DEFAULT 'both',
                PRIMARY KEY (`id_product`, `id_shop`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}product_lang` (
                `id_product` int unsigned NOT NULL,
                `id_lang` int unsigned NOT NULL,
                `id_shop` int unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                `link_rewrite` varchar(255) NOT NULL,
                PRIMARY KEY (`id_product`, `id_lang`, `id_shop`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}category_lang` (
                `id_category` int unsigned NOT NULL,
                `id_lang` int unsigned NOT NULL,
                `id_shop` int unsigned NOT NULL,
                `link_rewrite` varchar(255) NOT NULL,
                PRIMARY KEY (`id_category`, `id_lang`, `id_shop`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}product_attribute` (
                `id_product_attribute` int unsigned NOT NULL AUTO_INCREMENT,
                `id_product` int unsigned NOT NULL,
                `quantity` int NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_product_attribute`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}product_attribute_shop` (
                `id_product_attribute` int unsigned NOT NULL,
                `id_shop` int unsigned NOT NULL,
                `id_product` int unsigned NOT NULL,
                PRIMARY KEY (`id_product_attribute`, `id_shop`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}product_attribute_combination` (
                `id_product_attribute` int unsigned NOT NULL,
                `id_attribute` int unsigned NOT NULL,
                PRIMARY KEY (`id_product_attribute`, `id_attribute`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}attribute` (
                `id_attribute` int unsigned NOT NULL AUTO_INCREMENT,
                `id_attribute_group` int unsigned NOT NULL,
                PRIMARY KEY (`id_attribute`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}attribute_group` (
                `id_attribute_group` int unsigned NOT NULL AUTO_INCREMENT,
                `position` int NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_attribute_group`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}attribute_lang` (
                `id_attribute` int unsigned NOT NULL,
                `id_lang` int unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                PRIMARY KEY (`id_attribute`, `id_lang`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}attribute_group_lang` (
                `id_attribute_group` int unsigned NOT NULL,
                `id_lang` int unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                PRIMARY KEY (`id_attribute_group`, `id_lang`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}orders` (
                `id_order` int unsigned NOT NULL AUTO_INCREMENT,
                `id_cart` int unsigned NOT NULL,
                PRIMARY KEY (`id_order`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_history` (
                `id_order_history` int unsigned NOT NULL AUTO_INCREMENT,
                `id_order` int unsigned NOT NULL,
                `id_order_state` int unsigned NOT NULL,
                PRIMARY KEY (`id_order_history`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_state` (
                `id_order_state` int unsigned NOT NULL AUTO_INCREMENT,
                `paid` tinyint(1) NOT NULL DEFAULT 0,
                `shipped` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_order_state`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_detail` (
                `id_order_detail` int unsigned NOT NULL AUTO_INCREMENT,
                `id_order` int unsigned NOT NULL,
                `product_id` int unsigned NOT NULL,
                `product_attribute_id` int unsigned NOT NULL,
                PRIMARY KEY (`id_order_detail`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}cart` (
                `id_cart` int unsigned NOT NULL AUTO_INCREMENT,
                `id_shop` int unsigned NOT NULL DEFAULT 1,
                PRIMARY KEY (`id_cart`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}cart_product` (
                `id_cart` int unsigned NOT NULL,
                `id_product` int unsigned NOT NULL,
                `id_product_attribute` int unsigned NOT NULL DEFAULT 0,
                `quantity` int unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_cart`, `id_product`, `id_product_attribute`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8",
        ];

        foreach ($statements as $statement) {
            $this->execute($statement);
        }
    }

    private function truncateAll()
    {
        $tables = [
            'wishlist_product_cart',
            'wishlist_product',
            'wishlist',
            'blockwishlist_statistics',
            'cart_product',
            'cart',
            'order_detail',
            'order_history',
            'orders',
            'order_state',
            'product_attribute_combination',
            'attribute_lang',
            'attribute',
            'attribute_group_lang',
            'attribute_group',
            'product_attribute_shop',
            'product_attribute',
            'product_lang',
            'category_lang',
            'product_shop',
            'product',
            'customer',
        ];

        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $this->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . $table . '`');
        }
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }
}
