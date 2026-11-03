-- F15 — compare the old shop with ours, and list every difference worth acting on.
--
-- Read-only: this script never writes. Run it on launch morning against the
-- fresh clone, because today's answers are a snapshot, not a promise: products,
-- pages and categories can change on the old shop any day until we cut over.
--
-- Placeholders, substituted by f15-run.sh:
--   @OLD@    the clone's qualified prefix
--   @NEW@    our site's qualified prefix
--   @SINCE@  the date from which "changed" counts, normally 2026-07-10
--
-- Two rules learned on 3 November that this script encodes, and that must not be
-- undone (see migration/f15-delta-2026-11-03.md):
--   * orders are selected by `_order_key` absent on our side, never by date and
--     never by ID;
--   * prices are compared on the EFFECTIVE price (sale price if set, otherwise
--     regular), because our site carries an RRP with a sale price under it, so a
--     raw field comparison reports eighteen differences that do not exist.

SELECT '=== 1. ORDERS ===' AS '';

SELECT 'orders to import (order key not on our side)' AS what,
	COUNT(*) AS n,
	CONCAT('#', MIN(p.ID), ' .. #', MAX(p.ID)) AS id_range,
	CONCAT('GBP ', FORMAT(SUM(CAST(t.meta_value AS DECIMAL(12,2))), 2)) AS value
FROM @OLD@posts p
JOIN @OLD@postmeta k ON k.post_id = p.ID AND k.meta_key = '_order_key'
LEFT JOIN @OLD@postmeta t ON t.post_id = p.ID AND t.meta_key = '_order_total'
WHERE p.post_type = 'shop_order'
  AND k.meta_value NOT IN (SELECT meta_value FROM @NEW@postmeta WHERE meta_key = '_order_key');

SELECT REPLACE(p.post_status, 'wc-', '') AS status, COUNT(*) AS orders,
	CONCAT('GBP ', FORMAT(SUM(CAST(t.meta_value AS DECIMAL(12,2))), 2)) AS value
FROM @OLD@posts p
JOIN @OLD@postmeta k ON k.post_id = p.ID AND k.meta_key = '_order_key'
LEFT JOIN @OLD@postmeta t ON t.post_id = p.ID AND t.meta_key = '_order_total'
WHERE p.post_type = 'shop_order'
  AND k.meta_value NOT IN (SELECT meta_value FROM @NEW@postmeta WHERE meta_key = '_order_key')
GROUP BY p.post_status ORDER BY orders DESC;

SELECT 'order keys matching on both sides' AS what,
	(SELECT COUNT(*) FROM @OLD@postmeta o JOIN @NEW@postmeta n
	 ON n.meta_key = '_order_key' AND n.meta_value = o.meta_value
	 WHERE o.meta_key = '_order_key') AS matched,
	(SELECT COUNT(*) FROM @OLD@posts WHERE post_type = 'shop_order') AS old_orders_total,
	'after the import these two must be equal' AS note;

SELECT '=== 2. PRODUCTS: new on the old shop (to add) ===' AS '';
SELECT CONCAT('#', o.ID) AS old_id, o.post_name AS slug, DATE(o.post_date) AS created,
	LEFT(o.post_title, 60) AS title
FROM @OLD@posts o
WHERE o.post_type = 'product' AND o.post_status = 'publish'
  AND o.post_name NOT IN (SELECT post_name FROM @NEW@posts WHERE post_type = 'product')
ORDER BY o.post_date DESC;

SELECT '--- PRODUCTS: ours, no longer published on the old shop (to withdraw) ---' AS '';
SELECT n.post_name AS slug, LEFT(n.post_title, 60) AS title,
	COALESCE((SELECT o.post_status FROM @OLD@posts o
	          WHERE o.post_type = 'product' AND o.post_name = n.post_name LIMIT 1), 'deleted') AS status_on_old
FROM @NEW@posts n
WHERE n.post_type = 'product' AND n.post_status = 'publish'
  AND n.post_name NOT IN (SELECT post_name FROM @OLD@posts
                          WHERE post_type = 'product' AND post_status = 'publish')
