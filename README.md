# vadnica-bank 🔐

**Zero-knowledge bančni dnevnik** za varen vpis in sledenje osebnih transakcij.
Vsa občutljiva finančna podatka so šifrirana v brskalniku (AES-256) preden se pošljejo na strežnik — strežnik nikoli ne vidi podatkov v berljivi obliki.

**English:** A zero-knowledge banking ledger for securely recording and tracking personal transactions. All sensitive data is encrypted in the browser (AES-256) before reaching the server — the server never sees plaintext data.

## Funkcionalnosti / Features

- 🔐 Zero-knowledge šifriranje (AES-256) / Zero-knowledge encryption (AES-256)
- 🔑 Glavno geslo se nikoli ne shrani na strežniku / Master password is never stored on the server
- 📝 Vpis, urejanje in brisanje transakcij / Adding, editing and deleting transactions
- 🧾 Slovenski zapis decimalnih vejic / Slovenian decimal comma support
- 👤 Registracija z e-poštno potrditvijo / Registration with email confirmation
- 🛡️ 2FA (TOTP) podpora / 2FA (TOTP) support
- 🌍 Dvojezični vmesnik (sl/en) / Bilingual interface (sl/en)
- 📱 Deluje tudi offline kot PWA / Works offline as a PWA

## Tehnologije / Technologies

- PHP (mysqli)
- JavaScript (Vanilla)
- MySQL
- PHPMailer

## Namestitev / Installation

1. Kloniraj repozitorij:

git clone git@github.com:vadnica/vadnica-bank.git
2. Kopiraj `db.example.php` v `db.php` in vpiši svoje podatke za podatkovno bazo
3. Zaženi `setup.php` za ustvarjanje tabel
4. Nastavi SMTP podatke za pošiljanje e-pošte (PHPMailer)

## Varnostni model / Security model

Glavno geslo se zgušča (hash) in ni nikoli shranjeno v berljivi obliki. Vsi ostali podatki (transakcije, zneski, opisi) so šifrirani v brskalniku pred pošiljanjem na strežnik. Strežnik deluje zgolj kot shramba šifriranih podatkov.

## Licenca / License

Ta projekt je licenciran pod [AGPL-3.0](LICENSE).

---

Več o projektu na [https://vadnica.org/?lang=en](https://vadnica.org) 🚀

## Povezave / Links

- **Live Demo | Živ demo:** [https://bank.vadnica.org/?lang=en](https://bank.vadnica.org)
- **Tutorial Site | Učna stran:** [https://vadnica.org/?lang=en](https://vadnica.org)
- **Blog | Blog:** [https://blog.vadnica.org/individual_article.php?id=106&lang=en&p=1&kat=Vse&s=](https://blog.vadnica.org)
