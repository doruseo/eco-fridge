# O2X reports — findings from a real pre-launch migration

**Site:** eco-fridge.co.uk (WooCommerce, ~130 products) → rebuilt site on staging, launching 7 November 2026.

**Reports reviewed (all exported 30 October 2026):**

| Report | Crawl A (baseline) | Crawl B (pre-launch) |
|---|---|---|
| `compare-old-live-site-vs-staging-pre-launch` | #7 · 17 Oct · 2,872 URLs | #9 · 30 Oct · 1,289 URLs |
| `changes-by-field.csv` | 111 rows, all "lost pages" | — |
| `lighthouse-performance-3-phases` | #6 · 17 Oct · 20 pages | #9 · 30 Oct · 22 pages |
| `crawls` | crawl history | — |

Every signal in the reports was then verified against the sites themselves: each of the 111 "lost" paths was requested on the new site without following redirects, the rendered HTML of each page template was inspected, and Lighthouse was run locally to obtain the per-audit results. This write-up separates what the tool got right from what cost time, and suggests what would make the redirect recommendations trustworthy.

---

## 1. The headline problem: 111 "lost pages", none of which are lost

The CSV lists 111 rows, all with the same reason: *"Path does not exist in crawl B and is unmapped"*. That phrasing invites the reader to create 111 redirects.

Each path was requested on the new site with redirects **not** followed:

| Result | Count |
|---|---|
| `301` in one hop to a page that answers `200` | 105 |
| `200` directly | 5 |
| `301` → `302` → `200` (checkout → cart, correct WooCommerce behaviour) | 1 |
| `404` or `410` | **0** |

The correct number of pages needing attention was **zero**, not 111. Among the five that answer `200` directly is `/product/removal-disposal/`, the only row in the whole file with meaningful link equity (30 inlinks) — reported as lost while it is a live, healthy page.

**Why it happens.** Crawl B is a spider: it discovers URLs by following links and reading sitemaps. A URL that exists on the new site *only as a redirect source* is never discovered, so it is absent from B's inventory — and "absent from the inventory" is being read as "does not exist". The two are different things.

**The fix is one request per candidate.** Before a path is declared lost or unmapped, request it on site B (`HEAD`, falling back to `GET`), redirects not followed, and classify by the response:

| Response on B | Classification | Severity | Action to recommend |
|---|---|---|---|
| `200` | Still live | info | none |
| `301`/`308` → chain ending `200` | Handled by a redirect | info (warning at ≥3 hops) | none; show target and hop count |
| `302`/`307` → `200` | Handled, but temporary | warning | make it permanent |
| `301` → `404`/`410` | Broken redirect | **critical** | fix the target |
| `404` | Genuinely lost | critical *if it has inlinks, clicks or impressions*, else info | redirect to the closest match |
| `410` | Deliberately retired | info | none |

For this site that single probe would have turned 111 rows into 0, and the report would have gained a line worth reading: *"111 old paths are no longer published; all of them resolve through redirects, longest chain 2 hops."*

## 2. Rank recommendations by evidence, and group by pattern

Of the 111 rows, 108 had `Inlinks (A) = 0` and **all 111 had `Clicks = 0`**. The composition was:

- 72 × `/product-tag/…` — tags retired in the rebuild, each already redirecting to the matching category;
- 31 × `/https-eco-fridge-co-uk/…` — attachment pages from an old import, each redirecting to its image file;
- 2 × `/author/eco-fridge/page/2|3/`, 1 × `/condition/…`, 1 × `/product-category/…`, 1 × `/uncategorized/`, 1 × the old payment-gateway callback, 2 × `/product/…`.

Three improvements follow:

1. **Sort by evidence, not alphabetically.** Inlinks, clicks and impressions are already in the data. The one row that mattered (`/product/removal-disposal/`, 30 inlinks) sat in the middle of 110 rows with zero of everything.
2. **Collapse by pattern.** "72 `/product-tag/*` paths, all redirecting to product categories" is one line a human can accept or reject. 72 lines are 72 decisions nobody will make.
3. **Say what a redirect would cost.** When a path has no inlinks, no clicks and already redirects, recommending a redirect is noise that competes with the tool's own true positives.

