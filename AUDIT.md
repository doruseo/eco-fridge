# AUDIT — Migrare eco-fridge (ACF/Types → Gutenberg)

> **Sursă:** site migrat pe serverul Hostinger propriu · `eco-fridge.o2dev.de`
> **Cale server:** `/home/u510186744/websites/TvH4HuG7s/public_html` (accesat read-only prin `ssh hostinger-ecofridge`)
> **Data extragerii:** 2026-07-12 · **Server nemodificat** (doar `mysqldump`/`SELECT`/`tar` prin stdout, zero scriere)
> **Livrabile locale:** `source-dump/db/db.sql.gz` (162 MB gzip / 276 tabele, verificat `gzip -t` + „Dump completed"), `source-dump/theme/` (temele `eco-fridge` + `astra`), `source-dump/meta/` (liste, inventare, query-uri), `source-dump/wp-config.redacted.php`
>
> **⚠️ Acesta este DOAR auditul. Niciun cod de construcție nu a fost scris. Aștept aprobarea ta înainte de Faza 2.**

---

## 0. Descoperirea cea mai importantă (rezolvă enigma „nu arată ca originalul")

Există o **contradicție de stare** pe site-ul migrat:

| Sursă | Temă activă |
|---|---|
| `wp_options.template` / `wp_options.stylesheet` (starea reală salvată) | **`eco-fridge`** (tema originală custom) |
| Runtime efectiv (`wp theme list --status=active`) | **`astra`** (goală) |
| `wp_options.active_plugins` | doar stack-ul nou: `astra-addon`, `essential-blocks`, `ultimate-addons-for-gutenberg` (Spectra), `litespeed-cache`, `migrate-guru` |

**Interpretare:** conținutul, câmpurile custom, meniurile și setările sunt cele ale site-ului original (tema `eco-fridge`, câmpuri **Toolset Types**), dar runtime-ul rulează Astra fără ca acel conținut să fie exprimat în blocuri. De aceea site-ul „nu arată ca originalul": **datele originale există, dar stratul de prezentare (tema PHP + Types) a fost dezactivat, iar conținutul NU a fost încă reconstruit în Gutenberg.** Exact ce urmează să facem.

**Corecție factuală față de brief-ul tău:** site-ul **NU folosește ACF**. Câmpurile custom sunt gestionate de **Toolset Types** (plugin `types` 3.2.5, funcții `types_render_field`, chei meta `wpcf-*`). Nu există niciun grup ACF în DB (`acf-field-group` = 0). Restul brief-ului rămâne valabil — doar sursa câmpurilor diferă (Types în loc de ACF).

---

## 1. STACK

| Componentă | Versiune (sursă) | Țintă | Observație |
|---|---|---|---|
| WordPress core | **7.0** (copie migrată) · original live rulează WP **5.x/5.6** | 7.x latest | OK |
| PHP (copie migrată) | **7.4.33** · original live pe **7.2.34** | 8.3 (wp-env) / 8.5 dorit | brief-ul spunea 7.2 (corect pt. original) |
| MySQL/MariaDB | MariaDB (Hostinger) | 8.x / MariaDB 10.6+ | 276 tabele, 756 MB |
| Temă originală | **eco-fridge** (custom, bază „Bones") | Astra Pro + temă copil | sursa de design |
| Temă runtime | astra 4.13.6 | Astra Pro 4.13.x | deja instalată |
| WooCommerce | **3.5.10** (INACTIV) | Woo latest | vezi Riscuri — schema veche |
| Câmpuri custom | **Toolset Types 3.2.5** | blocuri Gutenberg / meta native | 16 câmpuri active |

### Plugin-uri — clasificare (57 total; listă completă în `source-dump/meta/plugins-list.txt`)

**ACTIVE (5) — stack-ul nou țintă, se păstrează:**

| Plugin | Rol | Verdict |
|---|---|---|
| `astra-addon` 4.13.5 | Astra Pro | **păstrează** (mentenanță activă) |
| `ultimate-addons-for-gutenberg` 2.19.29 | **Spectra** | **păstrează** (cerință) |
| `essential-blocks` 6.3.0 | Essential Blocks | **păstrează** (cerință) |
| `litespeed-cache` 7.8.1 | cache | păstrează (server LiteSpeed) |
| `migrate-guru` 5.65 | migrare | temporar; se elimină după |

**INACTIVE — moștenirea site-ului original (relevante pentru replicare):**

| Plugin | Rol | Verdict la migrare |
|---|---|---|
| `woocommerce` 3.5.10 | magazin | **înlocuit** cu Woo latest (rescriere schemă) |
| `types` (Toolset) 3.2.5 | CPT + câmpuri custom | **înlocuibil nativ** → Gutenberg + meta Woo |
| `wordpress-seo-premium` 9.7 + `wpseo-woocommerce` | Yoast SEO | înlocuiește cu Yoast/RankMath modern |
| `contact-form-7` 4.7 + `wpforms-lite` 1.8.1 | formulare | consolidează pe UNUL (decizie ta) |
| `tablepress` 1.9.1 (186 tabele) | tabele spec | păstrează SAU convertește în blocuri |
| `sliderpro` 4.1.1 | slider homepage `[sliderpro id=1]` | **înlocuit** cu slider Spectra/EB |
| `logos-showcase` 1.7.9 (CPT `lshowcase`, 15) | carusel logo-uri | înlocuibil cu bloc |
| `relevanssi` 4.1.2 | căutare | opțional |
| `yith-woocommerce-product-add-ons` 1.5.17 | opțiuni produs (`check_doors`) | evaluează necesitate |
| `woocommerce_lloydscardnetcw` 2.0.245 | gateway plată Cardnet (Lloyds) | **decizie ta** — mai e folosit? |
| `woo-checkout-field-editor-pro`, `woo-multistep-checkout`, `woocommerce-menu-bar-cart`, `woo-product-images-slider`, `woocommerce-upsells-shortcode`, `woo-product-feed-pro`, `woocommerce-product-feeds`, `woocommerce-jetpack` | extensii Woo | reevaluează 1 câte 1 la Woo modern |
| `better-wp-security` (iThemes) 7.2, `matomo`, `google-site-kit`, `all-in-one-seo-pack`, `all-in-one-wp-migration`, `classic-editor`, `tinymce-advanced`, `health-check`, `regenerate-thumbnails`, `webp-converter-for-media`, `simple-301-redirects`(+addon), `post-types-order`, `simple-taxonomy-ordering`, `menu-image`, `remove-category-url`, `magic-liquidizer-responsive-table`, `userfeedback-lite`, `wp-showhide`, `show-hidecollapse-expand`, `simple-banner`, `order-import-export-for-woocommerce`, `better-search-replace`, `akismet`, `hello`, `post-type-switcher`, `wp-custom-taxonomy-image` | utilitare diverse | **abandonate / redundante** — nu se migrează implicit |
| `elementor` (rămășiță: 1 `elementor_library`) | — | curăță |

**must-use:** `hostinger-h5g-plugin`, `hostinger-preview-domain` · **dropins:** `object-cache.php` (LiteSpeed), `maintenance.php`

---

## 2. CÂMPURI CUSTOM — inventar exhaustiv (Toolset Types, nu ACF)

**Grupuri de câmpuri (`wp-types-group`):** 5 publicate + 2 draft (+ 1 grup de termeni).

| Grup (ID) | Status | Se aplică pe | Câmpuri |
|---|---|---|---|
| Extra product info (65) | publish | `product` | `features`, `from-price`, `model-dimensions`, `spec-table`, `spec-table-2` |
| Brochure info (544) | publish | CPT `brochure` | `brochure-link`, `brochure-aditional-content` |
| Video info (558) | publish | CPT `video` | `youtube-id-number` |
| Additional content (567) | publish | `page` | `content-2` (`content-3` definit) |
| Graded unit info (653) | publish | „graded units" | `graded-unit-price/-original-price/-model/-damage/-dimensions/-shelves/-temperature/-weight` |
| Additional Content — term group (711) | publish | taxonomia `range` | `range-content-2` (termmeta) |
| 2nd description (7469), bottom content (7479) | **draft** | — | neutilizate |

**Toate câmpurile `wpcf-*` cu counturi reale (din `wp_postmeta`) — sursă `source-dump/meta/db-audit-queries.txt`:**

| Câmp (meta_key) | Tip Types | Posturi cu valoare | Consumat în template |
|---|---|---|---|
| `wpcf-features` | wysiwyg | **141** | `single-product/meta.php` |
| `wpcf-from-price` | textfield | **141** | archive-product, taxonomy-range, markets |
| `wpcf-model-dimensions` | textfield | **135** | `single-product/price.php` |
| `wpcf-spec-table` | wysiwyg | **102** | `single-product.php` |
| `wpcf-spec-table-2` | wysiwyg | **51** | (secundar) |
| `wpcf-content-2` | wysiwyg | **9** | `page.php`, `markets.php` |
| `wpcf-youtube-id-number` | textfield | **8** | `page.php` (iframe YouTube) |
| `wpcf-brochure-link` | file | **7** | `page.php` (link broșură) |
| `wpcf-graded-unit-*` (8 câmpuri) | textfield | **6–9** fiecare | `single-graded.php` |
| `range-content-2` (termmeta) | wysiwyg | pe termeni `range` | `taxonomy-range.php` |

**Câmp non-Types:** `check_doors` — **4353 rânduri** în `wp_postmeta`. Nu e Types, nu apare în template-uri; foarte probabil opțiune produs de la `yith-woocommerce-product-add-ons` (stocată și pe comenzi). **De confirmat** dacă are valoare de business sau e reziduu.

---

## 3. CONȚINUT (din DB — `db-audit-queries.txt`)

**Post types (toate statusurile):**

| Post type | Count | Note |
|---|---|---|
| `product` | 131 publish + 8 draft + 4 private = **143** | toate **`simple`** (0 variable) |
| `shop_order` | **5.931** | cancelled 1899, on-hold 1723, processing 1539, completed 661, pending 108, refunded 1 |
| `page` | **25** publish | vezi lista jos |
| `post` (blog) | 107 publish | + 2 draft, 2 auto-draft |
| `attachment` | **987** | (dar 22.357 fișiere pe disc — vezi Media) |
| `revision` | **2.842** | balast — de curățat |
| `tablepress_table` | 186 | tabele TablePress |
| `nav_menu_item` | 95 | 3 meniuri |
| CPT `brochure` | 6 · CPT `video` | 8 · CPT `lshowcase` | 15 (logo-uri) |
| `wpcf7_contact_form` | 2 | formulare CF7 |
| `elementor_library` 1, `amp_validated_url` 1 | reziduuri |

**Cele 25 de pagini** (toate cu template `default` — vezi Riscuri; niciun template PHP nu mai e asignat):

`https-eco-fridge-co-uk` (ID 7, **front page**, 8 KB content) · `about-us` (17) · `markets` (19, **content gol** — părinte pentru 8 sub-pagini) · `support` (33) · `contact-us` (35) · `privacy-cookies-policy` (598) · `eco-fridge` (612) · `graded-units` (636) · `shop`/`cart`/`checkout`/`my-account` (2496–2499, Woo) · `terms-conditions` (2808) · `cookie-policy-uk` (3675) · `we-are-open-for-business` (7136) · `returns` (10921) · `woo_lloydscardnetcw` (2858, gateway)
**Sub-paginile „markets" (parent 19):** `coffee-shops`, `sports-clubs`, `restaurants`, `airports`, `forecourts`, `convenience-stores`, `delis-and-butchers`, `display-freezers`.

**Taxonomii (nume · nr. termeni):**
`product_cat` **27** · `range` **44** (gama de frigidere — cheie) · `market` **8** (segmente piață) · `ranges` 5 (legacy, duplicat de `range`) · `condition` 2 (nou/graded) · `product_tag` 164 · atribute Woo: `pa_model` 86, `pa_warranty` 2, `pa_castors` 2, `pa_removal-disposal` 2 · balast: `yst_prominent_words` 3497 (Yoast), `amp_validation_error` 82.

**Meniuri:** `Main` (**83** iteme), `Display Cabinets` (6), `Storage Cabinets` (6).

**Produse Woo:** 143, toate `simple`, monedă **GBP**, țară **GB**. Meta ACF/Types purtat de produse: `features`, `from-price`, `model-dimensions`, `spec-table`, `spec-table-2` (+ eventual `check_doors`). Pagini Woo mapate: shop 2496, cart 2497, checkout 2498, my-account 2499.

**Identitate:** `blogname` = **ECO-Fridge Ltd**, tagline = „Display and Catering Commercial Refrigeration Equipment", front page statică = ID 7.

---

## 4. TEMA `eco-fridge` — reverse engineering

Temă custom pe bază **Bones** (`library/bones.php`). Arbore complet: `source-dump/theme/eco-fridge/`.

**Înregistrare CPT/taxonomii:** NU în temă — `functions.php:15` are includerea `custom-post-type.php` **comentată** (fișierul e doar exemplul Bones `custom_type`, neutilizat). CPT-urile (`brochure`, `video`, graded) și taxonomiile (`range`, `ranges`, `market`, `condition`) sunt înregistrate de **Toolset Types** (opțiuni `wpcf-custom-types`, `wpcf-custom-taxonomies` — salvate în `source-dump/meta/types-definitions.json`).

**Template-uri PHP și ce consumă fiecare:**

| Template | Rol | Câmpuri Types / secțiuni |
|---|---|---|
| `header.php` | antet global | logo, meniu `nav-main`, panou căutare; **home:** slider `do_shortcode([sliderpro id=1])`; **non-home:** banner „Call us today 01280 811411" + products.png; **GTM-NFMC9Z3**; jQuery **1.4.2** (CDN), Font Awesome 4.6 |
| `footer.php` | subsol global | adresă „18 Top Angel, Buckingham Industrial Park… MK18 1TH", tel 01280 811411, `sales@eco-fridge.co.uk`, linkuri Privacy/Returns, credit „Web Design by Ambrit" |
| `page.php` | pagină standard | `content-2` (wysiwyg), `brochure-link`, `youtube-id-number` (iframe) |
| `page-two-column.php` | **Template Name: Two column** | layout 2 coloane |
| `page-three-column.php` | **Template Name: Three column** | layout 3 coloane |
| `markets.php` | listare segmente piață | **hardcodat** ~20 blocuri `from-price` + `content-2` (rigid — de reconstruit ca listă dinamică) |
| `page-testimonials.php` | testimoniale | `pdf`, `testimonial-quote`, `testimonial-cite` (câmpuri neactive acum) |
| `graded-units.php` / `single-graded.php` | unități graded (second-hand) | tabel spec: `graded-unit-*` (model/damage/dimensions/shelves/temperature/weight/price) |
| `taxonomy-range.php` / `taxonomy-ranges.php` / `taxonomy.php` | arhive gamă | grilă produse + `from-price` (logică „£POA"), `range-content-2` (termmeta) |
| `archive-product.php` | shop/arhivă produse | grilă produse + `from-price` |
| `single.php`, `archive.php`, `search.php`, `404.php`, `sidebar.php`, `comments.php` | standard blog | — |

**Hooks / filtre notabile (`functions.php`):** suport WooCommerce; **elimină tab-urile produs** (description/reviews/additional_information); text T&C checkout custom (link `terms-conditions` + `Manual.pdf`); înlocuiri string-uri Woo („Ship to different address" → „CLICK HERE TO ADD A DIFFERENT DELIVERY ADDRESS"); thumbnails galerie 150×150; căutare **doar după titlu**; fonturi Google **Open Sans / Arimo / Oswald**; ascunde meniuri admin. **Fără `add_shortcode` propriu** (shortcode-urile vin din plugin-uri: `[sliderpro]`, `[woocommerce_product_upsells]`).

**Override-uri WooCommerce (în `eco-fridge/woocommerce/`) — fiecare vs. default:**

| Fișier override | Diferență față de default Woo |
|---|---|
| `single-product.php` | „Back to index" pe taxonomia `range` (caz special graded-units); `woocommerce_content()`; adaugă `spec-table`, notă „Trade prices/Free delivery", `[woocommerce_product_upsells]`, `spec-table-notes` (livrare/garanție hardcodat), **paragraf T&C imens hardcodat** |
| `single-product/meta.php` | afișează `features` (wysiwyg) în locul meta implicit |
| `single-product/price.php` | adaugă `model-dimensions` lângă preț/SKU |
| `single-product/title.php`, `product-image.php`, `add-to-cart/simple.php`, `add-to-cart/variable.php` | ajustări markup |
| `archive-product.php` | grilă custom cu `from-price` + `range-content-2` |
| `emails/email-styles.php` | stiluri email custom |
| _reziduuri de curățat:_ `Xsingle-product.php`, `woocommerce.zip`, `woocommerce - copied from server 110925/` | copii/backup-uri — a NU se migra |

---

## 5. FRONTEND

**Paletă (din `library/scss/partials/_variables.scss`):**
- Brand: **`#70b036`** (verde lime) · fundal `#fcfcfc` · text `#333`
- Alerte: yellow `#ebe16f`, red `#cc0000`, green `#009933`, blue `#0066cc`
- Meta `#9fa6b4`, borduri `#eaedf2`, divider `#eee`, stripe tabel `#f8f9fa`
- (reziduu: `msapplication-TileColor #f01d4f` în header — probabil dintr-o temă anterioară)

**Tipografie:** Google Fonts **Open Sans** (400/700), **Arimo** (400/700), **Oswald** (400/700). Icons: Font Awesome 4.6.

**Breakpoints (SCSS `library/scss/breakpoints/`):** 481up, 768up, 1030up, 1240up (+ retina `2x`). Grilă custom 12 coloane (`$col:5% $gutter:3.33%`).

**Secțiuni vizuale pe șablon (ordine):**
- **Homepage:** header(logo+meniu+căutare) → slider SliderPro(id 1) → conținut pagina 7 → footer(adresă/contact/credite).
- **Pagină internă:** header → banner „Call us today" → titlu → `content-2` + eventual broșură/YouTube → footer.
- **Produs:** header → banner → „Back to {range} index" → galerie + titlu + preț(+`model-dimensions`) + `features` → `spec-table` → note livrare + upsells + T&C → footer.
- **Arhivă gamă (`range`):** header → banner → grilă produse (thumbnail + titlu + „From {from-price}") → `range-content-2` → footer.

**Formulare:** Contact Form 7 (2 formulare) + WPForms Lite. Destinație probabilă `sales@eco-fridge.co.uk` (de confirmat în definițiile CF7 la construcție). Checkout Woo cu câmpuri editate (`woo-checkout-field-editor-pro`) + multistep.

**Media:** 22.357 fișiere / **772,9 MB** pe disc; din care ~**2.306 originale / 356 MB** (restul: thumbnails `-WxH` 13.366 jpg + **6.716 `.webp`** generate de `webp-converter-for-media`). **171 PDF** (broșuri/`Manual.pdf`). **⚠️ 37 fișiere `.php` + 475 `.tmp` + 32 `.z` în `uploads/`** — anomalie de securitate, de inspectat înainte de a aduce media (`source-dump/meta/uploads-inventory.txt`).

---

## 6. MAPARE DE MIGRARE (Types/temă PHP → Gutenberg) — cea mai importantă

| Element sursă (original) | Devine în noul site | Editabil din dashboard |
|---|---|---|
| **Homepage** (slider SliderPro `[sliderpro id=1]` + conținut) | Pattern Gutenberg: bloc **Spectra Slider / EB Slider** + secțiuni Spectra | ✅ da |
| **Banner „Call us today"** (non-home) | Astra: header/hook global SAU pattern Spectra reutilizabil | ✅ da |
| **Antet/subsol** (logo, meniu, adresă, telefon, credite) | **Astra Pro Header/Footer Builder** (nativ) | ✅ da |
| **Câmp `content-2` / `content-3`** (wysiwyg pagini) | mutat în **`post_content`** (blocuri core) — nu mai e câmp separat | ✅ da |
| **`brochure-link` (file)** + CPT `brochure` | Bloc buton/fișier în conținut; broșurile ca **media + pattern** | ✅ da |
| **`youtube-id-number`** | Bloc **Embed** core (YouTube) | ✅ da |
| **Produs → `features` (wysiwyg)** | **Descriere scurtă/lungă Woo** (nativ) SAU bloc în șablon produs | ✅ da |
| **Produs → `spec-table` / `spec-table-2` (wysiwyg tabel)** | Bloc **Table** (core) / **EB Table** / TablePress | ✅ da |
| **Produs → `from-price` (textfield „£POA")** | **Meta produs custom** (mic snippet) SAU preț Woo nativ + label | ⚠️ meta simplu |
| **Produs → `model-dimensions`** | **Atribut produs Woo** (nativ) sau câmp meta | ✅ da |
| **CPT `graded` + `graded-unit-*`** | **Categorie de produse Woo „Graded/Second-hand"** + atribute (model/damage/dimensions/shelves/temperature/weight) | ✅ da |
| **Taxonomii `range` / `ranges`** | **Categorii de produse Woo** (consolidare `range`+`ranges`) | ✅ da |
| **Taxonomia `market` (8) + pagini „markets"** | Categorii/landing pages construite cu **Spectra** (grilă dinamică produse) | ✅ da |
| **`range-content-2` (termmeta)** | Descriere categorie Woo (nativ, editor bloc) | ✅ da |
| **Override-uri șablon produs Woo** (spec-table, T&C, note livrare) | **Șablon produs în Site Editor / hooks temă copil** + pattern-uri reutilizabile pentru T&C | ✅ da (T&C ca pattern) |
| **CPT `video` + `youtube-id-number`** | Pagină/secțiune cu blocuri Embed (sau CPT păstrat dacă vrei arhivă video) | ✅ da |
| **`lshowcase` (15 logo-uri)** | Bloc **EB Logo/Image carousel** sau Spectra | ✅ da |
| **Tabele TablePress (186)** | Păstrează TablePress **sau** convertește selectiv în blocuri Table | ✅ da |
| **Formulare CF7/WPForms** | Un singur plugin de formulare (decizie ta) + bloc | ✅ da |
| **Blog (107 posturi)** | Rămâne nativ (post + blocuri) | ✅ da |
| **987 media / 143 produse / 5931 comenzi** | Migrate prin export/import (produse + comenzi Woo) + media | — |

**❌ Ce NU se exprimă curat în Gutenberg standard (necesită meta/temă copil sau snippet):**
- `from-price` cu logica „£POA" pe produse → **meta produs + mic filtru** în tema copil (nu există bloc nativ echivalent).
- Note livrare + **paragraful T&C hardcodat** din `single-product.php` → **pattern reutilizabil** injectat prin hook Woo în tema copil (nu e conținut per-produs).
- „Back to {range} index" contextual → logică în **tema copil** (nu bloc).
- Înlocuirile de string Woo și eliminarea tab-urilor produs → **tema copil** (`functions.php`).

**Concluzie:** ~85% din conținut → blocuri native/Spectra/EB direct editabile; ~15% (logică produs, T&C global, contextual) → **temă copil Astra** minimală + pattern-uri. Nimic nu rămâne dependent de Types/ACF.

---

## 7. RISCURI & DECIZII CARE ÎȚI APARȚIN

### Riscuri tehnice (7.4 → 8.3/8.5)
- **Tema `eco-fridge` NU rulează pe PHP 8** (folosește `like_escape()` eliminat, ghilimele „smart" în cod la `functions.php:205`, pattern-uri vechi). **Irelevant** — nu portăm tema, o reconstruim în Gutenberg. Sursa rămâne doar referință de design.
- **WooCommerce 3.5.10 → Woo latest:** schema de comenzi s-a schimbat radical (HPOS/`wc_orders`). Cele **5.931 comenzi** trebuie migrate prin importul Woo (nu prin copiere brută de `postmeta`). Risc de nepotriviri la câmpuri.
- **Toolset Types dezinstalat:** la dezactivare, CPT/taxonomiile nu se mai înregistrează → conținutul devine „orphan" până e remapat. Migrarea trebuie să **remapizeze `wpcf-*` → meta/atribute Woo** înainte de a scoate Types.
- **`.php`/`.tmp`/`.z` în `uploads/` (37+475+32):** posibil cod injectat/reziduu — **scanare de securitate** obligatorie înainte de a importa media.
- **DB 756 MB** dominată de comenzi + `revision` (2842) + `yst_prominent_words` (3497) + loguri: curățare recomandată → probabil <100 MB utili.
- **URL-uri hardcodate** către `eco-fridge.co.uk` în temă (T&C, „Back to index", Manual.pdf) — de parametrizat.

### Decizii care îți aparțin (înainte să scriu cod)
1. **Comenzile (5.931):** le migrăm integral, doar ultimele N luni, sau pornim magazinul „curat" (produse da, istoric comenzi nu)? *(afectează masiv dimensiunea și complexitatea)*
2. **Graded units:** categorie de produse Woo (recomand) **sau** CPT separat păstrat?
3. **`range` vs `ranges`:** consolidăm în categorii de produse Woo unice? (par duplicate)
4. **Gateway plată `woocommerce_lloydscardnetcw` (Cardnet):** încă folosit? Alternativă modernă (Stripe/PayPal/Cardnet nou)?
5. **Formulare:** standardizăm pe **CF7** sau **WPForms** (nu ambele)? Unde trimit (email destinație)?
6. **Tabele spec:** păstrăm **TablePress** sau convertim `spec-table` în blocuri Table nativ?
7. **`check_doors` (4353):** e opțiune de produs reală (YITH add-ons) de păstrat, sau reziduu de șters?
8. **Slider homepage:** SliderPro → **Spectra Slider** sau **EB Slider**? Ai imaginile/slide-urile originale?
9. **Domeniu final** și dacă păstrăm structura URL (`/%postname%/`) pentru SEO/redirect-uri.
10. **Blog (107 posturi):** îl păstrăm/migrăm sau site strict comercial?
11. **Multilingv:** tema are traduceri (`.po/.mo`) — site-ul e doar EN. Confirmi mono-lingv EN?

---

## Addendum — Site original LIVE (eco-fridge.co.uk)

Site-ul original **este încă activ** (© 2026) și a fost auditat separat — vezi **`source-dump/meta/original-site-live-audit.md`** (referință de design 1:1). Puncte cheie: hero cu **4 slide-uri** (Mirrored SS Multidecks / Slimline Patisseries / Slimline Multidecks / Bakery Retarder Prover); meniu bogat (**Display Cabinets 19** + **Storage Cabinets 14** sub-iteme, Bakery & Ovens, Medical, Low Temperature Freezers, Used/Graded Units, Commercial Fridge/Freezer, Blog, Contact); secțiuni: brand intro (30+ ani), **client logos** (Costa, Caffe Nero, Hotel Chocolat, Odeon, Welcome Break…), grile produse „From £X +VAT", graded units, conținut SEO ~2000 cuvinte; footer identic (Buckingham MK18 1TH, 01280 811411, sales@eco-fridge.co.uk). Stack original: tema `eco-fridge`, **PHP 7.2.34**, **WP 5.x/5.6**.

## Anexă — fișiere livrate în `source-dump/`
```
source-dump/
├── db/db.sql.gz                     162 MB · 276 tabele · verificat
├── theme/
│   ├── themes-ecofridge-astra.tar.gz
│   ├── eco-fridge/                  tema originală (reverse-engineered)
│   └── astra/                       (referință)
├── meta/
│   ├── plugins-list.txt             57 plugin-uri (status/versiune/update)
│   ├── recon-extended.txt           post types, users, meniuri, opțiuni
│   ├── db-audit-queries.txt         toate query-urile de audit
│   ├── types-definitions.json       definiții câmpuri/CPT/taxonomii Types
│   └── uploads-inventory.txt        22.357 fișiere + dimensiuni
└── wp-config.redacted.php           credențiale redactate
```

**STOP — sfârșit audit. Aștept deciziile tale (secțiunea 7) și aprobarea pentru Faza 2 (construcție locală wp-env + temă copil Astra + reconstrucție Gutenberg).**
