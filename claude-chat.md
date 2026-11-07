# Claude chat – jurnal de lucru (din 19 octombrie 2026, actualizat continuu)

Jurnalul de lucru pentru site-ul nou Eco-Fridge: ce a cerut Doru, ce s-a făcut, ce s-a decis și ce a rămas de făcut, secțiune cu secțiune, de la statusurile comenzilor și pluginul de plăți Lloyds Cardnet până la lansare.

> **Regulă de lucru (cerută de Doru pe 29 octombrie 2026):** de acum înainte, Claude actualizează acest fișier **direct, fără să mai fie nevoie să i se ceară**, după fiecare lucru făcut sau decizie luată. Fiecare intrare nouă cuprinde: ce a cerut Doru (citat), ce s-a verificat și s-a făcut, ce s-a decis și ce rămâne de făcut; la final se actualizează blocul „Stare". Modificarea se și comite în git. Dacă schimbarea atinge și procedurile, se actualizează în aceeași rundă memoria proiectului și fișierele din Drive (documentul de proces, procedura generală, checklistul eco-fridge și calendarul de lansare). Datele de plată (coduri de aprobare, referințe de tranzacție, credențiale) nu intră niciodată în acest fișier sau în git — ele stau doar în nota de proiect din memorie.

---

## 1. Statusul comenzilor: Pending în loc de On hold

**Doru:**
> vreau ca atunci cand un client plaseaza o comanda pe site, status sa fie "pending" in loc de "on-hold". Ai conexiune SSH la site-ul de staging https://eco-fridge.o2dev.de/

**Ce am făcut (Claude):**
- Am verificat staging-ul prin SSH: singura metodă de plată activă era **Direct bank transfer (BACS)**, iar WooCommerce pune singur aceste comenzi pe *On hold* (ultima comandă, #8007, era on-hold/bacs). Comenzile cu cardul (Lloyds) ajungeau pe *Processing* după plată.
- Am citit modulul existent `astra-child/inc/order-statuses.php` (fluxul de statusuri: Pending = comandă plasată, încă nepreluată).
- Am implementat:
  - comenzile prin transfer bancar și cec intră pe **Pending**;
  - acestea nu mai sunt anulate automat după 60 de minute („Hold stock”);
  - stocul se scade la plasarea comenzii;
  - firma primește emailul „New order”, iar clientul un email nou „Order received” cu datele bancare (`inc/emails/class-ef-email-order-status.php`).
- Am urcat fișierele pe staging (cu backup), le-am verificat cu `php -l` și am rulat un test complet: o comandă BACS de test a ajuns pe `pending`, stocul a fost marcat ca scăzut, comanda a fost exclusă de la anularea automată, s-au generat exact 2 emailuri (interceptate, nu s-au trimis), fără dubluri la reîncercare. Comanda de test (#8018) a fost ștearsă.

**Răspuns (Claude), pe scurt:** pe staging, comenzile noi prin transfer bancar se creau acum ca Pending; am explicat efectele acoperite, ce rămâne neschimbat (comenzile cu cardul, comenzile on-hold existente, lipsa SMTP pe staging) și că modificările nu erau încă în git.

---

## 2. Revenire: On hold e corect

**Doru:**
> ah , scuze,  Bank transfer and cheque orders go to On-Hold. Este corect asa

**Ce am făcut (Claude):**
- Am readus fișierele locale la versiunea din git și pe staging am pus înapoi fișierele originale din backup; verificare `php -l`, cache golit, backup-ul temporar șters.
- Verificat: filtrul pentru Pending și emailul „Order received” nu mai sunt active.

**Răspuns (Claude):** comenzile prin transfer bancar și cec merg din nou pe **On hold**, cele cu cardul pe Processing după plată, iar Pending se setează manual când e nevoie.

---

## 3. Lloyds Cardnet – ce întrebăm despre licența pluginului actual

**Doru:**
> clientul a spus: "mads@eco-fridge.co.uk – Hi Doru, Thats great, how would we go about swapping this over and using the Lloyds Cardnet account we have."
> Vreau sa-i explic ca stiu de faptul ca Eco-Fridge are deja un cont la Lloyds, insa procesatorul de plati este cel pe care trebuie sa-l setam. Verifica mai intai pluginul actual al eco-fridge (site-ul vechi live actual). Eu vad in site-ul lor WooCommerce LloydsCardnetCw (Visit plugin site: http://www.customweb.ch/ care are redirect catre https://en.wallee.com/). Intrand pe site-ul lor, am gasit https://wordpress.org/plugins/woo-wallee/. De fapt ce informatii sa-i cer lui Mads referitor la licenta pentru pluginul pe care il au la actualul site live?

**Ce am verificat (Claude)** – în backup-ul site-ului vechi de pe staging (`.ef-backup-2026-10-14`):
- Pluginul e **WooCommerce LloydsCardnetCw v2.0.245**, de la **customweb GmbH**, vândut prin **sellxed.com**; codul e din 2018.
- Licența e **criptată în codul pluginului** (`lib/Customweb/Licensing/LloydsCardnetCw/License.php`), nu e o cheie din setări; domeniul și data de expirare se văd doar în contul sellxed.
- Nu există informații de compatibilitate cu WooCommerce 11 / PHP 8.3.
- **`woo-wallee` e alt produs**: conector pentru un cont wallee, nu înlocuitor pentru pluginul Lloyds.

**Răspuns (Claude), pe scurt:**
- Contul Lloyds Cardnet (Store ID + Shared Secret) rămâne neschimbat; pluginul e doar conectorul dintre WooCommerce și Lloyds.
- Două variante: reutilizăm pluginul sellxed (dacă licența și actualizările permit) sau folosim **AG WooCommerce Lloyds Cardnet Payment Gateway**.
- De cerut lui Mads: contul sellxed / factura, domeniul licenței, dacă abonamentul de actualizări e activ, cine are acces la portalul Lloyds Cardnet.
- Recomandare: dacă datele sellxed nu se găsesc repede sau abonamentul a expirat, mergem pe pluginul AG.

---

## 4. Licența e pe eco-fridge.co.uk; ce e „portalul Lloyds Cardnet”

**Doru:**
> mai intai raspund eu: licenta este pe domeniul eco-fridge.co.uk ; Intrebare pentru tine: Acces la portalul Lloyds Cardnet - te referi la contul bancar?

**Răspuns (Claude):**
- Nu e contul bancar / internet banking. E **portalul de comerciant Lloyds Cardnet (Virtual Terminal)**, unde firma vede tranzacțiile cu cardul, face rambursări și are acces la magazinul de test.
- Nu e obligatoriu pentru conectare (Store ID + Shared Secret ajung), dar ajută la testare: cineva confirmă în portal că plata de test a ajuns la Lloyds. Nu avem nevoie de parolă.
- Licența pe eco-fridge.co.uk e bună pentru lansare (același domeniu), dar: staging-ul e pe alt domeniu, deci s-ar putea ca pluginul să nu meargă acolo; și rămâne esențial abonamentul de actualizări, pentru o versiune compatibilă cu WooCommerce 11 / PHP 8.3.

---

## 5. Kass are acces la Virtual Terminal

**Doru:**
> Kass are acces la Virtual Terminal

**Răspuns (Claude):** Kass confirmă plata de test în Virtual Terminal; domeniul licenței e cunoscut, deci iese din email. Detaliile au fost notate și în memoria proiectului.

---

## 6. Emailul final către Mads

Cerințe adăugate de Doru pe parcurs:
> scrie la final ca eu la acest plugin ma refeream ca recomand sa-l cumpere ei daca vor sa detina toate drepturile asupra magazinului

> mai scrie o linie de final in care sa-i spui lui Mads ca decizia le apartine, daca voi cumpara eu sau ei licenta acestui plugin, iar asta va fi un detaliu de final de migrare.

**Versiunea finală:**

> Hi Mads,
>
> Yes, we're aware Eco-Fridge already has a Lloyds Cardnet account, and that stays exactly as it is. We already have your Store ID and Shared Secret details, so the account side is covered.
>
> What we need to set up is the plugin that connects the new WooCommerce site to Lloyds. Your current site uses "WooCommerce LloydsCardnetCw" by customweb/sellxed, licensed for eco-fridge.co.uk. The installed version is from 2018, so to use it on the new site we need an up-to-date version. Could you please send us:
>
> 1. The login (or the purchase email / invoice) for the sellxed.com account where the plugin was bought
> 2. Whether the update/support subscription is still active, and its expiry date
>
> Once the plugin is set up, we'll make a test payment and ask Kass to confirm in the Lloyds Cardnet Virtual Terminal that it came through, before we go live.
>
> If the subscription has expired and the plugin can't be updated, the alternative is a current, actively maintained Lloyds Cardnet plugin for WooCommerce, which uses your same Lloyds account. We'd only need to enter your existing details and test it.
>
> This alternative, the AG WooCommerce Lloyds Cardnet Payment Gateway, is the plugin I was referring to earlier. I recommend that you purchase it yourselves, so the licence is in Eco-Fridge's name and you keep full ownership of everything your shop runs on.
>
> That said, the decision is entirely yours. Either you buy the licence or I buy it on your behalf, and we can settle this as one of the final steps of the migration.
>
> Kind regards,
> Doru

---

## 7. Salvarea conversației

**Doru:**
> salveaza tot ce faci, ce discutam, ce scriu eu si ce raspunzi tu intr-un fisier claude-chat.md

**Claude:** am salvat conversația în acest fișier (`claude-chat.md`, în rădăcina proiectului). Datele sensibile (Store ID-uri, Shared Secret) nu sunt incluse.

---

## 8. Footer: imaginea Facebook la hover (2026-10-23)

**Doru:**
> in footer pe prima coloana din cele 3 coloane, am o imagine a facebook cu link catre pagina de facebook a eco-fridge. La mouse hover aceasta imagine se albeste toata, are o umbra alba, o pata alba peste toata imaginea. Poti sa stergi acea umbra alba peste imagine care o face sa arate urat si sa ii pui o animatie sau ceva specific on-hover care sa faca imaginea de facebook sa arate in continuare bine (profesional ca design) dar care sa ramana clara? Pune mai intai pe local sa vad cum arata.

> sau implementeaza direct pe staging https://eco-fridge.o2dev.de/ daca ti-e mai usor si este mai rapid

**Ce am făcut (Claude):**
- Cauza: regula de hover a link-urilor din footer pune un fundal (la link-urile text e o linie de 1px); la link-ul cu imagine dimensiunea liniei se pierdea, deci fundalul acoperea toată imaginea, iar imaginea primea și opacitate 85%.
- În `astra-child/style.css`: fără fundal și fără transparență pe link-urile cu imagine; la hover imaginea se ridică 2px și primește o umbră închisă discretă; contur verde la navigarea cu tastatura; fără mișcare pentru cine are reducerea animațiilor activată.
- Pus pe local și pe staging (după ce am verificat că `style.css` de pe staging era identic cu cel din git).

**Doru:**
> arata super, urca pe git si merge pr

**Claude:** ramura `fix/footer-facebook-hover`, PR #14, merge făcut în `main`.

---

## 9. Bara de filtre pentru produse (2026-10-27)

**Doru:**
> dezvolta bara de filtre astfel incat aceste filtre sa se comporte relevant pentru o bara de filtre, sa contina categoriile relevante, iar categoriile sa fie expandabil (inchis, cu o sageata in jos care sa arate utilizatorului ca poate sa deschida/extinda in jos lista de categorii), cu pretul asa cum este acum (nicio schimbare, doar sa aiba intervalul de pret aferent categoriei in care se afla utilizatorul in acel moment), cu filtre grupate relevant si logic pentru display cabinets, storage cabinets, fridges, freezers, heated cabinets, bakery cabinets, ovens, medical cabinets, un filtru pentru new sau used, un filtru pentru markets cu lista de markets. Toate acestea selectate unic sa dupa la categoria sau range aferenta (cu url aferent), dar daca sunt selectate mai multe filtre, atunci sa se creeze un url aferent tuturor filtrelor selectate (care sa fie noindex, iar in robots sa fie disallow). In felul acesta utilizatorii vor filtra in pagina doar produsele ce corespund cerintelor lor.

**Întrebări puse de Claude și răspunsurile lui Doru:**
- Structura: **categorii grupate** (cele 8 familii, fiecare închisă cu săgeată, cu range-urile dedesubt), apoi Price, Condition, Markets, Width, Height.
- Ovens: **fără categorie nouă**, ci „product-tag pentru ovens, fridge, freezer, retarder, grill, stand”.
- Aplicare: **imediat la bifare**.

**Doru (în timpul lucrului):**
> pune product-tag relevante la toate produsele astfel incat sa poti avea filtre acolo unde nu poti avea direct din categorie

**Ce am făcut (Claude):**
- Modul nou `astra-child/inc/product-filters.php`: index al produselor vizibile (categorii, tag-uri, piețe, preț, dimensiuni) păstrat în cache, numărul de produse lângă fiecare opțiune, filtrarea în query-ul WooCommerce, `noindex` pe URL-urile filtrate, reguli `Disallow` în `robots.txt`.
- Filtre: Categories (8 grupuri), **Type** (tag-uri noi), Price (widget-ul WooCommerce, cu intervalul contextului curent), Condition (New / Used), Markets, **Features** (tag-urile existente: Black, Stainless Steel, Slimline…), Width, Height.
- Migrarea 63: tag-urile pentru bakery/ovens și sidebar-ul. Migrarea 64: 21 de tag-uri de tip pe toate produsele (clasificate manual după titlu și categorii; paginile lor `noindex`), plus tag-urile de caracteristici completate din titluri.
- Am observat inconsecvențe în categorii (de ex. vitrinele negre Grab & Go / Patisserie nu sunt în „Display Cabinets”, produsele second-hand sunt doar în „Used”) — de aceea tag-urile de tip.
- Test pe local: pagini de categorie, piață, tag, Used, `/shop/` filtrat; logica URL testată cu Node.

**Doru:**
> urcă pe staging, apoi commit și PR

**Claude:** backup (fișiere temă + tabele opțiuni/termeni) în `.ef-backup-2026-10-14/filter-bar-2026-10-27/`, temă urcată, migrările 63 și 64 rulate pe staging (aceleași ID-uri de produs), verificat prin WP-CLI. Ramura `feature/product-filter-bar`, **PR #15** (fără merge).

---

## 10. Filtrele în URL (pentru GA4) și etichetele „Selected”

**Doru:**
> filtrele selectate pentru a restrange aria de produse trebuie sa apara in url dupa "?" (pentru a putea vedea in GA4 ce au cautat clientii) si de asemenea sa apara in zona de filtre bifat ca sa stie clientul pe ce a apasat si chiar sa poata debifa la nevoie

**Întrebare (Claude):** un singur filtru cu pagină proprie — URL cu parametru sau pagina categoriei? **Răspuns Doru: parametru în URL.**

**Ce am făcut (Claude):**
- Pagina curentă e aria de căutare; fiecare bifă o restrânge și apare după `?` (de ex. `/range/display-cabinets/?filter_category=premium-patisserie-display-fridges`). Un singur filtru nu mai duce la pagina categoriei.
- Pagina curentă apare bifată; debifată → `/shop/` cu celelalte filtre păstrate.
- Etichete „Selected:” cu × deasupra filtrelor; bulină cu numărul de filtre active pe titlul fiecărei secțiuni.
- Pe staging + commit în PR #15.

---

## 11. Bakery: fridges / freezers / ovens

**Doru:**
> in categoria https://eco-fridge.o2dev.de/range/bakery-cabinets/ exista fridges, hai sa avem tag de fridges sau freezers sau altceva chiar si la bakery, pentru ca chiar si bakery fridges sunt fridges, iar grills, ovens sunt heated cabinets (cred)

**Claude:** Bakery Fridge → tipul „Upright Storage Fridges”, Bakery Freezer → „Upright Storage Freezers”; Retarder Provers, Ovens, Grills, Oven Stands în filtrul Type. Am recomandat să **nu** pun cuptoarele sub Heated Cabinets (echipament de gătit ≠ vitrină caldă).

**Doru:**
> si scuza-ma ca te zapacesc, dar hai sa facem categorii copil pentru Ovens, Grills, Oven Stands, Bakery Fridges, Bakery Freezers, Retarder Provers. Pune-le in filtre in loc de tags si pune-le si in meniu sub bakery & ovens. Pune tu tag la toate produsele acolo unde este nevoie ca sa poata fi filtrate dupa orice.

> si muta price sus de tot la zona de filtre

**Ce am făcut (Claude):**
- 6 categorii copil sub „Bakery Ovens & Refrigeration” (`/range/bakery-cabinets/ovens/`, `/grills/`, `/oven-stands/`, `/bakery-fridges/`, `/bakery-freezers/`, `/retarder-provers/`), cu imagini și în meniu sub „Bakery & Ovens”.
- În filtre, grupurile Bakery Cabinets și Ovens & Grills folosesc categoriile; tag-urile Bakery Fridges/Freezers șterse; Ovens, Grills, Oven Stands, Retarder Provers rămân ca tipuri (`noindex`).
- **Price e primul filtru.**

**Doru:**
> da, scrie descrierile pentru categoriile noi

**Ce am făcut (Claude):**
- `migration/category-copy/bakery-children.php`: H1, descriere scurtă, text lung, FAQ și articole pentru Commercial Ovens, Commercial Grills, Oven Stands, Bakery Fridges, Bakery Freezers, Retarder Provers — doar din specificațiile reale ale produselor.
- Pagina Bakery trimite spre subcategorii; tabelele comparative iau dimensiunile din atributul „Dimensions” și temperatura din „Features” când lipsește tabelul de specificații.
- La prima rulare locală s-au pierdut temporar secțiunile extinse ale paginii Bakery; restaurate înainte de staging.

**Doru:**
> iarta-ma ca insist, dar bakery fridges sunt in acelasi timp si fridges, la fel freezers,

**Claude:** Bakery Fridge pus și în Commercial Fridges (Freezer era deja în Freezers); Bakery Fridges / Freezers și în meniu sub Storage Fridge / Storage Freezer, deci și în grupurile Fridges / Freezers din filtre. (Backup-ul de dinaintea acestei rulări a ieșit gol — comandă greșită; modificarea e mică și reversibilă.)

---

## 12. Bara de filtre pe mobil + Categories închisă pe desktop

**Doru:**
> poti pe mobile sa pui bara cu filtre si sorting in topul paginii si elementele de filtrare sa fie sub buton Filters care deschide inca un "meniu" cu elementele de filtrare in linie?

> iar pe desktop, categories din bara de filtrare sa fie colapsata total si ea cu sageata de expand.

**Ce am făcut (Claude):**
- Mobil/tabletă: bară sus (lipită sub header) cu **Filters** (bulină cu nr. de filtre active) și **sortare**; Filters deschide un panou lateral cu secțiunile una sub alta, fiecare cu săgeată; sus „Clear all” + etichetele selectate, jos „Show N products”; panoul se redeschide după fiecare bifă.
- Bara fixă de jos a Astra oprită pe paginile de produse.
- Desktop: Categories complet închisă, cu săgeată pe titlu.
- Verificat cu capturi Chrome headless (390px și 1366px). Pe staging + PR #15.

---

## 13. „Out of stock” cu roșu

**Doru:**
> scrie out of stock pe produse in grila de produse cu scris rosu

> fa scrisul actual "out of stock" pe produse in grila de produse cu scris rosu

**Claude:** textul existent al Astra („Out of stock” pe imaginea cardului) e acum roșu (#c62828) și îngroșat — o regulă în `style.css`.

**Doru:**
> da, urcă pe staging și adaugă în PR si salveaza tot progresul si discutiile in claude-chat.md

**Claude:** urcat pe staging (backup în `.ef-backup-2026-10-14/out-of-stock-red-2026-10-27/`), commit în PR #15, iar conversația e adăugată în acest fișier.

---

## 14. Merge PR #15

**Doru:**
> fa merge la PR #15

**Claude:** merge făcut în `main` (8 commit-uri, 11 fișiere: bara de filtre desktop + mobil, tag-urile de tip și caracteristici, categoriile bakery cu textele lor, „Out of stock” roșu). Ramura `feature/product-filter-bar` ștearsă; local pe `main`, la zi cu GitHub. Staging avea deja tot conținutul.

**Doru:**
> salvează progresul în claude-chat.md

**Claude:** am adăugat această secțiune și am actualizat starea de mai jos.

---

## 15. Audit SEO și parametri în URL (PR #16, deschis)

**Ce s-a cerut:** verificarea SEO a site-ului și a URL-urilor cu parametri.

**Claude:** branch `fix/seo-audit-and-params`, PR #16 (încă neunit):
- `?orderby=` primește noindex; în robots.txt sunt blocate `filter_`, `query_type_`, `min_price`, `max_price` și `orderby`.
- Migrarea `65-seo-meta.php`: titluri și descrieri unice pentru 88 de produse, 56 de tag-uri de blog, 13 categorii, 9 articole și 5 broșuri, plus linkul mort spre seo2agency scos. Ce era înainte e salvat în `_ef_seo_before_65`.
- La checkout, sumarul comenzii nu mai afișează descrierea scurtă a produsului.
- Crawl pe 428 de URL-uri: 0 probleme. Totul e urcat pe staging și migrarea 65 a rulat acolo.
- Tabelele de garanție („One Week Warranty Trade Price”): **Doru a decis să rămână.**

---

## 16. Emailuri, SMTP, formularul de contact (doar pe staging)

- Notificările pentru companie (comandă nouă, anulată, eșuată, expediată, finalizată) merg la **mads@eco-fridge.co.uk**. Expeditorul e „ECO-Fridge Ltd” <noreply@eco-fridge.co.uk>, iar Reply-To-ul nativ WooCommerce e mads@.
- WP Mail SMTP l-a setat Doru, inclusiv parola: smtp.ionos.co.uk, port 587, TLS. Emailul de test trece SPF, DKIM și DMARC. Site-ul vechi trimite neautentificat, de pe serverul Ambrit (DMARC fail).
- Domeniul și emailul sunt în contul IONOS al clientului, deci DNS-ul se schimbă din IONOS.
- Formularul de contact trimite acum la mads@, pe staging și pe local. Memento-urile pentru coșuri abandonate merg doar la clienți, cu Reply-To mads@.

---

## 17. Lloyds Cardnet (plugin AG)

- Licența pluginului AG (premium 5.1.1) e activată pe staging. Gateway: `ag_cardnet_checkout`, magazin în modul Test, operațiune Sale.
- Comanda de test #8029: comunicarea cu Lloyds și webhook-ul funcționează, dar plata a fost refuzată cu „ECI-7 transactions are not supported by merchant”. Cauza: magazinul de test Lloyds nu are 3DS2 activ. Credențialele sunt corecte.
- Am scris un mesaj pe care Kass îl trimite la suportul Lloyds.
- Lacătul de staging lasă liber `/wc-api/ag_cardnet_checkout` (aprobat de Doru).
- Ora recomandată pentru lansare: **sâmbătă, cam 05:00–09:00 (ora UK)**. TTL-ul DNS din IONOS se scade cu 1–2 zile înainte.
- Backup final al site-ului vechi: UpdraftPlus nu se poate instala acolo (WordPress/PHP prea vechi). Varianta recomandată e un backup de la Ambrit.

---

## 18. Două pluginuri noi: EF Image Optimizer și EF Cookie Consent

**Ce a cerut Doru:**
1. Un optimizator de imagini: WebP și AVIF, servite doar browserelor care le suportă, cu setări detaliate. Conversia în masă o pornește Doru din dashboard, nu Claude; imaginile noi se convertesc automat; nicio imagine nu are voie să se strice.
2. Un plugin de cookies conform legii UK: banner, Cookie Policy cu tabel generat automat, Consent Mode v2 pentru GTM, buton colorat cu cookie în stânga-jos, butoanele REJECT / Manage options / ACCEPT, alegere pe fiecare categorie, dashboard detaliat. Inspirat din Complianz și Clickio.

**EF Image Optimizer** (`ef-image-optimizer/`):
- Conversia se face cu Imagick sau GD. Copiile stau lângă original (`poza.jpg.avif`, `poza.jpg.webp`); originalele nu se modifică niciodată. O copie se păstrează doar dacă e mai mică decât originalul.
- Imaginile se livrează prin `<picture>`. Opțional, există și o regulă `.htaccess` pe baza Accept.
- Conversia merge pe loturi, cu pauză între ele, și se poate pune pe pauză, relua sau opri. Are jurnal de activitate, coloană în Media Library, comenzile WP-CLI `wp efio status|convert|cleanup` și tab-urile Dashboard / Settings / Tools / How it works.
- Pe staging: AVIF și WebP prin Imagick, 838 de imagini.

**EF Cookie Consent** (`ef-cookie-consent/`):
- Banner și fereastră de preferințe cu comutator pe fiecare categorie și pe fiecare serviciu. Butonul colorat cu cookie apare după ce vizitatorul a ales.
- Consent Mode v2: totul pornește pe „denied”, iar la alegere se trimite update-ul. Evenimente dataLayer: `efcc_consent_ready`, `efcc_consent_update`, `efcc_<categorie>_granted`. GTM se încarcă după valorile implicite.
- Blochează scripturile și iframe-urile (harta Google de pe Contact apare cu un placeholder până la acord). Cookie-urile `sbjs_*` ale WooCommerce apar doar după acordul pentru Statistics. GPC e tratat ca „Reject”.
- Scanner de cookie-uri, registru editabil și jurnal de consimțământ (IP trunchiat, export CSV, păstrat 730 de zile).
- Shortcode-uri: `[efcc_cookie_table]`, `[efcc_cookie_policy]`, `[efcc_settings_button]`.
- Dashboard cu 10 tab-uri, inclusiv ghid pentru setarea GTM.
- Migrarea `66-cookie-policy-tables.php`: pe Cookie Policy (UK), tabelele scrise de mână sunt înlocuite cu `[efcc_cookie_table]`, iar la „Your choices” e adăugat butonul de setări. Conținutul vechi e salvat în `_ef_content_before_66`.
- Testat local (banner, reject, accept, preferințe, hartă, jurnal, mobil) și urcat pe staging. Doru a adăugat manual un rând liber după butonul de setări de pe Cookie Policy.

---

## 19. Equal prominence (ICO) și butoanele

**Doru:**
> Dacă scrisul este pentru Accept All este caps lock cu litere mari, încalc legea și cerințele legale de equal prominence? Dacă schimbăm ordinea, butonul de reject să fie la mijloc […], este în regulă?

**Claude:** „equal prominence” nu e scris ca atare în lege. Vine din ghidurile ICO, pe baza PECR și UK GDPR: refuzul trebuie să fie la fel de ușor ca acceptul. Majusculele doar pe Accept nu sunt automat ilegale, dar sunt nudging și cresc riscul. Ordinea butoanelor nu e impusă; Reject la mijloc e în regulă. Riscul real era Accept plin, cu celelalte butoane transparente. (Interpretare a ghidurilor, nu consultanță juridică.)

**Doru:**
> Am schimbat butoanele pe prominence. Te rog să schimbi pluginul să fie setat by default pe equal prominence, iar butonul de reject la mijloc în toate situațiile. Textul cu majuscule.

**Claude:** făcut, pe local și pe staging:
- Equal prominence e varianta implicită.
- Ordinea e mereu `MANAGE OPTIONS | REJECT ALL | ACCEPT ALL` în banner și `SAVE MY CHOICES | REJECT ALL | ACCEPT ALL` în preferințe, inclusiv pe mobil.
- Majusculele se aplică pe toate butoanele deodată (setarea „Button text”).

---

## 20. De amintit în ziua lansării

**Doru:**
> ține minte că nu am adăugat acum ID de GTM, îl adaug pe live în ziua lansării […] să-mi amintești să fac și setările în GTM […] pentru Consent Mode V2 […] să fac setările și să verific conexiunea cu Merchant Center.

**Claude:** salvat în checklist-ul de lansare (memorie) și în Drive (F14-06; Merchant Center era deja la F14-03):
1. **ID-ul GTM** se pune pe live, în Cookie Consent → Google Consent Mode. Atenție la GTM4WP: GTM nu trebuie să se încarce de două ori.
2. **Setările Consent Mode v2 în GTM:** consent overview, setările de consimțământ pe tag-urile care nu sunt Google, verificare în Preview.
3. **Merchant Center:** feed-ul `/feed/google-merchant/`, livrarea și retururile, verificarea domeniului, erorile din feed.

---

## 21. Animație și avertisment la optimizarea imaginilor

**Doru:**
> când pornesc procesul de optimizare a imaginilor, pune o animație care să indice mare și ușor de văzut că procesul încă rulează, iar dacă fereastra trebuie păstrată deschisă, să scrie mare și clar asta […]

**Claude:**
- Până acum conversia mergea doar prin WP-Cron, deci depindea de vizitatori. Pe staging, cu parolă, s-a oprit după 4 imagini. Acum pagina deschisă pornește ea loturile, unul după altul (AJAX `efio_tick`, cu pauza setată). WP-Cron a rămas varianta de rezervă.
- Panoul afișat cât timp rulează: chenar roșu care pulsează, cerc verde care se rotește, procent, bară de progres cu dungi și procentul în titlul tab-ului. Browserul cere confirmare dacă încerci să închizi pagina. La final apare „✅ Optimisation finished”; la pauză, „⏸ Paused”.

**Doru** a cerut apoi, pe rând: textul mai mare și îngroșat, apoi și mai mare, apoi poziționat deasupra Bulk conversion, după Server support.

**Claude:**
- „⚠️ KEEP THIS WINDOW OPEN UNTIL THE PROCESS FINISHES.” are acum 46 px (30 px pe mobil), roșu, majuscule, Arial Black. E primul rând din panou, iar panoul stă pe toată lățimea, între Server support / Media library și Bulk conversion.
- Versiunea pluginului e 1.0.3, ca browserul să nu mai folosească stilul vechi din cache.
- Totul e pe local și pe staging.

---

## 22. Lansarea se face din staging

**Doru:**
> și reține să faci migrarea după staging, modifică planul de migrare să facă după staging.

**Claude:** planul e schimbat în memorie (checklist-ul de lansare, notele despre staging și despre migrare) și în Drive (F14-05):
- Baza de date și fișierele de pe **eco-fridge.o2dev.de** devin site-ul live, inclusiv copiile AVIF/WebP.
- **Înainte:** se scot parola și noindex-ul de pe staging, se dezactivează „Discourage search engines”, Lloyds trece pe Live, iar licențele AG și Astra Pro se mută pe domeniu.
- **După:** search-replace `eco-fridge.o2dev.de` → `eco-fridge.co.uk` (inclusiv în Rank Math), apoi `wp rewrite flush --hard`.
- **De acum:** orice modificare locală se urcă și pe staging, iar baza de date locală nu mai suprascrie niciodată staging-ul.

**Doru:**
> salvează totul în claude-chat.md

**Claude:** am adăugat secțiunile 15–22 și starea de mai jos.

---

## 23. O2 Image Optimizer: pluginul devine general

**Doru:**
> fă-mi plugin-ul pentru image optimizer să fie general, pentru orice site, să funcționeze la orice site pe wordpress, să se numească O2 Image Optimizer, să fie creat de O2 Digital și să fie pentru optimizarea imaginilor și livrarea lor în webp și avif (fără să menționezi că este creat special pentru eco-fridge).

**Claude:**
- Pluginul nou e `o2-image-optimizer/`: **O2 Image Optimizer** 1.0.0, autor O2 Digital, prefix intern `o2io` (opțiuni, meta `_o2io`, clase, CSS, AJAX, comanda `wp o2io status|convert|cleanup`). Nu mai conține nicio mențiune despre eco-fridge.
- Cerințe: WordPress 6.2+ și PHP 7.4+ (testat pe PHP 8.3). Merge cu orice temă, page builder sau CDN, pe Apache, LiteSpeed sau Nginx (pe Nginx doar prin `<picture>`).
- Are `readme.txt` în formatul WordPress: descriere, instalare, întrebări frecvente.
- Migrarea `67-image-optimizer-to-o2.php` (doar pentru eco-fridge) mută datele vechiului plugin: setări, coadă, jurnal și meta `_efio` → `_o2io`. Scoate blocul vechi din `.htaccess` și joburile vechi. Fișierele `.avif`/`.webp` rămân pe loc.
- Pe local: plugin schimbat și migrare rulată (9 imagini mutate). Am verificat că o pagină de produs primește AVIF prin `<picture class="o2io-picture">`. Folderul `ef-image-optimizer/` e scos din proiect; copia lui e în scratchpad.
- Pe staging, prima încercare s-a oprit singură: Doru rula chiar atunci conversia în masă.
- Pentru alte site-uri: arhivezi `o2-image-optimizer/` ca zip și îl încarci din Plugins → Add New → Upload.

---

## 24. Două „Image Optimizer” pe staging

**Doru:**
> am urcat eu noul plugin și am actualizat vechiul plugin, însă acum îmi apar două Image Optimizer în secțiunea de Media […] unul care are progresul complet (finished) […] și altul care nu are niciun progres. Poți verifica site-ul de staging?

**Claude:** ambele pluginuri erau active. Cel vechi avea toate datele (838/838 imagini convertite, 0 erori), iar cel nou nimic, pentru că migrarea 67 nu rulase. Am făcut trecerea:
- backup în `.ef-backup-2026-10-14/plugins-2026-10-28-o2io/`;
- EF dezactivat, migrarea 67 rulată (838 de înregistrări mutate), blocul vechi scos din `.htaccess`;
- folderul vechi șters direct, nu prin „Delete” din WordPress, ca să nu ruleze dezinstalarea lui.

Rezultat: un singur **O2 Image Optimizer**, cu 838 de imagini optimizate, 0 erori și conversia marcată ca terminată. AVIF economisește aproximativ 196 MB, WebP aproximativ 134 MB.

---

## 25. `.htaccess` curățat și conversia automată a pozelor noi

**Doru:**
> da, șterge blocurile goale din .htaccess. Acum, orice imagine nouă încărcată se va optimiza automat sau trebuie apăsat butonul ca să continue optimizarea celor noi?

**Claude:**
- Am șters din `uploads/.htaccess` de pe staging blocurile goale WebP Express și ShortPixelWebp, rămase de pe site-ul vechi. Au rămas doar regulile de securitate și blocul O2 Image Optimizer. Fișierul de dinainte e salvat ca `uploads.htaccess.before-cleanup`.
- **Pozele noi se convertesc automat, fără niciun buton.** Setarea „Convert new uploads” e pe „In the background”. La câteva secunde după încărcare, Action Scheduler, pornit prin WP-Cron, creează copiile AVIF și WebP pentru toate dimensiunile. În coloana din Media apare „done”; pentru o conversie imediată există link-ul „Convert AVIF/WebP”.
- Butonul „Convert images not converted yet” e doar pentru imaginile rămase neconvertite (de exemplu urcate prin FTP sau cât timp pluginul a fost oprit).
- Pe staging nu există conversii blocate în coadă, iar WP-Cron rulează.

**Doru:**
> salvează totul în claude-chat.md

**Claude:** am adăugat secțiunile 23–25 și am actualizat starea de mai jos.

---

## 26. Lloyds Cardnet: Live Store activat, plăți reușite (2026-10-28)

**Doru:**
> am activat Live Store pe AG Lloyds Cardnet si am testat https://eco-fridge.o2dev.de/checkout/?add-to-cart=8035 cu comanda #8036 Payment via Credit or Debit Card (023387). Paid on October 28, 2026 at 3:16 pm. Customer IP: 86.120.129.107, iar Kass a testat acelasi produs cu comanda #8037 Payment via Credit or Debit Card (030469). Paid on October 28, 2026 at 4:41 pm. Customer IP: 62.172.77.226. Eco-Fridge a cumparat licenta pentru AG Lloyds Cardnet.

- Blocajul 3DS2 („ECI-7 transactions are not supported by merchant", comanda de test #8029) e **rezolvat**: cu magazinul pe **Live**, ambele plăți au trecut — #8036 (Doru) și #8037 (Kass, din UK), amândouă pe `processing`.
- Verificat pe staging: gateway-ul `ag_cardnet_checkout` e activ, `status = live`, `operation = sale`, titlul la checkout „Credit or Debit Card".
- **Licența AG e cumpărată de Eco-Fridge** — rămâne în proprietatea clientului, cum recomandase Doru lui Mads. Doru o dezactivează de pe staging și o activează pe domeniul live în ziua lansării.

---

## 27. Plan nou: un backup la zi al site-ului vechi (eco-fridge-backup.o2dev.de)

**Doru:**
> Pe langa backup de la Ambrit, vom face noi o noua migrare completa de la vechiul site https://www.eco-fridge.co.uk/ catre un nou site de backup eco-fridge-backup.o2dev.de pentru a prelua tot site-ul vechiul actualizat la zi chiar inainte de lansarea noului site pe live, iar din acest nou backup al site-ului vechi actualizat la zi vom prelua noii clienti, noile comenzi, stocul si pretul produselor actualizate la zi (daca mai trebuie preluat ceva, te rog sa-mi spui). Apoi dupa ce am actualizat bazele de date cu toate datele noi la zi, vom face curatenie si vom lansa (migra) noul site pe live.

Fluxul devine: **site vechi live → eco-fridge-backup.o2dev.de (copie la zi) → se extrag datele noi → se importă în eco-fridge.o2dev.de → curățenie → lansare pe eco-fridge.co.uk.**

Ce trebuie preluat din copia la zi (lista completă, recomandată de Claude):

1. **Comenzile noi** apărute după snapshotul din iulie — cu notele, rambursările, statusurile și liniile de comandă.
2. **Următorul număr de comandă.** Comenzile vechi au fost migrate cu ID-urile lor, iar staging-ul a ajuns la #8037. Secvența noului site trebuie pornită **peste** ultimul număr din site-ul vechi, altfel două comenzi diferite ar primi același număr.
3. **Clienții noi** — conturi, adrese de facturare/livrare, hash-urile parolelor (ca să se poată loga în continuare) și rolurile.
4. **Stocul** — cantități, „in stock / out of stock", backorder.
5. **Prețurile** — preț normal, preț redus, perioadele reducerilor și „from price"-ul categoriilor.
6. **Produse noi, produse scoase din vânzare sau redenumite**, plus schimbări de categorii.
7. **Cupoanele** WooCommerce active (inclusiv cel de 100%, folosit la teste).
8. **Media nouă** — tot ce s-a urcat în uploads după iulie, cu galeriile produselor.
9. **Conținutul editat** — pagini, articole noi de blog, descrieri de produse și de categorii modificate.
10. **Recenziile și comentariile** noi.
11. **Redirect-urile adăugate** pe site-ul vechi (Yoast Premium / Simple 301) — se regenerează `legacy-redirect-map.php`.
12. **Meta SEO** pentru conținutul nou (titluri, descrieri Yoast) → importate în Rank Math.
13. **Setările de livrare și taxe**, dacă s-au schimbat.
14. **Inventarul de URL-uri refăcut** din sitemap-urile live, ca verificarea de paritate 1:1 să fie pe starea reală de la lansare.

Nu se preia: log-urile de email, sesiunile, cache-ul, tokenii de plată (Lloyds e hosted), setările pluginurilor vechi.

**Alte confirmări ale lui Doru:** are acces la contul IONOS al companiei (deci DNS-ul se schimbă de acolo) și la contul AG Lloyds; WebToffee PDF Invoices rămâne **dezactivat, dar neșters** (se configurează altă dată, dacă Kass decide așa); a rulat din nou scanarea de cookie-uri din EF Cookie Consent, ca registrul să fie la zi. Site-ul live va rula pe **același cont Hostinger** ca staging-ul.

---

## 28. Local-ul adus la zi cu staging (2026-10-29)

**Doru:**
> actualizeaza si local-ul sa fie identic (la zi) cu ce e pe staging eco-fridge.o2dev.de acum (sa nu mai existe confuzii) - Staging-ul e sursa de adevăr.

**Ce am făcut (Claude):**
- **Codul:** comparat fișier cu fișier staging ↔ local pentru `astra-child`, `ecofridge-blocks`, `ef-cookie-consent`, `o2-image-optimizer` (138 de fișiere) — **identice**, deci munca locală era deja urcată.
- **Pluginuri:** aduse de pe staging toate pluginurile terțe, la versiunile de pe staging (AG Lloyds Cardnet premium 5.1.1, SMS Alert 4.0.3, UpdraftPlus 1.26.14 — lipseau local; WooCommerce 11.2.1, Astra Addon 4.13.11, LiteSpeed 7.9.2, WooCommerce Analytics 0.10.0, Check Email 2.0.17, WebToffee 5.0.4 — erau mai vechi). Șterse local Essential Blocks și Spectra (nu mai există pe staging; copie în scratchpad). Tema Astra 4.14.1.
- **`.wp-env.json`:** versiunile sunt acum fixate (woocommerce 11.2.1, two-factor 0.17.0, astra 4.14.1), ca `wp-env start` să nu mai aducă altă versiune decât cea de pe staging.
- **Baza de date:** dump al celor 73 de tabele `wp_` de pe staging (50 MB), backup al bazei locale înainte (`local-db-before-sync.sql.gz`, în scratchpad), import local, apoi `search-replace` staging → `http://localhost:8888` (8.539 de înlocuiri, inclusiv tabelele Rank Math) și `rewrite flush --hard`.
- **Imagini:** 733 de fișiere care lipseau local (miniaturi și câteva originale) aduse de pe staging. Copiile `.avif`/`.webp` nu s-au copiat — local se regenerează cu un clic din O2 Image Optimizer.
- **Verificat local:** 106 articole, 28 pagini, 144 produse, 5.936 comenzi, 4 utilizatori, 83 de categorii — identic cu staging; aceleași 16 pluginuri, aceleași versiuni; home, /shop/, categorie, subcategorie bakery, Contact, Cookie Policy, feed-ul Merchant și sitemap-ul răspund 200; bannerul de cookies și `dataLayer` apar în pagină; 0 adrese `o2dev` rămase în conținut.
- **De reținut:** local-ul folosește acum utilizatorii de pe staging — te loghezi cu contul `doru` și parola de pe staging (vechiul `admin` nu mai există). Singurele urme de `o2dev` rămase sunt datele istorice ale comenzii de test anulate #8029 și câmpul Freemius `fs_accounts` — corect așa.
- Pluginul AG scrie în `debug.log` avertismente „Creation of dynamic property … is deprecated" pe PHP 8.3 — doar notificări, vizibile local pentru că `WP_DEBUG` e pornit; pe live nu apar.

---

## 29. Git: PR #16 unit, restul muncii comisă (2026-10-29)

**Doru:**
> Apoi actualizeaza in git, comit, merge pr

**Claude:**
- **PR #16** (audit SEO și parametri) — **unit** în `main` (merge `22b442c`).
- Ramură nouă `feature/cookie-consent-image-optimizer-analytics`, cu 4 commit-uri: pluginul de cookies + migrarea 66; O2 Image Optimizer + migrarea 67 + regula CSS pentru `<picture>`; analytics GA4/GTM + migrările 68 și 69 + evenimentele din formular; documentația (acest fișier, ghidul GTM/GA4, versiunile fixate în `.wp-env.json`, `.gitignore`).
- `.gitignore` acoperă acum și fișierele cu coduri de licență (`*coupon*.txt`) și rapoartele mari generate cu redirect-uri.

---

## 30. Avertismentele pluginului AG și regula pentru comenzile noi (2026-10-29)

**Doru:**
> Pluginul AG scrie în debug.log avertismente „dynamic property … is deprecated" pe PHP 8.3 - este problema, ma poate afecta?

**Ce am verificat (Claude):**
- Cauza e în pluginul AG: în constructorul fiecărui gateway rulează `foreach ( $this->settings as $key => $value ) { $this->$key = $value; }`, deci fiecare setare devine o proprietate nedeclarată a clasei. Din PHP 8.2, asta produce un avertisment de tip „deprecated"; codul funcționează în continuare, nimic nu se pierde.
- Pe **staging nu există `debug.log`**: `WP_DEBUG` nu e definit acolo, deci avertismentele nici nu se scriu. Apar doar pe local, unde `WP_DEBUG` e pornit din `.wp-env.json` (277 KB, 858 linii).
- Pe site-ul live, cu debug oprit, nu apar nici în pagină, nici în log.

**Răspuns (Claude):** nu e o problemă acum. Trei lucruri de ținut minte:
1. **`WP_DEBUG_DISPLAY` nu trebuie pornit niciodată pe live.** Dacă avertismentele s-ar afișa în pagină, ar putea strica răspunsul webhook-ului Lloyds sau redirectul de după plată. Pe live se lasă debug oprit (sau, cel mult, `WP_DEBUG_LOG` pornit și `WP_DEBUG_DISPLAY` oprit).
2. **La PHP 9 avertismentul devine eroare fatală.** Până atunci e nevoie de o versiune nouă a pluginului de la AG. Rămânem pe PHP 8.3 și cerem actualizarea înainte de orice trecere la PHP 9.
3. Local, avertismentele ascund erorile reale în `debug.log`. Se pot opri cu `E_DEPRECATED` scos din `error_reporting` într-un mu-plugin, dacă devine deranjant.

**Doru:**
> Ce mai trebuie preluat din copia la zi: asigura-te ca vei prelua inclusiv (pe langa tot ce am scris si ai scris tu) toate comenzile noi (si vezi mai intai sa stergi comenzile noastre de test, sa nu ratezi vreo comanda nou din site-ul live care poate trebuie sa fie predata/livrata).

**Ce am verificat (Claude):**
- Ultima comandă reală adusă din site-ul vechi este **#7261, din 10 iulie 2026**. Tot ce a intrat pe site-ul vechi după această dată trebuie preluat — în ritmul actual (aproximativ 20 de comenzi pe lună) sunt în jur de 70–90 de comenzi, iar unele pot fi încă neexpediate.
- Comenzile noastre de test de pe staging: **#8007** (transfer bancar, £1.542), **#8029** (£2.760) și **#8030** (£1.908, amândouă anulate), **#8036** și **#8037** (câte £1,00, plătite real cu cardul, una de mine și una de Kass, amândouă pe Processing, fără rambursare înregistrată).
- Riscul pe care îl semnalează Doru e real: numerele 8007–8037 sunt deja ocupate de teste, iar site-ul vechi continuă să-și numeroteze comenzile din 7261 în sus. Dacă o comandă reală ar nimeri peste un număr de test, ar putea fi sărită la import.

**Regula stabilită:**
1. **Întâi se șterg comenzile de test** (#8007, #8029, #8030, #8036, #8037) și coșurile abandonate de test, abia apoi se importă.
2. Se importă **toate** comenzile apărute după #7261, cu numerele lor originale, cu liniile de produs, notele, rambursările și statusurile — în special cele neexpediate, care trebuie livrate după lansare.
3. **Reconciliere 1:1:** lista numerelor de comandă din copia la zi se compară cu lista din site-ul nou; diferența trebuie să fie zero. Se verifică și totalul încasat.
4. După import, secvența de ID-uri a site-ului nou se pornește peste cel mai mare număr importat.
5. Cele două plăți reale de £1,00 se rambursează din Lloyds Virtual Terminal (sau se lasă, dar apar în decontare) **înainte** de ștergerea comenzilor, altfel se pierde urma lor.

Regula a intrat în `claude-chat.md`, în memoria proiectului, în checklistul eco-fridge (faza 15), în calendarul de lansare și în procedura generală (ziua lansării).

---

## 31. Dacă nu rambursăm comenzile de test (2026-10-29)

**Doru:**
> daca nu mai rambursam nimic din comenzile de test, afecteaza procesul nostru de lansare sau altceva (inafara de decontare in contabilitate)? Retine ce efecte are nerambursarea comenzilor de test ca sa-i explicam lui Kass pe scurt.

**Ce am verificat (Claude), în baza de date:**
- Comenzile **#8036** (plătită de Doru) și **#8037** (plătită de Kass) sunt amândouă de **£1,00**, pe Processing, cu `TRANSACTIONSTATE = CAPTURED` și **fără TVA** (`_order_tax = 0`).
- Produsul folosit e **#8035 „Live payment test – do not buy"**, £1,00: ascuns din catalog și din căutare, fără stoc gestionat, nu apare în feed-ul Merchant și nici în sitemap, dar URL-ul direct răspunde 200 și produsul e încă cumpărabil — deci se șterge la curățenie, împreună cu comenzile.
- Referințele de tranzacție (023387 pentru #8036 și 030469 pentru #8037, plus codurile de aprobare și ID-urile IPG) dispar din WooCommerce odată cu comenzile. Le-am salvat în nota de proiect din memorie, nu în repository: datele de plată nu intră în Git.

**Răspuns (Claude): asupra lansării și a site-ului — niciun efect.** Rambursarea e o operațiune la procesator, complet separată de WordPress: ștergerea comenzilor nu atinge Lloyds, iar banii sunt deja capturați. Ce rămâne, pe scurt (varianta pentru Kass):

1. **Decontare și contabilitate:** 2 × £1,00 intră în contul de comerciant Eco-Fridge, minus comisionul pe tranzacție (comisionul fix poate depăși £1, deci net poate fi ușor negativ). După ștergerea comenzilor nu mai există comandă sau factură corespondentă, iar cele £1 nu au TVA, deci nu arată ca o linie de vânzare obișnuită.
2. **Referințele se pierd din site** la ștergerea comenzilor; o rambursare ulterioară se face din Virtual Terminal, pe baza referințelor salvate în nota de proiect.
3. **Fereastra de rambursare la procesator e limitată** (de regulă în jur de șase luni); după aceea rămâne doar transferul bancar.
4. **Risc de dispută:** dacă un deținător de card ar contesta un £1 nerecunoscut, ar fi chargeback plus taxa lui. Practic zero aici, amândoi știu de plăți.
5. **Nu ține de rambursare, ci de ștergere:** rândurile din `wp_wc_order_stats` trebuie să dispară împreună cu comenzile, altfel rapoartele WooCommerce arată venituri fantomă. Se verifică rapoartele după ștergere.
6. **GA4 și Google Ads:** evenimentele `purchase` de test pot fi deja în proprietatea GA4 live (traficul de pe staging merge acolo). O rambursare nu le șterge; se filtrează după hostname `eco-fridge.o2dev.de`.
7. Un £1 a fost plătit de Doru din banii lui, deci rămâne la Eco-Fridge — neglijabil, dar e corect spus.

**Concluzie:** a nu rambursa e o alegere validă; e suficient să-i dăm lui Kass cele două referințe, ca decontarea să fie explicabilă. O rambursare din Virtual Terminal ia două minute și lasă registrele curate, dar trebuie făcută **înainte** de ștergerea comenzilor. În ambele variante, comenzile #8007, #8029, #8030, #8036, #8037 și produsul #8035 se șterg la curățenia de dinaintea lansării.

---

## 32. SMS Alert: sample de SMS, răspunsul către suport și decizia de testare (2026-10-29)

**Doru:**
> scrie un sample de sms pentru o comanda pentru pluginul SMS alert. Vor cei de la suportul lor.
> […] vor sa stie si ce website va trimite SMS. […] Vreau sa trimit SMS cu sender ID: "ECOFRIDGE". Ce facem? Are rost sa mai testam pe staging sau il setam direct pe live ca sa nu am probleme tehnice la SMS Alert?

**Ce am verificat (Claude), în codul pluginului de pe local:**
- Variabilele se scriu cu paranteze drepte: `[billing_first_name]`, `[order_id]`, `[order_amount]`, `[order_total]`, `[order_status]`, `[payment_method_title]`, `[tracking_number]`, `[tracking_link]`, `[store_name]`, `[item_name_qty]`, `[pdf_invoice_link]` și altele.
- Lista de statusuri o ia din `wc_get_order_statuses()` și se leagă de `woocommerce_order_status_changed`, deci statusurile noastre proprii (Processed, Shipped, Returned) ar trebui să apară și să declanșeze SMS-uri.
- **Risc găsit:** checkout-ul nostru e blocul WooCommerce Checkout (Store API), iar pluginul **nu are niciun hook pe Store API**. Notificarea „comandă plasată" se bazează pe `woocommerce_checkout_order_processed` și `woocommerce_checkout_update_order_meta`, care rulează doar la checkout-ul clasic. Deci SMS-urile pe schimbarea de status ar trebui să funcționeze, dar cel de la plasarea comenzii probabil nu pornește. Dacă se confirmă la test, soluția e o adăugire mică în pluginul nostru (`woocommerce_store_api_checkout_order_processed` → declanșatorul lor) sau ne bazăm doar pe SMS-urile de status, care acoperă ambele metode de plată.

**Sample-urile de SMS** (fiecare un singur segment de 160 de caractere, GSM-7, fără emoji):
- Plată confirmată (card): `Eco-Fridge: thanks [billing_first_name], payment for order #[order_id] ([order_amount]) is confirmed. We are preparing your delivery and will confirm the date. Help: 01280 811411` (151 de caractere la randare).
- Transfer bancar în așteptare: `Eco-Fridge: thanks [billing_first_name], order #[order_id] for [order_amount] received. On hold until your bank transfer clears; bank details are in your email. Help: 01280 811411` (152).
- Expediat: `Eco-Fridge: order #[order_id] has been dispatched. Tracking [tracking_number]: [tracking_link] Questions: 01280 811411` (127).
- Expediat, fără tracking: `Eco-Fridge: order #[order_id] is on its way. Our carrier will call you before delivery. Questions: 01280 811411` (105).
- Finalizat: `Eco-Fridge: order #[order_id] is complete. Thank you for your business. For parts or service call 01280 811411.` (105).
- Alertă internă: `Eco-Fridge: new order #[order_id], [order_amount], [payment_method_title], [billing_first_name] [billing_phone].` (87).

**Răspunsul către suportul SMS Alert** (ei au cerut „website url and sample sms content"): le-am dat **https://www.eco-fridge.co.uk/** — site-ul live al firmei, verificabil, același domeniu pe care va rula magazinul nou. Staging-ul nu se dă, fiindcă răspunde 401 și recenzorul lor nu ar putea deschide site-ul. În mesaj: adresa firmei și telefonul, sender ID **ECOFRIDGE** (9 caractere, deci în limita de 11; sender alfanumeric înseamnă că clientul nu poate răspunde la SMS, de aceea numărul de telefon apare în fiecare mesaj), caracterul strict tranzacțional (fără marketing), volumul estimat (20–40 de comenzi pe lună, circa 60–120 de mesaje) și cele cinci sample-uri. Le-am cerut să confirme aprobarea sender ID-ului pentru UK, dacă template-urile trebuie înregistrate la ei și prețul pe mesaj pentru numerele UK.

**Decizia: se configurează și se testează pe staging**, pentru că setările călătoresc cu baza de date (configurat pe staging = configurat la lansare, ca la WP Mail SMTP, cookies și Lloyds), pentru că statusurile proprii trebuie dovedite și pentru că riscul cu Store API trebuie prins înainte de ziua lansării. Costul e de 2–3 SMS-uri către numărul lui Doru; contul SMS Alert e legat de API, nu de domeniu.

**Precauție la testare:** baza de date de pe staging are 5.936 de comenzi cu numere reale de clienți. Funcțiile de campanii în masă și SMS-urile pentru coșuri abandonate rămân **oprite** cât timp testăm; se testează doar cu o comandă manuală pe numărul propriu.

**Rămâne de făcut:** contul SMS Alert activ și sender ID-ul aprobat, apoi testul pe staging (comandă pe card și pe transfer bancar, schimbare de status spre Shipped, verificarea SMS-ului de la plasarea comenzii pe checkout-ul bloc), apoi notarea rezultatului și a eventualei corecții.

---

## 33. SMS Alert: credite adăugate, sender ID aprobat, pașii următori (2026-10-29)

**Doru:**
> Am trimis mailul si a fost aprobat tot. „Your sms credits have been added to your SMS Alert account. […] And your senderid (ECOFRIDGE) has been approved."

Suportul a mai trimis și lista scurtă: template-uri de SMS, sender ID, conectarea platformei, un prim SMS de test. Plus ghidul lor: https://kb.smsalert.co.in/knowledgebase/setting-up-a-live-account-with-sms-alert/

**Ce am verificat (Claude):**
- Ghidul lor cere sender ID de 6 caractere, numai litere — **ECOFRIDGE** are 9 și a fost aprobat oricum, deci regula aceea e cea indiană (DLT) și nu ne afectează.
- Ghidul spune că template-urile se cer din **Manage → Sys. Template**, pe baza regulilor TRAI, dar nu precizează cum se scriu părțile variabile.
- Răspunsul e în codul pluginului: formatul este **`{#var#}`** pentru fiecare parte variabilă (mesajul de eroare al pluginului e „*Please replace {#var#} with plugin variables"), iar avertismentul despre DLT se afișează **numai când codul de țară implicit este 91** (India). Pentru trafic UK (+44) înregistrarea DLT nu se aplică; template-urile le trimitem oricum, fiindcă ruta tranzacțională a furnizorului le cere.

**Template-urile de trimis la aprobare** (text fix identic cu ce va trimite pluginul, părțile variabile ca `{#var#}`):
1. `Eco-Fridge: thanks {#var#}, payment for order #{#var#} ({#var#}) is confirmed. We are preparing your delivery and will confirm the date. Help: 01280 811411`
2. `Eco-Fridge: thanks {#var#}, order #{#var#} for {#var#} received. On hold until your bank transfer clears; bank details are in your email. Help: 01280 811411`
3. `Eco-Fridge: order #{#var#} has been dispatched. Tracking {#var#}: {#var#} Questions: 01280 811411`
4. `Eco-Fridge: order #{#var#} is on its way. Our carrier will call you before delivery. Questions: 01280 811411`
5. `Eco-Fridge: order #{#var#} is complete. Thank you for your business. For parts or service call 01280 811411.`
6. `Eco-Fridge: new order #{#var#}, {#var#}, {#var#}, {#var#} {#var#}.` (alerta internă)

În plugin, după aprobare, fiecare `{#var#}` se înlocuiește cu variabila potrivită: `[billing_first_name]`, `[order_id]`, `[order_amount]`, `[payment_method_title]`, `[billing_phone]`, `[tracking_number]`, `[tracking_link]`.

**Setările din plugin (pe staging, de făcut de Doru pentru că includ parola contului):**
- API: utilizatorul și parola contului SMS Alert (sau cheia API) în WooCommerce → SMS Alert → Settings. Credențialele le introduce Doru, nu Claude.
- **Default country code: 44** (nu 91) — altfel numerele de la checkout se formatează greșit și apare notificarea despre DLT.
- Sender ID: ECOFRIDGE.
- Notificări client: On hold, Processing, Shipped, Completed. Notificare admin: numărul de mobil al firmei (de stabilit cu Mads sau Kass).
- **Campaniile în masă și SMS-urile pentru coșuri abandonate rămân OPRITE**: baza de date de pe staging are 5.936 de comenzi cu numere reale de clienți.

**Rămâne de făcut:** testul pe staging, imediat ce contul e conectat — o comandă cu cardul, una prin transfer bancar, o trecere pe Shipped, alerta internă, și verificarea dacă SMS-ul de la plasarea comenzii pornește pe checkout-ul bloc (vezi riscul cu Store API din secțiunea 32). Dacă nu pornește, adăugăm hook-ul `woocommerce_store_api_checkout_order_processed` în pluginul nostru sau rămânem doar pe SMS-urile de status.

---

## 34. SMS Alert: mesajele pe statusuri scrise ca migrare, plus auditul setărilor (2026-10-29)

**Doru:**
> cei de la Eco-Fridge nu vor sa fie sunati non-stop, deci stergem numarul de telefon din SMS si nu il promovam nici prin website agresiv decat acolo unde are rost. La Manage-Templates, platforma SMS Alert nu mai are Sys Templates, are DLT Templates, care imi spune: „Template approval not required for Global account". […] Nu mai gasesc unde sa adaug aceste mesaje. Poti sa ma ajuti? De asemenea, poti sa verifici setarile facute de mine in SMS Alert plugin si sa-mi spui daca am gresit ceva?

**Ce am verificat (Claude):**
- **Aprobarea de template nu se mai aplică:** contul e Global, iar pagina DLT Templates spune explicit „Template approval not required for Global account". Deci mesajele se scriu direct în plugin, nu se mai trimit la aprobare. Se confirmă și din codul pluginului: avertismentul despre DLT apare numai când codul de țară implicit e 91.
- **Unde se adaugă mesajele:** WooCommerce → SMS Alert (pluginul își pune submeniul sub WooCommerce, nu în Settings), în secțiunea WooCommerce; fiecare status are un bloc propriu, „When Order is Pending", „When Order is Processing" etc., cu o bifă și o casetă de text. În baza de date sunt `smsalert_general[order_status][<status>]` pentru bifă și `smsalert_message[sms_body_<status>]` pentru text, plus `admin_sms_body_<status>` pentru alerta internă.
- **Termenele reale, din politicile site-ului** (pagina de produs și Terms & Conditions): transfer bancar „estimated 5 to 8 working days from cleared payment", card „estimated 8 to 10 working days from cleared payment", iar în Terms „usually around 8 working days from cleared payment" și „goods are not reserved and will not be dispatched until payment has cleared". Peste York, Leeds și Blackpool, 2–4 săptămâni. **Pentru timpul în care banii de pe card ajung în cont, site-ul nou nu publică niciun termen**; singura sursă e footerul emailurilor de pe site-ul vechi, „Card payment processing can take approximately 3 - 4 working days". Am folosit acel „3 to 4 working days" în SMS-ul de Pending, dar e de confirmat cu Kass (sau de scos).

**Ce am găsit greșit în setări:**
1. **Toate template-urile erau cele implicite ale pluginului, cu sume în „Rs." și cu „Powered by www.smsalert.co.in"** la final.
2. **Textul de Pending era complet nepotrivit:** „you are just one step away from placing your order, please complete your payment" — la noi Pending înseamnă comandă plasată și plătită, în așteptarea verificării stocului și a banilor.
3. **Textul de Completed spunea „has been dispatched"** — la noi Complete înseamnă livrat.
4. **Processed, Shipped, Returned, Refunded foloseau `[first_name]`**, care e gol la comenzile ca vizitator — adică la aproape toate comenzile noastre. Corect e `[billing_first_name]`.
5. **`[order_amount]` se randează ca număr simplu**, fără monedă, deci trebuie scris `£[order_amount]`.
6. **Nu e setat niciun număr de mobil pentru alerte interne** (`sms_admin_phone` e gol), deși toate notificările de admin sunt pornite: deocamdată nu se trimite nimic intern.
7. **Alerta de înregistrare utilizator e pornită**, dar site-ul nu are conturi de client (modulul no-accounts) — se poate opri.
8. În grupul de coșuri abandonate, `customer_notify` e pornit, iar frecvența e la 10 minute; `cart_exit_intent_status` e gol. De verificat și oprit până după lansare, cât timp baza are 5.936 de comenzi cu numere reale.

**Ce e setat bine:** cod de țară implicit **44**, sender ID **ECOFRIDGE**, contul și parola introduse, toate funcțiile de OTP oprite (nu ar fi mers pe checkout-ul bloc), `auto_sync` oprit (numerele clienților nu se urcă în grupurile lor de contacte), alerta de credit scăzut pornită la 200 de credite pe doru.hanganu@gmail.com, iar `checkout-draft` lăsat nebifat (e statusul intern al checkout-ului bloc).

**Ce am făcut:** migrarea **`70-sms-alert-templates.php`** scrie toate mesajele, cu numărul de telefon **doar în cel de On hold** (acolo clientul trebuie să acționeze), fără telefon în restul, cu `£` pus explicit, cu `[billing_first_name]` peste tot și fără „Powered by"; curăță și template-urile pe care nu le rescriem (stoc, înregistrare) de footer și de „Rs.". Backupul vechi e în opțiunea `ef_smsalert_message_before_70`. Rulată **pe local și pe staging**, verificată: zero „Rs." și zero „smsalert.co.in" rămase.

Mesajele pentru client, cum arată la randare:
- **Pending:** „Eco-Fridge: payment for order #7262 (£2,220.00) is confirmed. We are now checking stock and clearing the payment, usually 3 to 4 working days." (142)
- **Processing:** „Eco-Fridge: your order #7262 is approved and we are preparing it. Delivery is usually 5 to 10 working days from cleared payment." (128)
- **Processed:** „Eco-Fridge: your order #7262 is ready to be shipped. We will text you when it is dispatched." (92)
- **Shipped:** „Eco-Fridge: your order #7262 has been dispatched. Tracking DX1234567890: https://track.dx.co.uk/DX1234567890" (108)
- **On hold:** „Eco-Fridge: thanks John, order #7262 for £2,220.00 is on hold until your bank transfer clears; the bank details are in your email. Help: 01280 811411" (149)
- **Complete:** „Eco-Fridge: your order #7262 is complete. Thank you." (52)
- Plus texte proprii pentru Refunded, Partially refunded, Cancelled, Failed, Returned și pentru nota de comandă, chiar dacă unele statusuri sunt deocamdată nebifate.

**Rămâne de făcut:** numărul de mobil pentru alertele interne (de la Mads sau Kass) sau oprirea notificărilor de admin; oprirea alertei de înregistrare și a SMS-urilor de coș abandonat; confirmarea termenului de 3–4 zile lucrătoare pentru plățile cu cardul; apoi testul pe staging (comandă cu cardul, comandă prin transfer bancar, trecere pe Processed și Shipped, verificarea dacă SMS-ul de la plasarea comenzii pornește pe checkout-ul bloc — riscul cu Store API din secțiunea 32). Despre telefon: pe site rămâne unde are rost (pagina Contact, caseta de ajutor, bara verde), fără să fie promovat agresiv.

---

## 35. SMS Alert: livrare cu mașinile proprii, fără SMS către firmă (2026-10-29)

**Doru:**
> la processing am sters „ from cleared payment" pentru ca clear payment se face la pending, deci e platita deja. Iar la refunded si partially refunded am inlocuit „a few" cu „3 to 5", pentru ca aproximativ atat dureaza. Ah, la tracking, Eco-Fridge livreaza cu masinile lor, deci am sters si „Tracking [tracking_number]: [tracking_link]". […] Nu vreau sa consum credite sa trimit SMS lui Mads, el va primi email. Opreste Alerta de înregistrare utilizator. Opreste SMS-urile de coș abandonat. Am pornit short URL in caz ca ne va trebui vreodata.

**Ce am făcut (Claude):**
- Am preluat în migrarea 70 textele editate de Doru, ca o rulare viitoare să nu le mai suprascrie: la **Processing** fără „from cleared payment" (plata e deja încasată la Pending), iar la **Refunded** și **Partially refunded** „within 3 to 5 working days" (am unificat și varianta cu cratimă, „3-5", la aceeași formă).
- **Shipped** rămăsese doar „Eco-Fridge:" după ștergerea părții de tracking. Textul nou, fără curier, pentru că Eco-Fridge livrează cu mașinile și inginerii lor: „Eco-Fridge: your order #7262 goes out for delivery with our own vehicles and engineers. We will contact you before we deliver." (126 de caractere la randare).
- **Niciun SMS către firmă:** migrarea oprește toate cele 12 notificări de admin pe statusuri, plus alerta de înregistrare, alerta de stoc scăzut și cea de stoc epuizat — 15 comutatoare oprite pe staging. Mads primește oricum emailurile de comandă, deci nu se consumă credite.
- **SMS-urile de coș abandonat** oprite (`customer_notify` → off).
- Starea de dinainte e salvată în opțiunea `ef_smsalert_switches_before_70`, pe lângă `ef_smsalert_message_before_70` pentru texte.
- Migrarea rulată pe **local și pe staging**. Verificat pe staging: alerte către companie pornite — niciuna; coș abandonat — oprit; short URL — pornit (lăsat cum l-a setat Doru); statusurile cu SMS la client: pending, processing, processed, shipped, on-hold, completed, refunded, partially refunded.

**Rămâne de făcut:** confirmarea termenului de 3–4 zile lucrătoare pentru plățile cu cardul (singura sursă e footerul emailurilor de pe site-ul vechi, nu e publicat pe site-ul nou) și testul pe staging — comandă cu cardul, comandă prin transfer bancar, trecere pe Processed și Shipped, plus verificarea dacă SMS-ul de la plasarea comenzii pornește pe checkout-ul bloc (riscul cu Store API din secțiunea 32).

---

## 36. Plățile cu cardul rămân pe Pending, plus alerta de credite SMS (2026-10-29)

**Doru:**
> pe mine ma anunta pe email daca scad creditele sub 200 de credite, corect? Termenul de 3–4 zile lucrătoare din SMS-ul de Pending — lasa-l. Cand clientii platesc cu cardul, trimite comanda in pending, ca sa astepte intrarea banilor de pe card in contul bancar.

**Alerta de credite — confirmat.** Pluginul rulează din oră în oră jobul `smsalert_balance_notify` (WP-Cron) și, când creditele scad la **200 sau mai puțin**, trimite un email la **doru.hanganu@gmail.com**, cel mult o dată pe zi (ține minte data ultimei alerte). Depinde de două lucruri: cronul să ruleze (pe staging rulează; pe live e pe checklist un cron real) și SMTP-ul să funcționeze (WP Mail SMTP e deja configurat). Raportul zilnic de balanță e oprit, doar avertismentul de credit scăzut e activ.

**Plățile cu cardul pe Pending — ce a implicat.** WooCommerce consideră Pending „neplătită", deci simpla schimbare de status ar fi stricat patru lucruri. Modulul nou `astra-child/inc/card-payments.php` le rezolvă:
1. Gateway-ul AG apelează `payment_complete()`, care ar pune Processing. Filtrul pune **Pending**, iar restul rămâne: tranzacția, data plății și scăderea stocului.
2. **„Hold stock (60 minutes)" ar fi anulat comanda plătită după o oră** (exact capcana de la transferul bancar din secțiunile 1–2). Comenzile cu dată de plată nu mai sunt atinse de jobul de anulare.
3. O comandă Pending e plătibilă, deci clientul ar fi putut primi un link de plată pentru o comandă deja plătită. Comenzile plătite nu mai sunt plătibile (nici pagina de plată, nici linkul din emailuri).
4. **Emailul „New order" pentru firmă pornește doar la intrarea în Processing, Completed sau On hold**, deci o comandă care stă pe Pending nu ar fi fost anunțată niciodată — iar Mads nu primește SMS, doar emailuri. Acum se trimite din `payment_complete`, o singură dată, cu urmă în meta, și nu se repetă când magazinul preia comanda.

**Email nou pentru client:** „Payment received (card)" (`EF_Email_Customer_Payment_Received`), trimis când plata cu cardul a trecut: spune că plata a intrat, că verificăm stocul și așteptăm banii, „usually 3 to 4 working days", și că urmează un email la aprobare. Apare în WooCommerce → Settings → Emails, cu subiect, titlu și text editabile. Se trimite doar pentru comenzi plătite cu cardul (o comandă Pending abandonată nu primește confirmare de plată) și o singură dată pe comandă.

**Migrarea 71:** emailul de Processing spunea „Your order has been received" — acum, când Processing înseamnă „preluată și în pregătire", scrie „Your order #… has been approved", cu „Delivery is usually 5 to 10 working days from cleared payment", la fel ca SMS-ul. Starea veche e în `ef_processing_email_before_71`.

**Testat pe local**, cu o comandă de test ștearsă după: statusul rămâne `pending`, data plății e setată, stocul scăzut, comanda nu e plătibilă, jobul de anulare o lasă în pace, pleacă exact două emailuri (firmă + client), un webhook repetat nu trimite nimic în plus, iar la trecerea pe Processing pleacă doar emailul clientului. Fișierele sunt urcate pe staging (verificate cu `php -l`, cu copia veche a `functions.php` în `/tmp/functions.php.before-card-pending`), migrarea 71 rulată acolo, iar filtrul verificat pe staging: pentru o comandă cu `ag_cardnet_checkout` întoarce `pending`.

**Transferul bancar și cecul rămân neatinse** — merg în continuare pe On hold.

**Două lucruri de ținut minte:**
- **WooCommerce Analytics exclude Pending din rapoarte** în mod implicit, deci încasările de pe card apar în rapoarte abia când comanda trece pe Processing. Dacă deranjează, se poate adăuga Pending în statusurile raportate.
- Funcțiile AG „status check" și „self healing" sunt **oprite** (`statusCheck=no`, `self_healing=no`), deci nu pot schimba ele statusul; de confirmat oricum la testul real cu cardul.

---

## 37. Comanda de test #8038: ce s-a trimis, cât a costat, și emailurile aliniate cu SMS-urile (2026-10-29)

**Doru:**
> am dezactivat notificarile din share cart din SMS Alert - Woocommerce. Am plasat comanda de test […] 8038 […] am pus numar de telefon de UK (insa este numar virtual de la NUMERO care primeste SMS […]), in platforma SMS Alert mi s-au scazut 80 de credite, insa nu am primit SMS inca. Scrie toate emailuri la fel ca mesajele din SMS.

**Ce am găsit pe staging la comanda #8038** (ora serverului, UTC, cu două ore în urma orei locale):
- `11:10:00` comanda creată pe Pending la checkout, `11:11:00` plata confirmată de Lloyds (cod 059993, 3D Secure „Successful authentication"), total £1,00, telefon +447575885965.
- `11:10:01` **SMS trimis** („SMS Send to buyer Successfully") — cel de Pending, la crearea comenzii.
- `11:11:02` emailurile „New order" și „Processing order", `11:11:03` **al doilea SMS** — cel de Processing.
- Statusul final: **processing**, nu pending, pentru că fișierele cu noua logică au ajuns pe staging la `11:20:32`–`11:24:33`, adică **după** comandă. Următorul test cu cardul va rămâne pe Pending.
- Un lucru bun aflat din asta: **SMS-ul de la crearea comenzii a pornit pe checkout-ul bloc**, deci îngrijorarea cu Store API din secțiunea 32 nu se confirmă pentru SMS — pluginul prinde comanda prin hook-ul generic de schimbare de status.

**Creditele:** 80 de credite pentru **2 SMS-uri** înseamnă aproximativ **40 de credite pe mesaj** către un număr din UK (ruta „global"). Soldul rămas, citit din API: **4.885 de credite**. Cu toate cele cinci statusuri dintr-o comandă normală (pending, processing, processed, shipped, completed) un singur client ar primi 5 SMS-uri, adică în jur de **200 de credite pe comandă** — la 20–40 de comenzi pe lună, 4.000–8.000 de credite lunar. De aceea am recomandat să păstrăm bifate doar statusurile care contează pentru client (de exemplu Pending sau On hold, plus Shipped), nu toate.

**SMS-ul nu a ajuns:** API-ul l-a acceptat de două ori, deci problema e la livrare, nu în site. Pluginul nu are endpoint de raport de livrare (doar push, credite, senderlist, templatelist), așa că raportul se vede doar în contul SMS Alert → Reports. Cauza cea mai probabilă e numărul virtual NUMERO: rutele internaționale resping des numerele VoIP. De verificat raportul de livrare pentru cele două mesaje (29 octombrie, 11:10 și 11:11 UTC, sender ECOFRIDGE, +447575885965) și de repetat testul pe un SIM real din UK (Kass sau Mads).

**Emailurile scrise la fel ca SMS-urile.** În cod (tema), pentru emailurile proprii:
- **Processed:** subiect „…is ready to be shipped", titlu „Your order is ready to be shipped", text „Your order is ready to be shipped. We will contact you before we deliver."
- **Shipped:** subiect „…is out for delivery", titlu „Your order is out for delivery", text „Your order goes out for delivery with our own vehicles and engineers, so we can make sure it arrives in good condition. We will contact you before we deliver." (fără curier, fără tracking)
- **Shipped (companie):** aceeași formulare cu mașinile și inginerii proprii.
- **Payment received (card):** rămâne cum a fost scris în secțiunea 36 — plata a trecut, verificăm stocul și banii, 3–4 zile lucrătoare.

În migrarea **72**, pentru emailurile WooCommerce:
- **On hold:** „Your order is on hold until your bank transfer clears; our bank details are below. Goods are not reserved until the payment has cleared. If anything is unclear, call us on 01280 811411." — singurul email cu numărul de telefon, la fel ca singurul SMS cu telefon.
- **Complete:** „Your order is complete. Thank you for your business. If you need parts or service for your equipment, just reply to this email." (în loc de îndemnul vechi la telefon).
- **Refunded:** „The funds should reach your account within 3 to 5 working days."
- **Processing** era deja aliniat în migrarea 71 („has been approved", livrare 5–10 zile lucrătoare).

Starea veche e în `ef_order_emails_before_72`. Migrarea rulată pe local și pe staging, clasa de emailuri urcată pe staging (copia veche în `/tmp/class-ef-email-order-status.before-72`). Verificat pe staging, subiectele acum: „We have received your payment…", „…is on hold", „…has been approved", „…is ready to be shipped", „…is out for delivery", „…is complete", „…has been refunded".

**Rămâne de făcut:** raportul de livrare la cele două SMS-uri și un test pe un SIM real din UK; decizia pe ce statusuri păstrăm SMS-ul, având în vedere costul; iar la următoarea comandă de test cu cardul, verificarea că rămâne pe Pending și că pleacă emailul „Payment received".

---

## 38. Renunțăm la Pending pentru card; status nou „Preparing" (2026-10-29)

**Doru:**
> sigur modificarea pentru comenzile platite in AG Lloyds Cardnet catre pending nu strica pluginul AG? Sigur la actualizarea pluginului AG nu se modifica nimic la loc? Sigur nu vom avea erori? Daca exista riscuri, atunci hai sa mutam inapoi pe processing si sa inventam un nou status pentru pregatirea comenzii in depozit (preparing sau ceva) […] La on-hold, ai bank details ale Eco-Fridge din vechiul site? Apar in emailurile de on-hold?

**Ce am verificat (Claude):**
- **Actualizarea pluginului AG nu poate anula modificarea:** codul nostru stă în tema copil, nu în plugin. Un update rescrie doar fișierele pluginului.
- **Dar riscul invers e real.** Pluginul AG verifică în vreo zece locuri `has_status( array( "processing", "completed", "on-hold" ) )` ca să decidă dacă o comandă e plătită — în gateway-ul hosted checkout, în webhook, în verificarea de status și în „self healing". O comandă plătită care stă pe **Pending** e văzută de acel cod ca neplătită. Acum cele două funcții sunt oprite, dar un update poate adăuga alte verificări de același fel, iar noi nu controlăm asta.
- În plus, WooCommerce însuși citește Pending ca „neplătită": a trebuit să blocăm anularea automată după 60 de minute, re-plata, emailul „New order" care nu mai pornea și rapoartele care exclud Pending. Patru ocolișuri pentru un singur status.

**Verdict: varianta ta e mai sigură**, așa că am trecut pe ea.

**Fluxul nou (fără niciun ocoliș):** Pending (neplătită, checkout abandonat) → **Processing** (plătită cu cardul, aștept banii în cont) → **Preparing** (aprobată, se pregătește în depozit) → Processed (gata de expediere) → Shipped (pe drum, cu mașinile noastre) → Complete (livrată). Transferul bancar rămâne pe On hold.

- Statusul nou **Preparing** e înregistrat în `astra-child/inc/order-statuses.php`, imediat după Processing, cu culoare și acțiuni în masă în admin, numărat ca plătit, cu stoc scăzut și inclus în rapoarte, exact ca Processed și Shipped.
- Email nou pentru client, **„Order being prepared"** (`EF_Email_Customer_Preparing_Order`): „Your order is approved and we are preparing it. Delivery is usually 5 to 10 working days."
- Emailul de **Processing** spune acum ce spunea cel de Pending: „We have received your payment… We are checking stock and waiting for the payment to reach our account, which usually takes 3 to 4 working days."
- **Modulul `card-payments.php` și emailul „Payment received (card)" au fost șterse** — nu mai sunt necesare. Emailul „New order" către firmă pornește din nou nativ, la trecerea pe Processing.
- **SMS-urile s-au mutat cu un pas** (migrarea 73): textul de plată confirmată e acum pe Processing, cel cu „approved and we are preparing it" pe Preparing, iar **Pending nu mai trimite SMS** (e starea de checkout neplătit) — ceea ce scade și costul. Starea veche: `ef_smsalert_message_before_73` și `ef_processing_email_before_73`.

**Testat pe local**, cu o comandă ștearsă după: plata cu cardul duce comanda pe `processing`, data plății și stocul sunt în regulă, comanda nu e plătibilă, pleacă emailul „New order" către firmă și „We have received your payment" către client; la trecerea pe Preparing pleacă „has been approved", la Processed „is ready to be shipped". Pe staging: fișierele urcate, cele două fișiere vechi șterse, migrarea 73 rulată, fluxul verificat (Pending | Processing | Preparing | Processed | Shipped | On hold | Complete …), Preparing numărat ca plătit, iar SMS-urile pe processing, preparing, processed, shipped, on-hold, completed, refunded.

**Datele bancare la On hold — da, le avem.** Au venit din site-ul vechi cu migrarea 38 și sunt în setările WooCommerce → Direct bank transfer: cont **ECO Fridge UK Ltd**, banca **Lloyds**, cu sort code și număr de cont completate (fără IBAN). Am randat emailul de On hold pentru comanda de test #8007 și apar: secțiunea „Our bank details", numele contului, „Sort code", „Account number", plus textul nostru „on hold until your bank transfer clears" și numărul de telefon. Deci clientul primește datele bancare în email, exact ca pe site-ul vechi.

**De decis (cost SMS):** chiar și fără Pending, o comandă normală trece prin Processing, Preparing, Processed, Shipped și Complete, adică 5 SMS-uri ≈ 200 de credite. Dacă vrei să scazi costul, cele mai utile pentru client sunt Processing (plata a intrat) și Shipped (vine livrarea); restul le acoperă emailurile.

---

## 39. Emailul „New order" la toate comenzile; fără SMS la Shipped și Complete (2026-10-29)

**Doru:**
> atat comenzile platite cu cardul cat si comenzile prin transfer bancar trimit email de comanda noua si catre mads@ si order@ ? […] Hai sa nu mai trimitem SMS la shipped pentru ca vor suna soferul si inginerul pe client si nici complete pentru ca stie si clientul ca e livrata (ce zici?). Restul de status-uri merita SMS pentru ca au zile de asteptare, ce parere ai?

**Verificat pe comenzi reale de pe staging, nu doar în teorie:**
- **#8038, plată cu cardul:** nota „Email «New order» sent" + „Email «Processing order» sent".
- **#8007, transfer bancar:** „Awaiting BACS payment. Order status changed from **Pending to On hold**", iar WooCommerce a încercat atât „New order", cât și „Order on-hold" — la vremea aceea au eșuat doar pentru că SMTP-ul nu era configurat („Could not instantiate mail function"). Acum SMTP merge.
- **#8030, plată eșuată:** „Email «Failed order» sent" către firmă.

**Deci da:** emailul „New order" pleacă pentru ambele metode — cardul îl declanșează la trecerea pe Processing, transferul bancar la trecerea pe On hold — iar destinatarii sunt aceiași, **mads@eco-fridge.co.uk și orders@eco-fridge.co.uk** (un singur set de destinatari, verificat: `enabled=yes`). Comanda nu se anunță de două ori: când transferul intră și muți comanda mai departe, „New order" nu se mai trimite.

**SMS la Shipped și Complete — de acord, le-am oprit** (migrarea 74). La Shipped șoferul și inginerul sună oricum, iar la Complete clientul are marfa în față; ambele mesaje ar fi costat credite degeaba (≈40 de credite fiecare). **Emailurile pentru Shipped și Complete rămân active**, ca urmă scrisă a livrării.

**Restul statusurilor merită SMS, de acord**, fiindcă fiecare are zile de așteptare: **Processing** (plata cu cardul a intrat, verificăm stocul și banii), **Preparing** (aprobată, 5–10 zile lucrătoare), **Processed** (gata de expediere, „we will contact you before we deliver"), **On hold** (clientul trebuie să facă transferul — singurul mesaj cu telefon) și **Refunded / Partially refunded** (3–5 zile lucrătoare). Verificat pe staging: SMS doar pe processing, preparing, processed, on-hold, refunded, partially_refunded. Textele rămân salvate, deci reactivarea unui status e o singură bifă. Cost estimat pe o comandă obișnuită: 3 SMS-uri ≈ 120 de credite.

**O regulă de flux, de reținut pentru comenzile prin transfer bancar:** mesajul de Processing e scris pentru card („am primit plata, așteptăm banii în cont, 3–4 zile lucrătoare"). La o comandă prin transfer, banii sunt deja în cont când o scoți din On hold, așa că **mut-o direct pe Preparing**, nu prin Processing. Fluxul pe transfer bancar: On hold → Preparing → Processed → Shipped → Complete.

---

## 40. Fluxul aprobat și urcat în git (2026-10-29)
## 41. SMS: coșul abandonat era deja oprit, dar am găsit alte trei funcții pornite (2026-10-29)

**Doru:**
> am facut o comanda de test cu numarul lui Mads si a functionat. La produsele lasate in cos, hai sa dezactivam SMS momentan. Te anunt eu ulterior daca se doreste.

**Livrarea SMS e confirmată:** cu numărul real al lui Mads mesajul a ajuns, deci problema era numărul virtual NUMERO (rutele internaționale resping frecvent numerele VoIP). Nu mai e nimic de reparat pe partea de livrare.

**Coșul abandonat era deja oprit** din migrarea 70 (`customer_notify = off`, `cart_exit_intent_status` gol). Dar, verificând restul pluginului, am găsit **trei funcții care trimiteau SMS pe setările lor implicite**, fără să le fi pornit nimeni:

1. **Cerere de recenzie.** `smsalert_or_general.customer_notify` era „on", cu mesaj gata scris („Can you take 1 minute to leave a review…"), `review_status = pending` și `schedule_day = 1`. Cu alte cuvinte: **la o zi după ce orice comandă intra pe Pending — inclusiv fiecare checkout abandonat — clientul ar fi primit un SMS cu cerere de recenzie**, cu „Powered by www.smsalert.co.in" la final. Ar fi fost și jenant și scump.
2. **SMS la recenzie postată**, către cel care lasă recenzia și către firmă.
3. **„Notify me when back in stock"**, pornit implicit pentru client și pentru abonat.

**Migrarea 75** le oprește pe toate trei, reconfirmă oprirea coșului abandonat (ca starea să nu mai poată aluneca) și scoate „Powered by www.smsalert.co.in" din textele rămase în acele grupuri (3 texte curățate), ca să nu plece nimic cu reclama lor dacă vreodată se repornește ceva. Textele se păstrează, deci reactivarea e o bifă. Starea veche e în `ef_smsalert_extras_before_75`.

Rulată pe local și pe staging. Verificat pe staging: `smsalert_or_general: customer_notify=off`, `smsalert_review: review_added_user_msg=off, review_added_admin_msg=off`, `smsalert_bis_general: customer_bis_notify=off, subscribed_bis_notify=off`, `smsalert_abandoned_cart: customer_notify=off`. **SMS către client rămâne doar pe Processing, Preparing, Processed, On hold, Refunded și Partially refunded.**

---

## 42. SMS către firmă: scăparea de la statusul nou Preparing (2026-10-29)

**Doru:**
> mai era bifat sa trimita SMS si catre admin la status order = preparing. L-am debifat eu.

**Cauza, găsită în cod:** pluginul citește o setare lipsă ca **pornită** — `smsalert_get_option( "admin_notification_" . $status, "smsalert_general", "on" )`. Migrarea 70 a oprit alertele către firmă parcurgând cheile **existente** la acel moment, iar statusul **Preparing** a fost creat abia mai târziu (migrarea 73), deci cheia lui nu exista și a apărut bifată din start. Aceeași capcană ar fi apărut la orice status adăugat ulterior, inclusiv pe site-ul live.

**Nu s-a trimis nimic însă:** pluginul cere și un număr de mobil al firmei, iar câmpul e gol, deci niciun SMS nu a plecat și nu s-au consumat credite. Bifa ar fi devenit reală în clipa în care cineva completa numărul.

**Migrarea 76** scrie explicit `off` pentru **toate** statusurile pe care le cunoaște WooCommerce (inclusiv cele proprii, plus `partially_refunded` și `checkout-draft`), nu doar pentru cheile deja salvate, și reconfirmă alertele care nu țin de status (înregistrare, stoc scăzut, stoc epuizat). Se rulează din nou după ce se adaugă un status sau pe live, dacă acolo apare ceva nou.

Rulată pe local (16 setări scrise, 13 statusuri acoperite) și pe staging (0 schimbări — Doru debifase deja Preparing, restul erau oprite). Verificat pe staging: **alerte către companie pornite: niciuna**; numărul firmei: gol; SMS către client doar pe processing, preparing, processed, on-hold, refunded, partially_refunded.

---

## 43. WooCommerce → Home deschidea wizardul; Lloyds apărea de două ori (2026-10-29)

**Doru:**
> atunci cand dau click in dashboard-ul de WordPress pe Woocommerce - Home, mi se deschide fereastra de setari a magazinului online. Nu este setat deja? De ce mai imi cere? […] Iar la Woocommerce - Payments imi apare Lloyds Cardnet de doua ori, odata active, apoi din nou dar inactive, iar cand am dat pe … Deactivate pe Lloyds Cardnet care este inactive, s-a dezactivat pluginul, asa ca l-am reactivat.

**1. Wizardul de setare.** Magazinul **era** configurat: adresa 18 Top Angel, Buckingham Industrial Park, Buckingham MK18 1TH, țara GB, moneda GBP, TVA, livrare, plăți, emailuri — iar lista de sarcini era deja marcată terminată. Wizardul apărea dintr-un singur motiv: opțiunea `woocommerce_onboarding_profile` **nu fusese scrisă niciodată**, pentru că site-ul a fost construit din scripturi de migrare, nu făcând clic prin setup. WooCommerce arată wizardul ori de câte ori lipsește steagul `completed` din acea opțiune.

**Migrarea 77** completează profilul cu ce este magazinul în realitate (produse fizice, 101–1000 produse, vânzare și la sediu, nu doar online) și îl marchează terminat, apoi ascunde lista de sarcini, ca WooCommerce → Home să deschidă direct tabloul de bord. Starea veche e în `ef_onboarding_before_77`.

**2. Lloyds de două ori.** Nu e pluginul instalat de două ori: pluginul AG înregistrează **două gateway-uri** — cel folosit de magazin, „Credit or Debit Card" (`ag_cardnet_checkout`), și o variantă alternativă, „Lloyds Cardnet Checkout" (`ag_lloyds_hosted_checkout`), care a rămas inactivă. Ambele apar în listă, iar meniul „…" de lângă ele oferă acțiuni **la nivel de plugin**, nu de gateway — de aceea „Deactivate" a oprit tot pluginul, inclusiv plățile cu cardul. Bine că l-ai reactivat imediat.

Am scos gateway-ul nefolosit din listă, printr-un filtru în `ecofridge-blocks/includes/woocommerce.php`, cu explicația în cod: dacă vreodată se dorește varianta hosted checkout, se șterge filtrul și se activează din WooCommerce → Payments. Verificat pe staging: lista are acum `ag_cardnet_checkout` (activ), `bacs` (activ), `cheque` și `cod` (inactive, cele standard WooCommerce).

**Observație separată:** `woocommerce_allow_tracking` e pe „yes", adică WooCommerce trimite date de utilizare către woocommerce.com. Nu am schimbat-o — spune dacă vrei pe „no".

---

## 44. Urmărirea WooCommerce oprită (2026-10-29)

**Doru:**
> pune woocommerce_allow_tracking pe no

**Ce am făcut (Claude):** migrarea **78** pune `woocommerce_allow_tracking` pe `no` și șterge jobul zilnic `woocommerce_tracker_send_event`, care trimitea datele de utilizare către woocommerce.com. Rulată pe local și pe staging; verificat în ambele: setarea e `no`, jobul nu mai e programat. Starea veche e în `ef_tracking_before_78`.

**Ce am mai găsit, legat:** pluginul **WooCommerce Analytics** (cel de la Automattic, 0.10.0) e activ, dar **nu e conectat** — `jetpack_options` are doar `last_heartbeat`, fără ID de blog, iar pe frontend nu apare niciun script de urmărire (am căutat `woocommerceanalytics`, `stats.wp.com`, `s.pubmine.com` în pagina /shop/: zero). Deci nu trimite nimic. Rămâne totuși un plugin activ care nu face nimic; dacă vrei, îl dezactivez, ca să nu înceapă să trimită date în ziua în care cineva conectează Jetpack.

---

## Stare la 2026-10-29 (final)
## 45. Setările Rank Math schimbate de Doru și Local SEO completat (2026-10-29)

**Doru:**
> am modificat setarile in RankMath SEO. Te rog verifica si actualizeaza notitele tale cu aceste schimbari sa stii de ele. Apoi completeaza tu la RankMath Local SEO toate campurile care nu sunt completate sau sunt gresite

**Cum le-am găsit:** am comparat opțiunile Rank Math de pe staging cu cele de pe local (local era snapshotul de dimineață, deci diferențele sunt exact schimbările tale).

**Ce ai schimbat:**
- **Module:** pornite **Local SEO**, **Image SEO**, **LLMs.txt** și AI Visibility; oprite SEO Analysis, Content AI și Role Manager. (Local le-am adus la aceeași listă.)
- **Image SEO:** alt și title puse automat pe imagini, din numele fișierului (`%filename%`).
- **Titles:** `capitalize_titles` pornit (titlurile generate automat devin Title Case; titlurile scrise explicit de migrarea 65 nu se schimbă).
- **Breadcrumbs:** eticheta de Home simplificată, „Eco Fridge : Commercial refrigeration" → **„Eco Fridge"**.
- **Knowledge Graph:** logo schimbat pe `eco-fridge-logo.png` (în loc de imaginea de catalog) și `website_name` completat.
- **Robots:** `noindex` pe taxonomiile goale și pe paginile protejate cu parolă; robots global „index"; advanced robots cu `max-snippet:-1`, `max-image-preview:large`, `max-video-preview:-1` (corect pentru snippeturi bogate).
- **Descrieri implicite:** `%excerpt%` pentru tipurile de conținut și `%term_description%` pentru taxonomii.
- **LLMs.txt:** taxonomiile incluse sunt product_brand, product_cat și market, limită 50.
- 404 Monitor ignoră parametrii din URL.

**Două observații:**
- **`/llms.txt` nu funcționa** nici pe staging, nici pe local: modulul era pornit, dar lipsea regula de rewrite. Am dat `wp rewrite flush --hard` pe ambele; acum fișierul răspunde 200 și listează sitemapul și conținutul principal. La lansare, flush-ul e deja în pașii obligatorii.
- `support_rank_math` e pornit, dar afectează **doar** eticheta de scor SEO afișată pe frontend (parametrul `backlink` al acelui widget), iar noi nu o folosim nicăieri — deci nu apare niciun link către Rank Math pe site.

**Local SEO completat (migrarea 79).** Toate datele vin din pagina Contact, adică din informația publicată de firmă:
- **Nume:** ECO-Fridge Ltd (și `knowledgegraph_name`, care era „Eco-Fridge UK" — mutat pe `website_alternate_name`).
- **Descriere organizație:** o frază despre ce vinde firma și că livrează cu inginerii proprii.
- **Adresă:** 18 Top Angel, Buckingham Industrial Park, Buckingham, Buckinghamshire, MK18 1TH, GB (lipsea complet).
- **Telefon:** +441280811411, plus două puncte de contact — customer support +441280811411 și sales +441280811410.
- **Email:** sales@eco-fridge.co.uk (lipsea).
- **Program:** era greșit, pe valorile implicite ale pluginului (09:00–17:00, **șapte zile pe săptămână**). Acum: **luni–vineri 08:30–17:00**, sâmbătă și duminică închis, exact ca pe pagina Contact.
- **Coordonate:** 51.986, -0.9858 (centroidul codului poștal MK18 1TH; se pot înlocui cu poziția exactă din Google Maps).
- **Price range:** ££.
- **Paginile About și Contact** legate în setările Local SEO (7316 și 7319).
- **Logo:** același ca pe staging, luat după ID-ul atașamentului, ca fiecare copie a site-ului să-l rezolve pe domeniul ei.

**Două lucruri lăsate intenționat:**
- **`legalName` rămâne gol.** Pagina Contact spune „ECO-Fridge Ltd", dar contul bancar e pe „ECO Fridge UK Ltd", iar numele legal din schema ar trebui să fie identic cu cel din Companies House. Se completează când Mads trimite numărul de companie și codul de TVA.
- **`local_business_type` rămâne „Organization"**, nu un subtip de LocalBusiness. Verificat în schema generată: așa ies în pagină numele, adresa, emailul și cele două numere de telefon, dar **nu** programul, coordonatele și price range — acelea se publică doar pentru un subtip de magazin. Pentru un furnizor B2B cu sediu în parc industrial, mi se pare corect să nu pretindem un magazin cu program afișat în Google. Dacă vrei să apară și programul, schimbi tipul pe „Store" (o singură setare) și restul datelor sunt deja completate.

Verificat în JSON-LD pe home, Contact și About: `Organization` cu nume ECO-Fridge Ltd, email, adresa completă și `contactPoint` pentru ambele numere.

---

## 46. Runda de SEO și mesaje, acceptată (2026-10-29)

**Doru:**
> totul e perfect. Actualizeaza claude-chat.md, apoi push in gir, commit, merge

**Claude:** confirmat și închis. Tot ce s-a făcut astăzi e pe staging, pe local și în `main`, prin PR-urile #26–#31: emailurile aliniate cu SMS-urile, fluxul Processing → Preparing, SMS-urile reduse la statusurile cu zile de așteptare, toate funcțiile SMS opționale oprite, alertele către firmă oprite pe toate statusurile, wizardul de WooCommerce închis, un singur gateway Lloyds în listă, urmărirea WooCommerce oprită și Local SEO completat din datele firmei.

**Ce a rămas de făcut până la lansare** (pe scurt, în ordinea execuției):
1. Copia la zi a site-ului vechi pe **eco-fridge-backup.o2dev.de**.
2. Ștergerea comenzilor de test (#8007, #8029, #8030, #8036, #8037, plus cea cu numărul lui Mads) și a produsului de test #8035.
3. Importul **tuturor** comenzilor noi apărute după #7261 (10 iulie 2026), inclusiv cele neexpediate, plus clienți, stoc, prețuri, produse noi, cupoane, media, conținut editat, recenzii, redirect-uri și meta SEO.
4. Reconciliere 1:1 a listei de comenzi și a totalului încasat; secvența de numere pornită peste cel mai mare număr importat.
5. Curățenie finală (backupul `.ef-backup-2026-10-14`, tabelele vechi `43lz2ypq8_`, log-uri, jurnalul de consimțământ de test).
6. Lansarea din staging pe eco-fridge.co.uk: domeniul adăugat în același cont Hostinger, protecțiile de staging scoase, LiteSpeed pornit, `search-replace` + `wp rewrite flush --hard` (și pentru `/llms.txt`), DNS-ul schimbat din IONOS (doar A/AAAA și CNAME www), licențele AG și Astra Pro mutate, parola SMTP retastată, migrarea 76 rulată din nou, GTM și Consent Mode v2, Merchant Center 133011514.
7. De la Mads: numele legal, numărul de companie și codul de TVA, revizuirea paginilor legale, backupul de la Ambrit.

---

## 47. Emailul către Mads cu propunerea de lansare pe 7 noiembrie (2026-10-29)

**Doru:**
> scrie-mi un email catre Mads in care il informam ca totul este instalat si setat pe site-ul de staging. Mai avem de facut cateva teste si verificari finale (cateva zile), apoi pregatim migrarea catre live pe eco-fridge.co.uk (alte cateva zile). As prefera sa fac migrarea noului site pe live sambata 7 Nov 2026, pentru ca….scrie motivele. […] scrie emailul intr-un fisier in format html colorat si organizat frumos cu design modern si structura clara sa poata intelege dintr-o privire.

**Ce am făcut (Claude):** fișierul **`email-mads-go-live-2026-11-07.html`** în rădăcina proiectului — email HTML cu stiluri inline (ca să supraviețuiască lipit într-un client de email), în engleză, fără jargon tehnic. Verificat: 7 noiembrie 2026 **este** sâmbătă, la 9 zile de astăzi; 6 noiembrie e vineri, 9 noiembrie luni.

Structura: antet verde cu întrebarea directă → „Where we are" (ce e gata) → cronologia în trei pași → caseta cu data propusă → motivele datei → ce ne trebuie de la Mads → ce ne trebuie de la Kass → „What does not change" → ce facem în ziua lansării → semnătură.

**Motivele datei, așa cum le-am scris:** biroul e închis sâmbăta (program luni–vineri 8.30–17.00), deci nicio comandă nu rămâne între două site-uri; dimineața de sâmbătă e intervalul cu cel mai puțin trafic; schimbarea adresei are nevoie de câteva ore ca să se propage, iar sâmbăta oferă două zile de marjă până luni; nu lansăm niciodată vineri, când o problemă ar sta nesupravegheată tot weekendul; iar nouă zile sunt suficiente pentru verificări și pentru copia de date, fără grabă.

**Cerut de la Mads:** confirmarea datei în scris; să ceară de la Ambrit un backup complet vineri 6 noiembrie după închidere, menținerea hostingului vechi minimum 30 de zile ca plasă de siguranță și confirmarea că nu umblă ei la DNS; înghețarea conținutului de vineri 6 noiembrie ora 17:00 (stocul și prețurile pot continua să se schimbe, se preiau automat); numărul de companie, codul de TVA și numele legal exact (avem și „ECO-Fridge Ltd" și „ECO Fridge UK Ltd"); citirea celor trei pagini legale; confirmarea că orders@eco-fridge.co.uk există și e urmărită; anunțarea echipei că sâmbătă dimineață magazinul e indisponibil o perioadă scurtă.

**Cerut de la Kass:** o jumătate de oră sâmbătă dimineață (8.00–9.00) ca să confirme în Lloyds Virtual Terminal plata reală de test de pe site-ul live și să o ramburseze; rambursarea celor două plăți de £1,00 din 28 octombrie (referințele i le trimit separat, nu în email și nici în git); confirmarea că nimic nu se schimbă la Lloyds.

Emailul nu conține referințe de tranzacție, parole sau alte date de plată.

---

## 48. Date reale în calendarul de lansare (2026-10-29)

**Doru:**
> pune datele reale in calendarul de lansare din Drive

**Ce am făcut (Claude):** tabul **„Calendar lansare"** din checklistul eco-fridge are acum date, nu termene relative, calculate de la sâmbătă 7 noiembrie 2026:

| Moment | Data |
| --- | --- |
| Confirmări și pregătiri (T-7) | până **vineri 30 octombrie** |
| TTL scăzut, repetiția copiei, teste finale (T-2) | **joi 5 noiembrie** |
| Înghețarea conținutului la 17:00, curățenie, verificări (T-1) | **vineri 6 noiembrie** |
| Lansarea, oră cu oră (05:00 → 09:15) | **sâmbătă 7 noiembrie** |
| Primele verificări (Z+1) | **duminică 8 noiembrie** |
| Search Console și feed (Z+3) | **marți 10 noiembrie** |
| Analytics și performanță (Z+7) | **sâmbătă 14 noiembrie** |
| Închiderea migrării, hostingul vechi se poate opri (Z+30) | **luni 7 decembrie** |

Primul rând („Data și ora lansării stabilite") spune acum explicit că 7 noiembrie e **propus** lui Mads și lui Kass pe 29 octombrie, cu motivele, și e pe „◐ Parțial" până la confirmarea în scris. În checklist a intrat și rândul **F14-13** cu toată cronologia, ca să se vadă din checklist fără să deschizi calendarul.

Dacă Mads propune alt weekend, mut tot calendarul cu o săptămână dintr-o singură rundă.

---

## 49. Oprirea comenzilor pe site-ul vechi în timpul migrării (2026-10-29)

**Doru:**
> putem opri cumva site-ul actual (vechi) al eco-fridge cat facem update si migrarea noului site, ca sa nu se poata face nicio comanda in acest timp? Ceva de genul, maintenance, sau sa-l bagam in debugging, ceva. Nu uita ca site-ul vechi are php 7.3, sa consideri aceasta problema

**Răspuns (Claude): da, dar nu cu mod mentenanță și în niciun caz cu debugging.**

- **Mod mentenanță / 503 ar fi greșit aici.** După schimbarea DNS, o parte din vizitatori mai ajung **ore în șir** pe serverul vechi, până se propagă adresa. Cu mentenanță, toți aceia văd un site mort, iar Google vede un site indisponibil exact în ziua lansării.
- **Debugging nu oprește nicio comandă.** `WP_DEBUG` doar afișează avertismente; pe PHP 7.3 ar scoate și căi de fișiere în pagină, pentru vizitatori.
- **Soluția: mod catalog.** Site-ul vechi rămâne în picioare și arată normal, dar nu se mai poate cumpăra nimic.

**Fișierul `migration/tools/old-site-pause-shop.php`**, de urcat în ziua lansării pe site-ul vechi ca `wp-content/mu-plugins/ef-pause-shop.php` (mu-plugins se încarcă singur, fără activare, și nu poate fi dezactivat din greșeală din admin). Ce face, pe straturi:

1. produsele devin necumpărabile (`woocommerce_is_purchasable`);
2. șabloanele standard de „Add to cart" sunt scoase, iar orice buton rămas de la temă e ascuns prin CSS;
3. adăugarea în coș e refuzată pe server, deci nici dintr-o pagină din cache, nici dintr-un link direct;
4. checkout-ul trimite înapoi la coș;
5. o comandă totuși trimisă e refuzată în `woocommerce_checkout_process`;
6. nicio metodă de plată nu mai e oferită;
7. vizitatorul vede un mesaj cu telefonul și emailul firmei: comenzile se pot da telefonic în acea dimineață.

**PHP 7.3:** scris intenționat fără nimic mai nou de PHP 7.0 — doar funcții și closure-uri clasice, fără arrow functions, fără `??=`, fără `str_contains`, fără proprietăți tipate. Verificat cu `php -l` și cu o căutare după construcțiile de 7.4+. Hook-urile folosite există din WooCommerce 2.x, deci merg și pe WooCommerce 3.5.10 al site-ului vechi.

**Testat pe local**, pe un WooCommerce modern: mesajul apare pe produs și în coș, `/checkout/` redirecționează la coș, iar un POST real de `add-to-cart` nu adaugă nimic și întoarce mesajul. Apoi am șters fișierul de pe local și am verificat că site-ul e din nou normal.

**Al doilea strat, din admin:** pe lângă fișier, oprim toate metodele de plată din WooCommerce → Settings → Payments pe site-ul vechi. Chiar dacă o pagină din cache ar arăta un buton, fără gateway nu se poate finaliza nimic.

**Când:** sâmbătă 7 noiembrie, ora 05:00, înainte de copia finală. Fișierul **rămâne pe loc** prin toată propagarea DNS și pe toată perioada de rollback de 30 de zile; se șterge doar dacă ar trebui vreodată să vindem iar de pe site-ul vechi (atunci se repun și gateway-urile).

**Cine urcă fișierul:** are nevoie de FTP/SFTP la Ambrit. Fie îl urcăm noi, dacă primim acces, fie îl trimitem lui Ambrit cu instrucțiunea de o linie. Am adăugat asta la lista de cerut de la Mads.

Actualizat și în Drive: pasul de 05:00 din „Calendar lansare" descrie acum modul catalog, iar procedura generală (Faza 8) înlocuiește „mod mentenanță" cu această metodă, pentru orice site viitor.

---

## 50. Patru corecții în emailul către Mads (2026-10-29)

**Doru** a pus patru întrebări pe ciorna de email; două au dus la corecții de fond.

**1. „Backupul de la Ambrit — n-ar fi mai bine luni, ca să comparăm cu ce avem noi?"**
Da și nu: ne trebuie **două**. Cel de **vineri 6 noiembrie, după închidere**, e plasa de siguranță dinainte să atingem ceva — dacă sâmbătă dimineață se întâmplă orice cu serverul vechi, avem o arhivă bună. Cel de **luni 9 noiembrie** e exact ce zice Doru: luat după comutare, de pe serverul vechi, de o terță parte, bun pentru comparația comandă cu comandă și pentru arhivă. Între sâmbătă 05:00 și luni, pe site-ul vechi nu se mai poate plasa nimic (mod catalog), deci cele două copii trebuie să conțină aceleași comenzi — tocmai de asta comparația are sens. Emailul cere acum ambele, cu explicația pentru fiecare.

**2. „Stocul și prețurile se preiau automat până la comutare — e adevărat?"**
Adevărat, dar formularea era prea largă. Se preia tot ce e pe site-ul vechi **până sâmbătă la 05:00**, când luăm copia finală; de acolo importăm comenzi, clienți, stoc și prețuri. Orice modificare făcută **după** acel moment pe site-ul vechi s-ar pierde, pentru că nu mai citim de acolo. Emailul spune acum explicit „până sâmbătă 7 noiembrie la 05:00", iar după acel punct modificările se fac în dashboardul nou.

**3. „Nu e de ajuns să opresc metodele de plată?"**
E un strat bun, dar nu e etanș. Pe WooCommerce, fără nicio metodă de plată, checkout-ul chiar eșuează la validare — **dar**:
- o comandă cu **cod de discount de 100%** are total zero, deci nu are nevoie de nicio metodă de plată și trece (iar Eco-Fridge folosește un astfel de cupon);
- dacă rămâne pornit din greșeală un gateway de tip cec sau plata la livrare, comenzile intră în continuare;
- clientul completează tot checkout-ul și abia apoi vede o eroare, fără să înțeleagă de ce.
De aceea păstrăm fișierul de mod catalog ca strat principal (refuză coșul și checkout-ul pe server, scoate butoanele, afișează mesajul cu telefonul) **și** oprim metodele de plată ca al doilea strat. Emailul explică asta și cere, nou: **acces FTP pentru sâmbătă dimineață sau urcarea fișierului de către Ambrit**.
Dacă nu obținem niciun acces, varianta minimă rămâne: metodele de plată oprite + notificarea de magazin din Customizer cu telefonul firmei; acoperă aproape tot, mai puțin cazul cu cuponul de 100%, pe care îl putem dezactiva manual din admin.

**4. „Cum adică dacă a ajuns plata de test? De ce sâmbătă, acum nu au ajuns?"**
Confuzia venea din formulare. Cele două plăți de **£1 din 28 octombrie au ajuns deja** în contul Lloyds — ele se pot rambursa oricând. Ce îi cerem lui Kass sâmbătă e pentru **o plată nouă**: la ora 08:30, după ce site-ul nou e pe domeniul live, plasăm o comandă reală de £1 cu cardul. E prima plată luată de pe site-ul nou, la adresa reală, cu licența și setările live — deci trebuie văzută cum intră în cont: Kass o confirmă în Virtual Terminal și o rambursează. Emailul spune acum explicit că e o plată nouă și de ce poate fi verificată doar în ziua aceea.

Am renumerotat lista pentru Mads (are acum opt puncte) și am corectat și punctul 8: site-ul **rămâne vizibil** sâmbătă dimineață, doar comenzile online sunt închise câteva ore, iar comenzile se preiau telefonic.

---

## 51. Contabilitatea anunțată despre încasările de test (2026-10-29)

**Doru:**
> adauga si sa anunte contabilitatea despre incasarile acestor comenzi de test

**Ce am adăugat în email**, ca punctul 4 din lista lui Kass: cineva să anunțe persoana care ține contabilitatea despre cele trei încasări de test, pentru că ele intră în contul bancar prin decontarea normală Lloyds și vor apărea în registre — **două de £1,00 din 28 octombrie** și **una de £1,00 în ziua lansării**, fiecare cu rambursarea ei, dacă se rambursează. În email scrie clar că sunt teste, nu vânzări, că nu există client și nici factură în spatele lor, și că trimitem referințele de tranzacție ca să poată fi potrivite și închise curat în contabilitate.

Emailul are acum 1.296 de cuvinte: lista lui Mads cu opt puncte, lista lui Kass cu patru.

---

## 52. Închiderea magazinului vechi devine un plugin urcat din dashboard (2026-10-29)

**Doru:**
> renuntam la inchiderea magazinului vechi la migrare, e prea complicat, cerem prea mult de la Ambrit. Sterge fișierul de mod catalog, o sa primim noua baza de date luni pentru comparatie. Sau: Nu putem urca un plugin compatibil cu php 7.3 care inchide magazinul?

**Răspuns (Claude): ba da, și e mai simplu decât varianta mea.** Un plugin se urcă **din dashboardul WordPress** — Plugins → Add New → Upload Plugin → Install → Activate — deci nu cere nici FTP, nici absolut nimic de la Ambrit. Dezactivarea lui redeschide magazinul cu un clic, ceea ce e mai bine decât fișierul în mu-plugins, care avea nevoie de acces la server și ca să fie scos.

**Ce am făcut:** am transformat fișierul în pluginul **„ECO-Fridge – Close online ordering"** (`migration/tools/ef-close-shop/`, arhivat ca `ef-close-shop.zip` în rădăcina proiectului — zip-ul nu intră în git, sursa da). Codul e același, cu header de plugin, instrucțiuni de instalare și de anulare scrise în el, și nota din admin schimbată („dezactivează pluginul ca să redeschizi magazinul").

**Testat ca plugin real pe local:** instalat din zip cu `wp plugin install --activate`, apoi verificat: mesajul apare pe produs, `/checkout/` trimite la coș, un POST real de `add-to-cart` nu adaugă nimic, iar numărul de metode de plată disponibile la checkout e **0**. Apoi l-am șters și am confirmat că magazinul e normal (2 metode de plată din nou).

**Compatibilitate PHP 7.3:** neschimbată — nimic mai nou de PHP 7.0, hook-uri care există din WooCommerce 2.x, `php -l` curat. WordPress-ul vechi instalează pluginul fără probleme, fiindcă nu cere nici PHP nou, nici WP nou (ăsta era motivul pentru care UpdraftPlus nu se putea instala acolo).

**Simplificat și cererea către Ambrit**, cum ai zis: nu mai cerem backup vineri. Rămân **trei lucruri simple**: un backup complet **luni 9 noiembrie** (pentru comparația comandă cu comandă și ca arhivă), hostingul ținut activ 30 de zile și să nu umble ei la DNS. Copia de siguranță de dinainte de comutare o facem noi sâmbătă dimineață, pe eco-fridge-backup.o2dev.de, deci backupul de la Ambrit e verificare încrucișată, nu singura copie.

**În email**, punctul 4 din lista lui Mads spune acum: un plugin instalat din dashboard în treizeci de secunde, fără Ambrit și fără transfer de fișiere; dacă preferă, îl instalăm noi, cu un cont de administrator pentru dimineața aceea. Plus straturile doi și trei: metodele de plată oprite și cuponul de 100% dezactivat.

Actualizate și pasul de 05:00 din „Calendar lansare" și procedura generală (Faza 8), ca la site-urile viitoare să pornim direct cu varianta plugin.

---

## 53. Ce verifică de fapt Kass sâmbătă (2026-10-29)

**Doru:**
> dar plata de sambata dimineata pe noul site migrat live, daca trece de AG Lloyds Cardnet nu inseamna ca a ajuns la Eco-Fridge in cont? Nu este acelasi lucru ca acum?

**Răspuns (Claude): ai dreptate, formularea mea era exagerată.** Dacă plata e aprobată și capturată, banii au intrat în același cont de comerciant Lloyds și se decontează ca orice vânzare — exact ca la cele două plăți de £1 din 28 octombrie. Site-ul singur ne spune asta: webhook-ul întoarce APPROVED/CAPTURED și comanda trece pe Processing. Deci „a ajuns banul?" **nu** e întrebarea pentru Kass.

**Ce poate vedea doar Kass**, și de asta merită totuși o privire în Virtual Terminal: că tranzacția e **în contul de comerciant live**, sub referința corectă. Sâmbătă dimineață mutăm licența și setările pe domeniul live; dacă, din greșeală, acolo ar ajunge credențialele magazinului de test, site-ul ar arăta tot „plată aprobată" — doar că aprobarea ar veni de la magazinul de test și n-ar circula bani reali. Singurul mod de a exclude asta e să vedem tranzacția în contul real. Și, oricum, rambursarea se face de acolo.

Riscul e mic (credențialele live sunt deja probate pe 28 octombrie, cu carduri reale), deci am scos și „fii disponibil între 8 și 9": îi trimitem referința, iar Kass confirmă când poate. Ideal în aceeași dimineață, ca să aflăm de orice problemă înainte să anunțăm magazinul deschis, dar e o treabă de două minute, nu o gardă la oră fixă.

Emailul spune acum exact asta, fără să pretindă că plata „trebuie confirmată că a ajuns".

---

## 54. Emailul nu mai dă numărul plăților de test (2026-10-29)

**Doru:**
> nu mai mentiona cate plati de test sunt, poate mai plasam vreuna.

**Ce am făcut (Claude):** am scos din email orice număr și orice dată legată de plățile de test, ca să rămână valabil oricâte mai facem:
- la Kass, punctul 1: „o comandă de test de £1,00 pe site-ul live" (în loc de „una nouă");
- punctul 2: „plățile noastre de test de mai devreme au ajuns deja în cont" (fără „cele două din 28 octombrie");
- punctul 4, pentru contabilitate: „plățile noastre de test ajung în cont prin decontarea normală Lloyds, fiecare de £1,00, cu rambursarea alături, dacă se rambursează" (fără numărătoare).

Referințele de tranzacție le trimitem separat, la fel ca până acum, deci contabilitatea are oricum lista exactă, indiferent câte ies până la lansare.

---

## 55. Verificarea de la Kass devine inutilă în ziua lansării (2026-10-29)

**Doru:**
> daca AG imi spune ca este live store, iar platile din zilele astea au trecut la el n cont, nu e acelasi lucru si cu plata de sambata? Nu e acelasi lucru daca ii cerem de acum sa verifice in terminal daca platile actuale au trecut? Ce rost are sa ma verifice si atunci?

**Răspuns (Claude): ai dreptate, iar argumentul meu nu stătea.** Plățile de zilele acestea au trecut prin aceleași setări live, în același cont de comerciant. Ce se schimbă sâmbătă e **doar domeniul și licența AG** — Store ID, shared secret și modul Live **călătoresc cu baza de date și nu se retastează**. Deci dacă acum banii ajung în contul real, sâmbătă ajung în același cont. Nu are rost să-i cerem lui Kass să confirme a doua oară ceva deja dovedit.

**Și pot dovedi asta fără nicio întrebare către client.** Am luat acum, de pe staging, o amprentă criptografică a setărilor gateway-ului (un md5 peste valorile stocate), plus faptul că magazinul e pe `live` și operațiunea `sale`. După mutarea pe producție recalculez aceeași amprentă: dacă iese identică, site-ul nou plătește în exact același cont ca plățile de test — verificat matematic, fără să ating sau să afișez vreo valoare. Amprenta stă doar în nota de proiect din memorie, nu în git și nu în Drive, fiindcă e derivată din datele de plată.

**Ce am schimbat în email:** lista lui Kass nu mai conține nicio sarcină pentru dimineața lansării.
1. O singură privire în Virtual Terminal, **oricând până la lansare**: dacă vede plățile de test în contul lui, e dovada că site-ul plătește în contul real — iar atunci în ziua lansării nu mai e nevoie de nimic de la el.
2. Rambursarea plăților de test, când îi e comod.
3. Confirmarea că nimic nu se schimbă la Lloyds.
4. Un cuvânt pentru contabilitate.

În pasul de 08:30 din calendar, comanda reală de test o urmărim noi cap-coadă (webhook, status, emailuri, SMS), fără să depindem de cineva din partea clientului.

---

## 56. Lista pentru Kass, scurtată la trei puncte (2026-10-29)

**Doru:**
> Confirmarea că nimic nu se schimbă la Lloyds - Nu se schimba nimic, garantez eu. Rambursarea plăților de test, când îi e comod (nu daca ii e comod, ci daca are timp, pentru ca ajunge in contabilitate)

**Ce am făcut (Claude):**
- **Am scos punctul cu confirmarea de la Lloyds.** Doru garantează că nu se schimbă nimic la contul de comerciant, deci nu are rost să cerem o confirmare pe care o avem deja.
- **Am rescris punctul cu rambursările.** Nu mai e „când îi e comod", ca și cum ar fi opțional: acum spune să le facă **imediat ce are un moment liber**, cu motivul pe față — fiecare plată ajunge în contul bancar și în contabilitate prin decontarea normală Lloyds, așa că e mai simplu de închis dacă rambursarea stă lângă plată.

Lista pentru Kass are acum trei puncte: o privire în Virtual Terminal oricând înainte de lansare, rambursările cât mai repede, și un cuvânt pentru contabilitate. Nimic în dimineața lansării.

---

## 57. Mads confirmă 7 noiembrie; ce facem cu comenzile de test (2026-10-29)

**Mads, prin email:**
> All good for the 7th start date, I have also checked the Lloyds Cardnet virtual terminal and all good the two £1 orders we did are showing on there, no need to do a refund.

**Doru:**
> ce facem cu comenzile, le putem anula fara probleme, stricam contabilitatea companiei daca anulam si stergem comenzile de test?

**Data e confirmată:** sâmbătă 7 noiembrie 2026. Marcată ca ✅ în calendarul de lansare și în checklist (F14-13). Mads a verificat și Virtual Terminal-ul: plățile de test apar în contul de comerciant, deci contul live primește corect și **nu mai e nevoie de nicio verificare în ziua lansării** — exact cum spunea Doru. Și a decis că **nu se rambursează** nimic.

**Atenție: sunt trei plăți de £1, nu două.** În Lloyds vor apărea **#8036** (cardul lui Doru), **#8037** (Kass) și **#8038** (testul de pe 29 octombrie, cu numărul NUMERO, aprobare 059993) — deci £3 în decontare, nu £2. Mads a menționat doar două, așa că merită să-i spunem de a treia înainte să ajungă la contabilitate. Restul comenzilor de test nu au bani în spate: **#8007** (transfer bancar, £1.542, neplătită), **#8029** și **#8030** (card, anulate la 3DS, neplătite) și **#8039** (transfer bancar, £1,00, testul SMS cu numărul lui Mads, neplătită).

**Răspuns (Claude): ștergerea comenzilor de test nu strică nimic în contabilitatea companiei.** Motivele, verificate:
1. **WooCommerce nu e sursa contabilității** aici. Registrele se fac din extrasul de cont și din decontarea Lloyds. Ștergerea unei comenzi din site nu atinge nici procesatorul, nici banca — banii încasați rămân încasați.
2. **Nu există nicio factură pentru ele.** Pluginul WebToffee de facturi a fost dezactivat tot timpul, deci nu s-a generat niciun document numerotat și nu se rupe nicio serie de facturare.
3. **Nu e TVA de desfăcut:** comenzile de £1 au avut `_order_tax = 0`, deci nu s-a colectat TVA pe ele.
4. **Ce are nevoie contabilitatea** e o singură linie: trei încasări cu cardul de £1,00, teste ale site-ului nou, fără client și fără factură, nerambursate, minus comisioanele obișnuite. Referințele de tranzacție sunt în nota de proiect și le trimitem.
5. **De ce merită totuși șterse:** ca rapoartele WooCommerce din prima lună să nu includă cele £3 (verificăm și că rândurile din `wp_wc_order_stats` dispar odată cu comenzile) și ca niciun număr de comandă de test să nu se lovească de o comandă reală importată de pe site-ul vechi — testele noastre stau exact în zona 8036–8039.

**Alternativa**, dacă s-ar dori o urmă pe site: să le lăsăm, trecute pe Cancelled cu o notă „test, nu se livrează". Nu o recomand: ar rămâne în lista de comenzi a magazinului live pentru totdeauna și ar păstra riscul de coliziune de numere. Oricum, decizia nu e grăbită — ștergerea e programată în pasul de 05:40 din ziua lansării, chiar înainte de import, ca să mai putem face teste până atunci.

**Ce rămâne de la Mads** din emailul trimis (a răspuns doar la dată și la terminal): backupul de luni 9 noiembrie de la Ambrit, hostingul ținut 30 de zile, fără intervenții pe DNS, înghețarea conținutului de vineri 17:00, numărul de companie și codul de TVA plus numele legal exact, citirea paginilor legale, confirmarea că orders@ e urmărită, instalarea pluginului de închidere sâmbătă dimineață și un cuvânt pentru echipă.

---

## 58. Audit complet al site-ului de staging (2026-10-30)

**Doru:**
> verifica absolut tot site-ul din staging […] vezi daca are vreo problema si daca totul functioneaza bine, si daca mai trebuie facut ceva pana la momentul 5 Noiembrie. De asemenea, noteaza in agenda sa verificam la migrarea noastra de sambata dimineata din vechiul site via Migrate Guru si daca au aparut categorii noi, pagini noi, produse noi, produse sterse […]

**Cum am verificat:** server și setări prin SSH pe staging; tot conținutul prin crawl pe oglinda locală, care e identică cu staging (staging e în spatele parolei, deci nu poate fi parcurs prin HTTP). Crawl-ul a luat toate URL-urile din sitemap, a verificat pe fiecare pagină statusul, erorile PHP, titlul, H1-ul și canonical-ul, apoi a testat **856 de imagini din uploads** și **1.271 de linkuri interne unice**.

**Ce era în regulă:** zero imagini lipsă, zero notificări sau avertismente PHP în pagini, zero linkuri rupte (în afara feed-urilor, mai jos), WordPress și toate pluginurile la zi, niciun `debug.log`, cronul rulează, schema Product completă (brand Frost-Tech, SKU, preț, disponibilitate, livrare, retur), BlogPosting pe articole împreună cu datele noi de Local SEO, feed Merchant cu 129 de produse, `robots.txt` blochează parametrii de filtrare și add-to-cart, coșul și checkout-ul funcționează cu ambele metode de plată, endpointul de căutare răspunde, 4 zone de livrare și TVA 20%.

**Două probleme reale, amândouă reparate azi (PR #44):**
1. **Feed-urile de categorie dădeau 404.** Fiecare pagină de categorie își anunță feedul RSS în head, iar toate cele 30 de adrese de forma `/range/<categorie>/feed/`, `/ranges/…`, `/condition/…` răspundeau cu 404, în timp ce site-ul vechi le servește cu 200. Rutarea pentru bazele vechi avea reguli pentru arhivă și paginare, dar nu pentru feed. Am adăugat două reguli (`/feed/` și `/feed/<format>/`); toate cele 30 răspund acum 200, iar arhivele, paginarea și celelalte feed-uri (piețe, categorii de blog, tag-uri, site, comentarii) nu au fost afectate.
2. **O acțiune eșuată pe zi în Action Scheduler:** raportul de email al modulului Analytics din Rank Math pornea fără niciun callback, pentru că nu există cont Rank Math conectat. Migrarea **80** oprește raportul și curăță rulările programate, ca lista de eșecuri să rămână goală și o problemă reală să sară în ochi.

**O alarmă falsă:** crawl-ul raporta canonical lipsă pe mai multe pagini. Cauza e noindex-ul de pe staging: cu indexarea pornită o clipă pe local, Rank Math scrie canonical pe toate tipurile de pagină. Se reverifică oricum după lansare.

**Lucruri de decis, nu defecte:**
- **#50 White Upright High Capacity Double Door Storage Chiller** nu are preț. Nici pe site-ul vechi nu are: acolo apare „Sale Price: £0.00". Din cauza asta lipsește și din feed-ul Merchant (129 din 132 de produse). De cerut prețul de la Mads sau de lăsat ca preț la cerere.
- **Șase produse draft** „Stainless Steel Heavy Duty…" fără preț și fără categorie — rămase așa din migrare; de completat sau de șters.
- **#82 Removal & Disposal Extra** rămâne publicat dar ascuns din catalog și noindex, cum s-a decis pe 13 octombrie.
- **Trei pagini draft** (Sample Page, Privacy Policy implicit, Refund and Returns implicit) — de șters la curățenia de vineri.
- **Contul kass nu are 2FA**; doru și mads au.
- **Astra 4.14.2** e disponibil. Recomandarea mea: se actualizează **după** lansare, nu în săptămâna migrării.

**În agendă pentru sâmbătă dimineață** (cerut de Doru) a intrat comparația de structură, nu doar de date: categorii noi de produse și mutări între categorii, pagini noi sau șterse, articole noi, produse noi, produse scoase din vânzare sau trecute pe draft, tag-uri noi și schimbări de meniu. Metoda e scrisă în checklist (F15-05): se exportă din copia Migrate Guru listele de termeni și de postări publicate cu slug și dată, se compară cu site-ul nou și se tratează fiecare diferență. O categorie nouă are nevoie și de intrare în meniu și, dacă e comercială, de landing (migrarea 39). Produsele noi vin cu preț, imagine și descriere, iar conținutul SEO, tag-urile de tip și tabelele comparative se completează și după lansare — site-ul rămâne în întreținere.

**Ce mai trebuie făcut până joi 5 noiembrie** (rândul F11-24 din checklist): subdomeniul eco-fridge-backup.o2dev.de creat în hPanel, repetiția migrării și a extragerii datelor, Lighthouse re-măsurat pe staging după bannerul de cookies și GTM, test cross-browser pe Safari iOS, Firefox și Edge, Rich Results Test la Google, 2FA pentru kass, deciziile de produs de mai sus. De la Mads mai așteptăm tot ce era în email, în afara datei: backupul de luni de la Ambrit, hostingul ținut 30 de zile, înghețarea conținutului, numărul de companie și codul de TVA, citirea paginilor legale, confirmarea că orders@ e urmărită și instalarea pluginului de închidere sâmbătă dimineață.

---

## 59. DNS-ul de la IONOS, site-ul de backup și curățenia paginilor draft (2026-10-30)

**Doru** a răspuns punct cu punct la auditul de mai sus, a creat subdomeniul de backup și a trimis capturile din IONOS.

**Deciziile lui:** produsul #50 și cele 6 produse draft se verifică **la migrare** — poate au fost șterse sau au primit preț între timp; dacă #50 tot nu are preț, îl întrebăm pe Mads. Produsele draft rămân decizia lui Mads, nu sunt publice. Paginile draft se șterg acum. Kass își face singur 2FA când începe să folosească site-ul. Astra se actualizează după lansare.

**Paginile draft, șterse** pe staging și pe local: Sample Page, Privacy Policy și Refund and Returns Policy (cele implicite din WordPress și WooCommerce). Am verificat întâi că nimic nu le folosea — pagina de confidențialitate a site-ului e 7320, termenii sunt 7322, iar paginile de shop, coș, checkout și cont au alte ID-uri.

**Site-ul de backup e gata:** `eco-fridge-backup.o2dev.de`, cu WordPress și Migrate Guru instalate și SSH activat. Am adăugat aliasul `ef-backup` în configul meu SSH (148.230.121.74, port 65002, user u510186744_biqFdbY8d), cu aceeași cheie ca pe staging; Doru adaugă cheia publică acolo.

**Nameserverele de la IONOS — totul e în regulă, chiar mai bine decât speram.** Domeniul folosește nameserverele implicite IONOS (`ns1030.ui-dns.com/.biz/.org/.de`), ceea ce înseamnă că **zona DNS e găzduită la IONOS, nu la Ambrit**. Ambrit are doar serverul web, iar IONOS trimite traficul acolo printr-un simplu A record. Pentru noi e varianta ideală: schimbarea o facem din contul clientului, fără să depindem de hosting-ul vechi.

Verificat și în direct, nu doar în capturi:
- `A @ → 185.230.220.98` (Ambrit) — **singurul rând care se schimbă**, în IP-ul Hostinger;
- `CNAME www → eco-fridge.co.uk` — urmează automat, nu se atinge;
- `A ftp → 185.230.220.98` — rămâne spre serverul vechi cât e ținut în viață; se actualizează sau se șterge după cele 30 de zile;
- `MX mx00/mx01.ionos.co.uk`, DKIM (`s1-ionos`, `s2-ionos`, `s42582890._domainkey`), `_dmarc`, SPF și TXT-ul de verificare Google — **neatinse**, deci emailul nu e afectat deloc.

Un detaliu de știut: SPF-ul conține `+a`, deci după mutare autorizează IP-ul Hostinger în locul celui vechi. E inofensiv, fiindcă trimitem autentificat prin smtp.ionos.co.uk, iar testul de email trecea deja SPF, DKIM și DMARC.

Pasul de 07:30 din calendar conține acum lista exactă a înregistrărilor: ce se schimbă, ce urmează automat și ce nu se atinge. Capturile din IONOS stau în folderul proiectului, dar sunt excluse din git (sunt capturi din panoul clientului).

**PR #44 unit** în `main`, cu permisiunea lui Doru: feed-urile de categorie reparate, migrarea 80 și jurnalul auditului.

---

## 60. Rapoartele O2X și trei probleme pe care le-au scos la iveală (2026-10-30)

Doru a trimis patru rapoarte din unealta O2X: Lighthouse pe trei faze, istoricul crawl-urilor, comparația site vechi vs staging și lista paginilor „pierdute". Le-am citit pe toate și am verificat fiecare semnal pe site, nu doar în raport.

**Lighthouse — totul în regulă.** Pe mobil, mediana paginilor: performanță **34 → 86**, accesibilitate **89 → 100**, best practices 96 → 96, LCP **8,95 s → 2,95 s**, CLS **0,922 → 0,001**, TBT 183 → 134 ms, greutate 1,84 MB → 974 KB, cereri 87 → 66. Pe desktop 70 → 97. Important: bannerul de cookies, GTM și optimizatorul de imagini nu au costat nimic — 86 acum, 86 și pe 15 octombrie. Cele mai slabe pagini rămân produsul (69, LCP 7,3 s), o categorie (70, LCP 7,1 s) și contactul (76, LCP 5,4 s); staging-ul e pe găzduire partajată, fără CDN, așa că se re-măsoară pe live.

**Scorul SEO 100 → 63 nu e o problemă de conținut.** Am rulat Lighthouse local, pe oglindă, și am cerut lista auditurilor picate: exact două. Primul e „pagina e blocată de la indexare" — noindex-ul de pe staging, care dispare la lansare. Al doilea era real și l-am reparat (mai jos).

**Cele 111 „pagini pierdute" nu sunt pierdute.** Le-am testat pe toate: 106 redirecționează 301 într-un singur pas către ținta corectă (72 de tag-uri vechi de produs, 31 de pagini de atașament, arhiva de autor, pagina vechiului gateway) și 5 răspund direct 200, printre ele `/product/removal-disposal/`, singura cu greutate reală: 30 de linkuri interne. Zero 404, zero lanțuri lungi. Raportul le vede „nemapate" fiindcă nu există ca URL-uri publicate la noi — exact cum trebuie.

**Comparația A vs B:** scor de sănătate 71 → 80; erori 4xx 10 → 0, linkuri interne rupte 44 → 0, H1 lipsă 11 → 0, titluri duplicate 114 → 0, descrieri lipsă 433 → 0, conținut duplicat 22 → 0, pagini orfane 642 → 2, răspuns lent 1.077 → 145, zero referințe către staging. Cele 638 „schimbări critice" sunt, aproape fără excepție, paginile de atașament ale site-ului vechi (fiecare imagine avea pagina ei, cu titluri de genul „Banner No1 Coffee new"); la noi redirecționează către pagina-părinte sau către fișier. Metricile „0 pagini indexabile", „fără canonical" și „URL-uri neindexabile în sitemap" sunt tot efectul noindex-ului.

### Trei probleme reale, toate reparate

**1. Site-ul live ar fi rămas fără robots.txt.** Pe staging, `/robots.txt` răspundea 404 în 0,08 s — prea repede ca să fi trecut prin WordPress. Serverul Hostinger răspunde el însuși la această adresă și dă 404 când nu există fișier fizic, așa că robots.txt-ul virtual al WordPress-ului nu ajunge niciodată la crawler. Am verificat ipoteza pe site-ul de backup, un WordPress gol pe același cont: tot 404. Fără fișier, Google ar fi citit „nicio restricție" și ar fi putut umbla liber prin URL-urile cu parametri de filtrare. Am scris un `robots.txt` real, cu aceleași reguli pe care le genera WordPress și cu sitemapul domeniului live, și l-am pus în rădăcina site-ului: acum răspunde 200 în 0,25 s. Fișierul e păstrat și în depozit, la `migration/files/robots.txt`. De știut pentru viitor: cât timp există fișierul fizic, editorul de robots.txt din Rank Math nu mai are efect.

**2. Meniul de pe telefon arăta lista tuturor paginilor.** Locația „Off-Canvas" nu avea niciun meniu atribuit, iar Astra o înlocuia cu lista alfabetică generată de WordPress: 49 de linkuri, printre ele Coș, Finalizare comandă, paginile legale și pagini care n-au ce căuta într-un meniu. Apărea pe fiecare pagină a site-ului. Am atribuit acolo meniul principal, același pe care îl vede desktopul: Home, Shop, Markets cu cele patru piețe, Blog, About Us, Contact Us. De decis cu Doru: dacă vrem ca pe telefon să apară și arborele de categorii comerciale, se adaugă un al doilea element de meniu în sertar.

**3. `/eco-fridge/` ducea la pagina greșită.** Pe site-ul vechi, adresa asta servește textul despre retururi. La noi exista pagina 7752 „ECO-Fridge" cu exact același text ca pagina 7325 „Return Policy" de la `/returns/`, iar o redirecționare din Rank Math trimitea `/eco-fridge/` către `/about-us/` — adică pe cineva care caută condițiile de retur îl trimitea la pagina „despre noi". Am mutat ținta redirecționării pe `/returns/` și am trimis pagina duplicat la coș, ca să nu mai concureze în sitemap cu pagina păstrată.

Toate trei sunt aplicate și verificate pe staging și pe oglinda locală (migrarea **81** pentru meniu și pagină, fișier pentru robots.txt).

**Lucruri bune confirmate în aceeași rundă:** feed Merchant cu 129 de produse, schema de produs completă (brand, SKU, preț, disponibilitate, livrare, retur), BlogPosting pe articole cu datele de Local SEO, zero imagini fără atribut alt pe toate tipurile de pagină (cifra din raport numără imaginile decorative cu `alt=""`, care e scrierea corectă), zero referințe către staging în conținut.

---

## 61. Feedback pentru unealta O2X și meniul de pe telefon (2026-10-30)

**Doru:** „salveaza toata analiza acestor rapoarte din O2X si concluziile tale intr-un fisier md ca sa-l pot trimite la O2X […] Pe telefon apare banda cu meniul comercial, deci nu este nevoie sa adaugam paginile comerciale in caseta off-canvas".

**Meniul de mobil rămâne cum l-am pus:** sertarul off-canvas are meniul principal (Home, Shop, Markets, Blog, About Us, Contact Us), iar categoriile comerciale nu se adaugă acolo, fiindcă pe telefon apar oricum în banda de sub antet. Întrebarea deschisă din rundă se închide aici.

**Am scris analiza pentru O2X** în `o2x-tool-feedback-2026-10-30.md`, în engleză, ca să poată fi trimisă mai departe. Structura: mai întâi problema principală — cele 111 „pagini pierdute", din care niciuna nu era pierdută, cu metoda de verificare (o singură cerere pe site-ul B, fără urmărirea redirecturilor) și un tabel de clasificare după codul de răspuns; apoi ordonarea recomandărilor după dovezi (linkuri, clicuri) și gruparea pe tipare, în loc de 72 de rânduri pentru tag-uri; detectarea paginilor de atașament, care umflau cele 638 de „schimbări critice"; cele cinci metrici pe care le strică automat un staging cu noindex și propunerea de a le suspenda cu un singur banner; numărul „427 linkuri interne către redirect", care a fost cel mai valoros semnal din tot raportul și care ar trebui să arate *care* linkuri, nu doar câte; afișarea auditurilor Lighthouse picate, nu doar a scorului; și promovarea stării lui `robots.txt` la rând de sine stătător în rezumatul fiecărui crawl — exact problema care nu apărea în niciunul dintre cele patru rapoarte. La final, ce face unealta bine, ca să nu se piardă între critici.

## 62. DNS: cat dureaza mutarea, TTL-ul si emailurile (2026-10-30)

**Doru:** „deci dupa migrare vom verifica si emailurile sa functioneze. Sa inteleg ca este de ajuns sa schimb doar A @ […] cat estimezi ca va dura sa apara site-ul nou in locul celui vechi? Mai trebuie scazute TTL-urile cu 2 zile inainte de migrare?"

**Emailul nu e atins de mutare.** Se schimba doar inregistrarea A, iar posta merge pe MX (mx00 si mx01.ionos.co.uk), cu DKIM, DMARC si SPF neschimbate. Dupa mutare confirmam oricum pe viu: un mesaj primit din afara pe mads@, sales@ si orders@; un mesaj trimis din casuta catre Gmail, cu SPF, DKIM si DMARC pass in headere; emailurile WooCommerce plecate prin smtp.ionos.co.uk cu expeditor pe domeniul live; webmailul deschis normal. Pasul a intrat in calendar, la 08:30.

**Da, o singura inregistrare:** `A @`, de la IP-ul Ambrit la cel de Hostinger. `www` e CNAME catre domeniul gol, deci urmeaza automat. Ce trebuie sa existe insa de partea cealalta: site-ul live creat ca website in hPanel, cu domeniul eco-fridge.co.uk si www, altfel serverul nu raspunde pentru numele acela; iar certificatul SSL se poate emite abia dupa ce DNS-ul arata spre Hostinger, deci se instaleaza imediat dupa schimbare.

**TTL-ul, masurat de la nameserverele IONOS:** 3600 de secunde, adica o ora, pe A, pe CNAME-ul www si pe MX. Asta inseamna ca fara nicio pregatire, dupa schimbarea de la 07:30, cei mai multi vizitatori ajung pe site-ul nou in cateva minute pana intr-o ora, practic toti intr-o ora-doua, cu o coada rara de rezolvere corporate care pot tine pana a doua zi.

**Scaderea TTL-ului nu e obligatorie cu doua zile inainte** — schimbarea de TTL se propaga ea insasi intr-o ora, deci chiar si vineri ar fi destul. O facem totusi joi, cum era planificat, din doua motive: in ziua lansarii nu mai atingem decat o singura inregistrare, iar castigul adevarat nu e viteza cu care apare site-ul nou, ci **viteza de revenire** — cu TTL 300 ne intoarcem pe serverul vechi in cinci minute, nu intr-o ora. Daca panoul IONOS nu lasa sub o ora, mergem mai departe cu o ora. Dupa o saptamana linistita, TTL-ul se pune inapoi la 3600.

## 63. Ghid: site-ul live pe Hostinger, cu www in fata, si SSL dupa DNS (2026-10-30)

**Doru:** „sa ma inveti la migrare cum sa creez site-ul sa aiba www in fata domeniului pe Hostinger «Agency Startup Web Hosting». Si sa-mi amintesti atunci sa activez SSL dupa propagarea DNS nu inainte."

Am scris ghidul in `ghid-hostinger-site-live-www.html` si am bagat cei doi pasi in calendar, ca sa apara singuri la momentul lor: **joi 5 noiembrie — site-ul live creat gol in hPanel** si **sambata 07:45 — SSL instalat, dupa propagarea DNS**.

**Partea cu www e contraintuitiva:** domeniul se adauga in hPanel **fara** `www.`. Hostinger ia domeniul inregistrabil ca domeniu al site-ului si serveste automat si varianta cu www, ca alias pe acelasi folder; daca pui `www.eco-fridge.co.uk` ca domeniu principal, il trateaza ca subdomeniu separat si ajungi cu doua intrari care se bat cap in cap. Canonicul cu www nu vine, de fapt, din hPanel: il da WordPress, prin `siteurl` si `home` setate pe `https://www.eco-fridge.co.uk` la search-replace-ul de la 07:15, dupa care WordPress redirectioneaza singur domeniul gol catre www. In ghid e si regula de `.htaccess`, pentru cine vrea redirectul inaintea WordPress-ului, cu mentiunea ca e optionala.

Alte doua lucruri din ghid: site-ul se creeaza **gol**, nu cu WordPress instalat, fiindca peste folderul ala vine copia de pe staging; si cand Hostinger propune mutarea nameserverelor, **se refuza** — raman la IONOS, altfel pleaca si emailul odata cu ele.

**SSL: dupa DNS, niciodata inainte.** Let's Encrypt valideaza printr-un fisier cerut pe `http://www.eco-fridge.co.uk/...`, deci cat timp inregistrarea A arata spre Ambrit, cererea ajunge pe serverul vechi si emiterea esueaza — cu riscul unui blocaj temporar de la Let's Encrypt daca se insista. Ordinea intrata in calendar: 07:30 se schimba `A @`, se asteapta 5-15 minute, se verifica cu `nslookup` ca domeniul ajunge la Hostinger, si abia apoi Install SSL, asteptat pana scrie Active, apoi Force HTTPS. La final se verifica si ca certificatul acopera ambele nume, nu doar unul.

Ramane o fereastra de cateva minute in care cineva care nimereste direct pe `https://` ar vedea o eroare de certificat; sambata la 07:40, pe un site B2B din UK, e aproape nimeni. Alternativa fara fereastra — certificat emis joi prin validare DNS si urcat ca Custom SSL — e descrisa in ghid si o pregatim daca o cere Doru.

## 64. Curatenie in calendar: backupul de la Ambrit si pregatirea domeniului (2026-10-30)

**Doru** a intrebat daca randurile 5 si 6 din calendar mai sunt necesare si daca nu ar fi mai simplu sa pregateasca contul eco-fridge.co.uk pe 6 noiembrie, urmand sa faca apoi copy website din eco-fridge.o2dev.de.

**Randul 5, backupul cerut de la Ambrit:** nu mai e o conditie de lansare. Emailul catre Mads a stabilit altceva, mai bun: Ambrit face backupul complet **luni 9 noiembrie**, dupa ce site-ul nou e dovedit, iar gazduirea veche ramane platita 30 de zile, fara nicio schimbare de DNS acolo. Noi nu depindem de el nici cat o secunda: joi facem propria copie completa a site-ului vechi pe eco-fridge-backup.o2dev.de, iar sambata la 05:10 luam copia finala, dupa inchiderea magazinului. Randul e marcat „Aranjat", cu explicatia in el.

**Randul 6, domeniul pregatit pe Hostinger:** era duplicat peste randul nou de joi 5 noiembrie. L-am marcat „Mutat" si am pastrat in el cele doua lucruri care NU intra acolo: SSL-ul se instaleaza sambata, dupa schimbarea DNS, iar cronul real se pune sambata, dupa copiere, fiindca are nevoie de fisierele WordPress la locul lor. Cronul a intrat explicit in pasul de 07:15: `DISABLE_WP_CRON` in wp-config si o sarcina in hPanel care ruleaza `wp cron event run --due-now` la fiecare 5 minute.

**Despre copy website:** da, daca unealta exista in cont, e cel mai scurt drum pentru pasul de sambata de la 06:45 — copiaza fisiere si baza de date intre doua site-uri din acelasi cont. De aceea recomand sa cream site-ul live **joi, nu vineri**: joi incercam copierea pe gol, vedem daca unealta exista, cat dureaza si daca inlocuieste singura URL-urile, apoi golim folderul. Daca nu exista sau cade, raman Migrate Guru de pe staging si, ca ultima varianta, dump plus arhiva prin SSH — dar aflam asta joi, nu sambata la 06:45. Pasul de 06:45 scrie acum toate cele trei variante si verificarile de dupa copiere: robots.txt fizic ajuns in radacina, blocul de staging scos din .htaccess, copiile .avif/.webp prezente, acelasi numar de tabele si de produse.

## 65. Cum golim destinatia dupa copia de proba (2026-10-30)

**Doru:** „cum golesc folderul, sterg toate fisierele?"

Raspunsul scurt e ca poate nici nu e nevoie, si de asta ghidul incepe acum cu un test: inainte de copia de proba se pune un fisier-martor in destinatie (`touch .../public_html/MARKER-TEST.txt`). Daca dupa copiere martorul a disparut, unealta goleste singura destinatia si sambata nu mai avem nimic de curatat; daca a ramas, unealta doar suprascrie peste ce gaseste, deci golim dupa test.

Daca golim, **ordinea conteaza**: intai `wp db reset --yes` din docroot, cat timp `wp-config.php` inca exista, si abia apoi `find . -mindepth 1 -delete` din interiorul lui `public_html`. Invers nu se poate: dupa stergerea fisierelor nu mai exista `wp-config.php`, WP-CLI nu mai stie ce baza sa curete, iar tabelele de test ar ramane si s-ar amesteca cu copia de sambata. Un `rm -rf *` nu e suficient nici el, fiindca sare peste fisierele care incep cu punct, intre ele `.htaccess`.

Am adaugat si varianta fara urme — stergi tot website-ul din hPanel si il creezi din nou gol — cu avertismentul ca dupa recreare trebuie verificat din nou IP-ul, fiindca ala e numarul care intra in IONOS.

Totul a intrat in `ghid-hostinger-site-live-www.html`, sectiunea noua „Repetitia copierii, si cum golesti dupa ea", si in randul de joi din calendar.

## 66. Ghidul de Hostinger, in format HTML (2026-10-31)

Doru l-a vrut usor de citit, asa ca ghidul a trecut din Markdown in HTML, in acelasi stil ca ghidul de GTM: `ghid-hostinger-site-live-www.html`, cu cuprins cu ancore, casete colorate pentru cele trei reguli care nu trebuie ratate (domeniul fara www, refuzul mutarii nameserverelor, SSL dupa DNS), tabel pentru orarul diminetii de sambata si blocuri de comenzi gata de copiat. Versiunea `.md` a fost stearsa, ca sa nu avem doua copii care o iau in directii diferite; ramane in istoricul git. Trimiterile din calendar si din memorie arata acum spre fisierul HTML.

## 67. Ghidul rescris in ordinea reala a pasilor (2026-10-31)

Doru a stabilit cum vrea sa decurga joi: creeaza site-ul, testeaza copierea, **nu noteaza nimic**, sterge site-ul din hPanel (Manage site > Delete site), il creeaza din nou exact prin metoda care a mers la test, si abia atunci noteaza IP-ul si docrootul. PHP-ul il pune el de fiecare data, nu se noteaza nicaieri. A cerut si ghidul rescris cronologic, cu verificarile facute de mine.

Am rescris `ghid-hostinger-site-live-www.html` exact in ordinea asta, cu etichete pe fiecare pas: DORU pentru clicurile din hPanel si IONOS, CLAUDE pentru comenzi. Verificarile cu `nslookup`, `curl` si `openssl` le rulez eu din chat si dau unda verde; Doru nu trebuie sa deschida niciun terminal.

**Despre Migrate Guru, presupunerea lui era corecta:** pentru metoda recomandata de ei, cea cu cheia de migrare, e nevoie de WordPress si de plugin **pe destinatie**, plus pluginul pe staging, unde acum nu exista. Pe staging il instalez eu, am SSH; pe site-ul nou, ori instaleaza el WordPress dintr-un clic si apoi pluginul, ori imi da cheia SSH si le pun eu pe amandoua — da, pot, exact cum am lucrat pe staging. In plus, pluginul cere parola lacatului de staging, fiindca sursa e protejata. Exista si calea cu date SFTP, fara plugin pe destinatie, dar e mai capricioasa.

**Un detaliu care tine de planul lui:** dupa stergere si recreare, utilizatorul SSH al site-ului e altul, deci cheia trebuie adaugata din nou — la fel si WordPress plus Migrate Guru, daca aia e metoda castigatoare. A intrat in ghid, la pasul J7.

**Regula de canonic recomandata, in doua straturi:** WordPress cu `siteurl` si `home` pe `https://www.eco-fridge.co.uk`, de unde vin linkurile, canonical-urile, sitemapul si emailurile; plus o singura regula in `.htaccess`-ul de productie, care prinde ce nu trece prin WordPress. Conditia e pe numele gazdei, nu pe `%{HTTPS}` — in spatele LiteSpeed-ului si al proxy-urilor `%{HTTPS}` raporteaza uneori gresit si se ajunge la bucle de redirect. Tinta e direct `https://www.`, deci orice caz se rezolva intr-un singur hop, iar `/.well-known/` e scutit, ca validarile de certificat si reinnoirile sa nu fie trimise aiurea. Se pune inaintea blocurilor administrate automat si niciodata pe staging.

Calendarul are acum aceeasi ordine la randul de joi si regula de canonic la pasul de 07:15.

## 68. Datele oficiale ale companiei, confirmate la Companies House (2026-11-01)

Doru a trimis datele firmei si le-am verificat direct in registrul britanic, pe pagina companiei. **Se inchide astfel intrebarea deschisa din 29 octombrie despre numele legal** — pe site si in schema Rank Math apare „ECO-Fridge Ltd", iar contul bancar scrie „ECO Fridge UK Ltd"; niciuna nu e forma inregistrata.

**Datele oficiale, asa cum apar la Companies House:**

| Camp | Valoare |
|---|---|
| Nume inregistrat | **ECO-FRIDGE UK LIMITED** (nu „Ltd", nu „ECO-Fridge Ltd") |
| Numar de inregistrare | **06958422** |
| Forma juridica | Private limited company |
| Data infiintarii | 10 iulie 2009 |
| Stare | Active |
| Sediu social (registered office) | 2 Hinksey Court, Church Way, Oxford, Oxfordshire, England, OX2 9SX |
| Obiect de activitate (SIC) | 82990 — Other business support service activities not elsewhere classified |

**Punctul de lucru** ramane cel pe care il stim si care e deja in Local SEO: 18 Top Angel, Buckingham Industrial Park, Buckingham, Buckinghamshire, MK18 1TH.

**Nu s-a schimbat nimic pe site**, asa cum a cerut Doru; ramane pentru mai tarziu. Cand ajungem acolo, de atins: `legalName` din schema Rank Math (lasat gol intentionat pe 29 octombrie, tocmai pentru ca nu aveam confirmarea), subsolul si paginile legale, unde cerinta britanica e ca numele inregistrat, numarul de companie si sediul social sa apara pe site, iar facturile sa poarte aceleasi date. Codul de TVA inca lipseste si ramane de cerut de la Mads.

**Adeverinta pentru ONRC.** Tot azi am scris, la cererea lui Doru, o adeverinta de angajare in engleza, pe antet de companie, pentru dosarul de infiintare a unui PFA in Romania: `employment-reference-doru-hanganu-2026-11-02.html`, gata de tiparit sau salvat ca PDF din browser. Antetul poarta datele oficiale de mai sus, iar numele companiei e scris in forma inregistrata, „ECO-FRIDGE UK LIMITED", fiindca intr-un act depus la un registru de comert forma prescurtata poate fi contestata la traducere. Documentul atesta perioada 5 februarie 2020 — 31 martie 2024, cele doua pozitii (Digital Marketing Specialist, apoi Full-Stack Developer), atributiile tehnice, si spune explicit ca e emis pentru depunerea la Oficiul National al Registrului Comertului. Se semneaza de Kaseem Traboulsi, Managing Director. Fisierul e exclus din git, fiindca include numarul de pasaport.

## 69. Ce PHP si ce WordPress trebuie pe site-ul de backup (2026-11-03)

Intrebarea lui Doru: „pentru ca site-ul de backup eco-fridge-backup.o2dev.de sa primeasca clona site-ului live https://www.eco-fridge.co.uk/ ar trebui setat sa aiba php 7.4 sau cat, conteaza si versiunea de wp? Ce versiune de WP si ce php trebuie sa aiba?"

**Ce am verificat azi, direct pe live** (doar cereri HTTP, nimic scris): antetul de raspuns da `Server: Apache` si `X-Powered-By: PHP/7.2.34`, deci site-ul vechi ruleaza inca pe **PHP 7.2.34**. Versiunea de WordPress nu e expusa (fara meta generator, fara `?ver=` pe assets), asa ca am dedus-o din fisierele de nucleu servite: `wp-includes/blocks/read-more`, `avatar`, `post-navigation-link` si `comments-query-loop` exista, dar `comments`, `footnotes` si `details` dau 404, iar jQuery e 3.6.0 cu Migrate 3.3.2 — combinatia asta e **WordPress 6.0.x** (`comments-query-loop` a fost inlocuit de `comments` in 6.1, `footnotes`/`details` au venit in 6.3). Reamintire din audit: WooCommerce de pe live e **3.5.10**, iar tema `eco-fridge` nu ruleaza pe PHP 8 (`like_escape()`, ghilimele „smart" in cod, acces cu acolade la string-uri).

**Ce ruleaza acum pe backup** (o singura conexiune SSH, `ef-backup`): WordPress **7.1.4**, cu `$required_php_version = '7.4'`, PHP CLI 7.4.33, docroot `~/websites/biqFdbY8d/public_html`. Pe masina exista binarele 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 si 8.5, deci **7.4 e selectabil** din hPanel. Folderul site-ului are si simlinkurile Hostinger `__wp__ -> /opt/h5g/flavors/wp-7.1.4` si `.mp`, adica „flavour"-ul h5g e legat de versiunea 7.1.4.

**Raspunsul, pe scurt:**

1. **PHP: 7.4, nu mai mult.** Live e pe 7.2.34, dar nu avem nevoie sa potrivim exact — 7.4 ruleaza acelasi cod fara probleme (copia din iulie a mers pe 7.4.33) si e cea mai mare versiune care mai duce WooCommerce 3.5.10 plus tema veche. Pe 8.0+ sunt erori fatale, nu doar avertismente. Se schimba din hPanel → Websites → eco-fridge-backup → Advanced → PHP Configuration → 7.4; se poate face si acum, fiindca WP 7.1.4 cere minim 7.4, deci Migrate Guru nu se strica intre timp.
2. **Versiunea de WP conteaza, dar nu o alegem noi** — nu e o setare de gazduire, vine din clona. Atentie insa la capcana din iulie: copia migrata atunci a ajuns pe **WP 7.0** desi live era pe 6.0, semn ca in fluxul asta nucleul destinatiei ramane, nu e suprascris. Deci dupa clonare se verifica `wp-includes/version.php` pe backup: daca scrie tot 7.1.4 peste o baza de date de WP 6.0 + Woo 3.5.10, fie inlocuim nucleul cu **6.0.x** exact (de pe wordpress.org) ca sa se potriveasca cu baza, fie nu deschidem deloc wp-admin si extragem la nivel de SQL + `uploads`, care oricum e planul.
3. **Must-use-urile Hostinger** (`hostinger-h5g-plugin`, `hostinger-preview-domain`) si drop-in-ul `object-cache.php` de la LiteSpeed se scot de pe clona — sunt legate de WP 7.1.4 si de contul gazduirii, nu de site-ul vechi.
4. **Update-urile automate se opresc pe backup** inainte de clonare (`WP_AUTO_UPDATE_CORE` pe `false`, auto-update pluginuri off). Daca WooCommerce s-ar actualiza singur pe copie, updaterul lui ar putea rescrie schema comenzilor (HPOS) — exact datele pe care vrem sa le copiem 1:1.
5. **Limite PHP pentru import**, pentru o baza de 756 MB (162 MB gzip): `memory_limit` 512M, `max_execution_time` 300, `upload_max_filesize` si `post_max_size` 256M.
6. Backupul ramane **blocat cu parola si noindex**, ca o clona identica a live-ului sa nu intre in index cu o zi inainte de lansare.

Nimic schimbat inca pe backup; setarea de PHP o face Doru din hPanel, restul intra in repetitia migrarii.

**Completare, aceeasi zi — de unde se opresc update-urile automate si ce facem cu CDN-ul.** Doru a intrebat ambele; am verificat starea reala pe backup.

**Update-uri automate, doua straturi.** In hPanel: Websites → eco-fridge-backup.o2dev.de → Dashboard → WordPress → Security, secțiunea de update-uri, unde nucleul se pune pe „Don't update", iar din „Customize" se trec si pluginurile si temele pe manual. Astea sunt „smart auto-updates"-urile Hostinger, care ruleaza din afara WordPress-ului, deci setarea din hPanel e cea care conteaza — Hostinger le porneste implicit la site-urile eligibile. In WordPress: `wp-config.php` are deja `WP_AUTO_UPDATE_CORE` pe `false` (linia 4, pus de Hostinger), dar in baza de date `auto_update_core_dev/minor/major` sunt toate `enabled`; constanta are prioritate, iar `auto_update_plugins` si `auto_update_themes` nici nu exista acum. Dupa clonare insa vin opțiunile din baza live, deci se reverifica. Centura si bretele, de adaugat in `wp-config.php` **dupa** ce clonarea s-a terminat: `AUTOMATIC_UPDATER_DISABLED` si `DISALLOW_FILE_MODS` pe `true` — a doua blocheaza si instalarea de pluginuri, deci nu inainte, ca sa nu incurce Migrate Guru.

**CDN: da, se dezactiveaza.** Chiar e activ acum — antetul de raspuns al backupului da `Server: hcdn`, in timp ce staging-ul da `hws`; Hostinger a pornit CDN-ul automat la site-urile eligibile din august 2026. De unde: Websites → Dashboard → Performance → CDN, cu doua variante — „Development mode", care sare peste cache si e reversibil dintr-un clic, sau „Disable" complet. Pentru o copie de unica folosinta merge „Disable". Motivul: clonarea schimba tot site-ul sub cache-ul de la margine, deci am vedea pagini si assets vechi, iar diff-urile de conținut si de URL-uri intre clona si site-ul nou ar ieși false. De reținut pentru ziua lansarii: la fel se poate intampla si pe `eco-fridge.co.uk` cand ajunge in cont, deci se verifica Performance → CDN si se face purge dupa go-live; cat e CDN-ul activ, Hostinger ține blocate si inregistrarile A/www. Pluginul `litespeed-cache` e instalat dar inactiv (`active_plugins` are doar `migrate-guru`), deci nu incurca; opțiunile `litespeed.conf.*` dispar oricum la clonare.

**Semnalare care nu era in plan: backupul e acum public si indexabil.** Nu are parola (raspunde 200 fara autentificare), `blog_public` e `1`, nu exista meta robots si `robots.txt` da 404. Dupa clonare devine copie 1:1 a magazinului live, cu cateva zile inainte de lansare — exact tipul de duplicat care nu trebuie sa ajunga in index. Imediat dupa clonare: `.htpasswd` ca pe staging si `blog_public` pe `0`.


## 70. „Your installed plugin is outdated" pe live — de fapt nu ne trebuie alt plugin (2026-11-03)

Doru: „pe site-ul live eco-fridge.co.uk imi cere «Your installed plugin is outdated. Please re-download the latest plugin and try again.» deci nu mai pot folosi Migrate Guru. Cum sa fac clona site-ului vechi?" si, imediat dupa, „ce plugin de clonare mai este compatibil cu php 7.2.34?"

**Diagnosticul, citit direct de pe live:** `wp-content/plugins/migrate-guru/readme.txt` de pe site-ul vechi are `Stable tag: 5.65` si „Tested up to 6.6", adica pluginul instalat acolo e vechi de cateva versiuni. In repo-ul WordPress versiunea curenta e **6.72**, cu `Requires PHP: 7.0` si `Requires at least: 4.0`. Live e pe PHP **7.2.34** si WP **6.0**, deci versiunea noua ruleaza fara probleme acolo — mesajul e doar verificarea de versiune a serverelor Migrate Guru, nu o incompatibilitate cu gazduirea veche. **Concluzia: nu schimbam unealta, doar actualizam pluginul.**

**Pasii:** Plugins → Migrate Guru → Update now; daca serverul vechi nu ajunge la wordpress.org, se descarca `https://downloads.wordpress.org/plugin/migrate-guru.zip` (~1 MB, verificat 200) si se urca din Plugins → Add New → Upload Plugin, cu „Replace current with uploaded". Apoi se verifica sa scrie 6.72 si se reia migrarea cu cheia de pe backup. Inainte de rulare: PHP 7.4 pe backup si CDN pe off sau Development mode.

Migrate Guru rămâne si varianta cea mai blanda pentru un magazin viu: nu construieste arhiva pe serverul vechi, trimite in bucati catre serverele lor. Un plugin care face arhiva local, pe o baza de 756 MB plus uploads, poate incarca serios serverul lui Ambrit.

**Compatibilitatea celorlalte unelte cu PHP 7.2.34**, verificata in paginile lor din repo:

| Plugin | Requires PHP | Verdict |
|---|---|---|
| Migrate Guru 6.72 | 7.0+ | merge — prima opțiune |
| WPvivid Backup & Migration 0.9.139 | 5.6+ | plan B; migrare site-la-site cu cheie, arhiva sparta in bucati |
| All-in-One WP Migration 7.113 | 5.3+ | plan C; exportul e de orice dimensiune, importul gratuit e limitat de upload-ul destinatiei |
| UpdraftPlus 1.26.14 | nedeclarat, avertizeaza sub 5.6.1 | pe 29 octombrie notasem ca nu s-a putut instala acolo; parti din UI cer 7.4+ |
| Duplicator 5.1.0 | 7.4+, WP 6.2+ | incompatibil cu live |

**Varianta fara nimic instalat pe live** rămâne FTP/SFTP plus dump de baza de date de la Ambrit — zero scrieri pe magazin, exact metoda din iulie. E deja pe lista de cerut de la Mads; daca vine accesul, dump-ul si arhiva le fac eu si le import pe backup.

## 71. cURL error 60 la Initiate migration — magazia de certificate a WordPress-ului vechi (2026-11-03)

Doru, dupa ce a actualizat pluginul si a introdus cheia de migrare a backupului: „mi-a dat eroarea «Failed to connect to migration service: cURL error 60: SSL certificate OpenSSL verify result: unable to get local issuer certificate (20)»".

**Diagnostic, cu dovada.** Am descarcat pluginul din repo si i-am citit codul: cererea de pornire a migrarii pleaca prin `wp_remote_post` (`wp_admin.php:357`), iar gazda API nu e `migrateguru.com`, ci **`https://mg.blogvault.net`** (`info.php:16`, prin `appUrl()`). Certificatul ei e `*.blogvault.net`, emis de **GlobalSign GCC R46 AlphaSSL CA 2025**, deci urca spre radacina GlobalSign Root R46. WordPress nu foloseste magazia de certificate a sistemului, ci lista proprie, `wp-includes/certificates/ca-bundle.crt`, pe care o trimite lui cURL prin argumentul `sslcertificates`. Am luat fisierul acela chiar de pe live (e servit public) si am verificat lanțul cu el: `verify error:num=20:unable to get local issuer certificate` — exact eroarea din interfata. Cu lista Mozilla actuala, acelasi lanț da `Verify return code: 0 (ok)`. Deci lista de radacini din WordPress 6.0 e mai veche decat radacina GlobalSign pe care o foloseste acum blogvault, si nu are legatura nici cu PHP 7.2, nici cu versiunea pluginului.

**Reparatia:** un plugin mic, de unica folosinta, care pentru cererile catre `blogvault.net` si `migrateguru.com` inlocuieste doar magazia de certificate cu una actuala, livrata in arhiva. Verificarea certificatului rămâne pornita — se schimba lista de radacini, nu nivelul de securitate — si nicio alta cerere a site-ului nu e atinsa. Pluginul arata si o notificare pe pagina Plugins, cu rezultatul unei cereri de test catre API, ca sa se vada inainte de migrare daca drumul e liber. Arhiva: `ecofridge-tls-fix.zip`, doua fisiere, ~115 KB, generata in scratchpad-ul sesiunii. Se urca din Plugins → Add New → Upload Plugin, se activeaza, se reia Initiate migration, apoi se dezactiveaza si se sterge.

**Daca tot refuza:** fie OpenSSL de pe serverul vechi e prea batran ca sa construiasca lanțul, fie portul 443 spre exterior e filtrat. Atunci: ori ceream gazdei lui Ambrit sa reinnoiasca pachetul `ca-certificates`, ceea ce e reparatia corecta pentru tot site-ul, ori trecem pe varianta fara conexiuni spre exterior — export cu WPvivid sau All-in-One, descarcat prin browser, iar eu il asez pe backup prin SSH si il restaurez de acolo, ocolind si limita de upload a destinatiei.

## 72. CLONA SITE-ULUI VECHI — PROCEDURA COMPLETA, DE CITIT SAMBATA (scrisa 2026-11-03)

Doru: „noteaza absolut tot in fisierul claude-chat.md sa stim sambata ce avem de facut". Mai jos e totul, in ordinea in care se executa, cu motivul fiecarui pas si cu variantele de rezerva. Repetitia e **joi 5 noiembrie**, treaba pe viu **sambata 7 noiembrie, 05:00–09:00**. Lista datelor de preluat din clona rămâne cea din planul de backup (randurile F15-01…F15-11); aici e **cum** obtinem clona si ce facem cu ea imediat dupa.

### 0. Coordonate si stare

| Ce | Valoare |
|---|---|
| Site vechi (sursa) | `https://www.eco-fridge.co.uk` — Apache, **PHP 7.2.34**, **WordPress 6.0.x**, WooCommerce 3.5.10 |
| Clona (destinatie) | `eco-fridge-backup.o2dev.de` — WordPress 7.1.4, Migrate Guru 6.72 |
| SSH clona | alias `ef-backup` → 148.230.121.74, port 65002, user `u510186744_biqFdbY8d` |
| Docroot clona | `/home/u510186744/websites/biqFdbY8d/public_html` |
| Ultima comanda reala preluata in iulie | **#7261, 10 iulie 2026** — tot ce e dupa ea trebuie sa vina acum |
| Comenzi de test, de sters INAINTE de import | #8007, #8029, #8030, #8036, #8037 si produsul ascuns #8035 |
| Regula SSH | **o singura conexiune la Hostinger in acelasi timp**, niciodata doua in paralel |

### 1. Pe clona, in hPanel, inainte de orice (DORU)

1. **PHP → 7.4.** Websites → eco-fridge-backup.o2dev.de → Advanced → PHP Configuration → 7.4. Motiv: WooCommerce 3.5.10 si tema veche mor pe PHP 8 (functia `like_escape()` scoasa din WordPress, ghilimele „smart" in cod); 7.4 e cea mai mare versiune care le duce, iar copia din iulie a mers pe 7.4.33. Se poate pune de acum: WP 7.1.4 cere minim 7.4, deci Migrate Guru nu se strica intre timp.
2. **CDN → off** (sau „Development mode"). Websites → Dashboard → Performance → CDN. Verificat pe 3 noiembrie: CDN-ul **era pornit** — antetul clonei dadea `Server: hcdn`, in timp ce staging-ul da `hws`; Hostinger il porneste automat la site-urile eligibile. Motiv: clonarea schimba tot site-ul sub cache-ul de la margine, deci am verifica pagini vechi si am obtine diff-uri false la conținut si la URL-uri.
3. **Auto-update → „Don't update".** Websites → Dashboard → WordPress → Security, sectiunea de update-uri; din „Customize" se trec si pluginurile si temele pe manual. Astea sunt „smart auto-updates"-urile Hostinger, care ruleaza din afara WordPress-ului, deci setarea din hPanel e cea care conteaza. In `wp-config.php` exista deja `WP_AUTO_UPDATE_CORE` pe `false`, dar in baza de date `auto_update_core_dev`, `auto_update_core_minor` si `auto_update_core_major` sunt toate `enabled`. Motivul greu: daca WooCommerce s-ar actualiza singur pe clona, updaterul lui ar putea rescrie schema comenzilor (HPOS) — exact datele pe care vrem sa le copiem 1:1.
4. **Limite PHP**, pentru o baza de 756 MB (162 MB gzip): `memory_limit` 512M, `max_execution_time` 300, `upload_max_filesize` si `post_max_size` 256M.

### 2. Pe site-ul vechi, inainte de clonare (DORU)

1. **Migrate Guru la zi.** Pe live era instalat **5.65** (citit din `wp-content/plugins/migrate-guru/readme.txt`), iar serverele lor refuza versiunile vechi cu „Your installed plugin is outdated". Versiunea curenta e **6.72**, cere PHP 7.0+ si WP 4.0+, deci merge pe serverul vechi. Plugins → Update now; daca serverul nu ajunge la wordpress.org, se urca `https://downloads.wordpress.org/plugin/migrate-guru.zip` din Plugins → Add New → Upload Plugin, cu „Replace current with uploaded".
2. **Pluginul de reparat certificatele.** Fara el, „Initiate migration" cade cu `cURL error 60: unable to get local issuer certificate (20)`. Arhiva e in repo: **`tools/ecofridge-tls-fix.zip`**, sursa in `tools/ecofridge-tls-fix/` cu README. Plugins → Add New → Upload Plugin → Install → Activate, apoi se citeste notificarea de pe pagina Plugins: verde = API-ul raspunde cu verificarea certificatului pornita. Motivul: Migrate Guru suna `https://mg.blogvault.net` (din `info.php`, prin `appUrl()`), al carui certificat `*.blogvault.net` urca spre radacina GlobalSign Root R46, iar lista de radacini pe care WordPress 6.0 o da lui cURL, `wp-includes/certificates/ca-bundle.crt`, e mai veche decat acea radacina. Dovada, facuta pe 3 noiembrie: lanțul verificat cu lista luata chiar de pe live da `verify error:num=20`, iar cu lista Mozilla actuala da `Verify return code: 0 (ok)`.
3. Nimic altceva nu se atinge pe magazinul viu. Pluginul de TLS **se dezactiveaza si se sterge** dupa migrare.

### 3. Clonarea (DORU la butoane, CLAUDE verifica)

1. Pe clona: Migrate Guru → se copiaza **migration key**.
2. Pe site-ul vechi: Migrate Guru → se lipeste cheia si emailul → **Initiate migration**.
3. Migrate Guru trimite in bucati catre serverele lor, deci **nu construieste arhiva pe serverul lui Ambrit** — de-aia rămâne prima varianta pentru un magazin viu.
4. Cat ruleaza, nu se atinge nimic pe niciunul din cele doua site-uri.

### 4. Imediat dupa clonare, prin SSH (CLAUDE) — o singura conexiune

Ordinea conteaza: mai intai inchidem site-ul, apoi verificam, abia apoi citim date.

1. **Lacat si noindex.** Verificat pe 3 noiembrie: clona e acum **publica si indexabila** — raspunde 200 fara parola, `blog_public` e `1`, nu are meta robots, iar `robots.txt` da 404. Dupa clonare devine copie 1:1 a magazinului, cu zile inainte de lansare, adica exact ce nu trebuie sa intre in index. Deci: bloc `AuthType Basic` in `.htaccess`, cu `AuthUserFile /home/u510186744/websites/biqFdbY8d/.htpasswd` si `Require valid-user`, parola generata cu `openssl passwd -apr1`, plus `UPDATE <prefix>_options SET option_value='0' WHERE option_name='blog_public';`.
2. **Oprit orice update.** In `wp-config.php` al clonei: `AUTOMATIC_UPDATER_DISABLED` si `DISALLOW_FILE_MODS` pe `true` — a doua blocheaza si instalarea de pluginuri, de-aia **numai acum**, nu inainte de clonare. Se verifica si ca `wp-config.php` a rămas cu datele de conectare ale clonei, nu ale sursei.
3. **Scos ce e al gazduirii, nu al site-ului vechi:** `wp-content/mu-plugins/hostinger-h5g-plugin.php`, `wp-content/mu-plugins/hostinger-preview-domain.php` si, daca apar, `wp-content/object-cache.php` si `advanced-cache.php` (LiteSpeed).
4. **Potrivirea nucleului cu baza de date.** Se citeste `wp-includes/version.php`. Daca scrie tot **7.1.4** peste o baza venita de la WordPress 6.0 cu Woo 3.5.10, **nu se deschide wp-admin**: fie punem nucleul 6.0.x de pe wordpress.org, fie lucram doar la nivel de SQL. Capcana e dovedita — copia din iulie a ajuns pe WP 7.0 desi live era pe 6.0, semn ca in fluxul asta nucleul destinatiei rămâne. Daca se deschide adminul cu nucleu nepotrivit, WordPress si WooCommerce isi pornesc rutinele de upgrade pe baza de date, adica strica exact ce am venit sa copiem.
5. **Verificari de integritate**, de comparat cu site-ul vechi: numarul de tabele, numarul de comenzi (`SELECT COUNT(*) FROM <prefix>_posts WHERE post_type='shop_order';`), cel mai mare ID (`SELECT MAX(ID) FROM <prefix>_posts;`) si `siteurl` plus `home` din `<prefix>_options`, ca sa vedem ca inlocuirea de URL-uri s-a facut.
6. **Dump-ul de lucru**, din care extragem datele: `mariadb-dump` — pe serverul asta **nu** exista `mysqldump` — comprimat si descarcat local. Din clipa aia datele nu se mai mișca si putem repeta extragerea de cate ori vrem.

### 5. Variante de rezerva, in ordinea in care le incercam

1. **Pluginul de TLS nu ajuta** (OpenSSL prea batran pe serverul vechi, sau 443 spre exterior filtrat) → cerem gazdei lui Ambrit sa reinnoiasca pachetul `ca-certificates`. E reparatia corecta pentru tot site-ul vechi, nu doar pentru migrare, dar depinde de altcineva, deci se cere **joi**, nu sambata.
2. **Fara nicio conexiune spre exterior:** export cu **WPvivid** (cere PHP 5.6+, sparge arhiva in bucati, bun pe server vechi) sau cu **All-in-One WP Migration** (PHP 5.3+), descarcat prin browser; eu asez fisierul pe clona prin SSH, direct in folderul de backupuri al pluginului, si il restaurez de acolo — asa ocolim si limita de upload a destinatiei, fara extensie platita. **Duplicator nu merge**: cere PHP 7.4+ si WP 6.2+.
3. **Fara niciun plugin pe magazinul viu:** FTP/SFTP plus dump de baza de date de la Ambrit, exact metoda din iulie, cu zero scrieri pe live. Accesul e deja pe lista de cerut de la Mads.
4. **Doar media noua**, daca restul vine altfel: fisierele din `uploads` sunt publice, deci le pot descarca direct prin HTTP de pe site-ul vechi, pe baza diferentei fata de ce avem din iulie.

### 6. Ce nu facem

- Nu deschidem wp-admin pe clona cu nucleu nepotrivit (vezi 4.4).
- Nu lasam WooCommerce sa se actualizeze pe clona.
- Nu pornim migrarea cu CDN-ul activ pe destinatie.
- Nu lasam clona fara parola nicio ora dupa ce e plina.
- Nu stergem WebToffee PDF Invoices de pe site-ul nou; rămâne inactiv, nesters.
- Nu punem date de plata in `claude-chat.md` sau in git.


## 73. „We are unable to connect to your destination site" — cheia de migrare expirase (2026-11-03)

Doru, dupa ce a urcat pluginul de TLS pe site-ul vechi: **initierea migrarii a functionat**. Apoi, in panoul Migrate Guru: „We are unable to connect to your destination site `https://eco-fridge-backup.o2dev.de`", cu propunerea de a introduce totul manual, prin date de FTP. Intrebarea lui: „pot face ceva sa mearga automat?"

**Da, si nu e nevoie de niciun FTP.** Am verificat pe rand fiecare motiv posibil:

- **Accesibilitatea clonei din exterior:** merge. Cererea pe care o face serviciul lor — adresa site-ului cu parametrul `blogvaultkey` — raspunde 200 atat pe `http`, cat si pe `https`; nu exista parola de acces, nu exista redirect ciudat, iar certificatul `eco-fridge-backup.o2dev.de` e valid (Let's Encrypt, `Verify return code: 0 (ok)`).
- **CDN-ul:** deja oprit de Doru — antetul clonei da acum `Server: hws`, nu `hcdn`. Deci nu mai e in cale.
- **Cauza reala, gasita in baza de date a clonei:** pluginul tine cheia in opțiunea `bv_default_secret_key`, cu un `expires_at` alaturi. Pe clona scria **expires_at = 2026-11-03 03:56:36 UTC**, iar verificarea am facut-o la **04:07 UTC** — adica **cheia expirase cu zece minute inainte**. Fusese generata la activarea pluginului, pe 28 octombrie la 06:41 UTC. In codul pluginului, `getDefaultSecret()` returneaza `null` cand cheia a expirat, deci `connectionTag()` nu mai poate fi calculat si `verifyTag()` da fals: serviciul lor nu se mai poate autentifica la destinatie. De aici mesajul, care sugereaza greșit o problema de reța.

**Ce e de facut, si e simplu:** cheia se regenereaza singura. `defaultSecret()` vede cheia lipsa sau expirata si apeleaza `refreshDefaultSecret()`, deci **e de ajuns sa deschizi pagina Migrate Guru pe clona**, care afiseaza o cheie noua. Se copiaza, se lipeste pe site-ul vechi si se porneste migrarea. Daca pagina nu arata una noua, dezactivare plus reactivare a pluginului pe clona o forteaza.

**Atentie la fereastra de timp.** In versiunea 6.72 din repo, `MGRecover::SECRET_TTL` e **1800 de secunde, adica 30 de minute** (cheia veche de pe clona avea aproape 6 zile, deci fusese generata de o versiune mai veche). Asta inseamna: **se genereaza cheia, se copiaza si se porneste migrarea imediat**, in aceeasi sesiune de lucru. Nu se pregateste cheia seara ca sa fie folosita dimineata — exact capcana in care am intrat acum.

**Si o reparatie de igiena, facuta de mine prin SSH:** `siteurl` si `home` ale clonei erau pe `http://`, in timp ce panoul lor vorbea despre `https://`. Le-am trecut pe `https://eco-fridge-backup.o2dev.de`, fiindca certificatul e valid si oricum asa trebuie sa fie; nepotrivirea de schema e un al doilea motiv tipic pentru care verificarea destinatiei cade.

**Pentru sambata, de pus langa pasii din intrarea 72:** cheia de migrare se ia de pe clona **in momentul** in care pornim migrarea, nu inainte; daca mesajul reapare, nu e nevoie de FTP, se regenereaza cheia si se reia. Varianta manuala cu date de FTP rămâne ultima, fiindca inseamna sa dam datele de acces ale gazduirii unui serviciu din afara.

**Despre folderul pluginului de TLS:** prima versiune a arhivei a ieșit in folderul temporar al sesiunii, ceea ce a fost o alegere proasta — un fisier de care avem nevoie sambata nu are ce caută acolo. Sursa si arhiva sunt acum in proiect, la `tools/ecofridge-tls-fix/` si `tools/ecofridge-tls-fix.zip`, cu README care explica de ce exista si cum se reconstruieste arhiva.

## 74. Migrarea de test a pornit, plus clonarea staging → site nou cu unealta Hostinger (2026-11-03)

Doru: „migrarea a inceput pe Migrate Guru. (…) Intre timp, in timp ce se face migrarea de test din vechiul site catre backup site, testez migrarea site-ului de staging catre noul site eco-fridge — am ales optiunea din Hostinger de clone site (din lista de websites, langa butonul de create a new website), am ales sa cloneze site-ul de staging eco-fridge.o2dev.de si acum il cloneaza. (…) Astept sau apas butonul Continue?"

**Migrate Guru ruleaza.** Deci lanțul de azi e inchis: plugin actualizat 5.65 → 6.72, pluginul de TLS urcat pe site-ul vechi, cheie de migrare regenerata pe clona si folosita imediat, `siteurl`/`home` ale clonei trecute pe `https`. Migrarea de test din site-ul vechi catre `eco-fridge-backup.o2dev.de` a pornit.

**Raspunsul la intrebare: se apasa Continue.** Clonarea ruleaza pe serverul Hostinger, nu in pagina din browser — chiar asa scrie si mesajul lor, „You can leave this page". Butonul doar inchide ecranul de progres; site-ul clonat apare in lista Websites cand e gata, in pana la ~15 minute pe site. Statul pe pagina nu grabeste nimic.

**Observatia de moment:** ruleaza **doua operatii grele in acelasi cont** — Migrate Guru scrie in backup site, iar unealta Hostinger copiaza staging → site nou. Nu e o problema in sine, dar nu pornim o a treia si e normal ca staging-ul sa raspunda mai lent in intervalul asta.

**Ce verificam pe site-ul nou imediat ce clonarea se termina** (clonarea Hostinger copiaza fisiere plus baza de date intre doua site-uri din acelasi cont, dar nu stim inca daca inlocuieste singura URL-urile — azi aflam, si de-aia facem repetitia acum, nu sambata la 06:45):

1. **URL-urile:** `siteurl` si `home`, plus cat conținut a rămas cu `eco-fridge.o2dev.de` in baza de date. Daca unealta nu a facut inlocuirea, o fac eu prin SSH, pe tabelele de opțiuni, posturi, postmeta, termmeta si pe conținutul blocurilor.
2. **Lacatul de staging:** blocul `AuthType Basic` din `.htaccess` vine cu copia, iar `AuthUserFile` pointeaza spre calea veche a staging-ului, deci pe site-ul nou poate da eroare de server. Se corecteaza calea sau se scoate blocul — dar site-ul nou **rămâne inchis** pana la go-live, nu se lasa deschis.
3. **Indexarea:** `blog_public` si `robots.txt` fizic din radacina. Staging-ul e pe noindex; site-ul nou trebuie sa iasa din noindex **la lansare**, nu inainte, si asta e un pas separat in ghid.
4. **PHP:** site-ul nou merge pe stiva moderna, ca staging-ul — deci **nu** 7.4; acela e doar pentru clona site-ului vechi. De verificat ca versiunea aleasa de unealta e aceeasi cu a staging-ului.
5. **CDN:** Hostinger il porneste automat la site-urile eligibile, deci se verifica Performance → CDN si se face purge dupa go-live.
6. **Cache si must-use:** `object-cache.php` de la LiteSpeed si pluginurile must-use ale gazduirii sunt legate de site-ul lor, nu de conținut — se verifica sa nu tina pagini vechi.
7. **Paritate:** acelasi numar de tabele, de produse, de comenzi si de pagini ca pe staging; copiile `.avif`/`.webp` prezente in `uploads`.
8. **Licenta AG Lloyds Cardnet** poate fi legata de domeniu — se verifica dupa ce domeniul final e pe site-ul nou.
9. **SSH:** un site nou inseamna un utilizator SSH nou, deci **cheia mea publica trebuie adaugata din nou** pe el (pasul J7 din ghid). Fara asta nu pot face verificarile de mai sus.

**Ce rămâne nelamurit pana se termina:** daca unealta de clonare inlocuieste singura URL-urile si cu ce domeniu a creat site-ul nou (temporar sau cel final). Amandoua se vad in lista Websites dupa clonare.

## 75. Clona staging → site nou a reusit; ce am verificat pe ea si daca se poate conecta domeniul de test (2026-11-03)

Doru: „clonarea din staging in noul site s-a facut, am copia 1:1 a staging site pe `https://eco-fridge-o2dev-de-857081.hostingersite.com/` si am buton de Connect your domain unde o sa scriu domeniul eco-fridge.co.uk — are rost sa scriu acum de test domeniul sa vad ce se intampla sau stric ceva? Oricum o sa stergem acest site si repetam sambata."

### Ce am verificat pe copie, prin HTTP, cat timp aștepta

- **URL-urile au fost inlocuite complet, singure.** Zero apariții literale ale `eco-fridge.o2dev.de` in pagina, 1363 pentru noul host, iar `og:url` arata deja `https://eco-fridge-o2dev-de-857081.hostingersite.com/`. **Asta inchide intrebarea deschisa din plan**: unealta „clone site" din Hostinger face si search-replace-ul de domeniu, nu doar copia de fisiere si baza de date. Pentru sambata inseamna ca nu mai trebuie sa pregatesc un search-replace manual ca plan principal — rămâne doar ca verificare.
- **Noindex a venit cu copia:** `<meta name="robots" content="nofollow, noindex"/>`. Bun pentru moment, dar e si capcana de sambata: pe site-ul final trebuie **scos** la lansare.
- **Dar copia nu are parola.** Lacatul de staging nu a venit: pagina principala da 200 fara autentificare, `wp-login.php` da 200, iar `my-account` si `checkout` dau 302. E o copie 1:1 a magazinului, **cu date reale de clienti si comenzi**, deschisa oricui nimerește adresa. De pus protectie cu parola din hPanel cat timp exista copia, sau de sters site-ul de test imediat ce se termina repetitia. Ca regula pentru sambata: **site-ul nou se inchide inainte de a fi umplut**, nu dupa.
- **CDN pornit automat si aici:** antetul da `Server: hcdn`. De verificat si pe site-ul final.
- Cele 3 apariții ale `eco-fridge.co.uk` rămase sunt conținut legitim (emailul din schema, linkul `g.page`), nu URL-uri de reparat.

### Raspunsul despre „Connect your domain"

**Se poate, nu strica magazinul live.** DNS-ul domeniului e la IONOS, iar adaugarea unui domeniu intr-un cont Hostinger nu atinge nicio inregistrare DNS: doar pregateste gazdirea sa raspunda pentru acel nume. Singurul lucru care ar muta vizitatorii inainte de vreme e **schimbarea DNS-ului sau a nameserverelor la IONOS** — asta nu se atinge pana sambata.

**Doua precauții:**

1. **Se face ultimul**, dupa orice altceva vrem sa verificam pe copia asta. Hostinger poate trece domeniul principal al site-ului pe `eco-fridge.co.uk`, iar atunci copia poate sa nu mai raspunda pe adresa de preview, fiindca numele cel nou rezolva inca spre Ambrit. Nu pierdem nimic — site-ul se sterge oricum — dar s-ar termina testele de azi pe el.
2. **SSL-ul nu se poate emite** cat timp DNS-ul nu indica spre Hostinger; e normal sa apara „pending" sau o eroare de validare. Nu e o problema si nu ne strica nimic pentru sambata.

**Ce merita urmarit in dialog, fiindca de asta avem nevoie sambata:** daca adauga si varianta **`www`** (canonicalul nostru e `https://www.eco-fridge.co.uk`), daca cere nameservere sau doar o inregistrare A, si ce adresa IP arata — ca sa o pot compara cu ce punem in IONOS.

### Ordinea corecta sambata, confirmata de repetitia asta

1. Site nou creat si **inchis cu parola** din prima clipa.
2. Clonare staging → site nou (URL-urile se inlocuiesc singure; se verifica).
3. Connect your domain, cu `eco-fridge.co.uk` **si** `www`.
4. DNS la IONOS: o singura inregistrare schimbata.
5. Se aștepta emiterea certificatului.
6. `siteurl` si `home` pe `https://www.eco-fridge.co.uk`.
7. Se scoate noindex-ul si se scoate parola — in ordinea asta, ultimul pas.
8. CDN verificat si purge dupa go-live.

## 76. Domeniul pe site-ul nou: parked vs domeniu principal, si varianta www (2026-11-03)

Doru: „la noul site (ce va deveni live site) la parked domains vad doar eco-fridge.co.uk — adaug si www.eco-fridge.co.uk la Parked Domains in Hostinger?"

**Raspuns scurt:** da, se poate adauga, nu strica nimic, dar nu e asta lucrul important. Important e ca `eco-fridge.co.uk` sa ajunga **domeniul principal** al site-ului, nu un domeniu parcat, fiindca domeniul principal e cel care primeste gazda virtuala si certificatul — iar certificatul trebuie sa acopere **si** `www`, de vreme ce canonicul nostru e `https://www.eco-fridge.co.uk`.

**Ce spune documentatia Hostinger:** un domeniu parcat „afiseaza acelasi conținut ca domeniul principal", adica e un alias peste acelasi docroot, si **trebuie sa indice deja spre serverele lor** (nameservere sau inregistrare A) ca sa poata fi adaugat; altfel adaugarea da eroare. De-aia `eco-fridge.co.uk` apare acum acolo fara sa functioneze: zona e la IONOS, in contul clientului, si inregistrarea A arata inca spre serverul lui Ambrit. (Corectat pe 4 noiembrie: scrisesem „DNS-ul e la Ambrit", ceea ce e greșit - Ambrit tine gazduirea, nu zona.) Daca varianta `www` e adaugata automat la un domeniu parcat, documentatia **nu spune**, si exact de aia nu ne bazam pe presupunere: dacă `www` nu intra in certificat, vizitatorii care ajung pe canonicul nostru vad avertisment de securitate in browser. Deci: se adauga explicit dacă panoul o accepta, iar **inainte de a scoate parola** se verifica in pagina de SSL ca certificatul conține amandoua numele.

**Verificarea pe care am incercat-o si de ce nu a mers:** am vrut sa intreb serverul direct, cu `curl --resolve`, dacă raspunde pentru `eco-fridge.co.uk` si `www.eco-fridge.co.uk` inainte de mutarea DNS-ului. Nu se poate deocamdata: adresa de preview a site-ului nou e un CNAME catre **`free.cdn.hstgr.net`**, adica spre marginea CDN-ului Hostinger, deci din afara nu exista un IP de origine pe care sa-l interoghez. Doua concluzii practice din asta:

1. **CDN-ul pe site-ul final rămâne oprit la go-live.** Il pornim, dacă vrem, dupa ce totul e verificat; altfel verificam o margine de cache, nu site-ul, exact cand avem cel mai mult nevoie de adevar.
2. **Ce inregistrare cere Hostinger** pentru domeniu — o inregistrare A spre IP-ul planului, sau un CNAME spre CDN-ul lor — se citeste din hPanel **inainte** de a schimba ceva la IONOS, fiindca la IONOS schimbam o singura inregistrare si vrem sa fie cea corecta din prima.

**Ordinea pe domeniu, de pus langa pasii din intrarea 75:**

1. `eco-fridge.co.uk` setat ca **domeniu principal** al site-ului nou (nu doar parcat); dacă panoul ofera „main domain" sau „connect domain", asta e drumul.
2. `www.eco-fridge.co.uk` acoperit si el — adaugat explicit dacă nu apare singur.
3. Se citeste din hPanel ce inregistrare DNS cere.
4. Se schimba inregistrarea la IONOS.
5. Se aștepta certificatul si **se verifica in pagina de SSL ca are amandoua numele**.
6. Verificarile mele de dupa: `https://eco-fridge.co.uk` si `https://www.eco-fridge.co.uk` raspund amandoua, certificat valid pe amandoua, iar varianta fara `www` face 301 spre `www` — regula de canonic din `.htaccess` e pe numele gazdei, nu pe `%{HTTPS}`, ca sa nu intram in bucle in spatele LiteSpeed.
7. Abia apoi se scoate noindex-ul si, la final, parola.

**Completare, aceeasi zi.** Panoul refuza varianta `www`: `failed [without_subdomain:www] rule validation`. **Nu e o problema de moment, ci o regula de validare** — campul interzice din principiu un domeniu scris cu `www.` in fata, deci nu se va putea nici dupa mutarea DNS-ului la IONOS. Si asta confirma exact ce voiam sa aflam: Hostinger trateaza `www` ca parte a aceluiasi domeniu, nu ca o intrare separata, de-aia nici nu te lasa sa-l scrii. Doru: „sper sa pot dupa conectarea domeniului din ionos" — nu, si nu e nevoie.

Prin urmare lista de reincercat dupa DNS e goala, iar verificarile rămân: certificatul din pagina de SSL a site-ului sa conțină **amandoua numele** (documentatia Hostinger promite SSL gratuit pentru fiecare domeniu si subdomeniu gazduit la ei, reinnoit automat cat timp domeniul indica spre ei, dar acoperirea `www` plus non-`www` apare doar in surse terte, deci se verifica cu ochii); amandoua adresele sa raspunda cu certificat valid; apex sa faca 301 spre `www`. Dacă `www` lipseste din certificat, se reemite SSL-ul din hPanel dupa propagarea completa, si abia apoi e caz de suport Hostinger — nu se rezolva prin parked domains.

## 77. Stergerea site-ului de test si ce a dovedit repetitia (2026-11-03)

Doru: „acum sterg acest site eco-fridge.co.uk si vom relua procedura de la zero din nou sambata. Confirmi?"

**Confirmat.** Copia e de o ora, facuta din staging, si nu s-a creat nimic pe ea; staging-ul e intact, iar magazinul live nu e atins in niciun fel, de vreme ce DNS-ul e in continuare la IONOS catre Ambrit. Stergerea unui website din cont scoate fisierele, baza de date si gazda virtuala ale acelui website, nimic altceva.

**Ce i-am cerut inainte de a apasa:**

1. **Sa noteze inregistrarea DNS pe care o arata hPanel** pentru `eco-fridge.co.uk` — IP-ul pentru A record sau CNAME-ul. IP-ul e al planului, nu al site-ului, deci va fi acelasi sambata, dar daca il avem acum nu-l mai cautam in fereastra de lansare si pot pregati valoarea pentru IONOS din timp.
2. **Sa verifice de doua ori rândul pe care il sterge:** `eco-fridge-o2dev-de-857081.hostingersite.com`. In aceeasi lista, langa el, e `eco-fridge.o2dev.de` — staging-ul, care conține toata munca si din care clonam sambata. Asta e singura greseala cu adevarat costisitoare din toata operatia de azi.

**Ce i-am cerut dupa stergere:** sa se uite daca `eco-fridge.co.uk` a mai rămas agatat undeva in cont — la Parked domains al altui site sau in lista de domenii. Daca rămâne ocupat, sambata „Connect domain" ar da „domain already in use" exact in fereastra de lansare.

**Despre mailuri:** la Hostinger nu exista cutii postale pentru `eco-fridge.co.uk`, iar MX-urile sunt la IONOS si nu se ating cand domeniul e scos de pe un website. Avertismentul din documentatia lor despre stergerea adreselor de email la despachetare nu ne priveste.

**Migrarea Migrate Guru** catre backup site nu e afectata: e alt website in cont.

### Ce a dovedit repetitia de azi, ca sa nu refacem descoperirile sambata

1. Unealta „clone site" din Hostinger copiaza fisiere plus baza de date **si face singura inlocuirea de URL-uri** — zero apariții ale domeniului de staging in pagina clonata.
2. **Noindex-ul vine cu copia**, deci site-ul nou va fi pe noindex din start si trebuie scos la lansare, ca pas separat si ultim.
3. **Parola nu vine cu copia** — copia a fost deschisa oricui, cu date reale de clienti. De aceea, sambata, site-ul nou se inchide cu parola **inainte** de a fi umplut.
4. **CDN-ul se porneste automat** pe site-urile noi, iar adresa de preview e un CNAME catre `free.cdn.hstgr.net`. Deci: CDN oprit pana dupa verificari, altfel verificam cache-ul, nu site-ul.
5. **Varianta `www` nu se adauga separat** — formularul o refuza prin regula `without_subdomain:www`, fiindca `www` aparține aceleiasi intrari ca domeniul fara `www`. Se verifica doar ca certificatul le conține pe amandoua.
6. Un website nou inseamna **utilizator SSH nou**, deci cheia mea publica se adauga din nou (pasul J7 din ghid) inainte sa pot verifica ceva pe server.

## 78. DNS-ul de sambata, lamurit complet: o singura inregistrare, plus TTL-ul de coborat ASTAZI (2026-11-03)

Doru a trimis din hPanel: „nu apare nicio inregistrare in DNS records", dar la Hosting details scrie `Access your website at https://eco-fridge.co.uk`, `Access your website with www https://www.eco-fridge.co.uk`, **Website IP address 148.230.121.74**, iar serverul e `uk-fast-h5g-node15.hstgr.io`, locatie United Kingdom.

**De ce nu apar inregistrari DNS la Hostinger:** zona domeniului e la IONOS. Nameserverele publice sunt `ns1030.ui-dns.de / .com / .biz / .org` (TTL 172800), adica IONOS, iar editorul de DNS din hPanel administreaza doar domeniile care folosesc nameserverele Hostinger. Nu lipseste nimic si nu e nimic de reparat.

**IP-ul de pus la IONOS: `148.230.121.74`.** E acelasi IP pe care stau si staging-ul si backup site-ul — e IP-ul planului, partajat, iar serverul alege site-ul dupa numele din cerere, nu dupa IP. Deci e normal si corect asa.

**Starea reala a zonei, interogata azi din exterior:**

| Inregistrare | Valoare acum | TTL | Ce facem sambata |
|---|---|---|---|
| `eco-fridge.co.uk` A | **185.230.220.98** (Ambrit) | **3600** | **se schimba in `148.230.121.74`** |
| `www.eco-fridge.co.uk` | **CNAME → `eco-fridge.co.uk`** | 3600 | **nu se atinge** — urmeaza automat |
| AAAA (apex si www) | **nu exista** | — | nimic |
| CAA | **nu exista** | — | nimic |
| MX | `mx00.ionos.co.uk`, `mx01.ionos.co.uk` | 3600 | **nu se ating** |
| TXT | SPF `v=spf1 +a +mx +ip4:83.223.113.98 include:_spf-eu.ionos.com ~all` si `google-site-verification=…` | 3600 | **nu se ating** |

**Trei lucruri bune care ies din tabel:**

1. **Chiar e o singura inregistrare de schimbat.** `www` e un CNAME spre apex, deci se mută singur odata cu A-ul — ceea ce confirma si randul „Access your website with www" din hPanel: gazduirea raspunde pentru amandoua numele, fara sa adaugam nimic (si de-aia formularul de parked domains refuza varianta `www`).
2. **Nu exista AAAA.** Asta scoate din joc capcana clasica de lansare, in care se schimba doar A-ul iar clientii cu IPv6 ajung in continuare pe serverul vechi.
3. **Nu exista CAA**, deci nimic nu blocheaza emiterea certificatului Let's Encrypt pentru cele doua nume.

**De facut ASTAZI, nu sambata: coborat TTL-ul.** Inregistrarea A are TTL 3600, deci dupa schimbare resolverele pot servi inca pana la o ora IP-ul vechi — adica unii clienti ar vedea magazinul vechi, in modul mentenanta, dupa ce noi credem ca am lansat. Reparatia: la IONOS, **se schimba doar TTL-ul inregistrarii A la 300 de secunde, valoarea rămâne 185.230.220.98**. Efect vizibil pe magazinul de acum: zero. Dar coborarea devine efectiva abia dupa ce expira TTL-ul vechi la resolverele care au deja raspunsul in cache, deci se face **cu cateva zile inainte**, iar azi e marti si lansarea e sambata: momentul potrivit e acum. Dupa ce lansarea e verificata, TTL-ul se poate urca din nou la 3600.

**O observatie despre emailuri, de tinut minte la verificarile de dupa lansare:** SPF-ul conține `+a`, adica autorizeaza sa trimita email exact IP-ul din inregistrarea A. Dupa mutare, acela devine serverul Hostinger, deci emailurile de comanda trimise de server rămân autorizate automat; nu e nevoie sa schimbam SPF-ul. Rămâne totusi de verificat livrarea efectiva a emailurilor de comanda dupa go-live.

**Si un detaliu care ne ajuta, desi Doru credea ca nu:** serverul e in Marea Britanie (`uk-fast-h5g-node15.hstgr.io`). Pentru un magazin britanic e exact unde trebuie, iar la masurarea Core Web Vitals de dupa lansare conteaza: comparatia cu scorurile de pe staging e corecta, fiindca staging-ul e pe acelasi nod.

## 79. Clona site-ului vechi: inchisa, ingheţata — si trei descoperiri care schimba planul (2026-11-03)

Doru: migrarea din site-ul vechi catre `eco-fridge-backup.o2dev.de` s-a terminat cu succes, clona arata exact ca site-ul vechi, site-ul de test al noului site a fost sters, iar TTL-urile le coboara el. Cererea: „pune tu noindex si orice e nevoie la eco-fridge-backup.o2dev.de ca sa nu apara online si sa nu poata fi gasit."

### Ce am facut, si cum am ajuns acolo

Am lucrat cu scripturi urcate pe server, nu cu comenzi inline, si am avut nevoie de patru treceri, fiindca serverul are trei limite pe care nu le stiam:

1. **Folderul site-ului si `$HOME` nu sunt scriibile** (sunt ale lui `root`), deci fisierul de parole nu putea sta langa docroot, ca pe un server obisnuit.
2. **`awk` nu exista**, iar **`sed -i` nu poate redenumi peste `wp-config.php`** („Device or resource busy") — fisierele de nucleu vin prin stratul h5g al Hostinger-ului. Soluția: se scrie in loc cu `cat fisier-nou > fisier`.
3. **Autentificarea Basic din `.htaccess` e ignorata** de serverul lor: site-ul raspundea in continuare 200 fara parola, desi `Require all denied` **functioneaza** (fisierul `.htpasswd` dadea 403). Deci parola pe directoare se poate pune doar din hPanel, nu din `.htaccess`.

**Lacatul final, la nivel de PHP, in `wp-config.php`:** orice cerere HTTP catre WordPress primeste **403** si `X-Robots-Tag: noindex, nofollow, noarchive`, in afara de cea care aduce tokenul; tokenul pune un cookie valabil sapte zile si dupa aceea copia se navigheaza normal. Tokenul si adresa de deblocare sunt in chat, **nu in git**. Verificat din exterior: `/`, `/wp-login.php`, `/wp-admin/`, `/feed/`, `/wp-json/wp/v2/posts` si `/sitemap_index.xml` dau toate 403 cu textul „Not available.", iar cu cookie-ul pus pagina principala revine cu titlul corect.

**Si restul masurilor:**

- `blog_public` pe **0**.
- `robots.txt` fizic in radacina, cu `User-agent: *` / `Disallow: /`.
- `.ht*` refuzate prin `.htaccess` (singura directiva de acces care chiar e respectata aici).
- In `wp-config.php`: `AUTOMATIC_UPDATER_DISABLED`, `DISALLOW_FILE_MODS` si `DISABLE_WP_CRON`, toate pe `true` — nimic nu se mai actualizeaza si **nimic programat nu mai ruleaza** pe copie, deci Action Scheduler-ul lui WooCommerce nu poate atinge datele pe care venim sa le citim. `php -l` curat, copii `.bak-pre-*` pastrate pentru fiecare fisier atins.
- **Nu am putut** muta pluginurile must-use ale Hostinger-ului (`hostinger-h5g-plugin.php`, `hostinger-preview-domain.php`): folderul `mu-plugins` e al lui `root`. Rămân pe loc; nu deranjeaza extragerea de date, dar pasul 4.3 din intrarea 72 e de corectat — pe clona nu se poate face.
- Nucleul clonei a rămas **WordPress 7.1.4** peste baza de date a site-ului vechi, exact capcana prevazuta. Site-ul se incarca, dar **wp-admin nu se deschide** pana nu decidem: ori nucleu 6.0.x, ori extragere doar prin SQL. Cu cronul oprit si update-urile blocate, riscul e ținut in loc.

### Trei descoperiri din datele clonei, care schimba planul

**Starea clonei:** prefix de tabele `43lz2ypq8_`, 281 de tabele, `siteurl`/`home` pe `https://eco-fridge-backup.o2dev.de`, 131 de produse publicate.

1. **Regula „tot ce are ID peste 7261 e comanda noua" e GREȘITA.** Pe site-ul vechi, comenzile merg de la **#2741 (26 martie 2019)** pana la **#14632 (31 octombrie 2026)**. Dupa ID, 5.168 de comenzi ar parea „noi"; in realitate, **comenzi plasate de la 10 iulie 2026 incoace sunt 85**, cu ID-uri intre **#14514 si #14632**, iar inainte de 10 iulie sunt 5.929. Deci #7261 e un numar din **numerotarea site-ului nou**, nu din cea a site-ului vechi: migrarea din iulie a reimportat comenzile ca posturi noi, cu ID-uri noi. **Delta se selecteaza dupa data (`post_date >= 2026-07-10`), niciodata dupa ID.** Cele 85 de comenzi se potrivesc exact cu estimarea de ~20 pe luna.
2. **Numerotarea viitoare trebuie decisa.** Nu exista meta `_order_number` pe site-ul vechi (zero randuri), deci **numarul comenzii ESTE ID-ul postului**. Clientii au primit prin email numere de forma #14xxx, in timp ce site-ul nou e la #8037. Dacă importam cele 85 de comenzi cu numerele lor originale, urmatorul numar pe site-ul nou trebuie sa fie **peste 14632**, nu peste 8037 — altfel doua comenzi diferite pot primi acelasi numar. Alternativa e sa le renumerotam si sa acceptam ca numarul din emailul clientului nu mai corespunde. **Decizia e a lui Doru si a lui Mads**, dar trebuie luata inainte de import.
3. **„Clienti noi" e aproape o lista goala, si asta e o veste bună.** Tabela de utilizatori are **5 conturi**, toate de personal (`ECO-Fridge` si `manager` administratori, `Eco-fridge-shop` shop manager, plus `Distinctly` si `Lockouts`). Din comenzi, **6.008 sunt plasate ca oaspete** si doar **6** au cont atasat. Exista 3.565 de adrese de facturare distincte, dar ele trăiesc in meta comenzilor, nu ca utilizatori. Prin urmare punctul 3 din planul de backup — conturi, adrese, parole — nu are practic ce migra; datele clientilor vin odata cu comenzile.

### Ce rămâne de facut pe clona

Dump-ul de lucru cu `mariadb-dump`, descarcat local, din care extragem cele 85 de comenzi, diferentele de produse si pagini, media noua, redirecturile si metadatele SEO. Il fac la cerere; de preferat dupa ce Doru confirma decizia de numerotare, fiindca de ea depinde forma importului.

## 80. Checklistul si calendarul din Sheet aduse la zi (2026-11-03)

Doru: „actualizeaza-mi si checklist dar mai ales calendarul". Am lucrat direct in „Eco-fridge: checklist cu bife", in ambele foi; linkul rămâne acelasi.

### Calendar lansare

**Mutat mai devreme, fiindca s-a facut azi:** randul de TTL si randul „Repetiție: copia site-ului vechi" au trecut de la joi 5 noiembrie la **marti 3 noiembrie**. TTL-ul e „in lucru", copia e bifata, iar randul de extragere a datelor a devenit „sambata, dupa copia finala", fiindca repetitia lui nu mai are rost separat — avem deja copia.

**Rand nou, chiar inaintea blocului de sambata: „Ce a dovedit repetitia si ce se schimba in ziua lansarii".** Sase puncte, scrise ca sa fie citite o singura data, dimineata: unealta „Clone site" exista si inlocuieste singura URL-urile; noindex vine cu copia dar **parola nu**, deci site-ul nou se inchide **inainte** de a fi umplut; CDN-ul porneste automat si se opreste pana dupa verificari; domeniul adaugat sta la Parked domains pana la DNS, iar varianta `www` nu se poate adauga separat; IP-ul pentru IONOS e 148.230.121.74 pe nodul britanic `uk-fast-h5g-node15.hstgr.io`; Migrate Guru are cele trei capcane deja rezolvate.

**Corectat randul critic de la 05:40** — importul comenzilor. Scria „TOATE comenzile apărute după #7261"; acum scrie regula adevarata: selectie **dupa data** (`post_date >= 2026-07-10`), 85 de comenzi cu ID-uri **#14514–#14632**, plus decizia de numerotare care trebuie luata inainte de import si faptul ca nu exista conturi de clienti de migrat.

**Randul de DNS de la 07:30** are acum valorile verificate azi din exterior: A @ `185.230.220.98` → `148.230.121.74`, `www` e CNAME si urmeaza singur, **nu exista AAAA** (deci nicio capcana IPv6) si **nu exista CAA** (deci nimic nu blocheaza Let's Encrypt), MX si TXT neatinse, TTL coborat azi.

**Si trei defecte ale foii, reparate:**

1. Randul de la **08:30, „Comandă și formular reale", avea copiat textul despre SSL** — pasul isi pierduse continutul. L-am rescris cu ce se face de fapt: o comanda reala cu cardul, de la cos la pagina de mulțumire, si un formular real din pagina de contact, cu verificarea emailurilor si a SMS-ului.
2. Randul de **GTM si Consent Mode v2 dispăruse**: textul lui fusese inlocuit de regula de canonic. I-am facut rand propriu la 08:15, cu ce trebuie verificat, inclusiv faptul ca vechiul site rula `GTM-NFMC9Z3`, deci containerul de pe site-ul nou se confirma inainte de a fi pus.
3. **Regula de canonic statea la 08:15, desi textul ei cere sa fie inainte de DNS.** Am mutat-o la **07:10**, dupa copiere si inaintea schimbarii DNS-ului, si am sters randul rămas dublat. Randul de SSL de la 07:45 nu avea nici „Cine", nici „Status"; le-am completat.

### Checklist eco-fridge

- **F15-01** (copia completa): bifat **◐ Parțial** cu data de azi — repetitia e facuta, rularea reala e sambata — si descrierea celor trei obstacole Migrate Guru plus starea de acum a clonei (403 fara token, update-uri si cron oprite, nucleu 7.1.4 peste baza veche deci wp-admin nu se deschide, must-use ale Hostinger nemutabile). Dovada: `tools/ecofridge-tls-fix/`.
- **F15-02** (comenzile): regula corectata, cu motivul numeric, si trimitere la F15-10 pentru numerotare.
- **F15-03** (clientii noi): **◐ Parțial**, lamurit — 5 conturi de personal, 6.008 comenzi ca oaspete, 6 cu cont, 3.565 de adrese de facturare care trăiesc in meta comenzilor. Nu e nimic de migrat ca utilizatori; rămâne doar verificarea ca meta de facturare a venit intreaga.
- **F15-10** (reconciliere si numarul urmator): scrisa ca **decizie**, cu cele doua variante si consecinta fiecareia.

Ce **nu** am atins: randurile F14 de lansare, fiindca detaliul zilei sta in calendar si nu are rost dublat, si nicio alta foaie sau formula.

## 81. Numerotarea comenzilor: decizia lui Doru, si de ce recomand totusi stergerea comenzilor de test (2026-11-03)

Doru: „sambata, comenzile noastre de test vor fi renumerotate astfel incat sa vina dupa (adica sa urmeze) ultima comanda ce a fost plasata pe site-ul live vechi inainte de sambata (…) Apoi, decizi tu daca este mai bine sa le stergem sau pastram si daca ii strica ceva in evidenta lui Mads daca le pastram".

**Decizia pe partea de numerotare e luata si e cea corecta:** comenzile reale vin cu numerele lor originale, iar magazinul nou continua dupa ele. Asta inseamna ca un client care caută numarul din emailul primit il regaseste identic, ceea ce e singurul lucru care conteaza pe termen lung.

### Ce am verificat pe staging inainte sa raspund

- **HPOS e OPRIT** pe site-ul nou: `woocommerce_custom_orders_table_enabled = no`, tabela `wp_wc_orders` nu exista. Deci comenzile sunt posturi, exact ca pe site-ul vechi, iar **numarul comenzii ESTE ID-ul postului** pe ambele parti. Vestea buna pentru sambata: cele 85 de comenzi se mută post-la-post, in aceeasi forma de stocare.
- **Starea numerelor acum:** 5.938 de comenzi importate in iulie, cu numere pana la **#7261**; comenzile noastre de test ocupa **#8007, #8029, #8030, #8036, #8037, #8038, #8039**, iar **cel mai mare ID de post e 8042**, cu `AUTO_INCREMENT = 8043`.
- **Statusurile comenzilor de test:** #8036, #8037, #8038 sunt **Processing** (£1,00 fiecare, plata reala cu cardul); #8007 (£1.542, transfer bancar) si #8039 (£1,00, testul SMS) sunt **On hold**; #8029 si #8030 sunt **Cancelled**.

### Recomandarea mea: se sterg, iar renumerotarea nu mai e necesara

**Renumerotarea costa mai mult decat stergerea, si tocmai pe comenzi pe care oricum le aruncam.** Cu HPOS oprit, schimbarea numarului unei comenzi inseamna schimbarea ID-ului postului, deci `UPDATE` pe `wp_posts`, `wp_postmeta`, `woocommerce_order_items`, `wp_wc_order_stats`, `wp_wc_order_product_lookup`, tabelele de lookup pentru taxe si cupoane, plus `wp_comments` pentru notele comenzii — sase-opt tabele per comanda, cu grija la coliziuni de chei. E chirurgie de baza de date, facuta la 05:40 in dimineata lansarii, pentru sapte comenzi care nu reprezinta vanzari reale. Stergerea e un clic pe fiecare.

**Iar rezultatul pe care il vrei se obtine cu o singura comanda SQL.** Dupa import, se pune `AUTO_INCREMENT` al tabelei `wp_posts` peste cel mai mare numar importat — daca ultima comanda reala de sambata e, sa zicem, #14888, se pune 14901. De acolo magazinul nou continua singur. Exact efectul descris: nicio suprapunere, numerotare care curge mai departe.

**Da, pastrarea lor strica ceva in evidenta, si concret:** WooCommerce Analytics exclude implicit doar comenzile `pending`, `failed` si `cancelled`. Comenzile **Processing** si **On hold** INTRA in rapoarte. Deci, pastrate, prima luna de pe site-ul nou ar arata cinci comenzi si aproximativ **£1.545** care nu exista — #8007 cu £1.542 fiind cea care strica cifra — plus o linie de produs pentru produsul ascuns de test in raportul de produse. Rapoartele se construiesc din `wp_wc_order_stats`, de aceea planul cere deja verificarea ca randurile de acolo dispar.

**Ce NU se schimba, oricum alegem:** cele trei plati reale de £1,00 cu cardul sunt in decontarea Lloyds si rămân acolo, fiindca stergerea unei comenzi din WooCommerce nu atinge procesatorul. Contabilitatea lui Mads lucreaza din extrasul de cont si din decontarea Lloyds, deci partea de bani e identica in ambele variante. Diferenta e doar dacă magazinul raporteaza sau nu vanzari care nu au existat.

**Si un risc in plus la pastrare:** dacă WebToffee PDF Invoices e activat vreodata, ar putea emite numere de factura pentru comenzi de test, iar o serie de facturare cu vanzari fictive e exact genul de lucru care se repara greu.

**Dacă totusi vrei sa rămână**, varianta curata e: toate trecute pe **Cancelled**, cu o nota pe comanda („test intern, nu e o vanzare"), ca sa iasa din rapoarte — si atunci da, renumerotarea are sens, dar o fac prin script, testat pe o copie, nu improvizat in dimineata lansarii. Urmele de care am nevoie ca dovada ca plata a functionat pe gateway-ul live sunt deja in nota de proiect, unde stau si referintele de tranzactie, nu in git.

**Concluzie, pe scurt:** ștergem cele sapte comenzi de test si produsul ascuns, importam comenzile reale cu numerele lor, apoi pornim secventa peste cel mai mare numar importat. Decizia de numerotare a lui Doru rămâne respectata integral — doar drumul pana la ea devine mai scurt si fara risc.

## 82. Aprobare permanenta: jurnal, git cu merge, si Sheet-ul, fara sa mai cer (2026-11-03)

Doru: „tine minte si noteaza in claude-chat.md: ai aprobarea mea sa actualizare a claude-chat.md, plus push, commit si merge automata pe git, plus actualizare calendar si checklist pe Google Drive."

**Ce se schimba, de acum:** regula din 29 octombrie, care cerea actualizarea jurnalului dupa fiecare lucrare, se extinde cu doua lucruri pe care pana acum le lasam lui Doru:

1. **Git, pana la capat.** Branch, commit, push, PR **si merge**, apoi inapoi pe `main` cu `pull`. Pana azi deschideam PR-ul si aparea pasul blocat „Merge Without Review", iar PR-urile se adunau. De acum le unesc eu. Dacă harnessul blocheaza totusi merge-ul, spun direct ca s-a blocat si las PR-ul deschis — nu caut ocolisuri.
2. **Sheet-ul „Eco-fridge: checklist cu bife"**, ambele foi — calendarul de lansare si checklistul — actualizate in acelasi tur cu jurnalul, direct in fisier, cu linkul neschimbat.

**Ce NU se schimba:** datele de plata, referintele de tranzactie si orice credentiale rămân in afara jurnalului, a Sheet-ului si a git-ului; stau in notele de proiect. Si rămâne regula de o singura conexiune SSH pe Hostinger la un moment dat.

**Ce inseamna practic:** la finalul fiecarei lucrari sau decizii, trei locuri se actualizeaza singure — jurnalul, istoricul git si Sheet-ul. Doru nu mai trebuie sa ceara niciunul din ele si nu mai trebuie sa uneasca PR-uri manual.

## 83. Numerotarea comenzilor, decis definitiv: comenzile de test se sterg sambata (2026-11-03)

Doru: „de acord, le stergem sambata — deci site-ul nou va continua numarul comenzilor noi dupa ultima comanda reala preluata la zi din site-ul vechi live."

**Decizia finala, in patru pasi, exact cum se executa sambata:**

1. **Stergerea.** #8007 (transfer bancar, £1.542), #8029 si #8030 (card, anulate la 3DS), #8036, #8037 si #8038 (card, £1,00 fiecare, plati reale), #8039 (transfer bancar, £1,00, testul SMS), produsul ascuns #8035 si cosurile abandonate de test. **Se sterg definitiv, nu doar la coș** — altfel posturile rămân si ocupa numerele. Nu se ramburseaza nimic, decizia lui Mads din 29 octombrie; stergerea din WooCommerce nu atinge Lloyds, iar contabilitatea lucreaza din extrasul de cont si din decontarea Lloyds. Dupa stergere se verifica si ca randurile din `wp_wc_order_stats` au disparut, fiindca rapoartele se construiesc de acolo.
2. **Importul.** Comenzile noi se selecteaza **dupa data** (`post_date >= 2026-07-10`), cu numerele lor originale, liniile de produs, notele, rambursarile si statusurile. La 31 octombrie erau 85, cu ID-uri intre #14514 si #14632; sambata vor fi cateva mai multe.
3. **Secventa.** `ALTER TABLE wp_posts AUTO_INCREMENT` pus peste cel mai mare numar importat. Dacă ultima comanda reala e, sa zicem, #14888, se pune 14901. De acolo magazinul continua singur, exact efectul cerut.
4. **Verificarea.** Reconciliere 1:1 a listei de numere si a totalului incasat, iar **prima comanda reala de la 08:30 inchide bucla**: numarul ei trebuie sa vina dupa ultimul importat. E si motivul in plus pentru care pasul acela, al carui text fusese pierdut in Sheet, merita reparat.

**Doua lucruri de stiut, ca sa nu dea nimeni alarma falsa sambata:**

- Pe site-ul vechi exista si comenzi reale cu numere in zona 8000 — adica fix numerele ocupate acum de testele noastre. Nu se suprapun cu nimic: acele comenzi vechi sunt deja pe site-ul nou, sub numerele 1–7261, din renumerotarea facuta la migrarea din iulie. Importam doar comenzile de dupa 10 iulie, care sunt in zona 14500+.
- Numerele viitoare vor avea **goluri**, fiindca secventa de ID-uri e comuna cu paginile, produsele si reviziile. Site-ul vechi se purta identic, deci nu e o schimbare pentru client.

**Actualizat in acelasi tur**, conform aprobarii permanente de la intrarea 82: randul de 05:40 din calendar (acum cu toti cei patru pasi si cu motivul in cifre), plus F15-02 si F15-10 din checklist, amandoua datate 3 noiembrie. Jurnalul, git-ul si Sheet-ul spun acum acelasi lucru.

## 84. Dump-ul din clona: luat azi, si de ce mai e nevoie de unul sambata (2026-11-03)

Doru: „cand trebuie sa dau dump din clona sa-l aduci pe local? sambata dimineata dupa clonarea site-ului vechi la zi? sau acum? acum e marti dimineata."

**Raspunsul: ambele, fiindca au rosturi diferite — iar nu trebuie sa-l dea el, il iau eu prin SSH.**

- **Cel de azi** e materialul de proba: pe el construiesc si rulez scripturile de extragere, fara presiune de timp. Clona e ingheţata (cron oprit, update-uri blocate, poarta inchisa), deci datele din el nu se mai mișca — exact de aia am facut copia cu patru zile inainte.
- **Cel de sambata** e marfa reala: intre azi si sambata magazinul vechi mai primeste comenzi, poate mai schimba stocuri, preturi sau conținut. Atunci scripturile sunt deja dovedite, iar singurul lucru nou e setul de date.

### Dump-ul de azi

Luat la 05:37 UTC, **9,1 MB arhivat, 71,5 MB desfacut, 127 de tabele**, cu „Dump completed" la final si `gzip -t` curat. Local: `source-dump/db/backup-clone-2026-11-03.sql.gz`, langa cel din iulie; folderul e deja in `.gitignore`, deci nu intra in repo.

**Ce am lasat deliberat afara** din cele 281 de tabele, dupa ce am masurat marimile: tabelele **Lloyds Cardnet** (`_ecc` 52 MB si `_transaction` 11 MB) — sunt date de plata si nu avem ce face cu ele, deci nu au ce caută pe laptop; tabelele **Matomo** (aproximativ 130 MB de jurnale de vizite si arhive binare, care nici nu se comprima); plus jurnalele de securitate, de email si Action Scheduler. De aici si diferenta fata de dump-ul din iulie, care avea 162 MB tocmai pentru ca le conținea pe toate.

**Verificat ca e intreg, nu doar ca exista:** sunt prezente `posts`, `postmeta`, `users`, `usermeta`, `terms`, `term_taxonomy`, `term_relationships`, `termmeta`, `comments`, `commentmeta`, `options`, `woocommerce_order_items` si `woocommerce_order_itemmeta`; `shop_order` apare de 12.072 de ori, adica cele 6.014 comenzi; comanda #14632 e in dump.

### O descoperire care schimba felul in care luam media

**Datele fisierelor nu mai pot fi folosite ca filtru.** In `uploads` sunt 908 MB, iar `find -newermt 2026-07-10` returneaza 22.366 de fisiere — adica practic tot, fiindca restaurarea Migrate Guru a rescris data fiecarui fisier. Deci delta de media se afla **din baza de date**, nu de pe disc: atasamente cu `post_date >= 2026-07-10`. Si sunt **doua fisiere**. Punctul F15-07 din checklist e, practic, gata inainte sa inceapa.

### O gaura de securitate pe care am facut-o si am inchis-o

Scripturile de ieri lasaseră in docroot copii de siguranta `wp-config.php.bak-pre-*`. Extensia `.bak` **nu** e executata de server, deci fisierul s-ar fi servit ca text simplu, cu datele de conectare la baza de date, iar poarta mea din `wp-config.php` nu acopera fisierele statice. Le-am mutat in `/tmp/efbk-config-backups/`, afara din docroot, si am lărgit regula din `.htaccess` ca sa refuze si `.bak`, `.sql`, `.gz`, `.zip`, `.tar`, `.log`, `.old`, `.orig`, `.save`, `.swp`. Verificat din exterior: fosta adresa a copiei da **403**, `.htaccess` si `.htaccess.keep` dau 403, pagina principala da 403 fara token si 200 cu token, `robots.txt` rămâne 200 cu `Disallow: /`. Copia dump-ului de pe server a fost stearsa dupa descarcare.

**De tinut minte pentru sambata:** orice copie de siguranta pe care o fac in docroot trebuie sa plece imediat din docroot. Pe site-ul live, unde nu exista poarta, ar fi fost o scurgere adevarata, nu una pe o copie de unica folosinta.

## 85. Scripturile rulate pe dump-ul de azi: 83 de comenzi, nu 85, si alte cinci lucruri aflate la timp (2026-11-03)

Doru: „da, ruleaza scripturile pe dump-ul de azi."

**Cum am lucrat:** am incarcat ambele baze in containerul MariaDB al wp-env, ca `oldsite` (copia site-ului vechi, prefix `43lz2ypq8_`) si `newsite` (site-ul nostru, prefix `wp_`), ca diferentele sa iasa din join-uri reale, nu din comparatii pe ochi. Raportul complet, cu cifre si liste, e in repo: **`migration/f15-delta-2026-11-03.md`**. Lista celor 83 de comenzi, cu numere, date, statusuri, totaluri si chei: `source-dump/_f15/orders-to-import.csv`, in afara git-ului.

### Descoperirea principala: regula de selectie a comenzilor era greșita, si ar fi creat duplicate

`_order_key` **s-a pastrat** la migrarea din iulie: **5.931 de comenzi se potrivesc exact** intre cele doua site-uri, iar site-ul vechi avea tot 5.931 pana la granita importului. **Reconcilierea e deja zero** — migrarea din iulie nu a pierdut nicio comanda, ceea ce e o veste bună in sine.

Granita reala nu e „10 iulie", ci comanda veche **#14515** (10 iulie, 10:56:03, £1.558,80), care la noi e **#7261**. Prin urmare regula pe care o aveam in plan, `post_date >= 2026-07-10`, dadea **85** de comenzi si **includea doua pe care le avem deja** (#14514 si #14515 = #7260 si #7261 la noi) — adica doua duplicate importate in magazin, cu doua numere noi, in dimineata lansarii.

Regula corecta: **comenzile a caror `_order_key` nu exista pe site-ul nou**. Ies **83 de comenzi, #14517–#14632, £205.708,74**, si niciuna nu e deja la noi. Si nu merge nici cu aritmetica: decalajul de ID-uri nu e constant (7253 la #14508, 7254 la #14510).

**Ce conțin cele 83:** 42 on-hold, £106.786,92 — transferuri bancare neincasate; 27 processing, £63.653,22 — plătite cu cardul, probabil nelivrate; 10 cancelled, £25.821,60; 4 pending, £9.447. Pe luni: 18 in iulie, 19 in august, 24 in septembrie, 22 in octombrie. **Pentru Mads:** cele 69 de comenzi on-hold plus processing trebuie parcurse dupa lansare, nu doar importate.

### A doua capcana: 18 din 19 „diferente de pret" sunt false

Comparatia pe campuri brute arata 19 produse cu preturi diferite. La 18 tiparul e identic: pretul normal de pe site-ul vechi este **pretul redus** de la noi, iar noi avem un pret normal mai mare. Exemplu: `patisserie-display-fridge-eco-p75-90cc` — vechi 1435; la noi 1690 cu reducere la 1435. **Pretul de vanzare e acelasi**, diferenta e prezentarea noastra, cu RRP si reducere. Deci comparatia trebuie facuta pe **pretul efectiv**, nu pe campuri brute; altfel cineva ar fi „reparat" 18 produse sambata dimineata si ar fi distrus afisarea reducerilor.

Singura diferenta reala de stoc: **`upright-cake-display-fridge`** — pe site-ul vechi `outofstock`, la noi `instock`, acelasi pret. Editat pe site-ul vechi **ieri, 2 noiembrie**.

### Si patru lucruri care nu mai trebuie facute deloc

- **Produse noi: zero.** Niciun produs publicat pe site-ul vechi nu lipseste la noi. Singurul produs publicat doar la noi e `live-payment-test-do-not-buy`, al nostru.
- **Categorii noi: zero.** Toate cele 27 de `product_cat` ale site-ului vechi exista la noi; noi avem 83. Tag-urile sunt deliberat diferite — 164 de tag-uri SEO cu sufix `-tag` acolo, 36 de tag-uri de tip la noi — si **nu se importa**, lucru pe care l-am scris explicit in checklist ca sa nu incerce nimeni.
- **Cupoane: zero.** Site-ul vechi nu are niciun cupon. Punctul F15-06 e gol.
- **Conturi de clienti si recenzii: zero.** Din 10 iulie incoace exista 135 de note de comanda si nicio recenzie.

### Ce rămâne, si e mic

- **25 de redirecturi** din site-ul vechi nu sunt in harta noastra: 10 Yoast 301, 4 Yoast 410, 11 Simple 301, intre care `/condition/refurbished/`, `/condition/second-hand/`, `/multidecks/shutter-display-fridge/`, `/ranges/patisserie-display-fridges/`. La regenerare: tintele din Simple 301 sunt URL-uri absolute (in copie apar pe domeniul clonei, deci se transforma in cai relative), iar unele sunt lanturi, deci se rezolva pana la pagina finala. Mai exista 5 redirecturi cu query string, pe care o harta de cai nu le poate exprima.
- **Meta SEO pe continutul nou:** 25 de titluri Yoast, 32 de meta-descrieri, 32 de focus keyword, plus `wpseo_taxonomy_meta` cu 58,9 KB de SEO pe termeni.
- **Doua fisiere de media** si **o schimbare de stoc**.
- **Trei pagini si un articol** exista doar pe site-ul vechi, toate resturi (`eco-fridge` cu titlul „Returns", `https-eco-fridge-co-uk`, `woo_lloydscardnetcw`, `add-media-test`); de verificat doar dacă apar in sitemapul live, caz in care au nevoie de redirect sau 410.

**Actualizat in acelasi tur:** randul de 05:40 din calendar si randurile F15-02, F15-04, F15-05, F15-06, F15-07, F15-08, F15-09 din checklist, toate cu data de azi si cu trimitere la raport. F15-06 e marcat „nimic de facut".

**Concluzia care conteaza:** repetitia a meritat de patru ori — fara ea, sambata am fi importat doua comenzi duplicate, am fi „reparat" 18 preturi corecte, am fi căutat produse si categorii care nu exista, si am fi ratat 25 de redirecturi.

## 86. Scriptul de import, scris si rulat pe o copie — plus ce se reverifica sambata (2026-11-03)

Doru: „da, scrie scriptul de import si ruleaza-l pe o copie, insa noteaza pentru sambata sa verificam daca au aparut produse noi, daca au fost sterse produse existente la noi pe staging, daca s-au modificat produsele existente, de verificat paginile si categoriile din nou daca s-au schimbat, sters, sau aparut altele noi."

### Uneltele, acum in repo si reutilizabile

| Fisier | Ce face |
|---|---|
| `migration/f15-run.sh` | rulează scripturile; `--old`/`--new` sunt `baza.prefix`, `--since` dă data de la care „modificat" conteaza, `--docker` rulează clientul in container, `--dry-run` se oprește după numărători |
| `migration/f15-compare.sql` | citește si compara; nu scrie nimic |
| `migration/f15-import-orders.sql` | importa comenzile selectate prin `_order_key`, cu verificări la final |

Cele doua baze pot sta in **acelasi server cu prefixe diferite**, si exact asa se rulează pe staging: se incarca tabelele clonei langa ale noastre, se importa, apoi se sterg.

### Repetitia, in ordinea exacta de sambata, pe baza locala (cu dump de siguranta pastrat)

1. **Stergerea cu WP-CLI curata si rapoartele.** `wp post delete 8007 8029 8030 8036 8037 8035 --force` a scazut comenzile din 5.936 in 5.931 **si a sters singura randurile din `wp_wc_order_stats`**. Deci nu e nevoie de curatenie manuala in tabelele de analiza, cum credeam.
2. **Importul a trecut toate verificările:** 83 de comenzi, 255 de linii, 4.232 de randuri de meta, 130 de note; zero coliziuni de ID; zero comenzi fara linii sau fara total; zero randuri de meta orfane dupa renumerotarea liniilor si a notelor (ID-urile de linie si de nota vin din secvente diferite pe fiecare site, deci se remapeaza).
3. **Reconcilierea e exacta, masurata:** dupa import, **6.014 chei de comanda se potrivesc pe ambele parti, din 6.014** comenzi pe site-ul vechi. F15-10 cere diferenta zero — e zero.
4. **WooCommerce citeste corect comanda importata.** `wc_get_order(14517)`: #14517, on-hold, GBP 1917,60, data corecta, „Direct bank transfer", adresa de facturare prezenta, linia „Multideck Fridge - Black Slimline (1000MM)" ×1, nota de comanda, si `stairs = 1`.
5. **Rapoartele se reconstruiesc dintr-o comanda:** sincronizarea prin punctele oficiale WooCommerce a produs 83 de randuri in `wp_wc_order_stats` si 89 in lookup-ul de produse, cu 14 comenzi anulate sau in asteptare excluse. Echivalentul din interfata e *Analytics → Settings → Import historical data*.
6. **Secventa continua corect:** `AUTO_INCREMENT` a ajuns la 14652.

### Trei lucruri aflate pe drum

**`stairs` e un camp real de livrare, nu un reziduu.** Raspunsurile clientilor sunt „No", „1", „none", „no steps", „2", „N/A", „1 step", iar 49 din cele 83 de comenzi il au: cate trepte sunt la adresa de livrare. Il pastrez la import, dar **site-ul nou nu mai intreaba nimic despre trepte** — de discutat cu Mads, separat de migrare, fiindca e o pierdere de informatie pentru echipa de livrare, nu o problema tehnica.

**Un bug in propria unealta, prins pentru ca am testat-o.** Prima versiune a runner-ului nu inlocuia `@SINCE@`, deci filtrul de data compara cu un text invalid si returna **toate** cele 132 de produse ca „modificate" in loc de 34. Reparat si reverificat. Exact de aia uneltele se rulează inainte, nu in dimineata lansarii.

**`post_modified` e un semnal slab pe site-ul vechi.** Pagina `support` are data 17 august si **conținut gol** — pe site-ul vechi textul stătea in campuri Toolset si in sabloane PHP, nu in `post_content`. Deci lista de „modificate" se foloseste ca lista de verificat cu ochii, nu ca adevar.

### Ce se reverifica sambata, asa cum a cerut Doru

Scris si in raport, si in checklist la F15-05, cu statusul „De facut sambata". Se rulează din nou `f15-compare.sql` pe clona proaspata si se citesc, pe rand: **produse noi** (azi zero), **produse sterse sau scoase din vanzare** acolo dar publicate la noi (azi doar produsul nostru de test), **produse modificate** (azi 34, cu avertismentul de mai sus), **categorii** noi sau dispărute pe slug (azi zero in ambele directii), **pagini** noi, dispărute sau modificate, plus pret si stoc pe pretul efectiv, media, cupoane si recenzii.

Despre categorii, o nota ca sa nu „repare" nimeni nimic: 25 de categorii au la noi nume curate la plural in locul celor vechi cu cuvinte-cheie — „Bar Fridge - Mini Fridge" a devenit „Bar Fridges", „Glass Door Fridge - Upright Display Fridge" a devenit „Glass Door Fridges". Slug-urile sunt identice, deci URL-urile nu se schimba; diferenta de nume e a noastra, deliberata.

**Starea bazei locale:** conține acum comenzile importate, ca sa pot verifica si in wp-admin. Dump-ul de dinainte e in `source-dump/db/local-before-f15-import-2026-11-03.sql.gz`, deci revenirea e un singur import.

## 87. Cele doua intrebari de livrare, preluate si implementate in checkout (2026-11-03)

Doru: „este necesar sa intrebam la checkout la adresa de livrare cate trepte sunt, echipa de livrare are nevoie sa stie si sa informeze clientul daca poate sa introduca produsul (frigider sau congelator sau ce este) in incinta sau il vor lasa la poarta. (…) Preia setarile din vechiul site pentru acest camp si verifici din vechiul site si alte campuri (…) Implementeaza in noul site la checkout si aceste campuri. Vreau sa le preluam, stiu despre ele de la Mads."

### Ce era pe site-ul vechi, exact

Definitiile stăteau in optiunea **`wc_fields_additional`**, adica in pluginul **WooCommerce Checkout Manager**. Am citit-o intreaga; nu existau alte campuri personalizate in afara acestora doua, iar la facturare singurele abateri erau `billing_address_3` si `billing_state`, **dezactivate** (`enabled = 0`).

| | `check_doors` | `stairs` |
|---|---|---|
| tip | select | text liber |
| eticheta | „We recomend you to check the height of your doors through the final fridge position. Have you checked the size of your doors? (T&C apply)" | „How many door steps? Curb side delivery will be at the drivers discretion (T&C apply)" |
| optiuni | YES / NO | — |
| obligatoriu | **da** | **da** |
| in comanda / in email | da / da | da / da |
| ordine | 10 | 20, apoi notele de comanda la 30 |
| cate comenzi il au | 4.436 | 3.877 |

**Alta regula nu exista:** campul `validate` era gol la amandoua, deci singura constrangere e ca nu poti plasa comanda fara raspuns. Restul e informativ, scris in eticheta: clientul e sfatuit sa masoare, iar livrarea la bordura rămâne la latitudinea soferului, cu T&C.

### Ce am implementat, si de ce asa

**In pluginul nostru, nu cu un plugin nou:** `ecofridge-blocks/includes/checkout-delivery-questions.php`.

**Detaliul decisiv:** coșul nostru e clasic, dar **checkout-ul e pe blocuri**, deci filtrul clasic `woocommerce_checkout_fields` ar fi fost ignorat — o capcana in care intrasem dacă nu verificam pagina. Campurile sunt inregistrate prin **Additional Checkout Fields API** (WooCommerce 11.2.1), in grupul comenzii, cu aceleasi etichete, acelasi tip, aceeasi ordine si acelasi caracter obligatoriu.

API-ul isi salveaza raspunsurile sub chei proprii (`_wc_other/ecofridge/doors-checked`), asa ca fiecare raspuns e **oglindit si in cheile vechi `check_doors` si `stairs`**. Motivul: cele 83 de comenzi importate poarta cheile vechi, iar asa ecranul de comanda, emailurile si orice export citesc la fel comenzile vechi si pe cele noi. Oglindirea nu ghiceste prefixul API-ului, ci caută cheia care se termina cu id-ul campului — deci supravieţuieste dacă WooCommerce isi schimba convenția.

**Unde se citesc raspunsurile:** pe ecranul comenzii, sub adrese, intr-un bloc „Delivery access"; in emailurile de comanda, inclusiv cel pe care il primește magazinul; si pe pagina de confirmare a clientului. Toate trei citesc din cheile vechi, deci **si comenzile importate se afiseaza**.

### Verificat local, nu presupus

- Ambele campuri sunt inregistrate: select YES/NO obligatoriu si text obligatoriu, in grupul comenzii.
- **Ajung in pagina de checkout**: datele blocului conțin id-urile si etichetele complete (codificate URL in sursa paginii).
- Drumul complet al unei comenzi: am creat o comanda de proba, am scris raspunsurile prin API, oglindirea a umplut `check_doors = YES` si `stairs = 3 steps and a kerb`, emailul le-a purtat pe amandoua, panoul din admin le-a afisat, iar comanda de proba a fost stearsa.
- **Comanda importata #14517**, care are doar cheile vechi, se afiseaza corect in acelasi loc: „Doorways measured: YES", „Steps at the delivery address: 1".
- Validare, masurata pe drumul real: gol → refuzat la amandoua; YES si NO → acceptate; **MAYBE → refuzat**.

**O gaura pe care am gasit-o si am inchis-o:** WooCommerce aplica „obligatoriu", dar **nu verifica dacă raspunsul unui select e dintre optiunile lui**, deci o cerere facuta pe langa pagina de checkout putea salva orice text. Am adaugat regula care accepta doar YES sau NO. Pe site-ul vechi aceeasi gaura exista si nu era acoperita.

**Si o corectura a unei afirmatii proprii:** primul meu test al validarii folosea `is_wp_error()`, care e adevarat si pentru un WP_Error gol, deci citise „refuzat" unde nu era. Rezultatele de mai sus sunt masurate cu `has_errors()`, pe metoda pe care checkout-ul o apelează chiar el.

### Pentru echipa ECO-Fridge, de trimis lui Mads

Ce inseamna cele doua raspunsuri, pe scurt: **„Doorways measured"** spune dacă clientul a confirmat ca a masurat ușile pe traseul pana la locul final al cabinetului — un NO e un semnal sa fie sunat inainte de livrare. **„Steps at the delivery address"** spune cate trepte sunt la intrare; de aici se decide dacă se duce in incinta sau se lasa la bordura, iar decizia rămâne a soferului, conform T&C. Se citesc in comanda din admin, sub adrese, si in emailul de comanda nouă.

**Doua lucruri de decis de client, nu de noi:**

1. **Eticheta conține o greseala de tipar din site-ul vechi:** „We **recomend** you" — un singur „m". Am pastrat textul exact asa cum il stie clientul; se repara intr-o linie, dacă vrea. Exista si filtrele `efb_checkout_doors_label` si `efb_checkout_steps_label`, deci textul se poate schimba fara cod.
2. **`stairs` e text liber, si asta se vede in date:** pe site-ul vechi au ieșit **396 de raspunsuri distincte** la aceeasi intrebare — „No", „1", „none", „no steps", „N/A", „one", „1 step". Echipa le poate citi, dar nu se pot filtra sau numara. Dacă Mads vrea date folosibile, un select (0 / 1 / 2 / 3+ / nu sunt sigur) ar rezolva; pentru moment am pastrat textul liber, fiindca asa functiona magazinul.

**De verificat la comanda reala de sambata, 08:30:** campurile apar la checkout, refuza trimiterea fara raspuns, iar raspunsurile ajung in emailul magazinului. A intrat in checklist ca F10-17.

## 88. Greseala de tipar reparata, iar intrebarea despre trepte devine o alegere (2026-11-03)

Doru: „repara greseala de tipar «recomend» si fa stairs select 0/1/2/3/4+".

**Amandoua facute**, in `ecofridge-blocks/includes/checkout-delivery-questions.php`.

1. **Eticheta** spune acum „We **recommend** you to check the height of your doors…". Verificat in pagina de checkout: forma veche apare de zero ori, cea corecta o data.
2. **Intrebarea despre trepte nu mai e text liber**, ci o alegere: **0, 1, 2, 3, 4+**. Validarea refuza orice altceva.

### Un bug prins la testare, si cum l-am rezolvat

Prima versiune a listei folosea chiar cifrele ca valori, si **„0" a fost refuzat la validare**: WooCommerce trateaza un „0" curat ca raspuns lipsa la un camp obligatoriu, deci clientul cu zero trepte — probabil cazul cel mai frecvent — nu ar fi putut plasa comanda. Nu am presupus asta, am masurat-o: „0" refuzat, „1" acceptat.

Soluția pastreaza exact etichetele cerute si rezolva si altceva pe drum: **clientul vede 0, 1, 2, 3, 4+**, dar ce se salveaza e „0 steps", „1 step", „2 steps", „3 steps", „4+ steps". Asa nimic nu mai e „gol" pentru WooCommerce, iar echipa de livrare citeste in email si in comanda „0 steps" in loc de o cifra singura. Cinci valori distincte, deci de acum raspunsurile se pot si numara — ceea ce nu se putea cu cele 396 de variante de pe site-ul vechi.

Lista se poate schimba fara cod, prin filtrul `efb_checkout_steps_options`, care primește o mapare valoare → eticheta.

### Reverificat, pe drumul real

- Campul e `select`, obligatoriu, cu alegerile 0 / 1 / 2 / 3 / 4+.
- Validare: „0 steps", „1 step", „4+ steps" acceptate; „0", „7", „none" si gol refuzate; la intrebarea despre uși, YES si NO acceptate, MAYBE si gol refuzate.
- Drumul complet al unei comenzi noi: raspunsurile NO si „0 steps" au ajuns in cheile vechi `check_doors` si `stairs`, emailul le-a purtat („Doorways measured: NO", „Steps at the delivery address: 0 steps"), iar pagina clientului le-a afisat. Comanda de proba a fost stearsa.
- **Comenzile importate nu sunt atinse:** raspunsurile lor de text liber rămân cum au fost si se afiseaza ca atare — #14517 arata in continuare „1". Nu convertim istoricul; intrebarea noua se aplica de la lansare incolo.
- In pagina de checkout: alegerea „4+ steps" e prezenta in datele blocului.

Checklistul, randul F10-17, e actualizat cu amandoua schimbarile si cu motivul pentru care valoarea salvata difera de eticheta.

## 89. Memoria proiectului intra in rutina, ca al patrulea loc (2026-11-03)

Doru: „salveaza tot si in memoria proiectului acestui folder si retine sa o fac mereu pe viitor."

**De acum rutina are patru locuri**, toate fara sa mai cer: jurnalul `claude-chat.md`, git-ul pana la merge, ambele foi din Sheet, si **memoria proiectului din folderul acesta**.

**Ce am scris acum in memorie:**

- **`ecofridge-f15-delta-rules`**, nou — cele patru reguli care au costat o dimineata: comenzile se selecteaza dupa `_order_key` (83, nu 85, fiindca granita e comanda veche #14515 = #7261 la noi), preturile se compara pe **pretul efectiv** (18 din 19 diferente sunt prezentarea noastra cu RRP), `post_modified` e semnal slab pe site-ul vechi, iar delta de media se afla din baza de date. Plus ce a dovedit repetitia si ce **nu** mai e de migrat deloc.
- **`ecofridge-checkout-delivery-questions`**, nou — de ce exista cele doua intrebari, ca sunt o cerinta a lui Mads si nu un reziduu, cele doua decizii de azi, si capcana cu „0": WooCommerce refuza un „0" curat la un camp obligatoriu, deci valoarea salvata e „0 steps". Dacă cineva „ordoneaza" valorile inapoi la cifre, comenzile cu zero trepte nu mai trec.
- **`ecofridge-launch-checklist`**, completat — faptele masurate azi pentru ziua lansarii: o singura inregistrare DNS de schimbat (apex A → 148.230.121.74), www e CNAME si urmeaza singur, nu exista AAAA si nici CAA, MX si TXT neatinse, TTL coborat la 300; unealta de clonare Hostinger merge si inlocuieste singura URL-urile, dar parola nu vine cu copia, deci site-ul nou se inchide **inainte** de a fi umplut, iar CDN-ul se opreste pana dupa verificari; varianta `www` nu se poate adauga ca domeniu parcat; serverul e nodul britanic, acelasi cu staging-ul.
- **`ecofridge-backup-site-plan`**, corectat — trimite acum la regulile noi, ca versiunea veche a regulii de selectie sa nu mai poata fi citita ca actuala.
- **`always-update-claude-chat`**, extins cu al patrulea loc si cu ce aparține memoriei: regulile si deciziile pe care o sesiune viitoare nu trebuie sa le redescopere sau sa le strice, motivele din spate, si trimiteri la locul unde sta detaliul — nu o a doua copie a jurnalului si nu ce spune deja codul.

**O regula de igiena pe care mi-am scris-o explicit:** cand o sesiune corecteaza o memorie mai veche — exact ce s-a intamplat azi cu regula de selectie a comenzilor — corectura se scrie **in aceeasi nota**, ca varianta greșita sa nu poata fi citita ca fiind curenta.

Indexul `MEMORY.md` are cele doua intrari noi.

## 90. TTL: doar pe A de la apex, si de ce 5 minute, nu 1 (2026-11-04)

Doru: „Scad TTL pe IONOS doar la A @ sau si la A ftp? Minim ca timp am chiar si 1 minut, setez 5 minute, cum ai recomandat?"

**Verificat direct la nameserverele IONOS, nu din cache:** `eco-fridge.co.uk A` are deja **TTL 300**, cu valoarea neschimbata (185.230.220.98). `www` e CNAME cu TTL 3600, `ftp` e A cu TTL 3600, amandoua neatinse.

**Raspuns: doar `A @`.**

- **`A ftp` nu se atinge** nici acum, nici sambata. Rămâne spre serverul vechi cat timp gazduirea aceea e tinuta in viata, si se actualizeaza sau se sterge dupa cele 30 de zile. Un TTL mic pe o inregistrare pe care nu o schimbi nu aduce nimic.
- **`CNAME www` nu are nevoie, si aici imi corectez propria nota** din calendar, scrisa pe 30 octombrie: „si, dacă panoul permite, pe CNAME www". Motivul tehnic: CNAME-ul nu se schimba, tinta lui rămâne `eco-fridge.co.uk`, iar cat de repede ajunge lumea pe IP-ul nou e decis de **TTL-ul inregistrarii A de la apex**. Un resolver care tine CNAME-ul in cache o ora tot rezolva apexul respectand TTL-ul de 300 de secunde. Inofensiv dacă il cobori, dar fara castig.

**Si da, 5 minute, nu 1.** 300 e pragul pe care il respecta toate resolverele; unele ISP-uri impun oricum un minim propriu si pot **ridica** valorile foarte mici, deci 60 de secunde nu garanteaza 60 de secunde. Pentru o mutare unica, castigul practic sub cinci minute e zero, iar costul e mai multe interogari zile la rand. Castigul care conteaza nu e viteza cu care apare site-ul nou, ci **viteza de revenire**: cu 300 ne intoarcem pe serverul vechi in 5 minute, nu intr-o ora.

**Un detaliu care l-a ajutat fara sa stie:** coborarea TTL-ului e ea insasi supusa TTL-ului vechi de o ora, deci resolverele care aveau raspunsul in cache inainte de modificare pot servi inca pana la o ora valoarea veche. Facuta cu trei zile inainte, se absoarbe complet.

Dupa o saptamana linistita de la lansare, TTL-ul se pune inapoi la 3600. Randul de TTL din calendar e acum bifat si rescris cu aceasta corectura, iar memoria de lansare la fel.

## 91. Cererea catre Ambrit despre DNS: inca valabila, dar din alt motiv decat pare (2026-11-04)

Doru: „mai este valabila cerinta catre Ambrit de «confirmation that they will not change any web address (DNS) settings themselves. We make that change in your IONOS account, and only for the website»?"

**Da, rămâne — dar motivul e mai ingust decat suna, si merita spus precis.**

Verificat pe 3 noiembrie la nameserverele autoritative: zona e servita de `ns1030.ui-dns.*`, adica **IONOS, contul clientului**. Ambrit tine **gazduirea** de la 185.230.220.98, nu inregistrarile. **Din panoul lor nu pot schimba DNS-ul.** Riscul rămas e unul singur: dacă mai detin credentiale de login in contul IONOS al clientului — plauzibil, ei au construit site-ul, iar `ftp.eco-fridge.co.uk` plus SPF-ul cu `+a` si `ip4:83.223.113.98` arata o istorie lunga. **Deci confirmarea e despre oameni cu parole vechi, nu despre panoul lor** — si exact asa merita formulata.

**A doua jumatate a frazei e acum demonstrabila**, ceea ce o face mai buna decat inainte. In loc de o promisiune vaga, se poate scrie exact ce se schimba: o singura inregistrare, `A` pentru eco-fridge.co.uk; `www` o urmeaza singura, fiind CNAME; iar nimic legat de email nu e atins — MX (mx00/mx01.ionos.co.uk), DKIM (s1/s2/s42582890._domainkey), `_dmarc`, SPF si TXT-ul de verificare Google rămân neschimbate.

**Doua cereri din emailul vechi pot fi scoase:**

- **acces FTP/SFTP plus dump de baza de date** — era varianta de rezerva dacă Migrate Guru nu merge; merge, probat ieri. Rămâne cel mult „nice to have";
- **reinnoirea `ca-certificates` pe serverul vechi** — nu mai e nevoie, pluginul nostru de TLS a rezolvat-o, si se sterge dupa migrare.

**Doua devin mai importante decat cea de DNS:**

1. **Sa nu opreasca si sa nu modifice gazduirea veche inainte de cele 30 de zile.** Ne trebuie pentru revenire, iar `ftp.eco-fridge.co.uk` inca arata acolo. Asta e acum riscul mare, nu DNS-ul.
2. **Ingheţ pe site-ul vechi de vineri seara.** Delta noastra se calculeaza fata de el: comenzile noi nu sunt o problema, le prinde clona de sambata la 05:10, dar un produs, o pagina, o categorie nouă sau un update de plugin intre clona si go-live s-ar pierde in liniste.

**Si un pas ieftin de siguranta, adaugat in calendar la 07:30:** verific zona din nou sambata dimineata, chiar inainte de schimbare. Dacă cineva a umblat la ea, aflam atunci, nu dupa.

**Doua corecturi in propriile mele note:** scrisesem de doua ori, pe 3 noiembrie, „DNS-ul e la Ambrit". E greșit si tocmai aici conteaza: Ambrit tine gazduirea, zona e la IONOS, in contul clientului. Ambele randuri sunt corectate in jurnal.

## 92. Textul final al celor trei cereri catre Ambrit (2026-11-04)

Doru: „nu lucreaza nimeni dupa vineri seara, iar noi oricum luam o copie completa a site-ului sambata dimineata. Deci [cele trei cereri] devine cum? Scrie in engleza clar si concis, ca sa-i trimit lui Mads sa-i ceara lui Ambrit."

Deci ingheţul nu mai e o cerere separata, ci un fapt: pe site-ul vechi nu lucreaza nimeni dupa vineri seara, iar copia completa de sambata dimineata acopera restul. Rămân trei cereri, nu patru.

**Textul trimis, in engleza:**

> **Ask Ambrit, your current hosting company, for three things:**
>
> **1. One complete backup of the website — files and database — taken on Monday 9 November,** after the switch, and sent to us. We check it order by order against the new site, so nothing can have been missed, and it stays as the archive of the old shop. We also take our own full copy on the Saturday morning, before the switch, so this is a cross-check rather than the only copy.
>
> **2. The hosting account left running, exactly as it is, for at least 30 days after the switch.** Not only active — unchanged: no plugin or PHP updates, no clean-up, nothing removed. This is our safety net: if anything serious went wrong, we could point the web address back to it within five minutes.
>
> **3. Confirmation that they will not change any web address (DNS) settings themselves.** We make that change in your IONOS account, and only for the website: a single record, the `A` record for eco-fridge.co.uk. The `www` address follows it automatically. Nothing to do with email is touched — the MX, DKIM, DMARC and SPF records all stay exactly as they are, so email keeps working throughout.
>
> Nothing else is needed from them. Work on the current website stops on Friday evening and nobody touches it after that, so the copy we take on Saturday morning is complete.

**Trei diferente fata de varianta de dinainte, si de ce:**

1. La punctul 2, **„exactly as it is"** cu exemple. O gazduire „activa" dar actualizata intre timp nu mai e plasa de siguranta: un update de plugin sau o schimbare de PHP pe serverul vechi ne-ar putea lasa cu o revenire care nu mai functioneaza. „Activ" nu acoperă asta, „neschimbat" da.
2. La punctul 3, **exact ce se schimba si ce nu**: o singura inregistrare, A pentru eco-fridge.co.uk, `www` urmeaza singura, iar MX, DKIM, DMARC si SPF rămân neatinse. E verificabil, si taie din start temerea ca se mișca emailul — care e, de obicei, singurul lucru care sperie o gazduire veche.
3. **„within five minutes"** in loc de „within minutes" — acum e o cifra adevarata, fiindca TTL-ul de pe A e 300 de secunde de pe 3 noiembrie.

Cererile de acces FTP/SFTP, de dump de baza de date si de reinnoire a `ca-certificates` au ieșit definitiv: prima si a doua erau rezerve pentru Migrate Guru, care a functionat, iar a treia a fost rezolvata de pluginul nostru de TLS.

## 93. Fraza prin care Mads afla ca e liber sa plece de la Ambrit (2026-11-04)

Doru: „cum sa formulez fraza in care ii spun ca este liber sa rezilieze contractul cu Ambrit? Scrie la cerinte ca ii cerem o fereastra (preaviz) de 30 de zile…deci nu mai este nevoie sa mentionam si in aceasta fraza."

**Varianta recomandata**, de pus imediat dupa cele trei cereri:

> **Once those three things are in hand, nothing further is needed from Ambrit.** The website will be running in your own hosting account and the domain is already in your own IONOS account, so whether you continue with them afterwards is entirely your decision — ending the arrangement will not affect the new website in any way.

**Varianta scurta**, dacă vrea un ton mai sec:

> **After that, nothing we do depends on Ambrit.** The website and the domain are both in your own accounts, so continuing with them, or not, is entirely your call.

**De ce asa:**

- **„Once those three things are in hand"** face treaba preavizului fara sa-l repete: spune implicit ordinea corecta — backupul de luni primit, perioada de siguranta servita — si abia apoi e liber. Exact ce a cerut Doru, fara a doua mentiune a celor 30 de zile.
- **„in your own hosting account… your own IONOS account"** e motivul real pentru care e liber, si e verificabil: site-ul nou sta in contul lui de Hostinger, domeniul in contul lui de IONOS. Dupa mutare, nimic nu mai rămâne la Ambrit.
- **„entirely your decision"** lasa decizia la el, fara sa impinga intr-o direcție; dacă rămâne cu ei pentru altceva, nu e treaba noastra.

**Ce nu scriem:** nicaieri „you can cancel the hosting now". Rezilierea inainte ca backupul de luni sa fie efectiv primit ne-ar lasa fara plasa de siguranta exact in saptamana in care conteaza. Formularea de sus inchide asta din construcție, fara sa facă din ea un avertisment.

## 94. Permisiunea de reziliere trece la inceput, si cuvantul care tine ordinea (2026-11-04)

Doru: „vreau la inceput sa scriu: Feel free to cancel (sau cum se zice la a rezilia) the contract with Ambrit."

**Despre termen:** „cancel the contract" e perfect idiomatic pentru o gazduire; „end your contract with" e varianta mai calda, „terminate" suna juridic. Oricare merge, nu e nevoie de altceva.

**Textul final, cu fraza lui pe primul loc:**

> **Feel free to cancel the contract with Ambrit.** The website will be running in your own hosting account and the domain is already in your own IONOS account, so nothing stays with them after the move. There are just **three things to get from them first:**
>
> **1.** One complete backup of the website — files and database — taken on **Monday 9 November**, after the switch, and sent to us. We check it order by order against the new site, so nothing can have been missed, and it stays as the archive of the old shop. We also take our own full copy on the Saturday morning, before the switch, so this is a cross-check rather than the only copy.
>
> **2.** The hosting account left running, **exactly as it is**, for at least 30 days after the switch. Not only active — unchanged: no plugin or PHP updates, no clean-up, nothing removed. This is our safety net: if anything serious went wrong, we could point the web address back to it within five minutes.
>
> **3.** Confirmation that they will not change any web address (DNS) settings themselves. We make that change in your IONOS account, and only for the website: a single record, the `A` record for eco-fridge.co.uk. The `www` address follows it automatically. Nothing to do with email is touched — the MX, DKIM, DMARC and SPF records all stay exactly as they are, so email keeps working throughout.
>
> Nothing else is needed from them. Work on the current website stops on Friday evening and nobody touches it after that, so the copy we take on Saturday morning is complete.

**Cuvantul care face toata treaba e „first".** Fraza lui rămâne intacta si pe primul loc, dar „three things to get from them **first**" asaza ordinea corecta fara sa para o conditie pusa clientului: ia backupul de luni si fereastra de siguranta, abia apoi reziliaza. Fara el, cineva ar putea rezilia marti si am pierde exact plasa de siguranta din saptamana in care conteaza — iar punctul 2, cu cele 30 de zile, ar deveni imposibil de onorat.

**Al doilea motiv pentru care merge bine la inceput:** propozitia a doua, „in your own accounts", explica imediat **de ce** e liber, deci permisiunea nu atarna in aer, iar cele trei cereri devin ultimele lucruri de luat de la un furnizor pe care il paraseste, nu o lista de favoruri.

Varianta anterioara, cu permisiunea dupa cele trei cereri, e in intrarea 93; a rămas acolo pentru comparatie, dar cea trimisa e aceasta.

## 95. Cand activam HPOS: nu inainte de lansare, si nu e grabă (2026-11-04)

Doru: „cand fac asta: Comenzile stau inca in posts; HPOS se activeaza din WooCommerce → Advanced → Features?"

**Nu inainte de lansare, si pentru magazinul asta HPOS e un „bine de avut", nu o nevoie.**

**De ce nu joi sau vineri:** scriptul de import, scris si dovedit ieri, scrie in `wp_posts` si `wp_postmeta`, iar continuarea numerotarii trece prin `AUTO_INCREMENT` pe `wp_posts`. Dacă HPOS devine depozitul principal inainte de import, ID-urile de comanda vin din `wc_orders` si ambele piese ar trebui rescrise si re-dovedite in doua zile, fara niciun castig pentru client.

**De ce nu sambata:** ar fi o migrare de schema a 6.014 comenzi exact in fereastra in care singurul lucru care conteaza e sa putem reveni in cinci minute. Risc gratuit langa DNS, SSL, licente si import.

**De ce nu e grabă:** HPOS se plateste la zeci de mii de comenzi si la interogari grele in admin. Aici sunt 6.014 comenzi si ~20 pe luna; nimic nu e lent din cauza stocarii in posts.

**Ce am verificat azi, ca sa stim ca e posibil cand vrem:**

- Starea: HPOS `no`, sincronizare `no`, tabela `wc_orders` nici nu exista.
- **Niciun plugin instalat nu declara incompatibilitate.** Declara compatibilitate: AG Lloyds Cardnet, GTM4WP, SMS Alert, Astra Addon, Woo Analytics. WebToffee PDF Invoices nu apare fiindca e inactiv; dacă e activat vreodata, se reverifica atunci.
- **Pluginul nostru e curat:** nu citeste nicaieri comenzi din `wp_posts`. Singura referinta la `shop_order` e o verificare de capabilitate, iar accesul la comenzi trece prin `wc_get_order()` si `wc_get_orders()`; SQL-ul din coșurile abandonate e pe propria tabela. Deci HPOS rămâne transparent pentru noi. Nu declara nimic despre compatibilitate, ceea ce se adauga in trei linii cand facem trecerea.

**Momentul potrivit: 2–3 saptamani dupa lansare**, cu pasii:

1. backup complet (UpdraftPlus), ca la orice migrare de schema;
2. WooCommerce → Settings → Advanced → Features → **High-performance order storage**, cu **modul de compatibilitate (sincronizare) pornit**;
3. Action Scheduler migreaza cele ~6.100 de comenzi in fundal; se verifica numarul de comenzi si se deschid cateva, inclusiv una din cele 83 importate;
4. o saptamana cu sincronizarea pornita — ea e plasa de siguranta, fiindca `wp_posts` rămâne scris in paralel;
5. abia apoi se opreste sincronizarea si se adauga declaratia de compatibilitate in pluginul nostru.

**Un lucru care sperie de obicei si nu e cazul:** numerele de comanda **nu se reseteaza**. Comenzile existente isi pastreaza ID-urile, iar noile ID-uri vin din `wc_orders` cu secventa pornita peste maximul existent, deci continuitatea construita sambata rămâne.

## 96. Fara conturi de client, ca cerinta a clientului (2026-11-04)

Doru: „retine ca Mads si Kass doresc ca toti clientii vechi si viitori sa fie guests, fara cont."

**Notat ca cerinta a clientului, nu ca preferinta tehnica**, deci nu se redeschide. Salvat in memoria proiectului, in nota `ecofridge-guest-only`, si in checklist la randul nou **F10-18**.

**E deja fortat in cod**, in `ecofridge-blocks/includes/no-accounts.php`, si asta conteaza: nu depinde de o setare pe care un plugin sau un clic o poate reporni. Creare de cont pe My Account, la checkout si dupa checkout — oprite; reminderul de login la checkout — oprit; `users_can_register` — oprit; inregistrarea prin wp-login.php si prin API-urile WooCommerce, REST si Store — refuzata pe server; guest checkout mereu pornit; My Account trimite vizitatorul pe prima pagina; conturile de personal se fac doar din Users → Add New.

**Si se potriveste cu ce am masurat ieri pe site-ul vechi**, ceea ce explica de ce importul a fost simplu: din 6.014 comenzi, **6.008 au fost plasate ca oaspete** si doar 6 aveau cont atasat, iar toata tabela de utilizatori avea 5 conturi, toate de personal. Niciuna din cele 83 de comenzi care se importa sambata nu e legata de un utilizator. Deci cerinta lui Mads si a lui Kass nu schimba nimic pentru datele vechi — le confirma.

**Cum se aplica de acum, ca sa fie clar si pentru o sesiune viitoare:** orice plugin sau functionalitate care cere conturi — urmarirea comenzii „pentru clienti logati", un plugin de recenzii care cere login, abonamente, loialitate, promptile proprii ale WooCommerce — primeste nu, iar alternativa e ruta de oaspete: detaliile comenzii prin linkul de order-received, si emailul clientului ca fir de discuție. Datele clientului trăiesc in meta comenzii, nu intr-un profil de utilizator, deci exporturile si echipa de livrare citesc comenzi, niciodata conturi.

**O precizare despre nume, ca sa nu se piarda:** Kass e partea clientului alaturi de Mads; directorul inregistrat la Companies House e Kaseem Traboulsi.

## 97. Vineri seara: curatenia, verificarile, si un lucru care ar fi stricat lansarea (2026-11-06)

Doru: „este vineri. Te rog sa faci: Curățenie pe staging (…) Verificări finale (…) Plus ce crezi ca mai este de facut inainte de migrarea de sambata."

### Lucrul care ar fi stricat lansarea

**Staging nu avea codul de miercuri.** `checkout-delivery-questions.php` lipsea, deci cele doua intrebari de livrare **nu ar fi existat pe site-ul lansat** — iar staging e exact ce devine site-ul live mâine. Nu exista git pe server, deploy-ul e manual, si fisierul nu fusese urcat.

Ca sa nu rămână alte surprize de acelasi fel, am comparat **tot codul nostru** — `ecofridge-blocks`, `ef-cookie-consent`, `o2-image-optimizer` si tema copil `astra-child` — fisier cu fisier, cu md5 pe conținut normalizat (local are CRLF, serverul LF, deci o comparatie naiva ar fi arătat 133 de diferente false). Rezultat: **133 de fisiere locale, 132 pe staging, exact doua nepotriviri** — modulul nou si fisierul principal al pluginului, care il incarca. Restul identic. Le-am urcat cu terminatii LF, cu o copie de siguranta a fisierului inlocuit, si am verificat: md5 identice, `php -l` curat, iar cele doua intrebari **se inregistreaza pe staging**, cu etichetele corecte, alegerile 0/1/2/3/4+ si YES/NO, amandoua obligatorii.

### Curatenia

| Ce | Inainte | Dupa |
|---|---|---|
| Comenzi de test (#8007, #8029, #8030, #8036, #8037, #8038, #8039) | 7 | **0 comenzi peste #7261** |
| Produsul ascuns de test #8035 | exista | sters |
| Randurile lor din `wp_wc_order_stats` | — | **0**, sterse singure de WP-CLI |
| Coșuri abandonate de test | 8 | 0 |
| Jurnalul de consimtamant (test) | 12 | 0 |
| Loguri de email si de formulare | 32 | 0 |
| Action Scheduler: actiuni terminate | 4.746 | 0 (cele **16 in asteptare** pastrate) |
| Action Scheduler: randuri de log | 14.254 | 16 |
| Fisiere de log WooCommerce | 38, 396 KB | 1, 4 KB |
| Tabelele vechi `43lz2ypq8_` | **263 de tabele, 723 MB** | **0** |
| Folderul `.ef-backup-2026-10-14` | **2,4 GB** | sters |
| Postmeta orfan | 2 | 0 |
| **Baza de date** | ~840 MB | **114 MB in 73 de tabele** |
| **Docroot** | ~3,6 GB | **1,2 GB** |

Asta nu e doar igiena: copierea de mâine la 06:45 muta o baza de 114 MB in loc de 840 si un docroot de 1,2 GB in loc de 3,6 — adica mult mai putin timp si mult mai putine motive sa cada la mijloc.

### Verificarile

- **SMTP si emailuri:** mailer `smtp` prin `smtp.ionos.co.uk:587`, TLS, autentificat ca `noreply@eco-fridge.co.uk`, parola setata, „from" fortat. `wp_mail()` a returnat **true** si am trimis un email real de test — **Doru confirma ca a ajuns**. Emailul „New order" merge la `mads@` plus `orders@`; emailurile de Processing, Completed si On hold sunt active.
- **Feed Merchant**, generat intern, fara HTTP: **129 de produse**, 184 KB, cu `g:id`, `g:price`, `g:availability` si `g:brand`. Cele doua care lipsesc din 131 sunt **excluderi corecte**: serviciul `removal-disposal`, ascuns din catalog, si singurul produs fara preț (£POA) — Google cere preț.
- **Sitemap:** indexul plus **11 sitemapuri copil**; cel de produse are **131 de URL-uri**. `robots.txt` e fisier real, scris deja pentru domeniul live, cu `Sitemap: https://www.eco-fridge.co.uk/sitemap_index.xml` si **fara Disallow general** — deci pasul de mâine „robots.txt fizic a ajuns in radacina" e deja acoperit.
- **Zero erori:** `debug.log` nu exista, `WP_DEBUG` oprit. Am parcurs nouă adrese pe oglinda locala (prima pagina, categorie, produs, coș, checkout, sitemap, feed, robots, un 404) — **zero apariții de „Fatal error", „Warning", „Notice" sau „Deprecated" in HTML**. Redirecturile verificate ajung unde trebuie: `/commercial-fridge/` → `/commercial-fridge-buying-guide/`, iar `/checkout/` cu coșul gol → `/cart/`.
- **Nimic orfan** dupa stergeri: zero linii de comanda fara comanda, zero meta fara linie, zero randuri de statistici sau de lookup fara comanda.
- **Lacatul de staging** e autentificare Basic facuta in PHP, din `.h5g/ef-staging-lock.php`, in afara docrootului, si **parola e doar hash bcrypt** — deci nu pot cere paginile prin HTTP de pe staging; de aia randarea am verificat-o pe oglinda locala, exact ca la auditul din octombrie.

### Ce am mai facut, din proprie initiativa

- **Punct de revenire pentru mâine:** dump al bazei de staging **de dupa curatenie**, adus local, verificat: `source-dump/db/staging-before-launch-2026-11-06.sql.gz`, 7,0 MB, 73 de tabele. Importul de mâine atinge doar baza, deci asta e exact ce trebuie ca sa ne intoarcem. UpdraftPlus nu are backup in istoric si nu are comanda CLI; dump-ul acoperă nevoia.
- **Uneltele de mâine sunt deja pe server**, in `~/f15`, si **testate pe MariaDB 11.8.9 al staging-ului**: comparand staging cu el insusi, scriptul read-only ruleaza fara nicio eroare SQL si da exact zerourile asteptate (0 comenzi de importat, 5.931 chei potrivite din 5.931). Deci mâine nu aflu pe viu dacă SQL-ul merge pe versiunea serverului.
- Am verificat ca spatiul pe disc si clientul `mariadb` sunt pregatite pentru incarcarea tabelelor clonei, iar prefixul `43lz2ypq8_` e acum liber — tocmai l-am eliberat.

### Ce am semnalat si NU am schimbat, deliberat

- **Politica de cookies spune mai mult decat trebuie.** Scanerul a reținut 32 de cookie-uri, dintre care unele sunt **doar de administrator**: `wp-settings-4`, `wp-settings-time-1/4`, `WP_DATA_USER_4`, `WP_PREFERENCES_USER_4`, `customizerVisitCount`, `spectra_onboarding_state`, plus cookie-ul de „notificare respinsa" din wc-blocks. Un client nu primeste niciunul. E **supra-declarare, nu o lipsa**, deci nu e o problema de conformitate si nu umblu la date stocate in seara de dinaintea lansarii; intra pe lista de dupa lansare.
- **Cele 255 de revizii rămân.** Sunt singurul istoric al muncii noastre de conținut, ocupa foarte putin dupa ce baza a scazut la 114 MB, iar stergerea lor nu cumpara nimic acum.

### Ce rămâne de la Doru

1. **Confirma ca emailul de test a ajuns** (trimis la `doru.hanganu@gmail.com`).
2. **Verificarea in browser a checkout-ului** — singurul lucru pe care nu l-am putut proba: extensia de Chrome nu are permisiune pe `localhost`, deci navigarea a fost blocata. Ce am probat pe staging, fara browser: blocul primeste exact cele doua campuri pentru secțiunea de comanda, ca select-uri obligatorii, cu alegerile corecte, iar validarea refuza gol, „MAYBE", „0" si „none" si accepta „YES", „NO", „0 steps", „4+ steps". Ce rămâne de vazut cu ochii: **se randeaza amandoua la checkout si trimiterea fara raspuns e refuzata** — adauga un produs in coș si deschide checkout-ul.
3. Pasii de mâine din hPanel si singura inregistrare DNS.
4. Containerul GTM de la Mads.

### De ce nu a mers browserul

Doru, intre timp: „de ce nu poti accesa browser-ul prin extensia Claude, ca o am instalata."

Extensia **e instalata si a raspuns** — a creat grupul de taburi, a returnat ID-ul, a inchis tabul la final. Ce a lipsit e **permisiunea pe site**: extensia cere aprobare per-site inainte sa acționeze intr-o pagina, iar `localhost` nu e in lista permisa. Simptomul se potriveste: `navigate` a raportat succes, dar tabul a rămas pe `chrome://newtab/`, iar screenshot-ul si căutarea au refuzat cu „Can't interact with browser-internal URLs", adica tabul nu plecase de pe pagina interna. Se rezolva o singura data, din setarile de permisiuni ale extensiei, adaugand `localhost`.

## 98. Intrebarile de livrare, facute citibile — si confirmarea ca sunt obligatorii (2026-11-06)

Dupa ce Doru le-a vazut pe staging: „apar cele 2 intrebari, insa nu se vede clar. Vreau ca fiecare intrebare sa fie toata vizibila iar dedesubt sa fie raspunsul cu drop-down". Apoi, dupa reparatii: „perfect", si intrebarea „sunt aceste intrebari obligatorii, pentru ca vreau ca clientii sa nu le ignore ci sa fie obligati sa le citeasca si sa raspunda la ele".

**Trei probleme, toate vazute in capturile lui, nu ghicite.** Capturile erau in folderul proiectului, nu atasate in conversație, asa ca le-am citit de pe disc.

1. **Eticheta era taiata.** Blocul de checkout deseneaza select-ul cu eticheta plutitoare: un singur rand, peste control, taiata unde se termina locul. Pentru „Country" e in regula, pentru o intrebare de 137 de caractere e inutila. Acum, doar in secțiunea asta, eticheta e un bloc obisnuit deasupra controlului, libera sa curga pe cate randuri are nevoie, ingroșata si in culoarea normala a textului. `!important` e deliberat si tinut in acel container: regulile peste care scriem vin din foaia WooCommerce, care se incarca dupa a noastra si se regenereaza la fiecare actualizare, deci specificitatea singura nu ar rezista.
2. **Textul din dropdown era absurd.** WooCommerce construieste optiunea goala din eticheta, deci scria „Select a we recommend you to check the height of your doors through the final fridge position. have yo…" — taiat in campul inchis si ocupand tot randul in lista deschisa. Doru a propus sa scurtam intrebarea si sa mutam restul intr-o descriere, dacă nu se poate altfel. **Se poate altfel:** `SelectFieldType` accepta un `placeholder` propriu, pe care il sanitizeaza si il foloseste ca prima optiune. Deci intrebarea rămâne exact cum o stie Mads, iar dropdown-urile spun acum **„Choose YES or NO"** si **„Choose the number of steps"**. (Camp de descriere nu exista in API-ul acela — verificat — deci varianta cu descrierea ar fi insemnat sa randez eu textul separat; nu a mai fost nevoie.)
3. **Sageata statea pe chenar.** Blocul isi pozitioneaza sageata fata de tot campul, eticheta inclusa; dupa ce intrebarea a inceput sa se aseze deasupra, sageata a urcat cu inaltimea etichetei. In loc sa alerg dupa acel decalaj, care s-ar strica la orice schimbare de text, am ascuns sageata lor si am desenat-o pe select ca imagine de fundal, unde rămâne centrata oricate randuri ar avea intrebarea.

**Sunt obligatorii, verificat pe trei niveluri:** inregistrate cu `required = true`, deci blocul nu lasa comanda sa plece; Store API verifica singur campurile obligatorii pe server, deci nici o cerere trimisa pe langa pagina nu trece; iar masurat, gol → refuzat la amandoua, „MAYBE" la prima si „0" sau „none" la a doua → refuzate, acceptate doar YES/NO si 0/1/2/3/4+. Niciunul nu are raspuns preselectat: clientul trebuie sa aleaga activ.

**Un loc unde nu se aplica, spus ca sa nu surprinda:** comenzile care nu trec prin checkout — create manual in wp-admin sau prin API — rămân cu campurile goale, fiindca nimeni nu e intrebat. Pe ecranul comenzii cele doua raspunsuri se **afiseaza**, dar nu se pot completa. Dacă echipa chiar introduce comenzi la telefon, se face si asta; am intrebat inainte sa construiesc ceva ce poate nu e folosit.

**Si o lectie pentru deploy**, legata de intrarea 97: toate cele trei retusari au fost urcate pe staging imediat si verificate cu md5 fata de repo. Staging e ce devine live mâine, deci „e in git" nu inseamna nimic pana nu e si acolo.

## 99. PLANUL DE SAMBATA, pas cu pas — varianta finala (2026-11-06, seara)

Doru: „primul lucru este sa instalez ef-close-shop.zip in vechiul site, corect? (…) Scrie planul aici pas cu pas, pentru ca in calendar nu este clar si bine facut pas cu pas." Apoi, o corectura importanta: „eu nu pot sa creez site gol, eu dau comanda de clone/copy site iar Hostinger cloneaza site-ul de staging pe un nou site temporar al Hostinger pe care ulterior il conectez cu domeniul eco-fridge.co.uk."

**Ordinea lui era corecta.** Prima mea varianta incepea cu „creeaza un site gol", ceea ce nu se poate in contul lui: **clonarea ESTE actul de creare**, iar site-ul nou se naste pe un domeniu temporar `*.hostingersite.com`. Asta schimba doua lucruri reale, nu doar numerotarea: URL-urile vor fi rescrise de unealta catre domeniul **temporar**, deci search-replace-ul meu e din el in `www.eco-fridge.co.uk`; si copia vine **fara parola**, dovedit marti, deci exista o fereastra in care un magazin cu date reale de clienti sta deschis pe o adresa publica. Prima actiune dupa clonare e inchiderea lui.

**Trei lucruri din calendar nu mai sunt necesare:** TTL-ul e coborat (marti), comenzile de test sunt deja sterse (aseara), iar dansul „creez → testez copierea → sterg → recreez" dispare, fiindca unealta de clonare e dovedita si rescrie singura URL-urile.

### Faza 1 — inchiderea magazinului vechi (05:00, DORU)
1.1 Site-ul vechi → Plugins → Add New → Upload Plugin → `ef-close-shop.zip` → Install → Activate.
1.2 WooCommerce → Settings → Payments → toate metodele oprite; cuponul de 100% dezactivat.
Verific: nu se mai adauga in coș, checkout-ul refuza pe server, site-ul rămâne vizibil si indexabil — catalog, nu mentenanta, fiindca ore bune dupa DNS unii vizitatori tot acolo ajung.

### Faza 2 — copia finala a magazinului vechi (05:15, DORU + eu)
2.1 Pe backup site: Migrate Guru → cheie noua → **se porneste imediat**, traieste 30 de minute.
2.2 Pe site-ul vechi: cheia lipita → Initiate migration.
Dacă `cURL error 60` → `tools/ecofridge-tls-fix.zip`. Dacă „unable to connect to destination" → cheia a expirat.

### Faza 3 — datele la zi in staging (05:45, eu)
Incarc tabelele clonei langa cele de staging (prefixul `43lz2ypq8_` e liber de aseara) → `f15-compare.sql` → `f15-import-orders.sql` → `AUTO_INCREMENT` peste ultimul numar → reconstruiesc rapoartele → tratez diferentele → sterg tabelele clonei.
Verificare: chei potrivite = total comenzi pe site-ul vechi, diferenta zero.

### Faza 4 — verificari pe staging (06:30, eu)
Comenzi, produse, ultima comanda importata se deschide, feed, sitemap, zero erori. **Protectiile rămân pe staging**, ca sa poata fi recopiat dacă e nevoie.

### Faza 5 — clonarea, care creeaza site-ul nou (06:45, DORU)
5.1 hPanel → Websites → `eco-fridge.o2dev.de` → Clone/Copy site. Hostinger creeaza site-ul pe un domeniu temporar; ~15 minute, pagina de progres se poate inchide.
5.2 **IMEDIAT: parola pe site-ul nou.** Copia vine fara lacat.
5.3 PHP 8.3, CDN off, auto-update „Don't update".
5.4 Connect domain: `eco-fridge.co.uk`, **fara www**; la nameservere → refuza. Va sta la „Parked domains" pana vine DNS-ul.
5.5 Cheia mea SSH, plus domeniul temporar si numele folderului.

### Faza 6 — pregatirea site-ului nou, inainte de DNS (07:10, eu)
6.1 `siteurl`/`home` pe `https://www.eco-fridge.co.uk`; search-replace din domeniul temporar in cel final, pe toate tabelele.
6.2 `wp rewrite flush --hard`, `wp cache flush`.
6.3 Regula de canonic in `.htaccess`, la inceputul fisierului.
6.4 `DISABLE_WP_CRON` plus sarcina cron reala in hPanel, la 5 minute.
6.5 Verific ca lacatul de staging nu s-a activat pe site-ul nou.
6.6 **Pasul care ne scapa de surprize:** testez site-ul nou **ca si cum DNS-ul ar fi mutat deja**, fortand rezolvarea lui `www.eco-fridge.co.uk` catre `148.230.121.74`. Vad prima pagina, un produs si coșul de pe serverul nou, pe domeniul real, **inainte** sa trimitem clientii acolo.
De reținut: din momentul 6.1 domeniul temporar nu mai e folosibil pentru verificari, fiindca site-ul trimite catre domeniul real — de aia verificarea se face prin 6.6.

### Faza 7 — DNS (07:30, DORU)
Verific intai zona. La IONOS: o singura inregistrare, `A @` de la `185.230.220.98` la **`148.230.121.74`**. `www` urmeaza singur. MX, DKIM, DMARC, SPF, TXT neatinse. 5–15 minute, verific propagarea.

### Faza 8 — SSL (07:45, DORU)
Doar dupa ce DNS-ul arata IP-ul nou: Security → SSL → Install SSL → „Active" → Force HTTPS.
Verific: ambele nume 200, certificat pe amandoua, apex → 301 spre www, http → https.

### Faza 9 — deschiderea (08:00–08:30)
9.1 Licente: AG Lloyds pe domeniul live, Astra Pro, parola WP Mail SMTP retastata (DORU).
9.2 GTM cu containerul de la Mads plus Consent Mode v2.
9.3 Scot noindex (`blog_public = 1`); `robots.txt` e deja corect.
9.4 Scot parola de pe site-ul nou — **ultimul pas**, dupa 9.3.
9.5 Comanda reala cu cardul plus un formular: cele doua intrebari apar si refuza trimiterea goala, emailul ajunge la `mads@` si `orders@`, numarul comenzii vine dupa ultimul importat.

### Faza 10 — dupa (08:45–09:15)
Paritate URL-uri si indexare, sitemap in Search Console, Merchant Center pe domeniul live, email catre client, plan de revenire notat.

### Revenire
`A @` inapoi la `185.230.220.98`, vizibila in ~5 minute cu TTL 300, plus dezactivarea `ef-close-shop` pe site-ul vechi. De aia cerem gazduirea veche **neschimbata** 30 de zile.

## Stare la 2026-10-30 (seara)

- **Git:** `main` cu PR #16–#46 unite. Migrări: 01–81.
- **Lansare:** sâmbătă 7 noiembrie 2026, 05:00–09:00. DNS: o singură înregistrare, din contul IONOS.
- **Site de backup:** `eco-fridge-backup.o2dev.de` funcțional, cheia SSH adăugată, aliasul `ef-backup` merge (WordPress 7.1.4, Migrate Guru 6.72).
- **Audit:** cele patru rapoarte O2X citite și verificate; trei probleme reale găsite și reparate.
- **De verificat pe live, imediat după mutare:** robots.txt răspunde 200, scorul SEO din Lighthouse revine la ~100, canonical-urile apar, paginile cu LCP mare se re-măsoară fără parola de staging.
- **Urmează până joi 5 noiembrie:** repetiția migrării pe site-ul de backup, cross-browser, Rich Results Test, deciziile de produs ale lui Mads.

## 100. Ziua lansarii, 05:00: magazinul vechi inchis, clona deblocata — si trei capcane care ar fi oprit din nou Migrate Guru (2026-11-07)

Doru, dimineata: „am instalat ef-close-shop.zip si am dat disable la toate modalitatile de plati din WooCommerce la site-ul vechi. Am verificat in magazin si nu mai am buton de shop, nu mai pot sa adaug produse in cos. Deci pluginul a mers." Apoi: „deblocheaza-mi site-ul eco-fridge-backup.o2dev.de ca sa pot accesa /wp-admin/", si, a doua oara, mai scurt: „deblocheaza site-ul de backup".

**Faza 1 e facuta, deci magazinul vechi e inchis** fara sa fie ascuns: catalogul se vede in continuare, dar nu se mai comanda. Exact ce trebuie, fiindca ore bune dupa schimbarea DNS-ului o parte din vizitatori tot pe serverul vechi ajung.

### De ce „deblocheaza" nu era doar o comoditate

Lacatul pe care l-am pus marti (intrarea 79) raspundea **403 oricui**, in afara cererii cu token. Si serviciul Migrate Guru verifica destinatia **printr-o cerere HTTP obisnuita**. Deci, lasat pe loc, ne-ar fi dat din nou exact eroarea de marti — „We are unable to connect to your destination site" — si am fi pierdut minute din fereastra de lansare căutand o cauza pe care o cunoasteam deja. Lacatul a fost scos complet din `wp-config.php`, cu `php -l` curat si copie de siguranta in `/tmp`, nu in docroot.

### Doua lucruri care l-ar fi blocat oricum la login, gasite inainte sa le intalneasca

1. **`/wp-admin/` dadea 404 si `/wp-login.php` ducea la `/not_found`.** Nu era lacatul: site-ul vechi are **iThemes Security cu „Hide Backend"**, iar clona a venit cu optiunea lui — loginul adevarat era `https://eco-fridge-backup.o2dev.de/ecofridge-login`. Am dezactivat modulul, deci acum merg adresele normale, pe care le cerea Doru.
2. **wp-admin trimitea la `upgrade.php`.** Nucleul clonei e WordPress 7.1.4 peste baza site-ului vechi: `db_version` 53496 fata de 61833. Am rulat upgrade-ul prin WP-CLI (0,4 secunde) si am probat apoi paginile **ca administrator**, cu cookie-uri de autentificare generate pe server: Dashboard 200, Plugins 200, iar pagina Migrate Guru 200 cu „Migration Key" in ea. **Zero „Fatal error"**, desi sunt 40 de pluginuri vechi sub un nucleu nou. Baza clonei se rescrie oricum integral la migrarea de azi, deci upgrade-ul nu strica nimic din ce preluam.
   Si un detaliu de care depinde clicul: **slug-ul paginii e `migrateguru`**, nu `migrate-guru` — `admin.php?page=migrate-guru` da 403 „you are not allowed to access this page".

**Si o masura preventiva:** am oprit blocarea automata a loginului din iThemes (`brute-force` si `network-brute-force`), ca o parola greșit tastata sa nu ne incuie accesul in dimineata lansarii. Zero blocari active in momentul schimbarii.

### Ce rămâne in loc, ca sa nu apara in Google

`blog_public = 0`, deci paginile ies cu `<meta name='robots' content='noindex, nofollow' />`, plus `robots.txt` fizic cu `Disallow: /` si refuzul fisierelor `.bak/.sql/.gz/...` din `.htaccess` (verificat: 403). Am incercat si un `X-Robots-Tag` din `.htaccess` si **nu functioneaza pe LiteSpeed** — masurat, antetul nu apare in raspuns, nici in `IfModule`, nici fara — deci am scos blocul mort ca sa nu ne bazam pe ceva care nu lucreaza. Clona e deschisa, dar nu e indexabila.

### De refacut imediat dupa migrarea de azi

Migrarea aduce din nou baza site-ului vechi, deci **„Hide Backend" si blocarea loginului revin**, loginul clonei redevine `/ecofridge-login`, iar nucleul va fi iar mai nou decat baza. **Si o regula pentru dupa migrare, mai importanta decat pare:** odata ce baza veche revine pe clona, nucleul 7.1.4 va cere din nou upgrade-ul bazei. Azi l-am rulat fara grija, fiindca baza aceea se rescria oricum in minutele urmatoare. **Dupa migrare nu mai e la fel:** acolo e exact baza din care extragem comenzile, iar un upgrade pornit atunci lasa WordPress si WooCommerce sa umble la datele pe care venim sa le citim. Deci dupa ce migrarea se termina: clona se deschide doar la citire, iar dacă wp-admin cere „Update WordPress Database", nu se apasa — eu citesc oricum prin SQL, nu prin wp-admin.

De verificat dupa migrare si **dacă Migrate Guru a rescris `wp-config.php`**: in iulie migrarea a adus prefixul sursei (`43lz2ypq8_`), deci pune mana pe fisier — adica si lacatul, dacă ar fi rămas, ar fi putut dispărea singur.

### Sheet

Randul **„Curățenie pe staging" era nebifat**, desi lucrarea e facuta de ieri seara — Doru avea dreptate. Acum e `✅ Facut (6 nov)`, cu rezultatele in celula (baza 840 MB → 114 MB, docroot 3,6 GB → 1,2 GB, ce s-a sters si ce s-a pastrat intentionat). Am completat si randul de 05:10 cu starea destinatiei si cu cele trei capcane de mai sus, si am marcat `✖ INLOCUIT` cele doua randuri vechi care spun sa creez un site gol in hPanel — nu se poate, si nu vrem sa fie urmate azi din greseala.

## 101. Faza 3: datele la zi in staging — 86 de comenzi, reconciliere 6.017 = 6.017 (2026-11-07, 04:30–05:00 UK)

Doru: migrarea Migrate Guru a pornit la 4:11 si s-a terminat la 4:28; wp-admin-ul clonei nu se mai deschide („database prea veche"), dar `ef-close-shop` se vede in File Manager, deci copia e a magazinului deja inchis. Cererea: „Comenzile de test sterse, apoi extragerea datelor la zi din copie si datele la zi importate in staging."

**Ca wp-admin-ul clonei nu se deschide e exact ce trebuie acum** (intrarea 100): baza e din nou cea veche, iar upgrade-ul ei nu se mai ruleaza — am citit totul prin SQL.

### Ordinea, cu ce a iesit la fiecare pas

1. **Clona, citita fara sa fie atinsa:** 6.017 comenzi, ultima **#14638 (5 noiembrie, 21:11)**, `ef-close-shop` activ, 131 de produse. Migrarea a adus inapoi **`blog_public = 1` si `robots.txt`-ul vechi** (doar `Disallow: /wp-admin/`), deci clona era din nou indexabila: puse la loc imediat `blog_public = 0`, `robots.txt` cu `Disallow: /` si refuzul fisierelor `.bak/.sql/...` din `.htaccess`. Garzile din `wp-config.php` (cron oprit, update-uri blocate) au supravietuit migrarii.
2. **Dump de lucru:** 103 din 266 de tabele, 9,1 MB, fara Matomo, Lloyds Cardnet, iThemes si Action Scheduler; local in `source-dump/db/backup-clone-2026-11-07.sql.gz`.
3. **Comenzile de test:** deja sterse vineri — verificat: zero comenzi peste #7261, niciunul din ID-urile de test, zero randuri de statistici.
4. **Punct de revenire inainte de orice scriere:** dump al staging-ului, 73 de tabele, 7,3 MB, adus local: `source-dump/db/staging-before-f15-2026-11-07.sql.gz`.
5. **Comparatia (`f15-compare.sql`) pe clona proaspata**, cum a cerut Doru marti: **zero produse noi, zero retrase, zero categorii noi sau disparute, zero pagini noi**, iar cea mai noua editare de produs e din **2 noiembrie** — adica nimic nu s-a schimbat dupa repetitia de marti. Singura diferenta de pret/stoc ramane `upright-cake-display-fridge` → outofstock. Cupoane zero, recenzii zero.
6. **Importul (`f15-import-orders.sql`):** **86 de comenzi, #14517–#14638, £211.930,74** (cu trei mai multe decat marti: #14635, #14636, #14638), 264 de linii, 4.387 de randuri de meta, 135 de note. Zero coliziuni de ID, zero comenzi fara linii sau fara total, zero meta orfan. **Chei potrivite 6.017 = 6.017 comenzi pe site-ul vechi** — reconcilierea F15-10 e zero, masurata. **Urmatorul numar: #14658.**
7. **Rapoartele:** 86 de randuri in `wc_order_stats`, 92 in lookup-ul de produse. `wc_get_order` citeste corect prima si ultima comanda importata, cu adresa, linia de produs si `check_doors`.
8. **Stocul:** `upright-cake-display-fridge` trecut pe outofstock.

### Doua lucruri din raportul de marti care s-au dovedit deja facute

- **Media:** PDF-ul manualului de retarder-prover e pe staging si legat de brosura lui; imaginea `GDT120C-Insitu-1` exista in marimea pe care o folosea pagina veche. Nimic de copiat.
- **Cele „25 de redirecturi lipsa" nu lipsesc.** Marti am comparat cu harta PHP, dar pe staging **Rank Math Redirections e pornit**, iar harta PHP se foloseste doar cand modulul e oprit. In Rank Math, 15 dintre ele sunt active, una e gunoi (`/recaptcha/…`), iar **9 sunt puse inactive intentionat de migrarea 52**, fiindca sursa lor functioneaza deja aici. Le-am activat intai — si apoi le-am verificat pe oglinda locala: `/range/double-glazed-counter-display-chiller/`, `/ranges/patisserie-display-fridges/`, `/condition/discount-products/` si cele doua `gastronorm` raspund **200**, iar `/cake-display-fridge/` si cele doua produse „double-glazed counter" sunt deja trimise de WordPress catre pagina potrivita. Redirecturile mele le-ar fi ascuns sau le-ar fi dat 410. **Le-am retras pe toate sapte** (create azi, sterse prin API-ul Rank Math), harta PHP e inapoi identica cu repo-ul, 227 de redirecturi active ca inainte. Concluzie: **nimic de adaugat la redirecturi.**
- **Meta SEO Yoast: nu se importa.** Pe cele 89 de valori de pe continut modificat, 32 sunt identice cu ale noastre, 54 difera fiindca **le-am rescris noi** in octombrie (unele vechi erau chiar gresite: cuptorul si grill-ul aveau descrierea unui frigider vertical), iar 3 sunt ale unei pagini vechi duplicat a homepage-ului, care nu exista la noi.

### Un risc gasit si inchis inainte de lansare

**Cinci comenzi importate sunt „Pending payment"** (#14564, #14596, #14600, #14628, #14635) — incercari de card abandonate, majoritatea urmate de o comanda platita cu aceeasi suma. Site-ul vechi nu le-a anulat niciodata, dar cu `hold_stock_minutes = 60` si o actiune `woocommerce_cancel_unpaid_orders` deja programata, **WooCommerce le-ar fi anulat la primul cron real de pe site-ul nou**: status schimbat, stoc pus inapoi si **cinci emailuri „Cancelled order" la Mads in dimineata lansarii**. Regula noua din `ecofridge-blocks/includes/woocommerce.php` scuteste comenzile create inainte de 7 noiembrie; comenzile de pe site-ul nou se anuleaza normal. Probat pe staging: cele cinci → `false`, o comanda de azi → `true`. Urcat pe staging, md5 identic cu repo-ul; PR #78.

### Curatenie

Tabelele clonei (103) **sterse din baza staging-ului** — trei au cerut `FOREIGN_KEY_CHECKS=0` — deci baza e la **73 de tabele, 111 MB**, si copierea de la Faza 5 nu duce date in plus pe site-ul nou. Dump-ul clonei urcat in `~/f15` pe staging **nu a putut fi sters** (stratul h5g refuza scrierea in fisier); e in afara docrootului, deci nu e servit si nu e copiat de Clone site, dar e de sters din File Manager dupa lansare.

## 102. Staging e gata de clonare: produsele sunt la zi si pe continut, nu doar pe pret si stoc (2026-11-07, ~05:30)

Doru: „produsele pe noul site de staging sunt actualizate la zi din sursa de backup (stoc, pret, produse sterse, produse adaugate, modificari)? Verifica site-ul de staging si vezi ce altceva mai trebuie, precum Protecțiile de staging scoase, si confirma cand e gata." Intre timp: „cele 5 comenzi cu plati cu cardul abandonate, din ce data erau?" — #14564 (4 sep, £1.554), #14596 (18 sep, £3.105), #14600 (23 sep, £2.568), #14628 (26 oct, £2.220), #14635 (4 nov, £2.340); patru au fost refacute si platite de client, #14600 nu.

### Ce lipsea din verificarea de la Faza 3

`f15-compare.sql` compara structura (produse noi/sterse, categorii), pretul efectiv si stocul. **Nu compara textele si specificatiile.** Lista celor 33 de produse „modificate dupa 10 iulie" era, din raportul de marti, doar o lista de verificat cu ochii. Acum am facut comparatia exacta: copia de azi fata de dump-ul din iulie din care s-a construit site-ul, produs cu produs, pe titlu, slug, descriere, descriere scurta, status, ordine, **toate** metadatele si categoriile.

**Capcana de la comparatie:** ambele dump-uri au domeniul rescris de migrare — cel din iulie pe `eco-fridge.o2dev.de`, cel de azi pe `eco-fridge-backup.o2dev.de` — deci la prima trecere aproape toate descrierile pareau schimbate, chiar si la produse neatinse din 2020. Dupa normalizarea ambelor domenii: **zero modificari de titlu, descriere, descriere scurta sau status.** Ce s-a schimbat cu adevarat pe site-ul vechi din iulie:

| Ce | Cate | Pe staging |
|---|---|---|
| Preturi (grab and go, patiserii ECO-P75/BP75, patiserii slimline „Winter Sale") | 18 | toate la valoarea noua (ex. £1.440, £698, £835) |
| Stoc in/out | 10 | toate la valoarea noua (+ upright-cake preluat azi) |
| SKU `X-SD75/120` → `X-SD75/120HC` | 1 | deja `X-SD75/120HC` |
| Link catre manualul PDF la Retarder Prover | 1 | deja in descrierea scurta |
| Poza „in situ" la Large Cake Display Fridge | 1 | deja in descrierea scurta |
| Trei congelatoare low-temperature mutate in Storage Freezer | 3 | sunt in categoria noastra Low Temperature Chest Freezers — structura noastra, mai fina |

Restul diferentelor sunt zgomot: datele de link-uri interne ale unui plugin vechi, cu lungimi serializate schimbate de domeniu. **Concluzie: produsele staging-ului sunt la zi pe tot ce conteaza.** Bazele locale de comparatie au fost sterse dupa, fiindca tabelele de posturi contin si comenzi.

### Protectiile de staging: raman pe staging, se scot pe copie

Randul de calendar „06:30 Protectiile de staging scoase" e de dinainte de planul din intrarea 99. Le-am verificat una cate una:

- **Lacatul** se incarca din `wp-config.php` doar dacă `../.h5g/ef-staging-lock.php` exista (`file_exists`), deci copia nu poate da eroare fatala, oricum ar copia Hostinger folderul.
- **`WP_ENVIRONMENT_TYPE` e `staging`** — si conteaza: `reviews.php` trimite emailurile automate de recenzii si `security.php` aplica protectia HTTPS doar in `production`. Pe site-ul nou trebuie `production`.
- `.htaccess` are blocul „staging no cache"; **cache-ul de pagini LiteSpeed e oprit** (`litespeed.conf.cache` gol); `blog_public = 0`; `X-Robots-Tag` nu exista nicaieri.

Scoase acum de pe staging, ar fi lasat un site cu date reale de clienti deschis si n-ar mai fi putut fi recopiat curat. Deci **raman pe staging** si se scot **pe site-ul nou**, la Faza 6: environment pe `production`, lacatul scos dupa parola din hPanel, blocul no-cache scos, LiteSpeed pornit dupa verificari, search-replace pe toate tabelele. La Faza 9: `blog_public = 1`, apoi parola hPanel scoasa ultima. Randul din Sheet e rescris asa.

### Starea staging-ului, gata de copiere

6.017 comenzi, 131 de produse publicate, 73 de tabele, 111 MB, zero orfane, `debug.log` absent, urmatorul numar #14658, cele cinci comenzi pending protejate. **Staging e gata pentru Clone/Copy site.**

## 103. Site-ul nou clonat; backup si staging inchise; copia locala completa (2026-11-07, ~05:40)

Doru: a clonat staging-ul cu Clone/Copy site; site-ul nou sta pe `ghostwhite-horse-401012.hostingersite.com`, conectat deja la `eco-fridge.co.uk` (A @ inca neschimbat), folderul `/home/u510186744/websites/PNxOJBHKQ/`, user SSH `u510186744_PNxOJBHKQ`. PHP 8.3 a venit cu clona, CDN oprit, LiteSpeed oprit. Nu a pus parola, fiindca DNS-ul se muta in mai putin de o ora. Cereri: lacat si noindex pe backup si pe staging, copie completa la zi pe laptop, verificarea SSL, cheie SSH.

- **SSL:** pe serverul nou, `eco-fridge.co.uk` si `www` primesc acum certificatul generic `*.hstgr.io`; „SSL is active" din dashboard e al domeniului temporar. Let's Encrypt emite certificatul real doar dupa ce A @ arata spre `148.230.121.74` — deci SSL se instaleaza imediat dupa propagare (Faza 8), cu cateva minute inevitabile de avertisment in browser.
- **Testul pe domeniul real, inainte de DNS** (rezolvare fortata spre IP-ul nou): apex https 200, cu titlul corect si `noindex` (vine din clona); **`www` face 301 spre apex** — clonarea a pus `siteurl` pe `https://eco-fridge.co.uk`, invers fata de canonicul nostru. Se corecteaza in Faza 6, **inainte** de A @.
- **Backup:** lacatul cu token pus la loc (token nou, doar local): orice adresa 403 cu `X-Robots-Tag: noindex`; plus `blog_public = 0` si `robots.txt` `Disallow: /`.
- **Staging:** era deja inchis — toate adresele, inclusiv sitemap, feed si API, 401 cu `X-Robots-Tag: noindex`; `blog_public = 0`. `robots.txt` lasat neatins intentionat: e fisierul pentru live si trece in orice recopiere.
- **Copie locala:** wp-env-ul local era din 29 octombrie, deci NU era la zi. Acum la mine: baza finala `source-dump/db/staging-final-2026-11-07.sql.gz` (7,5 MB, cu cele 86 de comenzi) si fisierele schimbate de atunci, `source-dump/final-2026-11-07/wp-content-delta-since-2026-10-29.tar.gz` (14.104 fisiere, 332 MB, zero erori). Impreuna cu wp-content-ul local, copia e completa. Prima incercare a esuat fiindca lista de fisiere scrisa pe Windows avea CRLF si `tar` a cautat nume cu `\r` la final.
- **Cheia SSH** data lui Doru (aceeasi ca pe staging si backup); aliasul `ef-live` configurat la mine. Prima incercare de conectare: cheia inca neadaugata.

## 104. Faza 6 pe site-ul nou: gata si testat pe domeniul real, inainte de DNS (2026-11-07, ~06:00)

Doru a adaugat cheia SSH; aliasul `ef-live` merge. Backup inainte de orice: `/tmp/live-before-faza6.sql.gz` pe server (7,5 MB).

**Ce gasit:** Hostinger, la „Connect domain", rescrisese `eco-fridge.o2dev.de` in **`eco-fridge.co.uk` fara www**, in 8.497 de locuri; zero ramasite de staging sau de domeniul temporar. Deci `siteurl`/`home` erau pe apex si `www` facea 301 spre apex — invers fata de canonicul nostru. Si `LSOC_PREFIX` era inca al contului de staging.

**Ce am facut:**
1. `search-replace '//eco-fridge.co.uk' '//www.eco-fridge.co.uk'` pe **toate tabelele**: 8.497 de inlocuiri, inclusiv **179 de tinte de redirect Rank Math**, guid-uri, termmeta, optiuni. `siteurl`/`home` = `https://www.eco-fridge.co.uk`. Verificat dupa, prin SQL direct (WP-CLI sarise cateva obiecte Astra serializate): zero linkuri ramase spre apex, zero `www.www.`.
2. `wp-config.php`: `WP_ENVIRONMENT_TYPE` → **`production`** (emailurile de recenzii si protectia HTTPS din codul nostru depind de asta), blocul lacatului de staging scos (fisierul lui nici nu venise cu clona), `LSOC_PREFIX` → contul nou. Pe server nu exista Python, deci editarile s-au facut cu PHP, scrise in loc cu `cat`.
3. `.htaccess`: blocul „staging no cache" scos; **regula de canonic pusa primul bloc** — orice gazda diferita de `www.eco-fridge.co.uk` → un singur 301 spre `https://www`, cu `/.well-known/` scutit.
4. Rewrite flush, cache flush.

**Testul cu DNS-ul simulat** (rezolvare fortata spre 148.230.121.74): `http://eco-fridge.co.uk/`, `https://eco-fridge.co.uk/` si `http://www…/` → **un singur 301** spre `https://www.eco-fridge.co.uk/`, cu calea si parametrii pastrati. Prima pagina, shop, o categorie, o pagina de gama, un produs, coș, blog, sitemap (index si produse), robots.txt, feed — toate **200**; checkout cu coșul gol → coș; `/contact/` → `/contact-us/`; un redirect vechi WordPress si unul Rank Math ajung unde trebuie; 404 real pe o adresa inexistenta. **Zero urme de staging/domeniu temporar/apex in HTML, zero erori PHP.** `/.well-known/acme-challenge/` raspunde 404 fara redirect pe ambele nume, deci Let's Encrypt poate valida.

**Alte verificari:** SMTP prin IONOS cu parola care se decripteaza (salt-urile au venit cu clona, deci nu mai trebuie retastata); „New order" la mads@ si orders@; plati active: Lloyds card si transfer bancar. Cron: WP-Cron ramane pornit (ruleaza la vizite) — `crontab` nu exista pe cont, iar un cron real din hPanel se poate pune dupa lansare.

**Ramas, deliberat, pana la Faza 9:** `blog_public = 0` (pagina are inca noindex), cache-ul LiteSpeed oprit.

## 105. Verificarea finala inainte de A @, si o regula noua: copia locala tinuta mereu la zi (2026-11-07, ~06:15)

Doru: „asigura-te ca SSL este neinstalat (…) apoi verifica tot site-ul sa fie totul in regula, actualizat si pregatit pentru lansarea live. Retine in memoria proiectului si in claude-chat.md ca vreau sa-mi actualizezi mereu si copia mea locala din laptop cu site-ul live la zi al eco-fridge.co.uk." Si: pe domeniul temporar unele imagini sunt stricate — „se rezolva pe live?"

**REGULA NOUA, permanenta:** dupa fiecare schimbare pe site-ul live (si regulat dupa lansare), copia locala din laptop se actualizeaza — baza de date si fisierele — in `source-dump/` (gitignored). Salvata si in memorie (`ecofridge-keep-local-copy-current`). Facut deja acum: `source-dump/db/live-2026-11-07-after-faza6.sql.gz` (7,5 MB, starea de dupa Faza 6) plus `wp-config.php`, `.htaccess` si `robots.txt` ale site-ului live, langa arhiva de fisiere de la intrarea 103.

**SSL — confirmat neinstalat:** serverul nou raspunde pentru `eco-fridge.co.uk` si `www` doar cu certificatul generic `*.hstgr.io`, exact cum spune hPanel („No SSL installed"). Site-ul vechi are inca propriul certificat Let's Encrypt. **Nu exista CAA** (verificat prin DNS-over-HTTPS) si nici AAAA, deci nimic nu blocheaza emiterea dupa DNS, iar `/.well-known/` nu e redirectat.

**Imaginile stricate de pe domeniul temporar:** nu sunt o problema a site-ului nou. Dupa Faza 6, toate adresele imaginilor sunt `https://www.eco-fridge.co.uk/wp-content/uploads/…`, iar `www.eco-fridge.co.uk` arata inca spre serverul vechi, care nu are fisierele noi (variantele AVIF/WebP, marimile noi). Masurat pe serverul nou: **457 din 457** de imagini folosite pe prima pagina, shop si o pagina de produs raspund **200**. Se rezolva singur la mutarea DNS.

**Tot site-ul, pe serverul nou, cu DNS simulat:**
- **Sitemap-ul nostru:** 11 sitemapuri, **428 de URL-uri, toate 200**, toate pe `https://www`, zero urme de staging/domeniu temporar/apex, zero erori PHP. Singurele `http://` sunt linkuri externe spre legislation.gov.uk.
- **Paritatea cu site-ul vechi:** toate cele 1.040 de URL-uri din sitemap-ul live al site-ului vechi, verificate pe serverul nou: **417 → 200 direct, 343 → 301 spre o pagina 200, 1 → 301 + 302 (tot spre o pagina buna)**. Restul de **279 sunt toate pagini de atasament de forma `/?attachment_id=N`** si dau 404 — **zero alte 404-uri**.
- De ce cele 279: la noi paginile de atasament trimit catre fisier (intrarea din octombrie), dar ID-urile media s-au schimbat la migrarea din iulie, deci `?attachment_id=<ID vechi>` nu mai gaseste nimic. Harta ID vechi → ID nou exista (`source-dump/_attachment-id-map.json`, migrarea 04). **Nu blocheaza lansarea** — sunt URL-uri de imagini, fara valoare de cautare — si se repara imediat dupa A @, cu o redirectionare din harta.

**Verdict: site-ul nou e gata pentru A @.**

## Stare la 2026-11-07 (dimineata lansarii, ~05:00)

- **Faza 1 GATA (05:00):** `ef-close-shop` activ pe site-ul vechi, plati oprite.
- **Faza 2 GATA (04:11–04:28):** copia finala pe clona; clona reinchisa la indexare (`blog_public = 0`, `robots.txt` `Disallow: /`). Wp-admin-ul ei NU se deschide si nu trebuie deschis — upgrade-ul bazei nu se ruleaza.
- **Faza 3 GATA (intrarea 101):** 86 de comenzi importate, #14517–#14638, reconciliere 6.017 = 6.017, urmatorul numar #14658; rapoarte reconstruite; stocul preluat; nimic nou la produse, categorii, pagini, media, redirecturi sau SEO; regula care pastreaza cele 5 comenzi pending (PR #78); tabelele clonei sterse din staging. Punct de revenire: `source-dump/db/staging-before-f15-2026-11-07.sql.gz`.
- **Produsele verificate si pe continut (intrarea 102):** la zi pe preturi (18), stoc (10), SKU, manual PDF si poza noua; zero modificari de text. **Staging e GATA de clonare.**
- **Faza 6, in plus:** `WP_ENVIRONMENT_TYPE` → `production` (altfel nu pleaca emailurile de recenzii si nu se aplica protectia HTTPS), blocul no-cache scos, LiteSpeed pornit dupa verificari.
- **REGULA (Doru, 7 nov): copia locala din laptop a site-ului live se actualizeaza MEREU** — baza si fisierele, in `source-dump/` (intrarea 105).
- **Verificare finala (intrarea 105):** 428/428 URL-uri 200, paritate cu site-ul vechi fara 404 in afara de 279 de `?attachment_id=` (de reparat dupa A @), SSL neinstalat confirmat, nicio CAA. GO pentru A @.
- **Faza 6 GATA (intrarea 104):** site-ul nou pe www, production, canonic, testat pe domeniul real. **Doru poate schimba A @.**
- **Faza 5 GATA (intrarea 103):** site nou pe `ghostwhite-horse-401012.hostingersite.com`, folder `PNxOJBHKQ`, alias `ef-live`. Urmeaza Faza 6 (eu), apoi A @ (Doru) — NU inainte de confirmarea mea.
- **Urmeaza Faza 5 (DORU):** Clone/Copy site din staging → parola imediat → PHP 8.3, CDN off, auto-update off → Connect domain `eco-fridge.co.uk` fara www → cheia SSH + domeniul temporar si folderul.
- **De retinut pentru Faza 6:** tintele redirecturilor Rank Math sunt URL-uri ABSOLUTE pe `https://eco-fridge.o2dev.de/...`, deci search-replace-ul se face pe TOATE tabelele, nu doar pe cele WordPress.
- **Dupa lansare:** de sters din File Manager `~/f15/backup-clone-2026-11-07.sql.gz` (nu s-a putut din SSH).

## Stare la 2026-11-03 (dimineata)

- **Git:** `main` cu PR #16–#55 unite. **PR #56 e deschis si neunit** — conține intrarile 69–72 din jurnal si pluginul `tools/ecofridge-tls-fix/`; merge-ul mi-a fost blocat de modul auto, deci il face Doru.
- **Lansare:** sambata 7 noiembrie 2026, 05:00–09:00. Repetitia migrarii: joi 5 noiembrie. DNS: o singura inregistrare, din contul IONOS.
- **Site vechi:** PHP 7.2.34, WordPress 6.0.x, WooCommerce 3.5.10, Migrate Guru actualizat la 6.72 (era 5.65).
- **Clona:** `eco-fridge-backup.o2dev.de`, WordPress 7.1.4, SSH `ef-backup` merge. **De facut in hPanel, de Doru:** PHP pe 7.4, CDN off, auto-update pe „Don't update".
- **PLANUL DE SAMBATA, pas cu pas: intrarea 99.** E varianta finala, dupa corectura lui Doru ca nu se poate crea un site gol - clonarea ESTE actul de creare, pe un domeniu temporar. Prima actiune dupa clonare: parola pe site-ul nou.
- **Cum lucram maine (Doru, 6 nov):** „anunta-ma doar cand ai nevoie de ceva de la mine, in rest lucreaza". Deci il caut doar la pasii lui, nu la fiecare verificare.
- **Aprobare permanenta de la Doru, 3 nov (intrarile 82 si 89):** jurnal, git pana la merge, Sheet-ul SI memoria proiectului - toate PATRU se actualizeaza fara sa mai cer.
- **Sheet adus la zi (intrarea 80):** calendarul si checklistul au randurile de azi, regula corectata de selectie a comenzilor, valorile DNS verificate, plus trei defecte reparate (textul despre SSL copiat peste pasul de comanda reala, randul de GTM dispărut, regula de canonic mutata inaintea DNS-ului).
- **Intrebarile de livrare, finalizate (intrarile 87 si 88, F10-17).** Greseala de tipar reparata, iar treptele sunt acum o alegere 0/1/2/3/4+. Clientul vede cifrele, dar se salveaza „0 steps" … „4+ steps", fiindca WooCommerce trateaza un „0" curat ca raspuns lipsa la un camp obligatoriu. Comenzile importate nu se convertesc.
- **Cum au fost preluate (intrarea 87).** Preluate 1:1 de pe site-ul vechi (check_doors YES/NO si stairs, amandoua obligatorii), implementate in pluginul nostru prin Additional Checkout Fields API, fiindca checkout-ul e pe BLOCURI; raspunsurile sunt oglindite in cheile vechi, deci si comenzile importate se afiseaza. De decis de client: greseala de tipar „recomend" din eticheta si dacă `stairs` rămâne text liber (396 de raspunsuri distincte pe site-ul vechi).
- **Importul scris, rulat si dovedit (intrarea 86).** Unelte in repo: migration/f15-run.sh, f15-compare.sql, f15-import-orders.sql. Pe o copie locala: stergerea cu WP-CLI curata si wp_wc_order_stats, importul a trecut toate verificările, reconcilierea e 6.014 = 6.014, WooCommerce citeste corect comanda importata, rapoartele se reconstruiesc, secventa a ajuns la 14652. **De reverificat sambata pe clona proaspata** (cerere Doru): produse noi, sterse, modificate, categorii si pagini - cu f15-compare.sql. Si: `stairs` e un camp real de livrare pe care site-ul nou nu-l mai intreaba, de discutat cu Mads.
- **Extragerea rulata pe date reale (intrarea 85, raport: migration/f15-delta-2026-11-03.md).** Comenzi de importat: **83**, nu 85 - selectia se face dupa `_order_key`, fiindca regula cu data includea doua comenzi pe care le avem deja. Reconcilierea e deja zero (5.931 = 5.931). Zero produse noi, zero categorii noi, zero cupoane, zero conturi, zero recenzii. Rămân: o schimbare de stoc, doua fisiere media, 25 de redirecturi lipsa, meta SEO pentru continutul nou. Si 18 din 19 diferente de pret sunt false pozitive - comparatia se face pe pretul efectiv.
- **Dump de proba luat (intrarea 84):** source-dump/db/backup-clone-2026-11-03.sql.gz, 9,1 MB, 127 de tabele, verificat; fara tabelele Lloyds, Matomo si jurnale. Delta de media: DOUA fisiere, aflate din baza de date, fiindca datele fisierelor au fost rescrise de migrare. Copiile .bak din docroot au fost mutate afara si extensiile riscante refuzate.
- **HPOS: NU inainte de lansare (intrarea 95).** Nu joi/vineri, fiindca importul dovedit scrie in wp_posts; nu sambata, fiindca e o migrare de schema in fereastra de revenire. Momentul: 2-3 saptamani dupa lansare, cu sincronizarea pornita prima. Verificat ca niciun plugin nu declara incompatibilitate si ca pluginul nostru nu citeste comenzi din posts.
- **Numerotare comenzi: DECIS (intrarile 81 si 83).** Comenzile de test se sterg definitiv sambata, comenzile reale vin cu numerele originale, iar secventa site-ului nou se pune peste cel mai mare numar importat. Fara renumerotare.
- **Context si argumentele deciziei (intrarea 81):** Doru a decis ca numerele reale se pastreaza si magazinul continua dupa ele. HPOS e OPRIT pe ambele site-uri, deci numarul comenzii e ID-ul postului; staging e la max ID 8042. Recomandarea mea: comenzile de test SE STERG, iar secventa se pune peste cel mai mare numar importat - renumerotarea lor ar fi chirurgie pe 6-8 tabele in dimineata lansarii, iar pastrate ar adauga ~£1.545 de vanzari inexistente in rapoartele primei luni.
- **Clona site-ului vechi: GATA si INCHISA** (intrarea 79). Migrarea a reusit, copia e identica cu site-ul vechi, iar acum orice cerere HTTP da 403 in afara de cea cu token; blog_public 0, robots.txt Disallow, update-uri si cron oprite. **Descoperiri care schimba planul:** comenzile noi se selecteaza DUPA DATA (85 de la 10 iulie incoace, #14514-#14632), nu dupa ID; numarul comenzii pe site-ul vechi e ID-ul postului, deci numerotarea viitoare trebuie decisa (peste 14632 sau renumerotare); clientii sunt aproape toti oaspeti, deci nu avem conturi de migrat.
- **TTL coborat si verificat (intrarea 90):** apex A e pe 300, valoarea neschimbata; www si ftp rămân la 3600, fiindca nu se schimba. Dupa o saptamana de la lansare, inapoi la 3600.
- **DNS lamurit (intrarea 78):** sambata se schimba O SINGURA inregistrare, A de la apex, 185.230.220.98 -> 148.230.121.74; www e CNAME spre apex si urmeaza singur; nu exista AAAA si nici CAA; MX si TXT nu se ating. **De facut azi:** TTL-ul inregistrarii A coborat la 300 la IONOS, cu valoarea neschimbata.
- **Site-ul de test se sterge** la finalul repetitiei, cu acordul cerut si dat; procedura se reia de la zero sambata, din staging-ul actualizat. Ce a dovedit repetitia: intrarea 77, punctele 1-6.
- **Domeniu:** pe site-ul nou, eco-fridge.co.uk apare la Parked domains si nu merge inca, fiindca inregistrarea A arata inca spre serverul lui Ambrit (zona e la IONOS, in contul clientului). De urmarit: domeniu PRINCIPAL, nu parcat, plus varianta www acoperita de certificat (intrarea 76).
- **Repetitia de azi, clonarea staging:** reusita, pe `https://eco-fridge-o2dev-de-857081.hostingersite.com/`; unealta Hostinger inlocuieste singura URL-urile, noindex vine cu copia, dar parola NU vine, deci copia e deschisa oricui — de inchis sau de sters. Detalii si ordinea corecta de sambata: intrarea 75.
- **Azi, in lucru:** migrarea de test site vechi → `eco-fridge-backup.o2dev.de` ruleaza prin Migrate Guru; in paralel, clonarea staging → site nou cu unealta Hostinger (lista de verificari de dupa: intrarea 74).
- **Blocajul de azi, rezolvat:** „Initiate migration" cadea cu cURL error 60. Cauza gasita si dovedita: lista de radacini a WordPress-ului 6.0 de pe live nu cunoaste radacina GlobalSign Root R46 folosita de `mg.blogvault.net`. Reparatia e `tools/ecofridge-tls-fix.zip`, de urcat pe site-ul vechi; **inca netestata pe viu** — asta e primul lucru de incercat.
- **Daca reparatia nu merge:** variantele din intrarea 72, secțiunea 5, in ordinea de acolo.
- **Procedura completa pentru sambata:** intrarea 72, de citit de la cap la coada inainte de orice clic.
- **De la Ambrit/Mads: textul final al celor trei cereri e in intrarea 92**, gata de trimis. Context si ce a ieșit din lista: intrarea 91.
- **Context (intrarea 91):** confirmarea ca nu umbla la zona DNS RAMANE valabila (zona e la IONOS, in contul clientului, dar ei pot avea credentiale vechi); mai importante acum: sa nu opreasca gazduirea veche inainte de 30 de zile si ingheţ pe site-ul vechi de vineri seara. Cererile de acces FTP/dump si de `ca-certificates` NU mai sunt necesare.
