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
class WishListTest extends WishlistDatabaseTestCase
{
    public function testAddProductInsertsANewRowAndKeepsCombinationsSeparate()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');

        $this->assertTrue(WishList::addProduct($wishlistId, $customerId, 10, 0, 2));
        $this->assertTrue(WishList::addProduct($wishlistId, $customerId, 10, 4, 1));

        $rows = $this->rows('SELECT `id_product`, `id_product_attribute`, `quantity`, `priority` FROM `' . _DB_PREFIX_ . 'wishlist_product` ORDER BY `id_product_attribute` ASC');
        $this->assertCount(2, $rows);
        $this->assertEquals(10, $rows[0]['id_product']);
        $this->assertEquals(0, $rows[0]['id_product_attribute']);
        $this->assertEquals(2, $rows[0]['quantity']);
        $this->assertEquals(1, $rows[0]['priority']);
        $this->assertEquals(4, $rows[1]['id_product_attribute']);
        $this->assertEquals(1, $rows[1]['quantity']);
    }

    public function testAddProductIncrementsAnExistingCombination()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 2, 1);

        $this->assertTrue(WishList::addProduct($wishlistId, $customerId, 10, 0, 3));

        $rows = $this->rows('SELECT `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product`');
        $this->assertCount(1, $rows);
        $this->assertEquals(5, $rows[0]['quantity']);
    }

    public function testAddProductRemovesTheRowWhenQuantityDropsToZeroOrBelow()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 2, 1);
        $this->insertWishlistProduct($wishlistId, 11, 0, 4, 1);
        $cartId = $this->insertCart();
        $productRowId = (int) $this->rows('SELECT `id_wishlist_product` FROM `' . _DB_PREFIX_ . 'wishlist_product` WHERE `id_product` = 10')[0]['id_wishlist_product'];
        $this->insertWishlistProductCart($productRowId, $cartId, 1);

        $this->assertTrue(WishList::addProduct($wishlistId, $customerId, 10, 0, -2));
        $this->assertTrue(WishList::addProduct($wishlistId, $customerId, 11, 0, -5));

        $this->assertSame([], $this->rows('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'wishlist_product`'));
        $this->assertSame([], $this->rows('SELECT `id_wishlist_product` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`'));
    }

    public function testAddProductCanStoreAZeroQuantityAndRejectsANegativeNewQuantity()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');

        $this->assertTrue(WishList::addProduct($wishlistId, $customerId, 10, 0, 0));
        $this->assertFalse(WishList::addProduct($wishlistId, $customerId, 11, 0, -1));

        $rows = $this->rows('SELECT `id_product`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product`');
        $this->assertCount(1, $rows);
        $this->assertEquals(10, $rows[0]['id_product']);
        $this->assertEquals(0, $rows[0]['quantity']);
    }

    public function testAddProductInsertsADuplicateWhenTheCustomerDoesNotOwnTheExistingRow()
    {
        $ownerId = $this->insertCustomer('Ada', 'Lovelace');
        $otherId = $this->insertCustomer('Grace', 'Hopper');
        $wishlistId = $this->insertWishlist($ownerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 2, 1);

        $this->assertTrue(WishList::addProduct($wishlistId, $otherId, 10, 0, 3));

        $rows = $this->rows('SELECT `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product` ORDER BY `quantity` ASC');
        $this->assertCount(2, $rows);
        $this->assertEquals(2, $rows[0]['quantity']);
        $this->assertEquals(3, $rows[1]['quantity']);
    }

    public function testRemoveProductDeletesTheProductAndItsCartReservation()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $productRowId = $this->insertWishlistProduct($wishlistId, 10, 3, 2, 1);
        $cartId = $this->insertCart();
        $this->insertWishlistProductCart($productRowId, $cartId, 2);

        $this->assertTrue(WishList::removeProduct($wishlistId, $customerId, 10, 3));
        $this->assertSame([], $this->rows('SELECT `id_wishlist_product` FROM `' . _DB_PREFIX_ . 'wishlist_product`'));
        $this->assertSame([], $this->rows('SELECT `id_wishlist_product` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`'));
    }

    public function testRemoveProductReturnsFalseWhenTheWishlistIsMissingOrOwnedBySomeoneElse()
    {
        $ownerId = $this->insertCustomer('Ada', 'Lovelace');
        $otherId = $this->insertCustomer('Grace', 'Hopper');
        $wishlistId = $this->insertWishlist($ownerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 1, 1);

        $this->assertFalse(WishList::removeProduct(999, $ownerId, 10, 0));
        $this->assertFalse(WishList::removeProduct($wishlistId, $otherId, 10, 0));
        $this->assertCount(1, $this->rows('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'wishlist_product`'));
    }

    public function testRemoveProductFromWishlistReturnsFalseWithoutATarget()
    {
        $this->assertFalse(WishList::removeProductFromWishlist(null, null));
    }

    public function testRemoveProductFromWishlistFiltersByProductAndAttribute()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 1, 1, 1);
        $this->insertWishlistProduct($wishlistId, 10, 2, 1, 1);
        $this->insertWishlistProduct($wishlistId, 11, 2, 1, 1);

        $this->assertTrue(WishList::removeProductFromWishlist(10, 2));

        $rows = $this->rows('SELECT `id_product`, `id_product_attribute` FROM `' . _DB_PREFIX_ . 'wishlist_product` ORDER BY `id_product` ASC, `id_product_attribute` ASC');
        $this->assertCount(2, $rows);
        $this->assertEquals(10, $rows[0]['id_product']);
        $this->assertEquals(1, $rows[0]['id_product_attribute']);
        $this->assertEquals(11, $rows[1]['id_product']);
    }

    public function testRemoveProductFromWishlistWithOnlyOneSideDeletesEveryMatch()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $first = $this->insertWishlist($customerId, 'One');
        $second = $this->insertWishlist($customerId, 'Two');
        $this->insertWishlistProduct($first, 10, 1, 1, 1);
        $this->insertWishlistProduct($first, 10, 2, 1, 1);
        $this->insertWishlistProduct($second, 11, 2, 1, 1);

        $this->assertTrue(WishList::removeProductFromWishlist(10, null));
        $remaining = $this->rows('SELECT `id_product`, `id_product_attribute` FROM `' . _DB_PREFIX_ . 'wishlist_product`');
        $this->assertCount(1, $remaining);
        $this->assertEquals(11, $remaining[0]['id_product']);

        $this->assertTrue(WishList::removeProductFromWishlist(null, 2));
        $this->assertSame([], $this->rows('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'wishlist_product`'));
    }

    public function testRemoveProductFromWishlistTreatsZeroAsEmptyAndDeletesEveryRow()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 1, 1, 1);
        $this->insertWishlistProduct($wishlistId, 11, 2, 1, 1);

        $this->assertTrue(WishList::removeProductFromWishlist(0, null));

        $this->assertSame([], $this->rows('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'wishlist_product`'));
    }

    public function testRemoveNonExistingProductAttributesKeepsKnownCombinations()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $productId = $this->insertCatalogProduct('Mug');
        $combinationId = $this->insertCombination($productId, 'Red', 8);
        $this->insertWishlistProduct($wishlistId, $productId, $combinationId, 1, 1);
        $this->insertWishlistProduct($wishlistId, $productId, 99999, 1, 1);

        WishList::removeNonExistingProductAttributesFromWishlist();

        $rows = $this->rows('SELECT `id_product_attribute` FROM `' . _DB_PREFIX_ . 'wishlist_product`');
        $this->assertCount(1, $rows);
        $this->assertEquals($combinationId, $rows[0]['id_product_attribute']);
    }

    public function testCleanupOfAttributeZeroDeletesEveryWishlistProduct()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $productId = $this->insertCatalogProduct('Mug');
        $combinationId = $this->insertCombination($productId, 'Red', 8);
        $this->insertWishlistProduct($wishlistId, $productId, $combinationId, 1, 1);
        $this->insertWishlistProduct($wishlistId, $productId, 0, 1, 1);

        WishList::removeNonExistingProductAttributesFromWishlist();

        $this->assertSame([], $this->rows('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'wishlist_product`'));
    }

    public function testUpdateProductRejectsPriorityOutsideZeroToTwo()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 2, 1);

        $this->assertFalse(WishList::updateProduct($wishlistId, 10, 0, -1, 9));
        $this->assertFalse(WishList::updateProduct($wishlistId, 10, 0, 3, 9));

        $row = $this->rows('SELECT `priority`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product`')[0];
        $this->assertEquals(1, $row['priority']);
        $this->assertEquals(2, $row['quantity']);
    }

    public function testUpdateProductPersistsBoundaryPrioritiesAndQuantity()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 2, 1);
        $this->insertWishlistProduct($wishlistId, 10, 5, 2, 1);

        $this->assertTrue(WishList::updateProduct($wishlistId, 10, 0, 0, 0));
        $this->assertTrue(WishList::updateProduct($wishlistId, 10, 5, 2, 7));
        $this->assertTrue(WishList::updateProduct($wishlistId, 99, 0, 1, 4));

        $rows = $this->rows('SELECT `id_product_attribute`, `priority`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product` ORDER BY `id_product_attribute` ASC');
        $this->assertCount(2, $rows);
        $this->assertEquals(0, $rows[0]['priority']);
        $this->assertEquals(0, $rows[0]['quantity']);
        $this->assertEquals(2, $rows[1]['priority']);
        $this->assertEquals(7, $rows[1]['quantity']);
    }

    public function testExistsRequiresTheCustomerAndTheCurrentShop()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday', ['id_shop' => 1]);

        $this->assertTrue(WishList::exists($wishlistId, $customerId));
        $this->assertFalse(WishList::exists($wishlistId, $customerId + 1));
        $this->assertFalse(WishList::exists(0, $customerId));

        $this->setShopContext(2, 1);
        $this->assertFalse(WishList::exists($wishlistId, $customerId));
    }

    public function testSetDefaultReplacesThePreviousDefault()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $first = $this->insertWishlist($customerId, 'First', ['default' => 1]);
        $second = $this->insertWishlist($customerId, 'Second');

        $this->assertTrue(WishList::isDefault($customerId));
        $this->assertSame($first, WishList::getDefault($customerId));

        $wishlist = new WishList();
        $wishlist->id = $second;
        $wishlist->id_customer = $customerId;
        $this->assertTrue($wishlist->setDefault());

        $this->assertSame($second, WishList::getDefault($customerId));
        $flags = $this->rows('SELECT `id_wishlist`, `default` FROM `' . _DB_PREFIX_ . 'wishlist` ORDER BY `id_wishlist` ASC');
        $this->assertEquals(0, $flags[0]['default']);
        $this->assertEquals(1, $flags[1]['default']);
    }

    public function testDefaultHelpersReturnEmptyValuesWhenNothingIsFlagged()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');

        $this->assertFalse(WishList::isDefault($customerId));
        $this->assertSame(0, WishList::getDefault($customerId));

        $wishlist = new WishList();
        $wishlist->id = $wishlistId;
        $wishlist->id_customer = $customerId;
        $this->assertTrue($wishlist->setDefault());
        $this->assertTrue(WishList::isDefault($customerId));
        $this->assertSame($wishlistId, WishList::getDefault($customerId));
    }

    public function testGetProductsByWishlistIgnoresZeroQuantities()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 0, 1);
        $this->assertFalse(WishList::getProductsByWishlist($wishlistId));

        $this->insertWishlistProduct($wishlistId, 11, 2, 3, 1);
        $products = WishList::getProductsByWishlist($wishlistId);
        $this->assertCount(1, $products);
        $this->assertEquals(11, $products[0]['id_product']);
        $this->assertEquals(2, $products[0]['id_product_attribute']);
        $this->assertEquals(3, $products[0]['quantity']);
    }

    public function testGetAllProductByCustomerFiltersShopAndPositiveQuantity()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $shopOne = $this->insertWishlist($customerId, 'Shop one', ['id_shop' => 1]);
        $shopTwo = $this->insertWishlist($customerId, 'Shop two', ['id_shop' => 2]);
        $this->insertWishlistProduct($shopOne, 10, 0, 2, 1);
        $this->insertWishlistProduct($shopOne, 11, 0, 0, 1);
        $this->insertWishlistProduct($shopTwo, 12, 1, 4, 1);

        $this->assertFalse(WishList::getAllProductByCustomer($customerId + 1, 1));

        $products = WishList::getAllProductByCustomer($customerId, 1);
        $this->assertCount(1, $products);
        $this->assertEquals(10, $products[0]['id_product']);
        $this->assertEquals($shopOne, $products[0]['id_wishlist']);
    }

    public function testGetByTokenRejectsEmptyAndUnsafeTokens()
    {
        foreach (['', null, 'bad<token', 'bad{token', 'bad}token'] as $token) {
            try {
                WishList::getByToken($token);
                $this->fail('Expected an invalid token exception for ' . var_export($token, true));
            } catch (PrestaShopException $exception) {
                $this->assertSame('Invalid token', $exception->getMessage());
            }
        }
    }

    public function testGetByTokenReturnsTheOwnerAndFalseWhenMissing()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $this->insertWishlist($customerId, 'Birthday', ['token' => "O'Hara"]);

        $row = WishList::getByToken("O'Hara");
        $this->assertEquals($customerId, $row['id_customer']);
        $this->assertSame('Birthday', $row['name']);
        $this->assertSame('Ada', $row['firstname']);
        $this->assertSame('Lovelace', $row['lastname']);
        $this->assertFalse(WishList::getByToken('missing-token'));
    }

    public function testCounterStartsAtZeroAndIncrementsOnlyExistingWishlists()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->execute('UPDATE `' . _DB_PREFIX_ . 'wishlist` SET `counter` = NULL WHERE `id_wishlist` = ' . (int) $wishlistId);

        $this->assertSame(0, WishList::getWishlistCounter($wishlistId));
        $this->assertSame(0, WishList::getWishlistCounter(999));
        $this->assertTrue(WishList::incCounter(999));
        $this->assertSame(0, WishList::getWishlistCounter(999));

        $this->assertTrue(WishList::incCounter($wishlistId));
        $this->assertTrue(WishList::incCounter($wishlistId));
        $this->assertSame(2, WishList::getWishlistCounter($wishlistId));
    }

    public function testGetCustomersOrdersByFirstnameAndCachesTheResult()
    {
        $zoeId = $this->insertCustomer('Zoe', 'Zed');
        $adaId = $this->insertCustomer('Ada', 'Lovelace');
        $this->insertWishlist($zoeId, 'Zoe list');
        $this->insertWishlist($adaId, 'Ada list');

        $customers = WishList::getCustomers();
        $this->assertSame(['Ada', 'Zoe'], array_column($customers, 'firstname'));

        $hiddenId = $this->insertCustomer('Bea', 'Hidden');
        $this->insertWishlist($hiddenId, 'Cached out');
        $this->assertSame(['Ada', 'Zoe'], array_column(WishList::getCustomers(), 'firstname'));

        Cache::clean();
        $this->assertSame(['Ada', 'Bea', 'Zoe'], array_column(WishList::getCustomers(), 'firstname'));
    }

    public function testGetByIdCustomerScopesByShopThenGroupAndCaches()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $this->insertWishlist($customerId, 'Zebra', ['id_shop' => 1, 'id_shop_group' => 1]);
        $this->insertWishlist($customerId, 'Alpha', ['id_shop' => 1, 'id_shop_group' => 1]);
        $this->insertWishlist($customerId, 'Other shop', ['id_shop' => 2, 'id_shop_group' => 1]);
        $this->insertWishlist($customerId, 'Other group', ['id_shop' => 3, 'id_shop_group' => 9]);

        $names = array_column(WishList::getByIdCustomer($customerId), 'name');
        $this->assertSame(['Alpha', 'Zebra'], $names);

        $this->insertWishlist($customerId, 'Cached out', ['id_shop' => 1, 'id_shop_group' => 1]);
        $this->assertSame(['Alpha', 'Zebra'], array_column(WishList::getByIdCustomer($customerId), 'name'));

        Cache::clean();
        $this->setShopContext(0, 1);
        $grouped = array_column(WishList::getByIdCustomer($customerId), 'name');
        $this->assertSame(['Alpha', 'Cached out', 'Other shop', 'Zebra'], $grouped);

        Cache::clean();
        $this->setShopContext(0, 0);
        $this->assertCount(5, WishList::getByIdCustomer($customerId));
        $this->assertSame([], WishList::getByIdCustomer($customerId + 50));
    }

    public function testGetAllWishlistsByIdCustomerCountsActiveProductsAndSorts()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $activeProduct = $this->insertCatalogProduct('Active mug');
        $inactiveProduct = $this->insertCatalogProduct('Hidden mug', 5, 0);
        $defaultId = $this->insertWishlist($customerId, 'Zebra', ['default' => 1, 'token' => 'default-token']);
        $otherId = $this->insertWishlist($customerId, 'Alpha', ['token' => 'alpha-token']);
        $foreignId = $this->insertWishlist($customerId, 'Foreign', ['id_shop' => 2, 'token' => 'foreign-token']);
        $this->insertWishlistProduct($defaultId, $activeProduct, 0, 2, 1);
        $this->insertWishlistProduct($defaultId, $inactiveProduct, 0, 4, 1);
        $this->insertWishlistProduct($defaultId, $activeProduct, 1, 0, 1);
        $this->insertWishlistProduct($otherId, $activeProduct, 0, 1, 1);
        $this->insertWishlistProduct($foreignId, $activeProduct, 0, 1, 1);

        $lists = WishList::getAllWishlistsByIdCustomer($customerId);

        $this->assertCount(2, $lists);
        $this->assertSame('Zebra', $lists[0]['name']);
        $this->assertEquals(1, $lists[0]['default']);
        $this->assertEquals(2, $lists[0]['nbProducts']);
        $this->assertSame('default-token', $lists[0]['token']);
        $this->assertSame('Alpha', $lists[1]['name']);
        $this->assertEquals(1, $lists[1]['nbProducts']);
    }

    public function testGetProductByIdCustomerReturnsCatalogDataAndFilters()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $mugId = $this->insertCatalogProduct('Mug', 12);
        $posterId = $this->insertCatalogProduct('Poster', 6);
        $this->insertWishlistProduct($wishlistId, $mugId, 0, 2, 1);
        $this->insertWishlistProduct($wishlistId, $posterId, 0, 0, 2);

        $all = WishList::getProductByIdCustomer($wishlistId, $customerId, 1);
        $this->assertCount(2, $all);
        $byName = [];
        foreach ($all as $product) {
            $byName[$product['name']] = $product;
        }
        $this->assertEquals(2, $byName['Mug']['wishlist_quantity']);
        $this->assertEquals(12, $byName['Mug']['product_quantity']);
        $this->assertEquals(1, $byName['Mug']['priority']);
        $this->assertSame('mug', $byName['Mug']['link_rewrite']);
        $this->assertSame('category-2', $byName['Mug']['category_rewrite']);
        $this->assertSame('', $byName['Mug']['attributes_small']);
        $this->assertArrayNotHasKey('attribute_quantity', $byName['Mug']);

        $filtered = WishList::getProductByIdCustomer($wishlistId, $customerId, 1, $mugId);
        $this->assertCount(1, $filtered);
        $this->assertSame('Mug', $filtered[0]['name']);

        $positive = WishList::getProductByIdCustomer($wishlistId, $customerId, 1, null, true);
        $this->assertCount(1, $positive);
        $this->assertSame('Mug', $positive[0]['name']);

        $this->assertSame([], WishList::getProductByIdCustomer($wishlistId, $customerId, 2));
        $this->assertSame([], WishList::getProductByIdCustomer($wishlistId, $customerId + 1, 1));
        $this->assertCount(2, WishList::getProductByIdCustomer($wishlistId, $customerId, 1, 0));
    }

    public function testGetProductByIdCustomerBuildsAttributeLabels()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $productId = $this->insertCatalogProduct('Shirt', 9);
        $redId = $this->insertCombination($productId, 'Red', 4);
        $largeId = $this->insertCombination($productId, 'Large', 6);
        $this->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'product_attribute_combination` (`id_product_attribute`, `id_attribute`)
            SELECT ' . (int) $redId . ', `id_attribute` FROM `' . _DB_PREFIX_ . 'attribute_lang` WHERE `name` = \'Large\' AND `id_lang` = 1'
        );
        $this->insertWishlistProduct($wishlistId, $productId, $redId, 1, 1);

        $products = WishList::getProductByIdCustomer($wishlistId, $customerId, 1);
        $this->assertCount(1, $products);
        $labels = array_map('trim', explode(',', $products[0]['attributes_small']));
        sort($labels);
        $this->assertSame(['Large', 'Red'], $labels);
        $this->assertEquals(4, $products[0]['attribute_quantity']);

        $orphanId = $this->insertCombination($productId, 'Blue', 2, 2);
        $this->insertWishlistProduct($wishlistId, $productId, $orphanId, 1, 1);
        $shopOne = WishList::getProductByIdCustomer($wishlistId, $customerId, 1, $productId);
        $blue = null;
        foreach ($shopOne as $product) {
            if ((int) $product['id_product_attribute'] === $orphanId) {
                $blue = $product;
            }
        }
        $this->assertNotNull($blue);
        $this->assertSame('', $blue['attributes_small']);
        $this->assertArrayNotHasKey('attribute_quantity', $blue);
    }

    public function testAddBoughtProductRecordsAndAccumulatesCartQuantity()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 3, 5, 1);

        $this->assertTrue(WishList::addBoughtProduct($wishlistId, 10, 3, 70, 2));
        $this->assertTrue(WishList::addBoughtProduct($wishlistId, 10, 3, 70, 2));

        $rows = $this->rows('SELECT `id_cart`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`');
        $this->assertCount(1, $rows);
        $this->assertEquals(70, $rows[0]['id_cart']);
        $this->assertEquals(4, $rows[0]['quantity']);
        $this->assertEquals(5, $this->rows('SELECT `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product`')[0]['quantity']);
    }

    public function testAddBoughtProductRejectsMissingProductsAndQuantitiesAboveTheWishlist()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $this->insertWishlistProduct($wishlistId, 10, 0, 2, 1);

        $this->assertFalse(WishList::addBoughtProduct($wishlistId, 99, 0, 1, 1));
        $this->assertFalse(WishList::addBoughtProduct($wishlistId, 10, 0, 1, 3));
        $this->assertSame([], $this->rows('SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`'));

        $this->assertTrue(WishList::addBoughtProduct($wishlistId, 10, 0, 1, 2));
        $this->assertTrue(WishList::addBoughtProduct($wishlistId, 10, 0, 2, 0));
        $this->assertCount(2, $this->rows('SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`'));
    }

    public function testRefreshWishListReleasesStaleReservationsThatHaveNoOrder()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $productRowId = $this->insertWishlistProduct($wishlistId, 10, 0, 4, 1);
        $cartId = $this->insertCart();
        $this->assertTrue(Db::getInstance()->insert('cart_product', [
            'id_cart' => $cartId,
            'id_product' => 10,
            'id_product_attribute' => 0,
            'quantity' => 2,
        ]), Db::getInstance()->getLastError());
        $this->insertWishlistProductCart($productRowId, $cartId, 2, 'DATE_SUB(NOW(), INTERVAL 7 HOUR)');

        WishList::refreshWishList($wishlistId);

        $this->assertSame([], $this->rows('SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'cart_product`'));
        $this->assertSame([], $this->rows('SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`'));
        $this->assertEquals(6, $this->rows('SELECT `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product`')[0]['quantity']);
    }

    public function testRefreshWishListKeepsARecentReservationAndAnOrderedStaleCart()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $recentProduct = $this->insertWishlistProduct($wishlistId, 10, 0, 4, 1);
        $orderedProduct = $this->insertWishlistProduct($wishlistId, 11, 0, 4, 1);
        $recentCart = $this->insertCart();
        $orderedCart = $this->insertCart();
        $this->assertTrue(Db::getInstance()->insert('cart_product', [
            'id_cart' => $recentCart,
            'id_product' => 10,
            'id_product_attribute' => 0,
            'quantity' => 2,
        ]), Db::getInstance()->getLastError());
        $this->assertTrue(Db::getInstance()->insert('cart_product', [
            'id_cart' => $orderedCart,
            'id_product' => 11,
            'id_product_attribute' => 0,
            'quantity' => 2,
        ]), Db::getInstance()->getLastError());
        $this->insertWishlistProductCart($recentProduct, $recentCart, 2, 'NOW()');
        $this->insertWishlistProductCart($orderedProduct, $orderedCart, 2, 'DATE_SUB(NOW(), INTERVAL 7 HOUR)');
        $this->insertOrder($orderedCart, 1, 1, 11, 0);

        WishList::refreshWishList($wishlistId);

        $this->assertCount(2, $this->rows('SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'cart_product`'));
        $this->assertCount(2, $this->rows('SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`'));
        $quantities = $this->rows('SELECT `id_product`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product` ORDER BY `id_product` ASC');
        $this->assertEquals(4, $quantities[0]['quantity']);
        $this->assertEquals(4, $quantities[1]['quantity']);
    }

    public function testRefreshWishListReturnsSurplusQuantityAndRestoresProductsMissingFromCart()
    {
        $customerId = $this->insertCustomer('Ada', 'Lovelace');
        $wishlistId = $this->insertWishlist($customerId, 'Birthday');
        $surplusProduct = $this->insertWishlistProduct($wishlistId, 10, 0, 10, 1);
        $missingProduct = $this->insertWishlistProduct($wishlistId, 11, 0, 4, 1);
        $surplusCart = $this->insertCart();
        $missingCart = $this->insertCart();
        $this->assertTrue(Db::getInstance()->insert('cart_product', [
            'id_cart' => $surplusCart,
            'id_product' => 10,
            'id_product_attribute' => 0,
            'quantity' => 2,
        ]), Db::getInstance()->getLastError());
        $this->insertWishlistProductCart($surplusProduct, $surplusCart, 5, 'NOW()');
        $this->insertWishlistProductCart($missingProduct, $missingCart, 3, 'NOW()');

        WishList::refreshWishList($wishlistId);

        $quantities = $this->rows('SELECT `id_product`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product` ORDER BY `id_product` ASC');
        $this->assertEquals(13, $quantities[0]['quantity']);
        $this->assertEquals(7, $quantities[1]['quantity']);

        $reservations = $this->rows('SELECT `id_wishlist_product`, `quantity` FROM `' . _DB_PREFIX_ . 'wishlist_product_cart`');
        $this->assertCount(1, $reservations);
        $this->assertEquals($surplusProduct, $reservations[0]['id_wishlist_product']);
        $this->assertEquals(2, $reservations[0]['quantity']);
    }
}
