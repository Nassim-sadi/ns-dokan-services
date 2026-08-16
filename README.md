# NS Dokan Services

Adds a **"Service provider"** (`Prestataire de services`) vendor type to Dokan: service shops, service listings, contact CTA and a dedicated services stores listing.

- Contributors: Nassim-sadi
- Tags: dokan, woocommerce, marketplace, services, vendors
- Requires at least: 6.0
- Tested up to: 7.0.4
- Requires PHP: 7.4
- License: GPLv2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Description

NS Dokan Services turns your Dokan marketplace into a hybrid store / services marketplace:

- **Two vendor types** — vendors pick "Store" or "Service provider" at registration.
- **Service listings** — service products are tagged automatically, hidden from shop/search and made unpurchasable.
- **Call / Email CTA** — replaces the add-to-cart button on service listings.
- **Services shops page** — assign any page as the services stores listing; service shops are hidden from other store listings.
- **Reusable services loop** — `[cds_services]` shortcode and `cds_get_service_products()` helper.
- **Translation ready** — text domain `camalg-services`, `.pot` file included, Polylang-aware page detection, `wpml-config.xml` for WPML.
- **French Dokan dashboard** — the official Dokan French translation (`.mo`/`.po` + React JSON files) is bundled and auto-installed on activation, so the whole vendor dashboard renders in French (PHP and React parts).

## Installation

1. Upload the `ns-dokan-services` folder to `/wp-content/plugins/`.
2. Activate the plugin through the *Plugins* screen in WordPress.
3. Go to **WooCommerce → NS Dokan Services** and assign the page that should list service shops.
4. Existing vendors are backfilled as "Store" on activation; the choice is made per-vendor at registration.

## Frequently Asked Questions

### Why are my services invisible on the shop page?

Service listings are tagged with `_dokan_listing_type = service` and excluded from shop, archives and search (all toggled in **WooCommerce → NS Dokan Services**). They appear on the page you assign as the services shops page.

### Can a vendor switch between Store and Service provider?

Not in v1. The choice is made at registration.

## Changelog

### 1.4.1

- Fix the "Services shop listing type" setting (show all / products only / services only) being dropped on every settings save — it now persists correctly.
- Add uninstall cleanup: plugin options and vendor-type / commission user meta are removed when the plugin is deleted.

### 1.4.0

- Admin-controlled commission: new "Disable commission for services" toggle pins service-vendor commission to 0% (fixed) so service providers keep 100% of their sales; store vendors are never affected.
- Simplify the service product form: hide product type, digital options, inventory, brands and the products-listing filters for service vendors; fix hidden-fields bug on the products listing page.
- Translate the "You Earn" box and additional dashboard strings to French.
- Remove the "Restrict service-vendor dashboard" option.
- Fix corrupted sidebar text (e.g. `Paramu00e8tres`): WordPress was stripping the backslash from `\uXXXX` JSON escapes when saving settings. Strings are now stored as plain UTF-8 and existing data is repaired automatically on upgrade.

### 1.1.0

- Bundle the official Dokan French translation (`.mo`/`.po` + JSON for the React dashboard) inside the plugin and auto-install it on activation.
- Add a "Dokan translations" section in the settings page with an install/refresh button.
- French fixes for the React dashboard: "All" → "Tout" and "Products"/"Product" → "Services"/"Service" for service vendors.
- Translate the Dokan withdraw method warning strings.

### 1.0.0

- Initial release.
