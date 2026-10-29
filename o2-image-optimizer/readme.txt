=== O2 Image Optimizer ===
Contributors: o2digital
Tags: webp, avif, image optimization, images, performance
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Creates WebP and AVIF versions of your images and serves each visitor the best format their browser supports.

== Description ==

O2 Image Optimizer converts the JPEG and PNG images of the Media Library to AVIF and WebP and delivers them only to browsers that support them. Every other browser keeps getting the original.

* **Original images are never modified.** Converted copies are written next to them (`photo.jpg.avif`, `photo.jpg.webp`); a copy that is not smaller than the original is discarded.
* **Delivery** through `<picture>` elements (works with any theme, page builder and CDN) or server rewrites on Apache / LiteSpeed (`uploads/.htaccess`).
* **New uploads** are converted automatically, in the background or immediately.
* **Bulk conversion** of the existing library from Media → Image Optimizer, in small batches with pause / resume / stop and a live progress panel.
* Per-image status in the Media Library, conversion of a single image, activity log.
* Quality, formats, image sizes, exclusions and resource limits are all adjustable.
* WP-CLI: `wp o2io status`, `wp o2io convert`, `wp o2io cleanup`.

Conversion uses Imagick or GD, whichever supports each format on your server; the dashboard shows what is available.

== Installation ==

1. Upload the `o2-image-optimizer` folder to `/wp-content/plugins/` and activate the plugin.
2. Open **Media → Image Optimizer** and check that AVIF and/or WebP are supported on your server.
3. Review **Settings**, then start the bulk conversion from the **Dashboard**. Keep that window open while the conversion runs.

== Frequently Asked Questions ==

= Are my original images changed? =

No. The plugin only adds `.avif` / `.webp` copies next to them. Deactivating the plugin brings back the originals everywhere.

= What happens if I close the window during the bulk conversion? =

Nothing is damaged. The conversion carries on in the background through WP-Cron, more slowly, and speeds up again when you reopen the dashboard.

= How do I remove everything? =

Tools → "Delete converted files" removes every copy the plugin made. With the "Delete converted files" setting on, deleting the plugin does the same.

== Changelog ==

= 1.0.0 =
* First release.
