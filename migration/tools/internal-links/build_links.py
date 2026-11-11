"""Add contextual internal links to the blog posts.

Reads the JSON export of the live posts and terms (export.php), finds relevant
anchor phrases inside the paragraph and list text of every post, and links them
to the commercial category / market landing pages first and to related blog
posts second.

Rules:
- only text inside <p> and <li> (never headings, captions, buttons or image links);
- never inside an existing <a>, never inside an HTML tag;
- one new link per paragraph, one link per target per post;
- product categories first (most specific first), at most one market page;
- article links go to the posts with the fewest inbound links first;
- a target the post already links to is skipped;
- a post never links to itself;
- at most MAX_CAT category/market links and MAX_POST article links per post;
- the anchor keeps the original wording and case of the text.

Usage: python build_links.py live.json changes.json report.tsv
"""

import html
import json
import re
import sys

MAX_CAT = 5
MAX_POST = 3

# Commercial category and market landing pages: (phrases, path). The list order
# is the priority: specific categories before the generic ones, markets last.
CATEGORY_RULES = [
    (["multideck display fridges", "multideck display fridge", "multideck fridges", "multideck fridge",
      "multideck chillers", "multideck chiller", "multidecks", "multideck"],
     "/product-category/commercial-display-fridge/multideck-fridge/"),
    (["patisserie display fridges", "patisserie display fridge", "patisserie fridges", "patisserie fridge",
      "cake display fridges", "cake display fridge", "cake fridges", "cake fridge", "cake display"],
     "/product-category/commercial-display-fridge/patisserie-fridge/"),
    (["serve over counter display fridges", "serve over counter display fridge", "serve over counters",
      "serve over counter", "serve over display fridges", "serve over display fridge", "serve-over counter"],
     "/product-category/commercial-display-fridge/serve-over/"),
    (["grab and go fridges", "grab and go fridge", "grab and go multideck", "grab and go display"],
     "/product-category/commercial-display-fridge/grab-and-go/"),
    (["beer fridges", "beer fridge"],
     "/product-category/commercial-display-fridge/commercial-drinks-fridge/beer-fridge/"),
    (["wine fridges", "wine fridge"],
     "/product-category/commercial-display-fridge/commercial-drinks-fridge/wine-fridge/"),
    (["back bar fridges", "back bar fridge", "bar fridges", "bar fridge"],
     "/product-category/commercial-display-fridge/commercial-drinks-fridge/bar-fridge/"),
    (["drinks fridges", "drinks fridge", "drinks chillers", "drinks chiller", "bottle coolers", "bottle cooler",
      "drinks coolers", "drinks cooler"],
     "/product-category/commercial-display-fridge/commercial-drinks-fridge/"),
    (["glass door fridges", "glass door fridge"],
     "/product-category/commercial-display-fridge/upright-fridge/"),
    (["glass front fridges", "glass front fridge"],
     "/product-category/commercial-display-fridge/glass-front-fridge/"),
    (["counter display fridges", "counter display fridge", "countertop display fridges",
      "countertop display fridge", "counter top display fridge"],
     "/product-category/commercial-display-fridge/counter-fridge/"),
    (["upright display freezers", "upright display freezer"],
     "/product-category/display-freezer/upright-freezer/"),
    (["chest freezers", "chest freezer"],
     "/product-category/display-freezer/chest-freezer/"),
    (["low temperature freezers", "low temperature freezer", "low temp freezer", "laboratory freezers",
      "laboratory freezer", "low temperature chest freezers", "low temperature chest freezer"],
     "/range/low-temperature-chest-freezers/"),
    (["under counter fridges", "under counter fridge", "undercounter fridges", "undercounter fridge",
      "under-counter fridge"],
     "/product-category/storage-fridge/under-counter-fridge/"),
    (["under counter freezers", "under counter freezer", "undercounter freezers", "undercounter freezer"],
     "/product-category/storage-freezer/under-counter-freezer/"),
    (["upright freezers", "upright freezer"],
     "/product-category/storage-freezer/upright-freezer-storage-freezer/"),
    (["meat fridges", "meat fridge", "gastronorm fridges", "gastronorm fridge", "gastronorm chiller"],
     "/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/"),
    (["meat freezers", "meat freezer", "gastronorm freezers", "gastronorm freezer"],
     "/product-category/storage-freezer/upright-freezer-storage-freezer/gastronorm-freezer/"),
    (["heated display cabinets", "heated display cabinet", "heated displays", "heated display"],
     "/product-category/heated-display/"),
    (["deli counters", "deli counter", "deli displays", "deli display"],
     "/ranges/deli-displays/"),
    (["retarder provers", "retarder prover"],
     "/range/bakery-cabinets/retarder-provers/"),
    (["commercial ovens", "commercial oven", "bakery ovens", "bakery oven"],
     "/range/bakery-cabinets/ovens/"),
    (["medical fridges", "medical fridge", "pharmacy fridge"],
     "/range/medical/"),
    (["graded fridges", "used fridges", "used commercial fridges", "second-hand fridges"],
     "/range/graded-units/"),
    (["display fridges", "display fridge", "display chillers", "display chiller"],
     "/product-category/commercial-display-fridge/"),
    (["display freezers", "display freezer", "ice cream freezers", "ice cream freezer"],
     "/product-category/display-freezer/"),
    (["storage fridges", "storage fridge", "storage chillers", "storage chiller"],
     "/product-category/storage-fridge/"),
    (["storage freezers", "storage freezer"],
     "/product-category/storage-freezer/"),
    (["refrigerated display cabinets", "display cabinets", "refrigerated display"],
     "/range/display-cabinets/"),
    (["storage cabinets"],
     "/range/storage-cabinets/"),
    (["commercial fridges", "commercial fridge", "commercial refrigerators", "commercial refrigerator"],
     "/ranges/commercial-fridge/"),
    (["commercial freezers", "commercial freezer"],
     "/ranges/commercial-freezer/"),
    # Markets (at most one per post)
    (["coffee shops", "coffee shop", "cafés", "cafes", "café"], "/markets/coffee-shops/"),
    (["delis and butchers", "butchers", "butcher shops", "butcher shop", "delicatessen"],
     "/markets/delis-and-butchers/"),
    (["convenience stores", "convenience store"], "/markets/convenience-stores/"),
    (["forecourts", "forecourt", "petrol stations", "petrol station"], "/markets/forecourts/"),
    (["schools", "school canteen"], "/market/schools/"),
    (["sports clubs", "sports club"], "/markets/sports-clubs/"),
    (["airports", "airport"], "/markets/airports/"),
    (["restaurants"], "/markets/restaurants/"),
]

