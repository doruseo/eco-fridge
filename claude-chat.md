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

## Stare la 2026-10-30 (seara)

- **Git:** `main` cu PR #16–#46 unite. Migrări: 01–81.
- **Lansare:** sâmbătă 7 noiembrie 2026, 05:00–09:00. DNS: o singură înregistrare, din contul IONOS.
- **Site de backup:** `eco-fridge-backup.o2dev.de` funcțional, cheia SSH adăugată, aliasul `ef-backup` merge (WordPress 7.1.4, Migrate Guru 6.72).
- **Audit:** cele patru rapoarte O2X citite și verificate; trei probleme reale găsite și reparate.
- **De verificat pe live, imediat după mutare:** robots.txt răspunde 200, scorul SEO din Lighthouse revine la ~100, canonical-urile apar, paginile cu LCP mare se re-măsoară fără parola de staging.
- **Urmează până joi 5 noiembrie:** repetiția migrării pe site-ul de backup, cross-browser, Rich Results Test, deciziile de produs ale lui Mads.
