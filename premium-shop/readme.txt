=== Premium Shop ===
Contributors: premiumshopstudio
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 8.5
WC tested up to: 10.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, conversion-focused WooCommerce theme. German-first, fully translated into French, Spanish and English.

== Description ==

Premium Shop is a complete WooCommerce theme with an editorial art direction ("Maison"):
warm ivory backgrounds, deep ink, a cognac accent, Fraunces headings and Inter body text
(both self-hosted — no request to Google, GDPR-friendly).

Firewood shop module (Brennholz / Kaminholz):

* "Firewood" product tab: wood species, log length, residual moisture, drying,
  sales unit (RM / SRM / FM / kg / litre), origin, certification.
* Automatic prices: price per unit × volume read from the variation ("2 RM"),
  with two quantity discounts. Products → Firewood prices changes all prices at once.
* Unit price (Grundpreis, PAngV) on cards, product pages and variations.
* Automatic descriptions, key-data chips and a wood data sheet tab.
* Postcode delivery check based on WooCommerce shipping zones; the postcode
  pre-fills the checkout. Checkout question "Accessible for a truck?".
* Needs calculator (kW × hours × days) and RM/SRM/FM converter, FAQ with
  FAQPage structured data. Shortcodes: [ps_delivery_check], [ps_firewood_calculator], [ps_faq].
* One-click sample catalogue (5 species × 3 lengths × 4 quantities, kindling,
  briquettes, box) and CSV import support (Products → Import).

Features:

* Homepage builder in the Customizer: hero, categories (bento grid), popular products,
  campaign banner, new arrivals, offers, bestsellers, benefits, testimonials (genuine
  WooCommerce reviews), brands, newsletter — drag & drop order, show/hide.
* Header: announcement bar (rotating, dismissible), sticky header, categories mega menu,
  live product search, language switcher DE | FR | ES | EN, account, wishlist, side cart.
* Shop: instant AJAX filters (categories, price range, availability, offers, attributes
  with color swatches, brands, rating, search), sorting, pagination, grid/list view,
  mobile filter drawer — all working without JavaScript too.
* Product cards: second image on hover, sale %/new/sold-out badges, quick view, wishlist,
  rating, stock, AJAX add to cart.
* Product page: gallery with zoom, lightbox and thumbnails, product video, "Buy now",
  delivery/returns/warranty/payment box, "Shipping & returns" tab, sticky mobile bar.
* Cart & checkout: free-shipping progress bar, "You may also like", progress steps,
  distraction-free checkout header (classic and block cart/checkout supported).
* My account: dashboard tiles, icon navigation.
* Multilingual: WPML, Polylang, TranslatePress compatible; built-in lightweight switcher
  when no plugin is installed. German is the default language.
* Performance: no jQuery dependency for theme scripts, deferred & conditional assets,
  minified CSS/JS, lazy images, ~16 KB JS.
* Accessibility: skip link, keyboard navigation, focus management in drawers, ARIA.
* Setup assistant (Appearance → Shop setup): pages, menus, languages, image settings.
* Built-in GDPR double opt-in newsletter (or any newsletter plugin shortcode).
* HPOS compatible (uses WooCommerce CRUD APIs only).

== Installation ==

1. Appearance → Themes → Add New → Upload Theme → choose premium-shop.zip → Install → Activate.
2. Install and activate WooCommerce.
3. Appearance → Shop setup: run the steps (pages, menus, languages, images).
4. Appearance → Customize → Premium Shop — Theme options: logo, colors, texts…

== Frequently Asked Questions ==

= How do I add a language (e.g. Italian)? =

Customizer → Premium Shop → Languages → "Languages offered": add "it" (de,fr,es,en,it).
Install Italian in Settings → General. Translate the theme with Loco Translate (or create
languages/it_IT.po/.mo from premium-shop.pot).

= How do I translate my own texts (hero, banners…)? =

With WPML/Polylang: String Translation ("Premium Shop" group). Without plugin, write
"[:de]Text[:fr]Texte[:es]Texto[:en]Text" in the field.

== Changelog ==

= 1.1.0 =
* Firewood shop module and "Firewood" style preset (default).

= 1.0.0 =
* Initial release.

== Credits ==

* Inter — SIL Open Font License 1.1 — https://rsms.me/inter/
* Fraunces — SIL Open Font License 1.1 — https://github.com/undercasetype/Fraunces
* Icons and illustrations: original work, GPLv2 or later.
