=== Premium Shop ===
Contributors: premiumshopstudio
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 8.5
WC tested up to: 11.2
Stable tag: 1.8.0
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

= 1.8.0 =
* Order language: the visitor's language is saved on the order (classic and block
  checkout) and on the customer account. Every customer e-mail (received,
  processing, completed, refund, note, invoice, account…) — subject and content —
  is sent in that language, also when triggered later from the dashboard. Shop
  e-mails (new order…) stay in the shop language.
* PDF Invoices & Packing Slips for WooCommerce: documents in the order language.
* Order screen: "Customer language" field to see or change it.
* Shipping method, tax and payment names typed in the shop language are shown in
  the customer's language (catalogue or "[:de]..[:fr].." versions).

= 1.7.2 =
* Updates apply on the first visit after uploading the theme (no need to open the
  dashboard); page translations, footer legal links and contact e-mail are
  re-checked after every update.
* Asset URLs carry the upload time: browsers and server caches always load the
  new styles after an update.
* Reassurance messages shown once, in a tidy 4-box bar at the bottom of pages
  (2 x 2 on mobile) instead of chips + a repeating band.
* Mobile language menu: flag + code only.

= 1.7.1 =
* Language switcher redesigned on mobile: slim pill with a round flag and chevron,
  compact dropdown (small text, flags, check mark on the current language, soft
  opening animation); flags in the mobile menu switcher too.

= 1.7.0 =
* Everything in the visitor's language: menu items, page titles (header, browser
  tab, breadcrumbs), widget titles, category names and WooCommerce setting texts
  (price suffix, store notice) written in the shop language are translated
  through the theme catalogue; labels can also hold "[:de]..[:fr].." versions.
* Pages created by the setup assistant (contact, order tracking, about us,
  shipping & payment, legal notice…) get their title and text in every enabled
  language (Translations box under the page editor).

= 1.6.1 =
* Contact e-mail on the shop's own domain (info@ + site domain): replaces the
  address of another domain filled in by 1.4.1, in the settings, the contact page,
  the footer and the legal pages.
* Footer "Legal" menu: published legal pages (terms of use, privacy policy, legal
  notice, terms, withdrawal) are added automatically when missing.

= 1.6.0 =
* Instant add to cart: the button, the flying photo, the cart counter and the
  message react on click while the server confirms in the background (undone
  with a message if it fails). Simple products on the product page now use one
  quick request instead of a full page load.
* Page headers redesigned: text first on a warm fireside background (generated,
  lightweight), glass chips; a page's featured image replaces the background.
  No more stacked photos before the content.
* Bottom of pages: a scrolling band of reassurance messages and a carousel of
  products (photo, price, add to cart) moving slowly, pausing on hover.
* Photos in content sections are shown in a horizontal row with a soft float.
* Richer buttons: gradient depth, coloured shadow, light sweep on hover.

= 1.5.1 =
* Fix: in French, English and Spanish (built-in language switcher), the quick view,
  live search and AJAX add to cart failed ("Something went wrong"): the language
  parameter re-encoded WooCommerce's AJAX endpoint placeholder.
* Quick view: if it cannot load, the product page opens instead of an error.

= 1.5.0 =
* Privacy policy and terms of use in German, French, English and Spanish, filled
  with the shop's contact details (Swiss FADP / EU GDPR, online shop data, cookies
  actually used). Created on update: WordPress' default privacy draft is replaced,
  a privacy policy you published yourself is kept. Linked in the footer.
* Pages can now be translated too: "Translations" box under the page editor
  (title and text per language), shown with the language switcher.

= 1.4.1 =
* Shop contact details (address, phone, e-mail) filled in when empty: contact page,
  footer and contact form recipient.

= 1.4.0 =
* Add to cart without leaving the page, everywhere (shop, product page, quick view,
  sticky bar): the product photo flies to the cart icon, the button confirms
  ("Added ✓"), a message with a thumbnail offers "View cart". The side cart no
  longer opens by itself (option in Customizer → Shop).
* Illustrated page headers (Customizer → General): warm gradient, rising embers,
  floating photos of your own products, tagline and reassurance chips on pages,
  cart, checkout (photos of the products in the cart), account, contact, wishlist.
* Order tracking page: animated delivery truck. Order received: animated check and
  confetti.
* Home page without a chosen hero image: floating photos of your products.
* Product categories without an image show a photo of one of their products.
* New sections (shortcodes): [ps_features], [ps_delivery_steps], [ps_shipping_info]
  (delivery time, zones and methods from WooCommerce), [ps_payment_methods]
  (enabled gateways), [ps_photo_collage], [ps_cta]. The untouched "About us" and
  "Shipping & payment" placeholder pages are filled with them.

= 1.3.2 =
* Product URLs: "m³" / "m²" in a name give "m3" / "m2" instead of "m%c2%b3".

= 1.3.1 =
* Products screen: warning listing the published products without a price (WooCommerce
  shows no "Add to cart" button for them), with edit links.
* Product importer: the CSV delimiter (";" from Excel) and a non-UTF-8 encoding are
  detected as soon as the file is chosen.
* Product importer: prices, weight and dimensions written "189,00", "1 250,50" or
  "1'250.50" are read correctly (WooCommerce read "189,00" as 18900).

= 1.3.0 =
* Product texts in several languages without a plugin: "Translations" tab (name, short
  description, description per language), translated category names, CSV columns
  "Meta: _ps_name_de" / "_ps_short_de" / "_ps_desc_de"…
* CSV import: columns of French, German and Spanish WooCommerce exports are recognised
  whatever the admin language; "Import placeholder" products get a proper URL when updated
  and can be moved to the trash in one click; firewood data read from product names.
* Contact page template with a ready-to-use GDPR contact form (messages also saved in the admin).
* Order tracking page: status timeline, planned delivery date, carrier and tracking number
  (order screen box), also shown in My account and in the order e-mails.
* Language switcher in the mobile header and at the top of the mobile menu.
* WordPress default widgets are moved out of the shop sidebar on existing sites.

= 1.2.0 =
* Quick view: variable products can be added to the cart (variation form).
* Fix: crafted filter URLs (nested arrays) no longer cause an error 500.
* Block cart: no duplicate recommendations; out-of-stock products are no longer recommended.
* Free-shipping bar: uses the WooCommerce "Free shipping" minimum amount automatically.
* Styles for the store notice, bank details, order highlights, category tiles,
  WooCommerce widgets, loading overlay and WooCommerce blocks inside pages.
* WordPress default widgets go to the blog sidebar on new sites (not under the shop filters).
* Privacy: no emoji images loaded from s.w.org.
* WooCommerce "Products per row" control removed (the theme setting is used).
* Tested with WordPress 7.1 and WooCommerce 11.2.

= 1.1.0 =
* Firewood shop module and "Firewood" style preset (default).

= 1.0.0 =
* Initial release.

== Credits ==

* Inter — SIL Open Font License 1.1 — https://rsms.me/inter/
* Fraunces — SIL Open Font License 1.1 — https://github.com/undercasetype/Fraunces
* Icons and illustrations: original work, GPLv2 or later.
