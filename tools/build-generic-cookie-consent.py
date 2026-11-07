"""Builds the general O2 Digital edition of the cookie consent plugin.

Source: ef-cookie-consent/ (the copy that runs on eco-fridge.co.uk).
Output: ../WP Plugins/ef-cookie-consent.zip (outside the repo), a version any
O2 Digital site can install:

  - name "O2 Cookie Consent", author O2 Digital with a link to seo2agency.com;
  - no Eco-Fridge leftovers: the efb_* cookie entry of the ECO-Fridge Blocks
    plugin is removed from the cookie library, and the default main colour is
    a neutral blue instead of the Eco-Fridge green;
  - without the temporary ?efcc_test diagnostic of 2026-11-07.

The folder, text domain, option names and PHP prefixes stay ef-cookie-consent /
efcc, so installing this zip over the Eco-Fridge copy updates it in place and
saved settings are kept.

Run from the repo root: python tools/build-generic-cookie-consent.py
"""
import os
import re
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, 'ef-cookie-consent')
OUT = os.path.join(os.path.dirname(ROOT), 'WP Plugins', 'ef-cookie-consent.zip')
AUTHOR_URI = 'https://seo2agency.com/'
NEUTRAL = '#2563eb'


def must_replace(text, old, new, name, count=1):
    found = text.count(old)
    if found != count:
        sys.exit(f'{name}: expected {count} x {old[:60]!r}, found {found}')
    return text.replace(old, new)


def transform(rel, text):
    name = rel.replace(os.sep, '/')
    if name == 'ef-cookie-consent.php':
        text = must_replace(text, ' * Plugin Name:       EF Cookie Consent\n',
                            ' * Plugin Name:       O2 Cookie Consent\n'
                            f' * Plugin URI:        {AUTHOR_URI}\n', name)
        text = must_replace(text, ' * Author:            O2 Digital\n',
                            ' * Author:            O2 Digital\n'
                            f' * Author URI:        {AUTHOR_URI}\n', name)
    if name == 'includes/class-efcc-admin.php':
        text = must_replace(text, '<h1>EF Cookie Consent</h1>', '<h1>O2 Cookie Consent</h1>', name)
    if name == 'includes/class-efcc-settings.php':
        text = must_replace(text, "'primary'      => '#1f821f',", f"'primary'      => '{NEUTRAL}',", name)
    if name == 'includes/class-efcc-library.php':
        text = must_replace(
            text,
            "\t\t\t// This site (ECO-Fridge Blocks).\n"
            "\t\t\tarray( 'efb_*', 'necessary', 'This website', 'Technical cookie of the website (forms, basket reminders).', 'Session', '' ),\n",
            '', name)
    if name == 'includes/class-efcc-frontend.php' and 'efcc_test' in text:
        # Drop the temporary page-speed diagnostic.
        text = re.sub(r"\t/\*\*\n\t \* Page-speed diagnostic \(temporary.*?\n\t}\n\n", '', text, count=1, flags=re.S)
        text = must_replace(text, "\t\tif ( 'nobanner' === self::test_mode() ) {\n\t\t\treturn true;\n\t\t}\n", '', name)
        text = must_replace(text, "&& '' === self::test_mode() ", '', name)
    if name in ('assets/efcc.css', 'assets/admin.css'):
        text = text.replace('#1f821f', NEUTRAL)
    text = text.replace('EF Cookie Consent', 'O2 Cookie Consent')
    leftovers = re.findall(r'eco-?fridge|efcc_test|1f821f', text, re.I)
    if leftovers:
        sys.exit(f'{name}: Eco-Fridge leftovers {sorted(set(leftovers))}')
    return text


def main():
    count = 0
    with zipfile.ZipFile(OUT, 'w', zipfile.ZIP_DEFLATED) as z:
        for root, _, files in os.walk(SRC):
            for f in sorted(files):
                path = os.path.join(root, f)
                rel = os.path.relpath(path, SRC)
                with open(path, encoding='utf-8', newline='') as fh:
                    text = fh.read().replace('\r\n', '\n')
                text = transform(rel, text)
                z.writestr('ef-cookie-consent/' + rel.replace(os.sep, '/'), text.encode('utf-8'))
                count += 1
    print(OUT, os.path.getsize(OUT), 'bytes,', count, 'files')


if __name__ == '__main__':
    main()
