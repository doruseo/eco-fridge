"""Extract title + main content of crawled live pages/posts into JSON.

Usage: python -I extract_live_content.py <live_pages_dir> <out_json>
"""
import html
import json
import os
import re
import sys

SKIP = re.compile(r"^(product__|range__|ranges__|product-category__|tag__|product-tag__|market__|condition__|brochure|video|author__|_home)")


def balanced(h, start):
    """Return the balanced <section>/<div> element starting at index start."""
    tag = re.match(r"<(\w+)", h[start:]).group(1)
    depth = 0
    for t in re.finditer(r"<(/?)%s\b[^>]*>" % tag, h[start:]):
        depth += -1 if t.group(1) else 1
        if depth == 0:
            return h[start:start + t.end()]
    return ""


def main(src, out):
    data = {}
    for f in sorted(os.listdir(src)):
        if not f.endswith(".html") or SKIP.match(f):
            continue
        h = open(os.path.join(src, f), encoding="utf8").read()
        path = "/" + f[:-5].replace("__", "/") + "/"
        m = re.search(r'<section class="entry-content[^"]*"[^>]*>', h)
        if not m:
            continue
        el = balanced(h, m.start())
        content = re.sub(r"^<section[^>]*>|</section>$", "", el).strip()
        content = re.sub(r"<script.*?</script>", "", content, flags=re.S)
        t = re.search(r'<h1 class="[^"]*entry-title[^"]*"[^>]*>(.*?)</h1>', h, re.S) or re.search(r"<title>(.*?)</title>", h, re.S)
        title = html.unescape(re.sub(r"<[^>]+>", "", t.group(1))).strip() if t else ""
        data[path] = {"title": title, "content": content}
    json.dump(data, open(out, "w", encoding="utf8"), indent=1, ensure_ascii=False)
    print(len(data), "pages/posts")


if __name__ == "__main__":
    main(sys.argv[1], sys.argv[2])
