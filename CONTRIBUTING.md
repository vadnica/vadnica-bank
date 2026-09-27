# Prispevanje k projektu Vadnica Bank

Hvala za zanimanje za prispevanje k projektu!

## Prijava napak

Preden odpreš novo težavo, preveri, ali podobna težava že obstaja.

Pri prijavi navedi:

- kratek in jasen opis težave,
- korake za ponovitev,
- pričakovano vedenje,
- dejansko vedenje,
- različico PHP-ja in uporabljeni operacijski sistem,
- morebitna sporočila o napakah.

Nikoli ne objavljaj resničnih bančnih podatkov, gesel, ključev ali vsebine datoteke `db.php`.

## Predlogi in nove funkcije

Opiši:

- kaj želiš dodati,
- zakaj bi bila funkcija koristna,
- kako naj bi delovala,
- morebitne varnostne posledice.

## Spremembe kode

1. Ustvari vejo iz `main`:
   ```bash
   git checkout -b ime-spremembe
   ```

2. Spremembe preizkusi lokalno.

3. Preveri, da ne dodajaš občutljivih podatkov:
   ```bash
   git status
   ```

4. Ustvari jasen commit:
   ```bash
   git add .
   git commit -m "Opiši spremembo"
   ```

5. Pošlji vejo na GitHub:
   ```bash
   git push -u origin ime-spremembe
   ```

6. Odpri pull request proti veji `main`.

## Varnost

Varnostne ranljivosti ne objavljaj javno kot običajno težavo. Pred prijavo odstrani vse občutljive podatke in uporabi zaseben način za stik z vzdrževalcem projekta.

## Licenca

Prispevki k projektu so objavljeni pod enakimi pogoji kot projekt, v skladu z licenco GNU AGPL v3.