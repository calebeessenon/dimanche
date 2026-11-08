=== Premium Shop ===
Contributors: premiumshopstudio
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 8.5
WC tested up to: 11.2
Stable tag: 2.3.14
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

= 2.3.14 =
* Cart (blocks) on phones: the line total and the "You save" badge no longer
  go off screen; image on the left, name, price, quantity and total stacked.

= 2.3.13 =
* Site installed in another language than the shop's (e.g. French, then
  switched to German): every text WordPress and WooCommerce saved in that
  language is now shown, and stored, in the shop language.
* Titles of WooCommerce's refund policy page as the language packs really
  write them ("Politique en matière de remboursements et de retours",
  "Richtlinie für Erstattungen und Rückgaben"…), read from the installed
  language packs; also the leftover draft in the page list.
* Payment and shipping method texts ("Virement bancaire", "Livraison
  gratuite"…) and the privacy texts under the checkout and registration
  forms: rewritten in the shop language on update, and shown in the visitor's
  language (classic checkout, account page). Texts you typed are kept.
* Default categories ("Non classé") of products and posts renamed in the shop
  language (same address) and shown in the visitor's language.
* WordPress' untouched example page and first post ("Page d'exemple",
  "Bonjour tout le monde !") taken offline (draft).
* Breadcrumbs of the account sub-pages ("Mein Konto / Mot de passe perdu"),
  meta descriptions of translated pages and products, and the cart block
  heading saved by WooCommerce ("Vous serez peut-être intéressé par…") in the
  visitor's language.

= 2.3.12 =
* Pages created by WooCommerce and WordPress (shop, cart, checkout, my
  account, terms, refund policy, privacy policy) in the visitor's language,
  also when WordPress was installed in another language than the shop's
  ("Validation de la commande" on a German shop). Their stored titles are
  set to the shop language (admin, menus, e-mails) with translations.
* WooCommerce's default category ("Non classé") in the visitor's language.

= 2.3.11 =
* Gaming catalogue: real product views instead of repeated pictures. Each
  product has 5 pictures: front, back, profile or edge (ports, triggers,
  thickness), a 3/4, TV-mode, packaging or open-case view depending on the
  product, and the data sheet with dimensions, weight and EAN barcode.
  New file names, so a CSV re-import replaces the old gallery.

= 2.3.10 =
* Product CSV import: the "GTIN, UPC, EAN, or ISBN" and "Brands" columns are
  now mapped automatically (WooCommerce left them unmapped, so GTIN and brand
  were lost when importing the gaming CSV), in English, German, French and
  Spanish.
