# Market copy

SEO content for the market landing pages (/markets/<slug>/ and /market/schools/), applied by `migration/44-tag-content-seo.php` together with the product tags.

Each file returns `market slug => [ short, long, faq, articles ]` (same format as `migration/tag-copy`).

The H1 is the title of the /markets/<slug>/ page (kept from the original site); the comparison table is built from the market's products.
Figures (sizes, litres, weights, prices) come from the product specification tables – update them when prices change.

Run (after migration 45):

    rm -rf source-dump/_tag-copy source-dump/_market-copy
    cp -r migration/tag-copy source-dump/_tag-copy && cp -r migration/market-copy source-dump/_market-copy
    cp migration/44-tag-content-seo.php source-dump/_run.php
    npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
