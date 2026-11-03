-- F15 — import the orders the old shop gained after our July migration.
--
-- Selection rule: an order comes across when its `_order_key` is not already
-- on the target site. Never select by date and never by ID: the July import
-- re-created the orders as fresh posts, so the ID offset is not even constant
-- (7253 at #14508, 7254 at #14510), and the boundary is order #14515, not
-- midnight on 10 July. See migration/f15-delta-2026-11-03.md.
--
-- Orders keep their original numbers, because customers hold those numbers in
-- the email they were sent.
--
-- Placeholders, substituted by f15-import-orders.sh:
--   @OLD@  the clone's qualified prefix, e.g. `oldsite`.`43lz2ypq8_`
--   @NEW@  the target's qualified prefix, e.g. `wordpress`.`wp_`
-- The two may live in the same database under different prefixes, which is how
-- this runs on staging: load the clone's tables next to ours, import, drop them.
--
-- Run order on launch day:
--   1. delete our test orders with WP-CLI (not SQL, so WooCommerce cleans up
--      its lookup tables): wp post delete <ids> --force
--   2. this script
--   3. WooCommerce → Analytics → Settings → Import historical data, or
--      wp wc tool run regenerate_product_lookup_tables, so the new orders
--      reach the report tables
--   4. the verification queries at the bottom

-- ---------------------------------------------------------------------------
-- 0. What is about to happen, and a refusal if any of it is already here.
-- ---------------------------------------------------------------------------

DROP TEMPORARY TABLE IF EXISTS f15_orders;
CREATE TEMPORARY TABLE f15_orders (
	order_id BIGINT UNSIGNED PRIMARY KEY
) ENGINE=InnoDB;

INSERT INTO f15_orders (order_id)
SELECT p.ID
FROM @OLD@posts p
JOIN @OLD@postmeta k ON k.post_id = p.ID AND k.meta_key = '_order_key'
WHERE p.post_type = 'shop_order'
  AND k.meta_value NOT IN (
      SELECT meta_value FROM @NEW@postmeta WHERE meta_key = '_order_key'
  );

SELECT 'orders selected for import' AS what, COUNT(*) AS n FROM f15_orders
UNION ALL SELECT 'their order items',
	(SELECT COUNT(*) FROM @OLD@woocommerce_order_items i JOIN f15_orders f ON f.order_id = i.order_id)
UNION ALL SELECT 'their meta rows',
	(SELECT COUNT(*) FROM @OLD@postmeta m JOIN f15_orders f ON f.order_id = m.post_id)
UNION ALL SELECT 'their order notes',
	(SELECT COUNT(*) FROM @OLD@comments c JOIN f15_orders f ON f.order_id = c.comment_post_ID)
UNION ALL SELECT 'post IDs already taken on the target (must be 0)',
	(SELECT COUNT(*) FROM @NEW@posts n JOIN f15_orders f ON f.order_id = n.ID);

-- ---------------------------------------------------------------------------
-- 1. The orders themselves, with their original IDs.
-- ---------------------------------------------------------------------------

INSERT INTO @NEW@posts
	(ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
	 post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
	 post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
	 menu_order, post_type, post_mime_type, comment_count)
SELECT o.ID, o.post_author, o.post_date, o.post_date_gmt, o.post_content, o.post_title,
	o.post_excerpt, o.post_status, o.comment_status, o.ping_status, o.post_password,
	o.post_name, o.to_ping, o.pinged, o.post_modified, o.post_modified_gmt,
	o.post_content_filtered, o.post_parent, o.guid, o.menu_order, o.post_type,
	o.post_mime_type, o.comment_count
FROM @OLD@posts o
JOIN f15_orders f ON f.order_id = o.ID;

-- ---------------------------------------------------------------------------
-- 2. Their meta, without the leftovers of plugins the new site does not run.
--    `stairs` is kept on purpose: it is what the customer answered about steps
--    at the delivery address, and the delivery team needs it.
-- ---------------------------------------------------------------------------

INSERT INTO @NEW@postmeta (post_id, meta_key, meta_value)
SELECT m.post_id, m.meta_key, m.meta_value
FROM @OLD@postmeta m
JOIN f15_orders f ON f.order_id = m.post_id
WHERE m.meta_key NOT IN (
	'_wc_google_analytics_pro_identity',
	'_wc_google_analytics_pro_tracked',
	'matomo-order-tracked',
	'woocommerce_lloydscardnetcw_processing_email',
	'_lloydscardnetcw_pending_state',
	'_edit_last',
	'_edit_lock'
);

-- ---------------------------------------------------------------------------
-- 3. Order items. Their own IDs come from a different sequence on each site,
--    so they are renumbered and the mapping is kept for the item meta.
-- ---------------------------------------------------------------------------

DROP TEMPORARY TABLE IF EXISTS f15_item_map;
CREATE TEMPORARY TABLE f15_item_map (
	old_id BIGINT UNSIGNED PRIMARY KEY,
	new_id BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

SET @item_cursor = (SELECT COALESCE(MAX(order_item_id), 0) FROM @NEW@woocommerce_order_items);

INSERT INTO f15_item_map (old_id, new_id)
SELECT old_id, @item_cursor := @item_cursor + 1
FROM (
	SELECT i.order_item_id AS old_id
	FROM @OLD@woocommerce_order_items i
	JOIN f15_orders f ON f.order_id = i.order_id
	ORDER BY i.order_item_id
) AS ordered;

INSERT INTO @NEW@woocommerce_order_items
	(order_item_id, order_item_name, order_item_type, order_id)
SELECT map.new_id, i.order_item_name, i.order_item_type, i.order_id
FROM @OLD@woocommerce_order_items i
JOIN f15_orders f ON f.order_id = i.order_id
JOIN f15_item_map map ON map.old_id = i.order_item_id;

INSERT INTO @NEW@woocommerce_order_itemmeta
	(order_item_id, meta_key, meta_value)
SELECT map.new_id, im.meta_key, im.meta_value
FROM @OLD@woocommerce_order_itemmeta im
JOIN f15_item_map map ON map.old_id = im.order_item_id;

-- ---------------------------------------------------------------------------
-- 4. Order notes. Same story: new comment IDs, and the note meta follows.
-- ---------------------------------------------------------------------------

DROP TEMPORARY TABLE IF EXISTS f15_note_map;
CREATE TEMPORARY TABLE f15_note_map (
	old_id BIGINT UNSIGNED PRIMARY KEY,
	new_id BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

SET @note_cursor = (SELECT COALESCE(MAX(comment_ID), 0) FROM @NEW@comments);

INSERT INTO f15_note_map (old_id, new_id)
SELECT old_id, @note_cursor := @note_cursor + 1
FROM (
	SELECT c.comment_ID AS old_id
	FROM @OLD@comments c
	JOIN f15_orders f ON f.order_id = c.comment_post_ID
	ORDER BY c.comment_ID
) AS ordered;

INSERT INTO @NEW@comments
	(comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url,
	 comment_author_IP, comment_date, comment_date_gmt, comment_content, comment_karma,
	 comment_approved, comment_agent, comment_type, comment_parent, user_id)
SELECT map.new_id, c.comment_post_ID, c.comment_author, c.comment_author_email,
	c.comment_author_url, c.comment_author_IP, c.comment_date, c.comment_date_gmt,
	c.comment_content, c.comment_karma, c.comment_approved, c.comment_agent,
	c.comment_type, 0, c.user_id
FROM @OLD@comments c
JOIN f15_orders f ON f.order_id = c.comment_post_ID
JOIN f15_note_map map ON map.old_id = c.comment_ID;

INSERT INTO @NEW@commentmeta (comment_id, meta_key, meta_value)
SELECT map.new_id, cm.meta_key, cm.meta_value
FROM @OLD@commentmeta cm
JOIN f15_note_map map ON map.old_id = cm.comment_id;

-- ---------------------------------------------------------------------------
-- 5. The sequence continues above the highest number now present, so the shop
--    never hands a new order a number a real one already has.
-- ---------------------------------------------------------------------------

SET @next_id = (SELECT MAX(ID) + 20 FROM @NEW@posts);
SET @stmt = CONCAT('ALTER TABLE @NEW@posts AUTO_INCREMENT = ', @next_id);
PREPARE bump FROM @stmt;
EXECUTE bump;
DEALLOCATE PREPARE bump;

-- ---------------------------------------------------------------------------
-- 6. Verification. Every line below has to read the way the comment says.
-- ---------------------------------------------------------------------------

SELECT 'orders imported (expected: the count from step 0)' AS check_name,
	(SELECT COUNT(*) FROM @NEW@posts n JOIN f15_orders f ON f.order_id = n.ID) AS value
UNION ALL SELECT 'orders still missing from the target (expected 0)',
	(SELECT COUNT(*) FROM @OLD@posts p
	 JOIN @OLD@postmeta k ON k.post_id = p.ID AND k.meta_key = '_order_key'
	 WHERE p.post_type = 'shop_order'
	   AND k.meta_value NOT IN (SELECT meta_value FROM @NEW@postmeta WHERE meta_key = '_order_key'))
UNION ALL SELECT 'order keys matching on both sides (expected: every old order)',
	(SELECT COUNT(*) FROM @OLD@postmeta o
	 JOIN @NEW@postmeta n ON n.meta_key = '_order_key' AND n.meta_value = o.meta_value
	 WHERE o.meta_key = '_order_key')
UNION ALL SELECT 'old shop order count (the number above must equal this)',
	(SELECT COUNT(*) FROM @OLD@posts WHERE post_type = 'shop_order')
UNION ALL SELECT 'imported orders with no items (expected 0)',
	(SELECT COUNT(*) FROM f15_orders f
	 WHERE NOT EXISTS (SELECT 1 FROM @NEW@woocommerce_order_items i WHERE i.order_id = f.order_id))
UNION ALL SELECT 'imported orders with no total (expected 0)',
	(SELECT COUNT(*) FROM f15_orders f
	 WHERE NOT EXISTS (SELECT 1 FROM @NEW@postmeta m WHERE m.post_id = f.order_id AND m.meta_key = '_order_total'))
UNION ALL SELECT 'item meta rows orphaned by the renumbering (expected 0)',
	(SELECT COUNT(*) FROM @NEW@woocommerce_order_itemmeta im
	 LEFT JOIN @NEW@woocommerce_order_items i ON i.order_item_id = im.order_item_id
	 WHERE i.order_item_id IS NULL)
UNION ALL SELECT 'next number the shop will hand out', @next_id;

SELECT '--- the imported orders, and what each one carries ---' AS '';
SELECT CONCAT('#', n.ID) AS number, DATE(n.post_date) AS placed,
	REPLACE(n.post_status, 'wc-', '') AS status,
	(SELECT meta_value FROM @NEW@postmeta WHERE post_id = n.ID AND meta_key = '_order_total') AS total,
	(SELECT COUNT(*) FROM @NEW@woocommerce_order_items i WHERE i.order_id = n.ID) AS items,
	(SELECT COUNT(*) FROM @NEW@comments c WHERE c.comment_post_ID = n.ID) AS notes,
	(SELECT COUNT(*) FROM @NEW@postmeta m WHERE m.post_id = n.ID) AS meta_rows
FROM @NEW@posts n JOIN f15_orders f ON f.order_id = n.ID
ORDER BY n.ID;