# Related blog posts: (phrases, slug).
POST_RULES = [
    (["refrigeration maintenance", "maintenance guide", "regular maintenance", "routine maintenance",
      "maintain your commercial fridge", "maintaining your fridge", "servicing"],
     "commercial-refrigeration-maintenance-guide"),
    (["freezer maintenance", "maintain your freezer", "maintaining your freezer"],
     "commercial-freezer-maintenance-guide"),
    (["food safety", "safe food temperatures", "safe temperatures", "food temperatures",
      "temperature danger zone", "danger zone"],
     "ensure-safe-food-temperatures"),
    (["food regulations", "food hygiene regulations", "food hygiene", "hygiene regulations",
      "food standards agency"],
     "10-ways-to-follow-the-food-regulations-in-the-uk"),
    (["buying guide", "buying a commercial fridge", "choosing a commercial fridge"],
     "commercial-fridge-buying-guide"),
    (["buying a commercial freezer", "choosing a commercial freezer", "freezer buying"],
     "commercial-freezer-purchasing-guide"),
    (["defrost your freezer", "defrost a chest freezer", "defrosting your freezer", "defrosting the freezer",
      "manual defrost"],
     "how-to-defrost-a-chest-freezer"),
    (["condenser coil", "condenser coils", "condenser", "clean the condenser"],
     "how-to-clean-the-chiller-condenser"),
    (["level the fridge", "levelling", "leveling", "level surface", "uneven floor"],
     "how-to-level-your-commercial-fridge"),
    (["energy efficiency", "energy efficient", "energy consumption", "energy bills", "running costs"],
     "tips-to-improve-commercial-refrigerator-efficiency"),
    (["eco-friendly", "environmentally friendly", "natural refrigerant", "refrigerant gas", "r290"],
     "eco-friendly-commercial-fridge-cabinets"),
    (["freon", "refrigerant leak", "gas leak"], "is-your-commercial-fridge-spilling-freon"),
    (["bad smell", "bad smells", "unpleasant smell", "unpleasant odours", "bad odours"],
     "best-tips-to-discard-a-horrendous-smell-in-your-commercial-fridge"),
    (["ice build-up", "ice buildup", "water leaking", "water leak", "frost build-up"],
     "water-or-ice-issues-in-your-commercial-fridge"),
    (["takeaway business", "start a takeaway", "starting a takeaway"], "how-to-start-a-takeaway-business"),
    (["online orders", "delivery apps", "food delivery"], "how-to-boost-your-online-takeaway-orders"),
    (["start a pub", "starting a pub", "pub business", "running a pub"],
     "10-most-important-steps-to-start-a-pub-business"),
    (["ice cream business", "ice cream shop", "ice cream parlour"], "how-to-start-an-ice-cream-business"),
    (["patisserie business", "cake shop", "patisserie shop"], "how-to-start-a-patisserie-business"),
    (["start a butcher", "butcher business"], "how-to-start-a-butcher-shop"),
    (["fast food business", "fast food restaurant", "fast food"],
     "5-essential-equipment-to-start-a-fast-food-business"),
    (["kitchen equipment", "commercial kitchen", "restaurant kitchen"],
     "14-must-have-restaurant-kitchen-equipment"),
    (["domestic fridge", "domestic fridges", "domestic refrigerator", "home fridge", "household fridge"],
     "differences-between-commercial-fridges-and-domestic-refrigerators"),
    (["refrigeration cycle", "how a commercial fridge works", "how commercial fridges work"],
     "how-does-commercial-fridges-work"),
    (["store food", "storing food", "food storage"], "how-to-store-food-in-a-commercial-fridge"),
    (["keep food fresh", "keeping food fresh"], "how-to-keep-fresh-food-in-the-fridge"),
    (["chest or upright", "upright or chest", "upright and chest"],
     "upright-freezer-vs-chest-freezer-display-freezers-pros-and-cons"),
    (["in a garage", "in the garage", "in a shed"], "can-i-use-a-chest-freezer-in-a-sheld-or-garage"),
    (["grocery store", "grocery stores"], "how-to-keep-customers-coming-back-to-your-grocery-store"),
    (["product placement", "merchandising", "impulse buys", "impulse purchases"],
     "boost-your-deals-with-shrewd-item-arrangement"),
    (["organise your fridge", "organize your fridge", "organising your fridge", "organizing your fridge"],
     "organizing-a-commercial-fridge-in-your-convenience-store"),
    (["restaurant fridge", "successful restaurant"], "12-tips-to-create-a-successful-restaurant"),
    (["covid", "pandemic"], "the-importance-of-a-commercial-fridge-due-to-covid"),
    (["future of refrigeration", "future of commercial refrigeration"], "the-future-of-commercial-refrigeration"),
    (["cakes and cookies", "store cakes", "storing cakes"], "store-cakes-and-cookies-in-a-commercial-fridge"),
    (["types of commercial", "different types of fridges", "types of fridges"],
     "the-different-types-of-commercial-refrigerators-fridges-for-the-shop"),
    (["right fridge for your business", "right fridge"], "how-to-choose-the-right-fridge-for-your-business"),
    (["keep it cold", "keep your fridge cold", "temperature fluctuations"],
     "how-to-keep-a-commercial-fridge-cold"),
    (["sanitation", "cleaning schedule"],
     "tips-to-rehearsing-legitimate-sanitation-at-your-restaurant"),
    (["store ice cream", "storing ice cream", "display ice cream", "displaying ice cream"],
     "display-and-store-ice-cream-in-ideal-conditions"),
    (["fridges for your bar", "fridges for a bar", "fridge for your bar"], "what-fridges-should-i-use-in-my-bar"),
]