## 3. Attachment pages inflate "critical changes"

The comparison reported **638 pages with critical changes**. The first hundred, which is all the export renders, were almost entirely WordPress attachment pages: `/markets/sports-clubs/banner-no1-coffee-new/`, `/range/…/eco-upright-mont-trans/`, `/12-tips-to-create-a-successful-restaurant/<image-slug>/`. Their titles are image filenames ("Banner No1 Coffee new", "ECO-Upright-Mont-trans"). On the new site attachment pages are disabled, so each redirects to its parent page or to the image file — a deliberate, standard cleanup.

The tool marks each as critical because title, H1, word count and structured data all change. Technically true, practically noise: 638 critical rows make a reader distrust the whole list.

**Suggested detection** (any two of these): the old URL is a child path of another indexed page; the title equals the image filename with separators replaced; the page has under ~150 words and no meta description; the new target is a file under `/wp-content/uploads/` or the parent page. Classify those as *"attachment page retired → parent/file"*, severity info, and collapse them into one group with a count.

## 4. A noindexed staging site poisons five metrics at once

Crawl B was taken on a staging site closed to indexing (`blog_public = 0`), as every pre-launch site should be. The report then shows:

- `Indexable pages 749 → 0` (−749)
- `noindex pages 0 → 427` (+427)
- `No canonical 0 → 427` (+427)
- `Non-indexable URLs in the sitemap 300 → 428`
- Lighthouse `SEO 100 → 63`

All five are the same single fact. The missing canonicals are a second-order effect: Rank Math (and Yoast) deliberately omit `rel=canonical` while the site is private — verified here by switching indexing on for a moment on a local mirror, at which point canonicals appeared on every template, then switching it back.

**Suggestion.** When crawl B is in the *pre-launch* phase and ≥95% of its pages are noindex, show one banner — *"Site B is closed to indexing (427/427 pages). Indexability, canonical and sitemap-indexability comparisons are suspended until launch."* — and suppress those rows, or mark them "expected on staging". That removes ~1,300 warnings nobody should act on, and stops readers from dismissing the genuine warnings next to them.

## 5. "Internal links → redirect: 427" was the most valuable number in the report

This one was a **true positive**, and it found two real bugs that nothing else caught:

1. The off-canvas (mobile) menu location had no menu assigned, so the theme fell back to an alphabetical list of every published page — cart, checkout, legal pages — in the mobile drawer on all 427 pages.
2. One of those links, `/eco-fridge/`, redirected to the wrong page (the about page, while that URL serves returns content on the old site).

What made it hard to act on is that the report gives only the count. A count equal to the total number of pages is the signature of a template, menu or footer problem — exactly the kind worth fixing.

**Suggestion:** show the top internal links that redirect, with the number of source pages and the target:

```
/eco-fridge/   → /about-us/   appears on 427 pages  (site-wide: header, menu or footer)
/checkout/     → /cart/       appears on 427 pages  (expected: empty cart)
```

Two lines instead of one number, and the fix becomes obvious. Known-benign targets (`/cart/`, `/checkout/`, `/my-account/` on WooCommerce) can be labelled as expected.

## 6. The Lighthouse module should show *which* audits failed

The comparison reports `SEO 100 → 63` and leaves it there. Running Lighthouse locally against the same content showed exactly two failing audits:

- `is-crawlable` — "Page is blocked from indexing" (weight ≈4): the staging noindex, expected, gone at launch;
- `robots-txt` — "Lighthouse was unable to download a robots.txt file" (weight 1): **a real problem** (see §7).

Printing those two lines in the report would have saved an hour and surfaced a launch-blocking issue immediately. Two further notes for that module:

