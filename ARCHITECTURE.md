# Architecture: blockwishlist

## Purpose

A PrestaShop module (Block Wishlist) that lets customers create and manage product wishlists. Customers can add products to named wishlists, share them via URL, and view wishlist-based purchase statistics in the back office.

## Directory Structure

```
blockwishlist.php                  — Module entry point: extends Module; registers hooks and admin controllers

src/
  Access/
    Customer_Access.php            — Authorisation checks: is the customer the owner of a wishlist?
  Calculator/
    Statistics_Calculator.php      — Computes wishlist statistics (added, converted, abandoned counts)
  Controller/
    Wishlist_Configuration_Admin_Controller.php  — Back-office Symfony controller for module settings
  Database/
    Install.php                    — Creates wishlist DB tables on module install
    Uninstall.php                  — Drops wishlist DB tables on module uninstall
  Grid/
    Data/
      *_Grid_Data_Factory.php      — Data sources for statistics grids (all-time, day, month, year)
    Definition/
      *_Grid_Definition_Factory.php — Column/filter definitions for statistics grids
  Repository/
    Wishlist_Repository.php        — Doctrine repository for Wishlist and WishlistProduct entities
  Search/
    Wish_List_Product_Search_Provider.php  — Integrates wishlists with PrestaShop's product search
  Type/
    Configuration_Type.php         — Symfony Form type for module configuration

views/
  templates/                       — Smarty/Twig templates for front-office wishlist UI
  js/ / css/                       — Bundled front-end assets
```

## Key Design Decisions

- **Symfony admin controller** — uses PrestaShop's Symfony-based back-office framework for the configuration page and statistics grids, rather than the legacy `AdminController`.
- **Statistics grid system** — four time-range grid factories (all-time, current day/month/year) share a `Base_Grid_Data_Factory` base class; each overrides only the date filter.
- **DB lifecycle management** — `Install` and `Uninstall` classes handle DDL explicitly (CREATE/DROP TABLE) so module installation and uninstallation are reversible without Doctrine migrations.
- **Hook-based front-end integration** — product page "add to wishlist" button and customer account wishlist listing are injected via PrestaShop hooks (`displayProductActions`, `displayCustomerAccount`).

## Extension Points

- Add new statistics time ranges by extending `Base_Grid_Data_Factory` and registering a new grid definition factory.
- Override front-office templates in the active theme's `modules/blockwishlist/` directory.
- Extend `Wishlist_Repository` with custom query methods for advanced filtering.

## Dependency Flow

```
Customer adds product to wishlist (front-end Ajax)
  └── blockwishlist front-office controller
        └── Wishlist_Repository::save()
              └── Doctrine ORM → ps_wishlist / ps_wishlist_product tables

Back-office statistics page
  └── Wishlist_Configuration_Admin_Controller
        └── Statistics_Calculator
              └── *_Grid_Data_Factory → PrestaShop Grid component → rendered table
```
