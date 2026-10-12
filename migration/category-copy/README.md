# Category copy

SEO content for the commercial categories, applied by `migration/39-category-content-seo.php`.

One PHP file per category tree; each returns `slug => [ name, short, long, faq, articles ]`:

- `name` — the category name, shown as the page H1 (one clear name, no "X – Y").
- `short` — 1–2 sentences under the H1 (the category Description).
- `long` — the long description below the products (HTML).
- `faq` — `[question, answer]` pairs; the first one defines the category.
- `articles` — blog post IDs for the "Buying guides & articles" section.

The comparison table is not written here: migration 39 builds it from the live product data.

Use typographic apostrophes (’) in the text so the PHP strings stay single-quoted.

Run:

    rm -rf source-dump/_category-copy && cp -r migration/category-copy source-dump/_category-copy
    cp migration/39-category-content-seo.php source-dump/_run.php
    npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