BLOCK_RE = re.compile(r"(<(p|li)\b[^>]*>)(.*?)(</\2>)", re.S | re.I)
TOKEN_RE = re.compile(r"(<[^>]+>)")
MARKET_PREFIXES = ("/markets/", "/market/")


def norm(url):
    url = html.unescape(url).strip()
    url = re.sub(r"^https?://(www\.)?eco-fridge\.co\.uk", "", url, flags=re.I)
    url = url.split("#")[0].split("?")[0]
    if not url.startswith("/"):
        return url
    return url if url.endswith("/") else url + "/"


def compile_rules(rules, to_path):
    """Return [(priority, path, patterns longest first)]; priority = position in the list."""
    out = []
    for prio, (phrases, target) in enumerate(rules):
        pats = [re.compile(r"(?<![\w-])" + re.escape(ph).replace(r"\ ", r"[\s ]+") + r"(?![\w-])", re.I)
                for ph in sorted(phrases, key=len, reverse=True)]
        out.append((prio, to_path(target), pats))
    return out


def text_nodes(inner):
    """Yield (index, text) of the text parts of a block that sit outside any <a>."""
    parts = TOKEN_RE.split(inner)
    depth = 0
    for i, part in enumerate(parts):
        if part.startswith("<"):
            low = part.lower()
            if re.match(r"<a\b", low):
                depth += 1
            elif low.startswith("</a"):
                depth = max(0, depth - 1)
            continue
        if not depth and part.strip():
            yield i, part


