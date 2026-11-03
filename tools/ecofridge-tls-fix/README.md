# ECO-Fridge TLS fix for Migrate Guru

One-off helper for the **old** site (www.eco-fridge.co.uk, WordPress 6.0, PHP 7.2.34),
needed only while cloning it to eco-fridge-backup.o2dev.de.

## Why it exists

Migrate Guru posts to `https://mg.blogvault.net` (see `info.php`, `appUrl()`).
That host serves a `*.blogvault.net` certificate issued by *GlobalSign GCC R46
AlphaSSL CA 2025*, which chains to GlobalSign Root R46. WordPress does not use
the system trust store: it hands cURL its own root list,
`wp-includes/certificates/ca-bundle.crt`. The copy shipped with WordPress 6.0
predates that root, so cURL cannot build a trust path and the migration stops with:

```
cURL error 60: SSL certificate OpenSSL verify result:
unable to get local issuer certificate (20)
```

Verified on 2026-11-03: the live site's own bundle fails that chain
(`verify error:num=20`), while the current Mozilla bundle passes
(`Verify return code: 0 (ok)`).

## What it does

For requests to `blogvault.net` and `migrateguru.com` only, it points the
`sslcertificates` request argument at the current Mozilla root list shipped here
as `ca-bundle.crt`. Certificate verification stays on — only the list of trusted
roots changes, and no other request the site makes is affected. On the Plugins
screen it runs one test request and reports whether the API now answers.

## Use

1. Zip this folder and upload it on the old site: Plugins → Add New → Upload Plugin.
2. Activate it, then read the notice on the Plugins screen.
3. Run the migration.
4. Deactivate and delete it afterwards. It has no place on a live shop beyond this job.

Rebuild the zip with:

```sh
python -c "import zipfile,os; d='tools/ecofridge-tls-fix'; z=zipfile.ZipFile('tools/ecofridge-tls-fix.zip','w',zipfile.ZIP_DEFLATED); [z.write(os.path.join(d,f),'ecofridge-tls-fix/'+f) for f in sorted(os.listdir(d)) if f!='README.md']; z.close()"
```

`ca-bundle.crt` is the public Mozilla CA list from https://curl.se/ca/cacert.pem,
downloaded 2026-11-03. It holds no secrets.
