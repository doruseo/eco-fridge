# Claude chat – 2026-10-19 → 2026-10-27

Conversație despre statusul comenzilor (bank transfer), pluginul de plăți Lloyds Cardnet, footer, bara de filtre a magazinului (desktop și mobil), categoriile bakery și afișarea „Out of stock” pe site-ul nou Eco-Fridge.

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

## Stare la 2026-10-29 (final)

- **Git:** `main` la `a103997`, cu PR #16, #17 și #18 unite; local, staging și git sunt identice; working tree curat.
- **Plăți:** Lloyds Cardnet pe Live, testat de Doru și de Kass (£1,00 fiecare, nerambursate — efectele sunt în secțiunea 31). Licența e a Eco-Fridge și se mută pe domeniul live. Avertismentele PHP ale pluginului AG sunt inofensive cu debug oprit (secțiunea 30).
- **Emailuri:** SMTP IONOS funcțional; cereri din formular → mads@ + kass@; emailuri de comandă pentru firmă → mads@ + orders@; expeditor noreply@, Reply-To mads@.
- **Cookies:** registrul rescanat de Doru; equal prominence implicit.
- **Imagini:** 838 convertite pe staging; pozele noi se convertesc singure.
- **Urmează, în această ordine:** copia la zi a site-ului vechi pe eco-fridge-backup.o2dev.de → ștergerea comenzilor și a produsului de test → importul tuturor comenzilor noi (de la #7261, 10 iulie 2026, încolo, inclusiv cele neexpediate) și al celorlalte date la zi → reconciliere 1:1 → curățenie finală → lansare din staging pe eco-fridge.co.uk (același cont Hostinger, DNS din IONOS). Data lansării nu e fixată; calendarul din Drive are termene relative (T-7 … Ziua Z … Z+30).
- **De la Mads:** numărul de companie și codul de TVA, revizuirea paginilor legale, backupul de la Ambrit.
- **Documentație la zi:** acest fișier, memoria proiectului (inclusiv o notă separată despre plățile de test), documentul de proces eco-fridge, procedura generală (v2), checklistul eco-fridge cu fazele 15–16 și tabul „Calendar lansare", checklistul general cu rândurile noi.