* Gaming edition: no firewood wording left in the cart ("the warmth is on its
  way"), in the terms of use (example "appearance of wood, a natural
  product"; existing pages are updated) or in the theme description shown
  under Appearance > Themes.
* Dates in the shop language follow it too ("8. November 2026" instead of
  "November 8, 2026" on a site installed in English and switched to German),
  on the site and in e-mails.
* Customizer preview no longer reloaded in the browser's language.
* Product page (desktop): the sticky summary column no longer slides over
  the tabs ("Additional information") and related products when it is
  taller than the gallery.

= 2.3.9 =
* Gaming catalogue: 4 pictures per product (main picture, close-up, second
  view, data sheet with package dimensions, weight and EAN barcode), GTIN,
  shipping weight, package dimensions, brand and technical attributes, and
  a technical data table in every description (DE/FR/EN/ES). The CSV and the
  sample catalogue both include them.
* Product attributes of the gaming catalogue are shown in the visitor's
  language.

= 2.3.8 =
* Gaming edition: no firewood features left. The "Firewood" product data tab,
  the firewood price tool, the postcode check, the truck question at
  checkout and the firewood calculator are no longer loaded; the Customizer
  section becomes "Shop style & FAQ" without firewood settings; the setup
  assistant no longer offers the firewood catalogue; the order tracking page
  no longer mentions wood.

= 2.3.7 =
* Gaming edition: "Returns & refunds" page (DE/FR/EN/ES) with precise time
  limits for returns without a reason, faulty items, wrong items or order
  mistakes and damaged parcels, the return procedure and refund times per
  payment method. Also set as WooCommerce's refund and returns page.
* Shipping page: delivery times table (cut-off 2 pm, standard, express, bank
  transfer) and shipping costs read from WooCommerce → Settings → Shipping.
* Links "Delivery: times & costs" and "Returns & refunds" on product pages,
  in the cart, at checkout and in the footer.

= 2.3.6 =
* WooCommerce buttons and texts ("Add to cart", "Sale!", cart, checkout,
  account…) in the visitor's language even when WooCommerce's language pack
  is missing on the site; the missing language packs are also downloaded in
  the background after the update.

= 2.3.5 =
* Gaming edition, holiday mode: the garland of lights at the top of the page
  is no longer hidden behind the WordPress admin bar on computers (logged-in
  visitors), and still glows softly on computers set to reduce animations.

= 2.3.4 =
* Gaming edition: NeoPlay logo (white, header and footer) and browser icon
  built in, used while no logo / site icon is chosen in Customizer → Site
  Identity.

= 2.3.3 =
* Privacy policy and terms of use follow the contact details (company,
  address, e-mail, phone, city) in every language: updated with the theme and
  after each Customizer save. Pages edited by hand keep their text; only the
  old e-mail address, phone number and company name are replaced.
* Gaming edition: company name NeoPlay by default; contact details copied from
  the firewood shop (warmeholz e-mail) are replaced by info@neoplay.ch.

= 2.3.2 =
* Gaming edition (NeoPlay): contact details filled in by default: 9 route du
  Beauregard, 1180 Rolle, +4175731714, info@neoplay.ch (contact page, footer,
  legal pages). Values entered in the Customizer still win.

= 2.3.1 =
* First visit in the browser's language (FR, ES, EN; other languages fall back
  to English): one reload at most, never for search engines, the language
  switcher always wins. The choice now also works behind page caches that
  serve the same page to everyone (remembered in the "ps_lc" cookie).
* E-mails: the shop's German "new order" e-mail for an order placed in another
  language shows the product names, payment method, truck question and
  WooCommerce's default texts in German; dates follow the visitor's language
  ("5 novembre 2026").
* Sample firewood catalogue: product names, descriptions, quantities ("1 stère")
  and filter names are now created in every language.
* Footer "Legal" menu: terms of use added by the setup assistant, and legal
  pages are linked as soon as they are published.
* Product page: "Add to cart" / "Buy now" no longer break over two lines in
  French and Spanish.
* Gaming style: no horizontal scroll caused by the tilted marquee; no PHP
  notice during the CSV product import.

= 2.3.0 =
* Gaming catalogue rewritten for the holiday season: 26 products (PlayStation 5
  Slim / Pro / Portal, DualSense colours and Edge, Nintendo Switch 2, bundle,
  OLED, Lite, Pro Controller, Joy-Con 2, JBL Flip 6 / Go 4 speakers, headset,
  gift cards, games, graphics cards) in 6 categories, with full descriptions in
  DE/FR/EN/ES and holiday prices; 22 new original illustrations.
* Ready-to-import CSV (dist/gaming-produits-import.csv): image column uses the
  file names of the theme's illustrations, resolved automatically on import.
* New gaming copy: hero words, platform band, gift finder, holiday campaign,
  about text and three new FAQ answers (gift for a child, gift cards, gift
  message).

= 2.2.0 =
* Gaming style: lighter, friendlier midnight-indigo palette and lighter
  product illustrations.
* Holiday season mode (Customizer → Style preset → "holiday season mode", on by
  default): festive texts in 4 languages, blinking garland, snowfall with an
  on/off switch, holiday tag and gift boxes in the hero, "Gift ideas for every
  budget" finder, live countdown to Christmas, ornaments on product cards,
  confetti when adding to the cart, sparkle trail, jingling cart icon, snowy
  footer. Reduced-motion friendly.

= 2.1.0 =
* "Gaming & tech" style redesigned with its own identity: dark neon palette,
  Space Grotesk headings, glass surfaces, gradient buttons and neon frames.
