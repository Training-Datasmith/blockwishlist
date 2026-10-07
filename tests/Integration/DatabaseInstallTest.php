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

use PrestaShop\Module\BlockWishList\Database\Install;
use PrestaShop\Module\BlockWishList\Database\Uninstall;

class DatabaseInstallTest extends WishlistDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Tab::reset();
        Configuration::reset();
    }

    public function testInstallTablesAreIdempotent()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Keep me');

        $this->assertTrue((new Install(new TestTranslator()))->installTables(), Db::getInstance()->getLastError());

        $rows = $this->rows('SELECT `id_wishlist`, `name` FROM `' . _DB_PREFIX_ . 'wishlist`');
        $this->assertCount(1, $rows);
        $this->assertEquals($wishlistId, $rows[0]['id_wishlist']);
        $this->assertSame('Keep me', $rows[0]['name']);
        $this->assertModuleTablesExist();
    }

    public function testRunInstallsConfigurationAndAdminTabs()
    {
        $this->assertTrue((new Install(new TestTranslator()))->run());

        $this->assertSame('My wishlists', Configuration::get('blockwishlist_WishlistPageName', 1));
        $this->assertSame('My wishlists', Configuration::get('blockwishlist_WishlistPageName', 2));
        $this->assertSame('My wishlist', Configuration::get('blockwishlist_WishlistDefaultTitle', 2));
        $this->assertSame('Create new list', Configuration::get('blockwishlist_CreateButtonLabel', 1));

        $this->assertSame(3, Tab::$addCount);
        $classNames = array_column(Tab::$added, 'class_name');
        $this->assertSame([
            'WishlistConfigurationAdminParentController',
            'WishlistConfigurationAdminController',
            'WishlistStatisticsAdminController',
        ], $classNames);
        $this->assertSame('blockwishlist', Tab::$added[1]['module']);
        $this->assertTrue((bool) Tab::$added[1]['active']);
        $this->assertFalse((bool) Tab::$added[0]['active']);
        $this->assertSame('Configuration', Tab::$added[1]['name'][1]);
        $this->assertSame('Configuration', Tab::$added[1]['name'][2]);

        $this->assertTrue((new Install(new TestTranslator()))->installTabs());
        $this->assertSame(3, Tab::$addCount);
    }

    public function testUninstallDropsModuleTablesAndDeletesKnownTabs()
    {
        $this->assertTrue((new Install(new TestTranslator()))->installTabs());
        $this->assertTrue((new Uninstall())->run());

        $this->assertModuleTablesMissing();
        $this->assertCount(3, Tab::$deleted);
    }

    public function testUninstallSkipsTabsThatAreNotInstalled()
    {
        Tab::$ids['WishlistStatisticsAdminController'] = 9;

        $this->assertTrue((new Uninstall())->run());

        $this->assertSame([9], Tab::$deleted);
        $this->assertModuleTablesMissing();
    }

    private function assertModuleTablesExist()
    {
        foreach ($this->moduleTables() as $table) {
            $this->assertNotEmpty(
                $this->rows('SHOW TABLES LIKE "' . _DB_PREFIX_ . $table . '"'),
                $table . ' should exist'
            );
        }
    }

    private function assertModuleTablesMissing()
    {
        foreach ($this->moduleTables() as $table) {
            $rows = Db::getInstance()->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . $table . '"');
            $this->assertTrue(empty($rows), $table . ' should have been dropped');
        }
    }

    private function moduleTables()
    {
        return [
            'wishlist',
            'wishlist_product',
            'wishlist_product_cart',
            'blockwishlist_statistics',
        ];
    }
}
