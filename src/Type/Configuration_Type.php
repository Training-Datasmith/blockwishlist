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
namespace Presta_Shop\Module\Block_Wish_List\Type;

use Presta_Shop\Presta_Shop\Core\Constraint_Validator\Constraints\Default_Language;
use Presta_Shop_Bundle\Form\Admin\Type\Translatable_Type;
use Symfony\Component\Form\Abstract_Type;
use Symfony\Component\Form\Extension\Core\Type\Text_Type;
use Symfony\Component\Form\Form_Builder_Interface;
class Configuration_Type extends Abstract_Type
{
    public function build_form(Form_Builder_Interface $builder, array $options): void
    {
        $builder->add('WishlistDefaultTitle', Translatable_Type::class, [
            // we'll have text area that is translatable
            'type' => Text_Type::class,
            'constraints' => [new Default_Language()],
        ])->add('CreateButtonLabel', Translatable_Type::class, [
            // we'll have text area that is translatable
            'type' => Text_Type::class,
            'constraints' => [new Default_Language()],
        ])->add('WishlistPageName', Translatable_Type::class, [
            // we'll have text area that is translatable
            'type' => Text_Type::class,
            'constraints' => [new Default_Language()],
        ]);
    }
}