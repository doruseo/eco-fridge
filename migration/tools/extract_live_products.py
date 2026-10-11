"""Extract product data from the crawled live product pages into JSON.

Usage: python -I extract_live_products.py <live_pages_dir> <out_json>
"""
import html
import json
import os
import re
import sys


def block(h, start_pat, tag="div"):
    """Return the balanced <tag>...</tag> element starting at the first match of start_pat."""
    m = re.search(start_pat, h)
    if not m:
        return ""
    i = m.start()
    depth = 0
    for t in re.finditer(r"<(/?)%s\b[^>]*>" % tag, h[i:]):
        depth += -1 if t.group(1) else 1
        if depth == 0:
            return h[i:i + t.end()]
    return ""


def inner(el):
    return re.sub(r"^<[^>]+>|</[a-z0-9]+>$", "", el.strip(), flags=re.S).strip()


def text(s):
    return html.unescape(re.sub(r"<[^>]+>", " ", s)).replace("\xa0", " ").strip()


def main(src, out):
    products = {}
    for f in sorted(os.listdir(src)):
        if not f.startswith("product__") or not f.endswith(".html"):
            continue
        slug = f[len("product__"):-5]
        h = open(os.path.join(src, f), encoding="utf8").read()
        p = {"slug": slug}
        m = re.search(r'<div id="product-(\d+)" class="([^"]+)"', h)
        p["live_id"] = int(m.group(1)) if m else 0
        classes = m.group(2) if m else ""
        p["stock"] = "outofstock" if "outofstock" in classes else ("instock" if "instock" in classes else "")
        p["cats"] = re.findall(r"product_cat-([a-z0-9-]+)", classes)
        images = block(h, r'<div class="images">')
        desc = re.search(r'<div class="images">\s*<div style="font-size: 0.8em;">(.*?)</div>\s*<section', images, re.S)
        p["description"] = desc.group(1).strip() if desc else ""
        t = re.search(r'<h1 class="desktop">(.*?)</h1>', h, re.S)
        p["title"] = text(t.group(1)) if t else ""
        price = re.search(r'<div class="price-excluding-tax">(.*?)</div>', h, re.S)
        p["price_label"] = text(price.group(1)) if price else ""
        amount = re.search(r"([0-9][0-9,]*\.[0-9]{2})", p["price_label"])
        p["price"] = amount.group(1).replace(",", "") if amount else ""
        model = re.search(r'<div class="sku-wrapper"><strong>Model:</strong>(.*?)</div>', h, re.S)
        p["model_line"] = text(model.group(1)) if model else ""
        code = re.search(r'<div class="sku-wrapper">Product Code:(.*?)</div>', h, re.S)
        p["product_code"] = text(code.group(1)) if code else ""
        specs = block(h, r'<div class="product-specs">')
        p["specs"] = [text(x) for x in re.findall(r"<p>(.*?)</p>", specs, re.S) if text(x)]
        feats = block(h, r'<div class="product-features">')
        p["features"] = inner(feats) if feats else ""
        wrapper = block(h, r'<div class="spec-table-wrapper">')
        p["spec_tables"] = re.findall(r'(<table id="tablepress-\d+".*?</table>)', wrapper, re.S)
        p["spec_table_ids"] = [int(x) for x in re.findall(r'id="tablepress-(\d+)"', wrapper)]
        note = re.search(r'<div class="spec-table-wrapper">\s*<p>(.*?)</p>', h, re.S)
        p["spec_note"] = note.group(1).strip() if note else ""
        ups = block(h, r'<section class="up-sells upsells products">', "section")
        p["upsells"] = re.findall(r'href="https://www\.eco-fridge\.co\.uk/product/([^/"]+)/" class="woocommerce-LoopProduct-link', ups)
        gallery = block(h, r'<section id="wpis-gallery"', "section")
        p["gallery"] = [re.sub(r"-\d+x\d+(\.[a-z]+)$", r"\1", g.split("/uploads/")[-1]) for g in re.findall(r'<img [^>]*src="([^"]+)"', gallery)]
        main_img = re.search(r'<section class="slider wpis-slider-for">.*?<img [^>]*src="([^"]+)"', h, re.S)
        p["image"] = main_img.group(1).split("/uploads/")[-1] if main_img else ""
        yt = re.findall(r"youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{11})", h)
        p["youtube"] = sorted(set(yt))
        products[slug] = p
    json.dump(products, open(out, "w", encoding="utf8"), indent=1, ensure_ascii=False)
    print(len(products), "products")
    print(sum(1 for p in products.values() if p["spec_tables"]), "with spec tables;",
          sum(1 for p in products.values() if not p["description"]), "without description;",
          sum(1 for p in products.values() if not p["price"]), "without price")


if __name__ == "__main__":
    main(sys.argv[1], sys.argv[2])
