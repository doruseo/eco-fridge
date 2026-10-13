# Product tag copy

SEO content for the product tag landing pages, applied by `migration/44-tag-content-seo.php`.

One PHP file per group; each returns `slug => [ short, long, faq, articles ]`:

- `short` – 1–2 sentences under the H1 (the tag Description).
- `long` – the long description below the products (HTML).
- `faq` – `[question, answer]` pairs; the first one defines the tag.
- `articles` – blog post IDs for "Buying guides & articles".

The H1 is the tag name (set by migration 42). The comparison table is built from the tag's products.
Figures (sizes, litres, weights, prices) come from the product specification tables – update them when prices change.

Run:

    rm -rf source-dump/_tag-copy && cp -r migration/tag-copy source-dump/_tag-copy
    cp migration/44-tag-content-seo.php source-dump/_run.php
    npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