ORDER BY n.post_name;

SELECT '--- PRODUCTS: changed on the old shop since @SINCE@ ---' AS '';
SELECT o.post_name AS slug, DATE(o.post_modified) AS edited, LEFT(o.post_title, 50) AS title,
	CASE WHEN n.ID IS NULL THEN 'not on our site' ELSE 'present' END AS on_ours
FROM @OLD@posts o
LEFT JOIN @NEW@posts n ON n.post_name = o.post_name AND n.post_type = 'product'
WHERE o.post_type = 'product' AND o.post_status = 'publish'
  AND o.post_modified >= '@SINCE@ 00:00:00'
ORDER BY o.post_modified DESC;

SELECT '=== 3. PRODUCT CATEGORIES ===' AS '';
SELECT 'only on the old shop (to create)' AS what, t.slug, t.name, tt.count AS products
FROM @OLD@term_taxonomy tt JOIN @OLD@terms t ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'product_cat'
  AND t.slug NOT IN (SELECT t2.slug FROM @NEW@terms t2 JOIN @NEW@term_taxonomy tt2
                     ON tt2.term_id = t2.term_id WHERE tt2.taxonomy = 'product_cat')
ORDER BY t.slug;

SELECT '--- categories whose name changed on the old shop (same slug) ---' AS '';
SELECT t.slug, t.name AS name_on_old, t2.name AS name_on_ours
FROM @OLD@term_taxonomy tt
JOIN @OLD@terms t ON t.term_id = tt.term_id
JOIN @NEW@term_taxonomy tt2 ON tt2.taxonomy = 'product_cat'
JOIN @NEW@terms t2 ON t2.term_id = tt2.term_id AND t2.slug = t.slug
WHERE tt.taxonomy = 'product_cat' AND t.name <> t2.name
ORDER BY t.slug;

SELECT '--- categories only on ours: expected, our structure is finer; listed as a count ---' AS '';
SELECT COUNT(*) AS categories_only_on_ours
FROM @NEW@term_taxonomy tt2 JOIN @NEW@terms t2 ON t2.term_id = tt2.term_id
WHERE tt2.taxonomy = 'product_cat'
  AND t2.slug NOT IN (SELECT t.slug FROM @OLD@terms t JOIN @OLD@term_taxonomy tt
                      ON tt.term_id = t.term_id WHERE tt.taxonomy = 'product_cat');

SELECT '=== 4. PAGES AND POSTS ===' AS '';
SELECT 'page only on the old shop' AS what, o.post_name AS slug, DATE(o.post_date) AS created,
	LEFT(o.post_title, 55) AS title
FROM @OLD@posts o
WHERE o.post_type = 'page' AND o.post_status = 'publish'
  AND o.post_name NOT IN (SELECT post_name FROM @NEW@posts WHERE post_type IN ('page','product','post'))
UNION ALL
SELECT 'page gone from the old shop', n.post_name, DATE(n.post_date), LEFT(n.post_title, 55)
FROM @NEW@posts n
WHERE n.post_type = 'page' AND n.post_status = 'publish'
  AND n.post_name NOT IN (SELECT post_name FROM @OLD@posts WHERE post_status = 'publish')
UNION ALL
SELECT 'page changed on the old shop', o.post_name, DATE(o.post_modified), LEFT(o.post_title, 55)
FROM @OLD@posts o
WHERE o.post_type = 'page' AND o.post_status = 'publish' AND o.post_modified >= '@SINCE@ 00:00:00'
UNION ALL
SELECT 'blog post only on the old shop', o.post_name, DATE(o.post_date), LEFT(o.post_title, 55)
FROM @OLD@posts o
WHERE o.post_type = 'post' AND o.post_status = 'publish'
  AND o.post_name NOT IN (SELECT post_name FROM @NEW@posts WHERE post_type IN ('post','page'))
ORDER BY what, slug;