def first_match(inner, pats):
    """First (part index, match) of any pattern in the text nodes; longer phrases win on ties."""
    for i, part in text_nodes(inner):
        best = None
        for pat in pats:
            m = pat.search(part)
            if m and (best is None or m.start() < best.start()
                      or (m.start() == best.start() and len(m.group(0)) > len(best.group(0)))):
                best = m
        if best:
            return i, best
    return None


def insert_link(inner, part_index, m, path):
    parts = TOKEN_RE.split(inner)
    t = parts[part_index]
    parts[part_index] = t[:m.start()] + '<a href="%s">%s</a>' % (path, m.group(0)) + t[m.end():]
    return "".join(parts)


def main():
    src, out_json, out_tsv = sys.argv[1:4]
    data = json.load(open(src, encoding="utf-8"))
    post_paths = {p["slug"]: norm(p["url"]) for p in data["posts"]}
    live_paths = {norm(t["url"]) for t in data["terms"] if t["count"] > 0}

    cat_rules = compile_rules(CATEGORY_RULES, lambda p: p)
    missing = sorted({r[1] for r in cat_rules if r[1] not in live_paths})
    if missing:
        sys.exit("Unknown or empty category paths: %s" % missing)
    unknown = [r[1] for r in POST_RULES if r[1] not in post_paths]
    if unknown:
        sys.exit("Unknown post slugs: %s" % unknown)
    post_rules = compile_rules(POST_RULES, lambda s: post_paths[s])

    # Existing inbound links between posts, so new article links go to the least linked posts first.
    inbound = {path: 0 for path in post_paths.values()}
    for p in data["posts"]:
        for u in set(norm(u) for u in re.findall(r'<a\b[^>]*href="([^"]+)"', p["content"], re.I)):
            if u in inbound and u != norm(p["url"]):
                inbound[u] += 1

    changes, report = {}, []
    for p in data["posts"]:
        content = p["content"]
        self_path = norm(p["url"])
        linked = {norm(u) for u in re.findall(r'<a\b[^>]*href="([^"]+)"', content, re.I)} | {self_path}
        blocks = list(BLOCK_RE.finditer(content))

        def candidates(rules):
            found = []
            for prio, path, pats in rules:
                if path in linked:
                    continue
                hits = []
                for b, blk in enumerate(blocks):
                    fm = first_match(blk.group(3), pats)
                    if fm:
                        hits.append((b, fm))
                if hits:
                    found.append((prio, path, hits))
            return found

        cats = candidates(cat_rules)
        products = sorted((c for c in cats if not c[1].startswith(MARKET_PREFIXES)), key=lambda c: c[0])
        markets = sorted((c for c in cats if c[1].startswith(MARKET_PREFIXES)), key=lambda c: c[0])
        articles = sorted(candidates(post_rules), key=lambda c: (inbound[c[1]], c[0]))

        used_blocks, plan = set(), []

        def take(cands, limit, kind):
            n = 0
            for _, path, hits in cands:
                if n >= limit:
                    break
                for b, (pi, m) in hits:
                    if b not in used_blocks:
                        used_blocks.add(b)
                        plan.append((b, pi, m, path, kind))
                        n += 1
                        break

        take(products, MAX_CAT - 1 if markets else MAX_CAT, "category")
        take(markets, 1, "market")
        before = len(plan)
        take(articles, MAX_POST, "article")
        for _, _, _, path, _ in plan[before:]:
            inbound[path] += 1

        if not plan:
            continue
        by_block = {b: (pi, m, path) for b, pi, m, path, _ in plan}
        out, last = [], 0
        for b, blk in enumerate(blocks):
            if b in by_block:
                pi, m, path = by_block[b]
                out.append(content[last:blk.start(3)])
                out.append(insert_link(blk.group(3), pi, m, path))
                last = blk.end(3)
        out.append(content[last:])
        changes[str(p["id"])] = "".join(out)
        for b, pi, m, path, kind in sorted(plan, key=lambda x: x[0]):
            report.append("\t".join([str(p["id"]), p["slug"], kind, m.group(0), path]))

    json.dump(changes, open(out_json, "w", encoding="utf-8"), ensure_ascii=False)
    with open(out_tsv, "w", encoding="utf-8", newline="\n") as f:
        f.write("post_id\tslug\ttype\tanchor\ttarget\n" + "\n".join(report) + "\n")
    print("posts changed: %d, links added: %d" % (len(changes), len(report)))


if __name__ == "__main__":
    main()