- Audits *expected* to fail on staging (`is-crawlable`, and `canonical` for the same reason) should be marked as such rather than counted as regressions.
- Lighthouse cannot fetch `robots.txt` through HTTP Basic Auth, so on a password-protected staging site this audit fails for a reason unrelated to the site. The tool already holds the staging credentials — it should fetch `robots.txt` itself, with them, and report the status code.

## 7. The crawler should request `/robots.txt` directly and report its status

The most serious finding of the day appeared in none of the four reports: on this host, `/robots.txt` returned **404**.

The host (Hostinger) answers `/robots.txt` at the edge and returns 404 when no physical file exists, so WordPress's virtual robots.txt never reaches a crawler. Confirmed by installing an empty WordPress on the same account: also 404. The new live site would have launched with no robots.txt at all, and the faceted-filter parameters (`?filter_*`, `?orderby=`, `?min_price=`) would have been crawled freely. Fixed here by placing a real file in the web root.

The crawler does read `robots.txt` ("Respect robots.txt" is on), so it must have received that 404 — but the result never surfaced. **Suggestion:** a first-class row in every crawl summary and in the comparison:

```
robots.txt    A: 200 (1,180 B, 18 rules)    B: 404 — not served    ← critical before launch
sitemap       A: 200 (1,040 URLs)           B: 200 (428 URLs)
```

A missing or unreachable `robots.txt` on site B, when site A serves one, deserves to be critical: it is silent, invisible in page-level data, and shows its effect only weeks after launch.

## 8. Two smaller data issues

- **`Average words / page: 1,136 → 0`.** The per-page diffs in the same report show real word counts for B (one page reads `402 → 2,535`), so the aggregate is wrong — probably averaging over pages whose word count was not stored, or over non-HTML responses only.
- **`Images without an alt attribute: 1,049 → 427`** — that is *every page* on both sides, which is a sign the check is too broad. On this site no rendered page contains a single `<img>` without an `alt` attribute; what the pages do contain are decorative images with `alt=""`, which is the correct way to mark an image a screen reader should skip. Lighthouse's accessibility score for the same pages is 100. **Suggestion:** count only images where the attribute is absent; report `alt=""` separately as "decorative", and flag it only when the image is the sole content of a link, where an empty alt really is a defect.

---

## What the reports got right

Worth saying plainly, because the signal was good where it counted:

- Redirect-map matching across domains (A on `www.eco-fridge.co.uk`, B on a staging subdomain) worked, including paths rewritten by the map.
- The before/after issue table is genuinely useful: 4xx 10 → 0, broken internal links 44 → 0, missing H1 11 → 0, duplicate titles 114 → 0, missing meta descriptions 433 → 0, exact duplicate content 22 → 0, orphan pages 642 → 2, slow responses 1,077 → 145.
- "References to old/staging: 0" is exactly the check a migration needs, and the first thing a reviewer looks for.
- The three-phase Lighthouse structure (baseline → pre-launch → post-launch) with a fixed page list is the right shape for proving a rebuild to a client: performance 34 → 86 on mobile and 70 → 97 on desktop, LCP 8.95 s → 2.95 s, CLS 0.922 → 0.001, page weight 1.84 MB → 974 KB.
- Keeping the per-page table sorted by change in performance makes the weakest templates obvious at a glance.

## The one-paragraph summary

The crawl data is good; the **classification** on top of it is what needs work. Three changes would carry most of the value: (1) probe each candidate path on site B before calling it lost, and classify by the response code rather than by absence from the inventory; (2) suppress or clearly mark the metrics that a noindexed staging site inevitably breaks, and group the attachment-page diffs; (3) promote three silent facts to first-class rows — the status of `robots.txt`, the actual failing Lighthouse audits, and the specific internal links that redirect site-wide. In this migration those three changes would have found every real problem in the first minute and recommended zero unnecessary redirects.

---

*Prepared 30 October 2026 from the four reports listed above, with every claim checked against the live and staging sites.*