SELECT '=== 5. STOCK AND PRICE, on the effective price ===' AS '';
SELECT o.post_name AS slug,
	COALESCE(os.meta_value, '-') AS stock_on_old,
	COALESCE(ns.meta_value, '-') AS stock_on_ours,
	COALESCE(NULLIF(osp.meta_value, ''), orp.meta_value) AS price_on_old,
	COALESCE(NULLIF(nsp.meta_value, ''), nrp.meta_value) AS price_on_ours
FROM @OLD@posts o
JOIN @NEW@posts n ON n.post_name = o.post_name AND n.post_type = 'product' AND n.post_status = 'publish'
LEFT JOIN @OLD@postmeta os  ON os.post_id  = o.ID AND os.meta_key  = '_stock_status'
LEFT JOIN @NEW@postmeta ns  ON ns.post_id  = n.ID AND ns.meta_key  = '_stock_status'
LEFT JOIN @OLD@postmeta orp ON orp.post_id = o.ID AND orp.meta_key = '_regular_price'
LEFT JOIN @NEW@postmeta nrp ON nrp.post_id = n.ID AND nrp.meta_key = '_regular_price'
LEFT JOIN @OLD@postmeta osp ON osp.post_id = o.ID AND osp.meta_key = '_sale_price'
LEFT JOIN @NEW@postmeta nsp ON nsp.post_id = n.ID AND nsp.meta_key = '_sale_price'
WHERE o.post_type = 'product' AND o.post_status = 'publish'
  AND (COALESCE(os.meta_value, '') <> COALESCE(ns.meta_value, '')
    OR CAST(COALESCE(NULLIF(osp.meta_value, ''), orp.meta_value, 0) AS DECIMAL(12,2))
     <> CAST(COALESCE(NULLIF(nsp.meta_value, ''), nrp.meta_value, 0) AS DECIMAL(12,2)))
ORDER BY o.post_name;

SELECT '=== 6. MEDIA added since @SINCE@ (from the database: file dates are rewritten by the migration) ===' AS '';
SELECT CONCAT('#', p.ID) AS id, DATE(p.post_date) AS added, p.post_title AS title,
	COALESCE((SELECT meta_value FROM @OLD@postmeta WHERE post_id = p.ID AND meta_key = '_wp_attached_file'), '-') AS file
FROM @OLD@posts p
WHERE p.post_type = 'attachment' AND p.post_date >= '@SINCE@ 00:00:00' ORDER BY p.ID;

SELECT '=== 7. COUPONS on the old shop ===' AS '';
SELECT CONCAT('#', p.ID) AS id, p.post_title AS code, p.post_status AS status, DATE(p.post_date) AS created
FROM @OLD@posts p WHERE p.post_type = 'shop_coupon' ORDER BY p.post_status, p.ID;

SELECT '=== 8. REVIEWS AND COMMENTS since @SINCE@ ===' AS '';
SELECT comment_type AS type, comment_approved AS approved, COUNT(*) AS n
FROM @OLD@comments WHERE comment_date >= '@SINCE@ 00:00:00'
GROUP BY comment_type, comment_approved;

SELECT '=== 9. REDIRECTS AND SEO: sizes only, the comparison itself is done outside SQL ===' AS '';
SELECT option_name, CONCAT(ROUND(LENGTH(option_value) / 1024, 1), ' KB') AS size
FROM @OLD@options
WHERE option_name IN ('wpseo-premium-redirects-base', 'wpseo-premium-redirects-export-plain',
	'301_redirects', 'wpseo_taxonomy_meta', 'wpseo_titles')
ORDER BY LENGTH(option_value) DESC;

SELECT '--- Yoast titles and descriptions on content changed since @SINCE@ ---' AS '';
SELECT m.meta_key, COUNT(*) AS rows_found
FROM @OLD@postmeta m JOIN @OLD@posts p ON p.ID = m.post_id
WHERE m.meta_key IN ('_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw')
  AND p.post_modified >= '@SINCE@ 00:00:00' AND m.meta_value <> ''
GROUP BY m.meta_key;
