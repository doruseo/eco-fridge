# Cum creăm site-ul live pe Hostinger, cu www în față

Ghid pentru contul de **Agency Startup Web Hosting**, pentru domeniul **eco-fridge.co.uk**, înregistrat la IONOS. Pașii sunt împărțiți în ce facem **joi 5 noiembrie** (fără efect asupra traficului) și ce facem **sâmbătă 7 noiembrie**, în ziua lansării.

Numele butoanelor din hPanel se mai schimbă de la o lună la alta. Scriu cum se numesc acum și ce cauți, ca să le recunoști chiar dacă eticheta diferă.

---

## Partea 1 — joi 5 noiembrie: site-ul live, creat gol

Îl creăm cu două zile înainte ca sâmbătă să avem unde copia și ca să știm dinainte IP-ul de pus în IONOS. Nimeni nu ajunge pe el: DNS-ul încă arată spre Ambrit.

### 1. Adaugă site-ul

**hPanel → Websites → Add Website** (butonul mare din dreapta sus; în unele conturi se numește „Create or migrate a website").

La întrebarea ce fel de site vrei:

- alege **Empty website** / **Blank** / „Skip, I'll set it up myself";
- **nu** alege „WordPress" și nu lăsa instalarea automată. Peste folderul ăsta vine copia de pe staging, iar o instalare proaspătă ar însemna fișiere de șters și o bază de date în plus.

### 2. Pune domeniul, fără www

La pasul cu domeniul alege **„Use an existing domain"** (nu „Buy a domain", nu „Use a temporary domain") și scrie:

```
eco-fridge.co.uk
```

**Fără `www.`** — asta e important și e contraintuitiv. Hostinger ia domeniul înregistrabil ca domeniu al site-ului și servește automat și varianta cu www, ca alias pe același folder. Dacă adaugi `www.eco-fridge.co.uk` ca domeniu principal, Hostinger îl tratează ca pe un subdomeniu separat și ajungi cu două intrări care se bat cap în cap.

### 3. Ignoră avertismentul despre DNS

Hostinger va spune că domeniul nu arată spre serverele lor și îți va propune să schimbi nameserverele. **Refuză**: alege „I'll do it later", „Skip" sau închide pur și simplu fereastra.

Nameserverele rămân la IONOS, cum sunt acum. Noi schimbăm sâmbătă o singură înregistrare A, din contul IONOS. Dacă accepți propunerea Hostinger de a muta nameserverele, pleacă și emailul odată cu ele — exact ce nu vrem.

### 4. Notează trei lucruri

După ce site-ul e creat, din panoul lui:

1. **IP-ul serverului** — îl găsești la Websites → site → **Dashboard**, în secțiunea „Point your domain" / „DNS records", sau la Hosting → Details → „Website IP". Ăsta e numărul pe care îl punem sâmbătă în IONOS, în locul lui 185.230.220.98.
2. **Calea docroot-ului** (ceva de forma `/home/u510186744/websites/<cod>/public_html`) — acolo copiem site-ul.
3. **Versiunea de PHP** — pune-o pe **8.3**, ca pe staging (Websites → site → Advanced → PHP Configuration).

### 5. Verifică www

La **Domains** (sau Websites → site → Domains / „Manage domains") trebuie să vezi `eco-fridge.co.uk`, iar lângă el, ca alias, `www.eco-fridge.co.uk`. În mod normal Hostinger îl adaugă singur.

Dacă nu apare, adaugă-l manual ca **alias** (uneori se numește „Domain alias", „Parked domain" sau „Addon domain") **spre același folder**, nu ca site nou.

---

## Partea 2 — de unde vine „www în față"

Merită înțeles o dată, pentru că nu e o setare din hPanel:

- **Hostinger** servește ambele variante, cu și fără www. Nu are o setare „canonic".
- **WordPress** decide care e varianta oficială, prin `siteurl` și `home`. Le punem pe `https://www.eco-fridge.co.uk` la pasul de search-replace, sâmbătă la 07:15.
- Din momentul acela, **WordPress redirectează singur** `eco-fridge.co.uk` → `www.eco-fridge.co.uk`, cu 301. La fel fac și sitemapul, canonical-urile și linkurile din conținut.

Nu e nevoie de reguli de redirect în hPanel. Dacă vrei totuși redirectul la nivel de server, înaintea WordPress-ului, se adaugă în `.htaccess`, **înainte** de blocul `# BEGIN WordPress`:

```apache
RewriteEngine On
RewriteCond %{HTTP_HOST} ^eco-fridge\.co\.uk$ [NC]
RewriteRule ^(.*)$ https://www.eco-fridge.co.uk/$1 [R=301,L]
```

E opțional. WordPress face oricum treaba, iar o regulă în plus e încă un loc de verificat dacă ceva merge prost.

---

## Partea 3 — sâmbătă: SSL **după** DNS, niciodată înainte

### De ce în ordinea asta

Certificatul gratuit e Let's Encrypt, iar Let's Encrypt validează prin **HTTP pe domeniu**: cere un fișier de la `http://www.eco-fridge.co.uk/.well-known/...`. Cât timp DNS-ul arată spre Ambrit, cererea aia ajunge pe **serverul vechi**, care nu are fișierul. Rezultat: emiterea eșuează, uneori cu blocaj temporar de la Let's Encrypt (sunt limite la câte încercări ratate accepți pe oră).

Deci: **întâi DNS, apoi certificat.** Invers nu merge, oricât de repede ai apăsa.

### Ordinea exactă în dimineața lansării

| Oră | Pas |
|---|---|
| 07:30 | Schimbi `A @` în IONOS, din IP-ul Ambrit în IP-ul Hostinger notat joi |
| 07:35 | Aștepți 5–15 minute (cu TTL 300) și verifici că domeniul ajunge la Hostinger |
| ~07:45 | hPanel → Websites → site → **Security → SSL** → **Install SSL** (gratuit, Let's Encrypt) |
| | Aștepți până scrie **Active** — de obicei 1–5 minute |
| | Activezi **Force HTTPS** din aceeași pagină |
| | Verifici (comenzile de mai jos) |

Verificarea că DNS-ul s-a mutat, înainte de a cere certificatul:

```bash
nslookup www.eco-fridge.co.uk 8.8.8.8
curl -sI http://www.eco-fridge.co.uk/ | head -3
```

Dacă IP-ul returnat e cel de Hostinger, poți cere certificatul. Dacă e încă 185.230.220.98, mai aștepți.

### Fereastra de câteva minute

Între schimbarea DNS-ului și certificatul emis există câteva minute în care cineva care nimerește direct pe `https://` vede o eroare de certificat. Sâmbătă la 07:40 dimineața, pe un site B2B din UK, înseamnă aproape nimeni.

Dacă vrei zero risc, varianta fără fereastră e: emitem certificatul **joi**, prin validare **DNS** (un TXT temporar la IONOS, care nu depinde de unde arată site-ul), și îl urcăm în hPanel ca **Custom SSL**. Apoi, după o săptămână, îl lăsăm pe Hostinger să preia automat cu Let's Encrypt. E mai multă muncă pentru un risc mic — spune-mi dacă vrei varianta asta și o pregătim joi.

### Verificări după ce certificatul e activ

```bash
# www pe https trebuie sa raspunda 200
curl -sI https://www.eco-fridge.co.uk/ | head -3

# domeniul fara www trebuie sa redirecteze 301 catre www
curl -sI https://eco-fridge.co.uk/ | head -5

# http trebuie sa redirecteze catre https
curl -sI http://www.eco-fridge.co.uk/ | head -5

# certificatul trebuie sa acopere ambele nume
openssl s_client -connect www.eco-fridge.co.uk:443 -servername www.eco-fridge.co.uk </dev/null 2>/dev/null | openssl x509 -noout -subject -ext subjectAltName
```

La ultima comandă trebuie să vezi **ambele** nume: `eco-fridge.co.uk` și `www.eco-fridge.co.uk`. Dacă apare doar unul, certificatul s-a emis înainte ca aliasul www să fie în vhost — se cere din nou, din aceeași pagină, după ce aliasul e la locul lui.

---

## Rezumat în cinci rânduri

1. Joi: site gol în hPanel, domeniul **fără www**, nameserverele rămân la IONOS, notezi IP-ul și pui PHP 8.3.
2. Joi: verifici că `www.eco-fridge.co.uk` apare ca alias al aceluiași site.
3. Sâmbătă 07:15: search-replace pune `https://www.eco-fridge.co.uk` în WordPress — de aici vine www-ul canonic.
4. Sâmbătă 07:30: schimbi **o singură înregistrare** în IONOS, `A @`.
5. Sâmbătă ~07:45, **după** ce DNS-ul s-a mutat: instalezi SSL, aștepți „Active", pornești Force HTTPS, verifici cu comenzile de mai sus.
