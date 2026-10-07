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
class StatisticsTest extends WishlistDatabaseTestCase
{
    public function testRemoveProductFromStatisticsReturnsFalseWithoutATarget()
    {
        $this->insertStatistic(10, 1);
        $this->assertFalse(Statistics::removeProductFromStatistics(null, null));
        $this->assertCount(1, $this->rows('SELECT `id_statistics` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics`'));
    }

    public function testRemoveProductFromStatisticsFiltersByProductAndAttribute()
    {
        $this->insertStatistic(10, 1);
        $this->insertStatistic(10, 2);
        $this->insertStatistic(11, 2);

        $this->assertTrue(Statistics::removeProductFromStatistics(10, 2));
        $rows = $this->rows('SELECT `id_product`, `id_product_attribute` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics` ORDER BY `id_product` ASC, `id_product_attribute` ASC');
        $this->assertCount(2, $rows);
        $this->assertEquals(10, $rows[0]['id_product']);
        $this->assertEquals(1, $rows[0]['id_product_attribute']);
        $this->assertEquals(11, $rows[1]['id_product']);

        $this->assertTrue(Statistics::removeProductFromStatistics(10, null));
        $remaining = $this->rows('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics`');
        $this->assertCount(1, $remaining);
        $this->assertEquals(11, $remaining[0]['id_product']);

        $this->assertTrue(Statistics::removeProductFromStatistics(null, 2));
        $this->assertSame([], $this->rows('SELECT `id_statistics` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics`'));
    }

    public function testRemoveNonExistingProductAttributesFromStatisticsKeepsKnownCombinations()
    {
        $productId = $this->insertCatalogProduct('Mug');
        $combinationId = $this->insertCombination($productId, 'Red', 3);
        $this->insertStatistic($productId, $combinationId);
        $this->insertStatistic($productId, 88888);

        Statistics::removeNonExistingProductAttributesFromStatistics();

        $rows = $this->rows('SELECT `id_product_attribute` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics`');
        $this->assertCount(1, $rows);
        $this->assertEquals($combinationId, $rows[0]['id_product_attribute']);
    }

    public function testRemoveProductFromStatisticsTreatsZeroAsARealFilterValue()
    {
        $this->insertStatistic(0, 1);
        $this->insertStatistic(11, 0);
        $this->insertStatistic(11, 2);

        $this->assertTrue(Statistics::removeProductFromStatistics(0, null));

        $afterProductZero = $this->rows('SELECT `id_product`, `id_product_attribute` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics` ORDER BY `id_product_attribute` ASC');
        $this->assertCount(2, $afterProductZero);
        $this->assertEquals(11, $afterProductZero[0]['id_product']);
        $this->assertEquals(0, $afterProductZero[0]['id_product_attribute']);
        $this->assertEquals(2, $afterProductZero[1]['id_product_attribute']);

        $this->assertTrue(Statistics::removeProductFromStatistics(11, 0));

        $remaining = $this->rows('SELECT `id_product`, `id_product_attribute` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics`');
        $this->assertCount(1, $remaining);
        $this->assertEquals(11, $remaining[0]['id_product']);
        $this->assertEquals(2, $remaining[0]['id_product_attribute']);
    }

    public function testCleanupOfAttributeZeroDeletesOnlyThatOrphanStatistic()
    {
        $productId = $this->insertCatalogProduct('Mug');
        $combinationId = $this->insertCombination($productId, 'Red', 3);
        $this->insertStatistic($productId, $combinationId);
        $this->insertStatistic($productId, 0);

        Statistics::removeNonExistingProductAttributesFromStatistics();

        $rows = $this->rows('SELECT `id_product_attribute` FROM `' . _DB_PREFIX_ . 'blockwishlist_statistics`');
        $this->assertCount(1, $rows);
        $this->assertEquals($combinationId, $rows[0]['id_product_attribute']);
    }
}