* New gaming homepage hero: interactive particle field, aurora, moving neon
  grid, rotating platform names (typing effect), animated counters and a 3D
  stage of floating products following the mouse.
* Platform marquee band, animated category frames, 3D tilt and shine on product
  cards, deal countdown in the campaign banner, magnetic buttons, cursor glow,
  scroll progress bar, blur reveal animations. Everything respects "reduce
  motion".
* Product illustrations on a dark neon studio background.

= 2.0.0 =
* New style preset "Gaming & tech" for consoles (Nintendo, PlayStation 5),
  graphics cards and accessories: vivid violet/neon colors, neon page headers,
  modern Inter typography, gaming texts and FAQ in DE/FR/ES/EN, firewood tools
  switched off.
* Bundled original illustrations (hybrid and handheld consoles, console tower,
  controllers, graphics cards, headset, games, hero, share image).
* Setup assistant: "Create the sample gaming catalogue" — 4 categories and 12
  products with images, prices (incl. deals) and texts in every language.
* Separate package premium-shop-gaming.zip with the gaming style by default.
* The Warmeholz contact details are only filled in on warmeholz.ch.

= 1.9.9 =
* New font choice "Cambria" for headings and text (Customizer → Typography),
  applied to the whole site on update. Uses Cambria when installed on the
  visitor's device, else the bundled Caladea (free font with the same design
  and proportions, SIL OFL), so every phone and computer sees the same look.

= 1.9.8 =
* Texts saved inside the cart blocks when the page was created ("Dein Warenkorb
  ist gerade leer!", "Neu im Shop"…) are shown in the visitor's language.
* Checkout truck-access question: "Please choose" instead of the awkward
  "Select a Is the unloading point…?".

= 1.9.7 =
* Payment method descriptions and instructions saved with WooCommerce's default
  text in any language (e.g. the bank transfer text in French) are shown in the
  visitor's language, using WooCommerce's own translations.

= 1.9.6 =
* Fix: shipping method names stayed in the first language seen (rates kept in
  the customer session). They are now translated when read and recalculated
  after a language change — classic and block cart / checkout.
* Payment method names, descriptions and instructions are translated in the
  block checkout too (Überweisung → Virement, Nachnahme → Paiement à la livraison…).

= 1.9.5 =
* Site slogan (title, link previews) in the visitor's language: "[:de]…[:fr]…"
  versions, else the translated homepage hero line.

= 1.9.4 =
* Shipping / payment names and menu labels are recognised whatever language they
  were typed in (e.g. "Livraison express" on a German shop) and shown in the
  visitor's language. More common names in the catalogue (express, standard,
  free delivery, flat rate, local pickup, crane truck, pallet, bank transfer,
  cash on delivery…).

= 1.9.3 =
* Footer logo: no more white box. The logo is shown on a light card, or use the
  new Customizer option "Footer logo" for a light version.

= 1.9.2 =
* Link previews (WhatsApp, Facebook, LinkedIn, X): Open Graph title, description
  and image (product / page photo, else a bundled share image or the one chosen
  in Customizer → Homepage hero → Link preview image). Skipped when an SEO
  plugin is active.
* Homepage description and title no longer repeat the shop name when the slogan
  is empty or equal to it.

= 1.9.1 =
* Stylesheets and scripts are also served under a name that changes with their
  content (assets/build), so host caches that ignore "?ver=" (LWS Varnish) can
  no longer serve an old file after an update (unstyled carousel buttons).

= 1.9.0 =
* Homepage hero carousel: after the main hero, automatic slides for the current
  offers and the main categories, each with photos of its own products (soft
  crossfade, animated texts, progress dots, arrows, swipe, pauses on hover;
  no autoplay with reduced motion). Customizer → Homepage hero → Hero carousel.
* Product rails on the homepage glide one card at a time and show a progress line.
* Bottom of pages: the product cards are replaced by a ribbon of small landscape
  photos gliding in two rows (product photos linking to their product, plus
  ambience photos).
* Footer: payment methods shown as small logos (Visa, Mastercard, PayPal, Klarna,
  TWINT, PostFinance, invoice, prepayment…).

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
