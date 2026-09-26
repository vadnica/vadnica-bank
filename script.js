// 1. DVOJEZIČNI SLOVARJI IN NASTAVITVE JEZIKA
const jeAnglescina = document.documentElement.lang === 'en';

const privzetiBancniProfil = {
    sl: {
        lbl_zacetno: 'Začetno stanje na računu',
        lbl_placa: 'Mesečna plača / redni prilivi',
        lbl_krediti: 'Mesečni krediti / limiti',
        lbl_stroski: 'Mesečni stroški vodenja osebnega računa',
        lbl_obresti: 'Obresti dovoljenega negativnega stanja',
        lbl_varcevanje: 'Varčevanje'
    },
    en: {
        lbl_zacetno: 'Starting account balance',
        lbl_placa: 'Monthly salary / regular income',
        lbl_krediti: 'Monthly loans / overdrafts',
        lbl_stroski: 'Personal account monthly maintenance fees',
        lbl_obresti: 'Overdraft interest',
        lbl_varcevanje: 'Savings'
    }
};

const privzetiFiltri = {
    sl: {
        vse: 'Vse transakcije',
        poloznice: 'Položnice',
        nakupi: 'Nakupi & Hrana',
        kreditna: 'Kreditna kartica',
        narocnine: 'Naročnine',
        avto: 'Vozilo & Gorivo',
        ostalo: 'Ostalo'
    },
    en: {
        vse: 'All transactions',
        poloznice: 'Bills & Utilities',
        nakupi: 'Shopping & Groceries',
        kreditna: 'Credit card',
        narocnine: 'Subscriptions',
        avto: 'Vehicle & Fuel',
        ostalo: 'Other'
    }
};

const filterIkone = {
    vse: '📊',
    poloznice: '📄',
    nakupi: '🛒',
    kreditna: '💳',
    narocnine: '🔄',
    avto: '⛽',
    ostalo: '🏷️'
};

// Pomožne funkcije za kategorije po meri in filtre
function pridobiCustomKategorije() {
    const user = pridobiTrenutnegaUporabnika() || {};
    try {
        const uId = user.id ? `_${user.id}` : '';
        return JSON.parse(localStorage.getItem(`banka_custom_categories${uId}`) || localStorage.getItem('banka_custom_categories') || '[]');
    } catch (e) {
        return [];
    }
}

function shraniCustomKategorije(kategorije) {
    const user = pridobiTrenutnegaUporabnika() || {};
    const uId = user.id ? `_${user.id}` : '';
    localStorage.setItem(`banka_custom_categories${uId}`, JSON.stringify(kategorije));
    localStorage.setItem('banka_custom_categories', JSON.stringify(kategorije));
    shraniBancniProfilStrežnik();
}

function pridobiFiltre(langKey) {
    const user = pridobiTrenutnegaUporabnika() || {};
    const uId = user.id ? `_${user.id}` : '';
    return { ...privzetiFiltri[langKey], ...JSON.parse(localStorage.getItem(`banka_filters${uId}_${langKey}`) || localStorage.getItem(`banka_filters_${langKey}`) || '{}') };
}

function pridobiNazivKategorije(katKey) {
    const langKey = jeAnglescina ? 'en' : 'sl';
    const fData = pridobiFiltre(langKey);
    if (fData[katKey]) return fData[katKey];

    const customCats = pridobiCustomKategorije();
    const najdena = customCats.find(c => c.id === katKey || c.name.toLowerCase() === (katKey || '').toLowerCase());
    if (najdena) return najdena.name;

    return katKey || fData.ostalo || 'Ostalo';
}

function pridobiIkonoKategorije(katKey) {
    if (filterIkone[katKey]) return filterIkone[katKey];
    const customCats = pridobiCustomKategorije();
    const najdena = customCats.find(c => c.id === katKey);
    if (najdena && najdena.icon) return najdena.icon;
    return '🏷️';
}

// Zero-Knowledge sinhronizacija bančnega profila s strežnikom
async function shraniBancniProfilStrežnik() {
    const user = pridobiTrenutnegaUporabnika() || {};
    if (!user.id) return;

    try {
        const uId = `_${user.id}`;
        const pSl = JSON.parse(localStorage.getItem(`banka_profile${uId}_sl`) || localStorage.getItem('banka_profile_sl') || '{}');
        const pEn = JSON.parse(localStorage.getItem(`banka_profile${uId}_en`) || localStorage.getItem('banka_profile_en') || '{}');
        const amounts = JSON.parse(localStorage.getItem(`banka_amounts${uId}`) || localStorage.getItem('banka_amounts') || '{}');
        const fSl = JSON.parse(localStorage.getItem(`banka_filters${uId}_sl`) || localStorage.getItem('banka_filters_sl') || '{}');
        const fEn = JSON.parse(localStorage.getItem(`banka_filters${uId}_en`) || localStorage.getItem('banka_filters_en') || '{}');
        const customCats = JSON.parse(localStorage.getItem(`banka_custom_categories${uId}`) || localStorage.getItem('banka_custom_categories') || '[]');

        const payload = {
            profile_sl: pSl,
            profile_en: pEn,
            amounts: amounts,
            filters_sl: fSl,
            filters_en: fEn,
            custom_categories: customCats
        };

        const enc = await ZKCrypto.encrypt(payload);
        await fetch('profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_bank_profile',
                user_id: user.id,
                encrypted_profile: enc.encrypted_data,
                bank_iv: enc.iv
            })
        });
    } catch (e) {
        console.error('Napaka pri shranjevanju bančnega profila na strežnik:', e);
    }
}

async function naloziBancniProfilStrežnik(userData) {
    if (!userData || !userData.encrypted_profile || !userData.bank_iv) return;
    try {
        const dec = await ZKCrypto.decrypt(userData.encrypted_profile, userData.bank_iv);
        if (dec && typeof dec === 'object') {
            const uId = userData.id ? `_${userData.id}` : '';
            if (dec.profile_sl) {
                localStorage.setItem(`banka_profile${uId}_sl`, JSON.stringify(dec.profile_sl));
                localStorage.setItem('banka_profile_sl', JSON.stringify(dec.profile_sl));
            }
            if (dec.profile_en) {
                localStorage.setItem(`banka_profile${uId}_en`, JSON.stringify(dec.profile_en));
                localStorage.setItem('banka_profile_en', JSON.stringify(dec.profile_en));
            }
            if (dec.amounts) {
                localStorage.setItem(`banka_amounts${uId}`, JSON.stringify(dec.amounts));
                localStorage.setItem('banka_amounts', JSON.stringify(dec.amounts));
            }
            if (dec.filters_sl) {
                localStorage.setItem(`banka_filters${uId}_sl`, JSON.stringify(dec.filters_sl));
                localStorage.setItem('banka_filters_sl', JSON.stringify(dec.filters_sl));
            }
            if (dec.filters_en) {
                localStorage.setItem(`banka_filters${uId}_en`, JSON.stringify(dec.filters_en));
                localStorage.setItem('banka_filters_en', JSON.stringify(dec.filters_en));
            }
            if (dec.custom_categories) {
                localStorage.setItem(`banka_custom_categories${uId}`, JSON.stringify(dec.custom_categories));
                localStorage.setItem('banka_custom_categories', JSON.stringify(dec.custom_categories));
            }
            posodobiSidebarNazive();
            posodobiBancniPrikaz();
        }
    } catch (e) {
        console.error('Napaka pri dešifriranju bančnega profila:', e);
    }
}

const t = (typeof I18N !== 'undefined') ? I18N : (jeAnglescina ? {
    login_title: 'Online Bank Login',
    register_title: 'Register New Account',
    btn_login: 'Login',
    btn_register: 'Register',
    no_account: "Don't have an account?",
    have_account: 'Already have an account?',
    link_register: 'Register here',
    link_login: 'Login here',
    greeting: 'Welcome',
    no_transactions: 'No transactions recorded yet.',
    trans_saved: 'Transaction successfully saved!',
    conn_error: 'Connection error.',
    pass_mismatch: 'Passwords do not match!',
    member_since: 'Member since: '
} : {
    login_title: 'Prijava v spletno banko',
    register_title: 'Registracija novega računa',
    btn_login: 'Prijava',
    btn_register: 'Registriraj račun',
    no_account: 'Še nimate računa?',
    have_account: 'Že imate račun?',
    link_register: 'Registrirajte se tukaj',
    link_login: 'Prijavite se tukaj',
    greeting: 'Pozdravljeni',
    no_transactions: 'Ni še zabeleženih transakcij.',
    trans_saved: 'Transakcija uspešno shranjena!',
    conn_error: 'Napaka pri povezavi.',
    pass_mismatch: 'Gesli se ne ujemata!',
    member_since: 'Član od: '
});

// 2. UPRAVLJANJE DARK / LIGHT NAČINA IN GLAVNEGA MENIJA
const themeToggleBtn = document.getElementById('theme-toggle');
const themeToggleMobileBtn = document.getElementById('theme-toggle-mobile');
const headerMenuToggle = document.getElementById('header-menu-toggle');
const headerMenuDropdown = document.getElementById('header-menu-dropdown');

function posodobiPrikazTeme(jeDark) {
    const themeIcon = themeToggleMobileBtn ? themeToggleMobileBtn.querySelector('.theme-mode-icon') : null;
    const themeText = document.getElementById('theme-mode-text');
    const txtSvetli = (typeof I18N !== 'undefined' && I18N.theme_light) ? I18N.theme_light : 'Svetli način';
    const txtTemni = (typeof I18N !== 'undefined' && I18N.theme_dark) ? I18N.theme_dark : 'Temni način';

    if (jeDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        if (themeToggleBtn) themeToggleBtn.textContent = '☀️';
        if (themeIcon) themeIcon.textContent = '☀️';
        if (themeText) themeText.textContent = txtSvetli;
    } else {
        document.documentElement.removeAttribute('data-theme');
        if (themeToggleBtn) themeToggleBtn.textContent = '🌙';
        if (themeIcon) themeIcon.textContent = '🌙';
        if (themeText) themeText.textContent = txtTemni;
    }
}

function preklopiTemo() {
    const jeDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const novaTema = !jeDark;
    localStorage.setItem('banka_theme', novaTema ? 'dark' : 'light');
    posodobiPrikazTeme(novaTema);
}

const zacetnaTema = localStorage.getItem('banka_theme') || 'light';
posodobiPrikazTeme(zacetnaTema === 'dark');

if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', preklopiTemo);
}
if (themeToggleMobileBtn) {
    themeToggleMobileBtn.addEventListener('click', preklopiTemo);
}

// Upravljanje spustnega menija v glavi (za mobilni pogled)
if (headerMenuToggle && headerMenuDropdown) {
    headerMenuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = headerMenuDropdown.classList.toggle('hidden');
        headerMenuToggle.setAttribute('aria-expanded', (!isHidden).toString());
    });

    document.addEventListener('click', (e) => {
        if (!headerMenuDropdown.classList.contains('hidden') &&
            !headerMenuDropdown.contains(e.target) &&
            e.target !== headerMenuToggle &&
            !headerMenuToggle.contains(e.target)) {
            headerMenuDropdown.classList.add('hidden');
            headerMenuToggle.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !headerMenuDropdown.classList.contains('hidden')) {
            headerMenuDropdown.classList.add('hidden');
            headerMenuToggle.setAttribute('aria-expanded', 'false');
        }
    });
}

let jeNacinPrijave = true;

// 3. GLAVNI DOM ELEMENTI
const authSection = document.getElementById('auth-section');
const dashboardSection = document.getElementById('dashboard-section');

// Prijava (Login)
const loginBox = document.getElementById('login-box');
const loginForm = document.getElementById('login-form');
const loginEmail = document.getElementById('login-email');
const loginGeslo = document.getElementById('login-geslo');
const loginWebsite = document.getElementById('login-website');
const loginMsg = document.getElementById('login-msg');
const switchToRegister = document.getElementById('switch-to-register');

// Registracija (Register)
const registerBox = document.getElementById('register-box');
const registerForm = document.getElementById('register-form');
const regIme = document.getElementById('reg-ime');
const regEmail = document.getElementById('reg-email');
const regGeslo = document.getElementById('reg-geslo');
const regPotrdi = document.getElementById('reg-potrdi');
const regWebsite = document.getElementById('reg-website');
const regMsg = document.getElementById('reg-msg');
const switchToLogin = document.getElementById('switch-to-login');

const btnLogout = document.getElementById('btn-logout');

// Elementi za 2FA
const box2faRegister = document.getElementById('box-2fa-register');
const regSecretDisplay = document.getElementById('reg-secret-display');
const regTwoFaSecret = document.getElementById('reg-two-fa-secret');
const regTwoFaCode = document.getElementById('reg-two-fa-code');
const regBtnCopySecret = document.getElementById('reg-btn-copy-secret');
const regBtnRegenSecret = document.getElementById('reg-btn-regen-secret');

const polje2faLogin = document.getElementById('polje-2fa-login');
const loginTwoFaCode = document.getElementById('login-two-fa-code');

// Profil 2FA elementi
const disp2faBadge = document.getElementById('disp-2fa-badge');
const btnToggleProfile2fa = document.getElementById('btn-toggle-profile-2fa');
const boxManage2fa = document.getElementById('box-manage-2fa');
const profSecretDisplay = document.getElementById('prof-secret-display');
const profTwoFaSecret = document.getElementById('prof-two-fa-secret');
const profTwoFaCode = document.getElementById('prof-two-fa-code');
const profTwoFaPass = document.getElementById('prof-two-fa-pass');
const profBtnCopySecret = document.getElementById('prof-btn-copy-secret');
const profBtnRegenSecret = document.getElementById('prof-btn-regen-secret');
const btnCancelProfile2fa = document.getElementById('btn-cancel-profile-2fa');
const btnDisableProfile2fa = document.getElementById('btn-disable-profile-2fa');
const formProfile2fa = document.getElementById('form-profile-2fa');

// Funkcije za 2FA generiranje in kopiranje
function osveziReg2FaSecret() {
    if (typeof generateRandomSecret === 'function') {
        const novo = generateRandomSecret(20);
        if (regSecretDisplay) regSecretDisplay.textContent = novo;
        if (regTwoFaSecret) regTwoFaSecret.value = novo;
    }
}

function osveziProf2FaSecret() {
    if (typeof generateRandomSecret === 'function') {
        const novo = generateRandomSecret(20);
        if (profSecretDisplay) profSecretDisplay.textContent = novo;
        if (profTwoFaSecret) profTwoFaSecret.value = novo;
    }
}

if (regBtnCopySecret && regSecretDisplay) {
    regBtnCopySecret.addEventListener('click', () => {
        navigator.clipboard.writeText(regSecretDisplay.textContent).then(() => {
            const originalText = regBtnCopySecret.textContent;
            regBtnCopySecret.textContent = (typeof I18N !== 'undefined' && I18N.two_fa_copied) ? I18N.two_fa_copied : '✅ Kopirano!';
            setTimeout(() => { regBtnCopySecret.textContent = originalText; }, 1500);
        });
    });
}

if (regBtnRegenSecret) {
    regBtnRegenSecret.addEventListener('click', () => {
        osveziReg2FaSecret();
        if (regTwoFaCode) regTwoFaCode.value = '';
    });
}

if (profBtnCopySecret && profSecretDisplay) {
    profBtnCopySecret.addEventListener('click', () => {
        navigator.clipboard.writeText(profSecretDisplay.textContent).then(() => {
            const originalText = profBtnCopySecret.textContent;
            profBtnCopySecret.textContent = (typeof I18N !== 'undefined' && I18N.two_fa_copied) ? I18N.two_fa_copied : '✅ Kopirano!';
            setTimeout(() => { profBtnCopySecret.textContent = originalText; }, 1500);
        });
    });
}

if (profBtnRegenSecret) {
    profBtnRegenSecret.addEventListener('click', () => {
        osveziProf2FaSecret();
        if (profTwoFaCode) profTwoFaCode.value = '';
    });
}

if (loginTwoFaCode && loginForm) {
    loginTwoFaCode.addEventListener('input', function() {
        if (this.value.length === 6) {
            loginForm.requestSubmit();
        }
    });
}

// Elementi vmesnika nadzorne plošče
const viewTrans = document.getElementById('view-transactions');
const viewProfile = document.getElementById('view-profile');
const btnNavTrans = document.getElementById('btn-nav-trans');
const btnNavProfile = document.getElementById('btn-nav-profile');
const dashMsg = document.getElementById('dashboard-msg');

// Elementi za filtre in izbire
const selectMonthFilter = document.getElementById('select-month-filter');
const mobileFilterSelect = document.getElementById('mobile-filter-select');

// Nastavi privzeto izbiro meseca na trenutni mesec
if (selectMonthFilter && (!selectMonthFilter.value || selectMonthFilter.value === 'vse')) {
    const currentMonthNum = String(new Date().getMonth() + 1).padStart(2, '0');
    selectMonthFilter.value = currentMonthNum;
}

// Elementi za prenos položnic / naročnin iz prejšnjega meseca
const btnCopyPrevMonth = document.getElementById('btn-copy-prev-month');
const lblBtnCopy = document.getElementById('lbl-btn-copy');
const modalCopyPrev = document.getElementById('modal-copy-prev-month');
const copyModalTitle = document.getElementById('copy-modal-title');
const copyModalDesc = document.getElementById('copy-modal-desc');
const copyItemsContainer = document.getElementById('copy-items-container');
const btnCopyToggleAll = document.getElementById('btn-copy-toggle-all');
const copySelectedCount = document.getElementById('copy-selected-count');
const btnSubmitCopyPrev = document.getElementById('btn-submit-copy-prev');
const copyModalMsg = document.getElementById('copy-modal-msg');

// Elementi za urejanje profila
const btnShowNameBox = document.getElementById('btn-show-name-box');
const boxChangeName = document.getElementById('box-change-name');
const btnCancelName = document.getElementById('btn-cancel-name');
const formChangeName = document.getElementById('form-change-name');
const profNovoIme = document.getElementById('prof-novo-ime');
const btnShowEmailBox = document.getElementById('btn-show-email-box');
const boxChangeEmail = document.getElementById('box-change-email');
const btnCancelEmail = document.getElementById('btn-cancel-email');
const formChangeEmail = document.getElementById('form-change-email');
const profilePasswordForm = document.getElementById('profile-password-form');

// Elementi za vnos transakcij
const actionBar = document.getElementById('action-bar');
const formTrans = document.getElementById('form-transaction');
const transMsg = document.getElementById('trans-msg');

// Globalno stanje
let vseTransakcije = [];
let aktivnaKategorija = 'vse';

// Pomožna funkcija za varno branje številk
function pocistiZnesek(vrednost) {
    if (!vrednost) return 0;
    let str = vrednost.toString().trim();
    if (str.includes('.') && str.includes(',')) {
        if (str.lastIndexOf(',') > str.lastIndexOf('.')) {
            str = str.replace(/\./g, '').replace(',', '.');
        } else {
            str = str.replace(/,/g, '');
        }
    } else if (str.includes(',')) {
        str = str.replace(',', '.');
    }
    return parseFloat(str) || 0;
}

// Pomožna funkcija za pridobitev trenutnega uporabnika
function pridobiTrenutnegaUporabnika() {
    try {
        const user = localStorage.getItem('banka_user');
        if (user) return JSON.parse(user);
    } catch (e) {
        return null;
    }
    return null;
}

// Preveri obstoječo sejo ob zagonu
const shranjen = pridobiTrenutnegaUporabnika();
if (shranjen) {
    prikaziDashboard(shranjen);
} else {
    document.body.classList.add('auth-mode');
}

// 4. PREKLOP MED PRIJAVO IN REGISTRACIJO
if (switchToRegister) {
    switchToRegister.addEventListener('click', (e) => {
        e.preventDefault();
        if (loginMsg) {
            loginMsg.textContent = '';
            loginMsg.style.display = 'none';
        }
        if (regMsg) {
            regMsg.textContent = '';
            regMsg.style.display = 'none';
        }
        if (loginBox) loginBox.classList.add('hidden');
        if (registerBox) {
            registerBox.classList.remove('hidden');
            if (!regTwoFaSecret || !regTwoFaSecret.value) {
                osveziReg2FaSecret();
            }
            if (regIme) regIme.focus();
        }
    });
}

if (switchToLogin) {
    switchToLogin.addEventListener('click', (e) => {
        e.preventDefault();
        if (loginMsg) {
            loginMsg.textContent = '';
            loginMsg.style.display = 'none';
        }
        if (regMsg) {
            regMsg.textContent = '';
            regMsg.style.display = 'none';
        }
        if (registerBox) registerBox.classList.add('hidden');
        if (loginBox) {
            loginBox.classList.remove('hidden');
            if (loginEmail) loginEmail.focus();
        }
    });
}

// 5. ODDAJA OBRAZCA ZA PRIJAVO (LOGIN)
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (loginMsg) {
            loginMsg.textContent = '';
            loginMsg.style.display = 'none';
        }

        const emailVal = loginEmail ? loginEmail.value.trim() : '';
        const gesloVal = loginGeslo ? loginGeslo.value : '';

        const podatki = {
            email: emailVal,
            geslo: gesloVal,
            two_fa_code: loginTwoFaCode ? loginTwoFaCode.value.trim() : '',
            website: loginWebsite ? loginWebsite.value : ''
        };

        try {
            const res = await fetch('login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(podatki)
            });

            const rezultat = await res.json();

            if (rezultat.status === '2fa_required') {
                if (polje2faLogin) {
                    polje2faLogin.classList.remove('hidden');
                }
                prikaziLoginSporocilo(rezultat.message, false);
                if (loginTwoFaCode) {
                    loginTwoFaCode.focus();
                }
                if (gesloVal && emailVal) {
                    await ZKCrypto.deriveKey(gesloVal, emailVal);
                }
                return;
            }

            if (!res.ok || rezultat.status === 'error') {
                prikaziLoginSporocilo(rezultat.message || (jeAnglescina ? 'Login failed.' : 'Napaka pri prijavi.'), true);
                return;
            }

            prikaziLoginSporocilo(rezultat.message, false);

            if (rezultat.user) {
                // Izpeljava Master ZK Ključa v brskalniku
                if (gesloVal && emailVal) {
                    await ZKCrypto.deriveKey(gesloVal, emailVal);
                } else {
                    await ZKCrypto.restoreSessionKey();
                }

                if (polje2faLogin) polje2faLogin.classList.add('hidden');
                if (loginTwoFaCode) loginTwoFaCode.value = '';
                if (loginGeslo) loginGeslo.value = '';
                localStorage.setItem('banka_user', JSON.stringify(rezultat.user));

                // Dešifriraj shranjen bančni profil iz baze podatkov
                if (rezultat.user.encrypted_profile && rezultat.user.bank_iv) {
                    await naloziBancniProfilStrežnik(rezultat.user);
                }

                prikaziDashboard(rezultat.user);
            }

        } catch (err) {
            prikaziLoginSporocilo(t.conn_error, true);
        }
    });
}

// 5.1 ODDAJA OBRAZCA ZA REGISTRACIJO (REGISTER)
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (regMsg) {
            regMsg.textContent = '';
            regMsg.style.display = 'none';
        }

        const gesloVal = regGeslo ? regGeslo.value : '';
        const potrdiVal = regPotrdi ? regPotrdi.value : '';

        if (gesloVal !== potrdiVal) {
            prikaziRegSporocilo(t.pass_mismatch, true);
            return;
        }

        const podatki = {
            ime: regIme ? regIme.value.trim() : '',
            email: regEmail ? regEmail.value.trim() : '',
            geslo: gesloVal,
            potrdiGeslo: potrdiVal,
            website: regWebsite ? regWebsite.value : '',
            two_fa_secret: regTwoFaSecret ? regTwoFaSecret.value : '',
            two_fa_code: regTwoFaCode ? regTwoFaCode.value.trim() : ''
        };

        try {
            const res = await fetch('register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(podatki)
            });

            const rezultat = await res.json();

            if (!res.ok || rezultat.status === 'error') {
                prikaziRegSporocilo(rezultat.message || (jeAnglescina ? 'Registration failed.' : 'Napaka pri registraciji.'), true);
                return;
            }

            prikaziRegSporocilo(rezultat.message, false);
            if (regGeslo) regGeslo.value = '';
            if (regPotrdi) regPotrdi.value = '';
            if (regTwoFaCode) regTwoFaCode.value = '';

        } catch (err) {
            prikaziRegSporocilo(t.conn_error, true);
        }
    });
}

// Pomožne funkcije za sporočila
function prikaziLoginSporocilo(tekst, jeNapaka) {
    if (!loginMsg) return;
    loginMsg.textContent = tekst;
    loginMsg.className = 'message ' + (jeNapaka ? 'error' : 'success');
    loginMsg.style.display = 'block';
}

function prikaziRegSporocilo(tekst, jeNapaka) {
    if (!regMsg) return;
    regMsg.textContent = tekst;
    regMsg.className = 'message ' + (jeNapaka ? 'error' : 'success');
    regMsg.style.display = 'block';
}

function showDashMessage(tekst, jeNapaka) {
    if (!dashMsg) return;
    dashMsg.textContent = tekst;
    dashMsg.className = 'message ' + (jeNapaka ? 'error' : 'success');
    dashMsg.style.display = 'block';
    setTimeout(() => { dashMsg.style.display = 'none'; }, 5000);
}

// Prikaz nadzorne plošče
async function prikaziDashboard(uporabnik) {
    document.body.classList.remove('auth-mode');
    authSection.classList.add('hidden');
    dashboardSection.classList.remove('hidden');

    if (actionBar) actionBar.classList.remove('hidden');

    if (document.getElementById('user-greeting') && uporabnik.ime) {
        document.getElementById('user-greeting').textContent = `${t.greeting}, ${uporabnik.ime}!`;
    }
    if (document.getElementById('disp-ime') && uporabnik.ime) {
        document.getElementById('disp-ime').textContent = uporabnik.ime;
    }
    if (document.getElementById('disp-email') && uporabnik.email) {
        document.getElementById('disp-email').textContent = uporabnik.email;
    }

    if (btnNavTrans) btnNavTrans.classList.add('active');
    if (btnNavProfile) {
        btnNavProfile.classList.remove('active');
        btnNavProfile.classList.remove('hidden');
    }
    if (viewTrans) viewTrans.classList.remove('hidden');
    if (viewProfile) viewProfile.classList.add('hidden');

    // Privzeto nastavi mesec na trenutni koledarski mesec, če še ni izbran
    const currentMonthNum = String(new Date().getMonth() + 1).padStart(2, '0');
    if (selectMonthFilter && (!selectMonthFilter.value || selectMonthFilter.value === 'vse')) {
        selectMonthFilter.value = currentMonthNum;
    }

    posodobiSidebarNazive();
    posodobiBancniPrikaz();
    await ZKCrypto.restoreSessionKey();
    await naloziProfil();
    await naloziTransakcije();
}

// 6. ODJAVA
if (btnLogout) {
    btnLogout.addEventListener('click', () => {
        ZKCrypto.clearKey();
        localStorage.removeItem('banka_user');
        sessionStorage.clear();
        vseTransakcije = [];
        aktivnaKategorija = 'vse';

        // Počisti tabelo transakcij in sporočila
        const tbody = document.getElementById('trans-body');
        if (tbody) tbody.innerHTML = '';

        // Ponastavi vsebino vseh polj in statistik
        if (document.getElementById('user-greeting')) document.getElementById('user-greeting').textContent = '';
        if (document.getElementById('disp-ime')) document.getElementById('disp-ime').textContent = '';
        if (document.getElementById('disp-email')) document.getElementById('disp-email').textContent = '';
        if (document.getElementById('stat-zacetno')) document.getElementById('stat-zacetno').textContent = '0.00 €';
        if (document.getElementById('stat-placa')) document.getElementById('stat-placa').textContent = '0.00 €';
        if (document.getElementById('stat-krediti')) document.getElementById('stat-krediti').textContent = '- 0.00 €';
        if (document.getElementById('stat-stroski')) document.getElementById('stat-stroski').textContent = '- 0.00 €';
        if (document.getElementById('stat-obresti')) document.getElementById('stat-obresti').textContent = '- 0.00 €';
        if (document.getElementById('stat-varcevanje')) document.getElementById('stat-varcevanje').textContent = '- 0.00 €';
        if (document.getElementById('stat-prosto')) document.getElementById('stat-prosto').textContent = '0.00 €';

        document.body.classList.add('auth-mode');
        dashboardSection.classList.add('hidden');
        if (actionBar) actionBar.classList.add('hidden');
        authSection.classList.remove('hidden');
        if (loginBox) loginBox.classList.remove('hidden');
        if (registerBox) registerBox.classList.add('hidden');
        if (loginEmail) loginEmail.value = '';
        if (loginGeslo) loginGeslo.value = '';
        if (polje2faLogin) polje2faLogin.classList.add('hidden');
        if (loginTwoFaCode) loginTwoFaCode.value = '';
        if (loginMsg) loginMsg.style.display = 'none';
        if (regMsg) regMsg.style.display = 'none';
        if (dashMsg) dashMsg.style.display = 'none';
    });
}

// 7. PREKLOP ZAVIHKOV V NADZORNI PLOŠČI
if (btnNavTrans && btnNavProfile) {
    btnNavTrans.addEventListener('click', () => {
        viewTrans.classList.remove('hidden');
        viewProfile.classList.add('hidden');
        btnNavTrans.classList.add('active');
        btnNavProfile.classList.remove('active');
        naloziTransakcije();
    });

    btnNavProfile.addEventListener('click', () => {
        viewTrans.classList.add('hidden');
        viewProfile.classList.remove('hidden');
        btnNavProfile.classList.add('active');
        btnNavTrans.classList.remove('active');
        naloziProfil();
    });
}

// 8. NALAGANJE IN FILTRIRANJE TRANSAKCIJ (MESEC + KATEGORIJA)
async function naloziTransakcije() {
    const user = pridobiTrenutnegaUporabnika() || {};
    if (!user.id) return;

    try {
        const res = await fetch(`transactions.php?action=get_transactions&user_id=${user.id}`);
        const data = await res.json();

        if (res.ok && data.status === 'success') {
            const rawList = data.transactions || [];
            // Zero-Knowledge dešifriranje transakcij v brskalniku
            const decryptedList = await Promise.all(rawList.map(async (item) => {
                if (item.encrypted_data && item.iv) {
                    try {
                        const dec = await ZKCrypto.decrypt(item.encrypted_data, item.iv);
                        if (dec && typeof dec === 'object') {
                            return {
                                id: item.id,
                                opis: dec.opis ?? item.opis ?? '',
                                znesek: dec.znesek ?? item.znesek ?? 0,
                                vrsta: dec.vrsta ?? item.vrsta ?? 'odliv',
                                kategorija: dec.kategorija ?? item.kategorija ?? 'ostalo',
                                datum: dec.datum ?? item.datum
                            };
                        }
                    } catch (decErr) {
                        console.error('Napaka pri dešifriranju transakcije ID ' + item.id, decErr);
                    }
                }
                return item;
            }));

            vseTransakcije = decryptedList;
            uveljaviFiltreInIzrisi();
        }
    } catch (e) {
        console.error('Napaka pri nalaganju transakcij:', e);
    }
}

// Funkcija za hkratno filtriranje po mesecu in kategoriji ter izris v tabelo
function uveljaviFiltreInIzrisi() {
    const tbody = document.getElementById('trans-body');
    if (!tbody) return;

    const izbranMesec = selectMonthFilter ? selectMonthFilter.value : 'vse';

    // Dvojni filter: MESEC + KATEGORIJA
    const filtriraneTransakcije = vseTransakcije.filter(tItem => {
        let ustrezaMesec = true;
        if (izbranMesec !== 'vse' && tItem.datum) {
            ustrezaMesec = (tItem.datum.split('-')[1] === izbranMesec);
        }

        let ustrezaKategorija = true;
        if (aktivnaKategorija !== 'vse') {
            ustrezaKategorija = (tItem.kategorija === aktivnaKategorija);
        }

        return ustrezaMesec && ustrezaKategorija;
    });

    posodobiBancniPrikaz(filtriraneTransakcije);
    posodobiGumbZaPrenos();

    tbody.innerHTML = '';

    if (filtriraneTransakcije.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">${t.no_transactions}</td></tr>`;
        return;
    }

    filtriraneTransakcije.forEach(tItem => {
        const tr = document.createElement('tr');
        const znesekBarva = tItem.vrsta === 'priliv' ? '#16a34a' : '#dc2626';
        const znesekZnak = tItem.vrsta === 'priliv' ? '+' : '-';
        const znesekFormat = `${znesekZnak} ${parseFloat(tItem.znesek).toFixed(2)} €`;

        const labelVrsta = tItem.vrsta === 'priliv'
            ? (jeAnglescina ? 'Income' : 'Priliv')
            : (jeAnglescina ? 'Expense' : 'Odliv');

        const katNaziv = pridobiNazivKategorije(tItem.kategorija);
        const katIkona = pridobiIkonoKategorije(tItem.kategorija);
        const btnEditTitle = (typeof I18N !== 'undefined' && I18N.modal_edit_trans_title) ? I18N.modal_edit_trans_title : (jeAnglescina ? 'Edit transaction' : 'Uredi transakcijo');
        const btnEditText = (typeof I18N !== 'undefined' && I18N.btn_edit) ? I18N.btn_edit : (jeAnglescina ? 'Edit' : 'Uredi');

        tr.innerHTML = `
            <td>
                <div class="trans-desc-cell">
                    <span class="trans-desc-text">${tItem.opis}</span>
                    <button type="button" class="btn-edit-trans" data-id="${tItem.id}" title="${btnEditTitle}">✏️ ${btnEditText}</button>
                </div>
            </td>
            <td style="font-weight:bold; color:${znesekBarva}; text-align:right;">${znesekFormat}</td>
            <td><span class="badge ${tItem.vrsta}">${labelVrsta}</span></td>
            <td><span class="badge" style="background:var(--hover-bg);">${katIkona} ${katNaziv}</span></td>
            <td>${tItem.datum}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Poslušalec za spremembo izbranega meseca v stranskem traku
if (selectMonthFilter) {
    selectMonthFilter.addEventListener('change', () => {
        uveljaviFiltreInIzrisi();
    });
}

// Funkcija za povezavo klikov na gumbe filtrov
function poveziGumbeFiltrov() {
    document.querySelectorAll('.desktop-filters .filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.desktop-filters .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            aktivnaKategorija = btn.dataset.category || 'vse';

            const badge = document.getElementById('active-filter-badge');
            if (badge) badge.textContent = btn.innerText.replace(/^[^\s]+\s/, '');

            if (mobileFilterSelect) {
                mobileFilterSelect.value = aktivnaKategorija;
            }

            uveljaviFiltreInIzrisi();
        });
    });
}

// Poslušalec za mobilni dropdown (Mobile)
if (mobileFilterSelect) {
    mobileFilterSelect.addEventListener('change', (e) => {
        aktivnaKategorija = e.target.value;

        const badge = document.getElementById('active-filter-badge');
        if (badge) {
            badge.textContent = e.target.options[e.target.selectedIndex].text.replace(/^[^\s]+\s/, '');
        }

        document.querySelectorAll('.desktop-filters .filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === aktivnaKategorija);
        });

        uveljaviFiltreInIzrisi();
    });
}

// Pomožna funkcija za formatiranje datuma članstva (evropski vs. ameriški format)
function formatirajDatumClanstva(datumStr) {
    if (!datumStr) return '';
    const datePart = datumStr.split(/[ T]/)[0];
    const deli = datePart.split('-');
    if (deli.length === 3) {
        const [y, m, d] = deli;
        const dan = d.padStart(2, '0');
        const mesec = m.padStart(2, '0');
        const leto = y;
        if (jeAnglescina) {
            // US format: MM/DD/YYYY (npr. 09/13/2026)
            return `${mesec}/${dan}/${leto}`;
        } else {
            // Evropski format: DD.MM.YYYY (npr. 13.09.2026)
            return `${dan}.${mesec}.${leto}`;
        }
    }
    return datumStr;
}

// 9. NALAGANJE PODATKOV PROFILA
async function naloziProfil() {
    const user = pridobiTrenutnegaUporabnika() || {};
    if (!user.id) return;

    // Takojšnji prikaz shranjenih podatkov uporabnika za brezhibno uporabniško izkušnjo
    if (document.getElementById('disp-ime') && user.ime) {
        document.getElementById('disp-ime').textContent = user.ime;
    }
    if (document.getElementById('disp-email') && user.email) {
        document.getElementById('disp-email').textContent = user.email;
    }
    if (document.getElementById('user-greeting') && user.ime) {
        document.getElementById('user-greeting').textContent = `${t.greeting}, ${user.ime}!`;
    }
    if (document.getElementById('profile-created') && user.ustvarjen) {
        const label = (t.member_since || (jeAnglescina ? 'Member since:' : 'Član od:')).trim().replace(/:$/, '');
        document.getElementById('profile-created').textContent = `${label}: ${formatirajDatumClanstva(user.ustvarjen)}`;
    }

    try {
        const res = await fetch(`profile.php?action=get&user_id=${user.id}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            if (document.getElementById('disp-ime') && data.user.ime) document.getElementById('disp-ime').textContent = data.user.ime;
            if (document.getElementById('disp-email') && data.user.email) document.getElementById('disp-email').textContent = data.user.email;
            if (document.getElementById('user-greeting') && data.user.ime) {
                document.getElementById('user-greeting').textContent = `${t.greeting}, ${data.user.ime}!`;
            }
            if (document.getElementById('profile-created') && data.user.ustvarjen) {
                const label = (t.member_since || (jeAnglescina ? 'Member since:' : 'Član od:')).trim().replace(/:$/, '');
                document.getElementById('profile-created').textContent = `${label}: ${formatirajDatumClanstva(data.user.ustvarjen)}`;
            }

            const hiddenUserField = document.getElementById('prof-username-hidden');
            if (hiddenUserField) {
                hiddenUserField.value = data.user.email;
            }

            // Posodobitev 2FA prikaza v profilu
            if (disp2faBadge) {
                if (data.user.has_2fa) {
                    disp2faBadge.className = 'two-fa-badge active';
                    disp2faBadge.textContent = (typeof I18N !== 'undefined' && I18N.two_fa_status_active) ? I18N.two_fa_status_active : '2FA je vklopljen 🛡️';
                    if (btnDisableProfile2fa) btnDisableProfile2fa.classList.remove('hidden');
                } else {
                    disp2faBadge.className = 'two-fa-badge inactive';
                    disp2faBadge.textContent = (typeof I18N !== 'undefined' && I18N.two_fa_status_inactive) ? I18N.two_fa_status_inactive : '2FA ni nastavljen';
                    if (btnDisableProfile2fa) btnDisableProfile2fa.classList.add('hidden');
                }
            }

            user.email = data.user.email;
            user.ime = data.user.ime;
            user.ustvarjen = data.user.ustvarjen;
            user.is_admin = data.user.is_admin ?? user.is_admin ?? 0;
            localStorage.setItem('banka_user', JSON.stringify(user));

            // Dešifriraj in naloži bančni profil s strežnika
            if (data.user.encrypted_profile && data.user.bank_iv) {
                await naloziBancniProfilStrežnik(data.user);
            }
        }
    } catch (e) {
        showDashMessage(jeAnglescina ? 'Error loading profile.' : 'Napaka pri nalaganju profila.', true);
    }
}

// 10. UPRAVLJANJE 2FA V PROFILU
if (btnToggleProfile2fa) {
    btnToggleProfile2fa.addEventListener('click', () => {
        if (boxManage2fa) {
            const isHidden = boxManage2fa.classList.toggle('hidden');
            if (!isHidden) {
                osveziProf2FaSecret();
                if (profTwoFaCode) profTwoFaCode.value = '';
                if (profTwoFaPass) profTwoFaPass.value = '';
            }
        }
    });
}

if (btnCancelProfile2fa) {
    btnCancelProfile2fa.addEventListener('click', () => {
        if (boxManage2fa) boxManage2fa.classList.add('hidden');
        if (formProfile2fa) formProfile2fa.reset();
    });
}

if (formProfile2fa) {
    formProfile2fa.addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = JSON.parse(localStorage.getItem('banka_user') || '{}');
        const code = profTwoFaCode ? profTwoFaCode.value.trim() : '';
        const pass = profTwoFaPass ? profTwoFaPass.value : '';
        const secret = profTwoFaSecret ? profTwoFaSecret.value : '';

        try {
            const res = await fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_2fa',
                    user_id: user.id,
                    two_fa_secret: secret,
                    two_fa_code: code,
                    geslo: pass
                })
            });
            const data = await res.json();
            if (res.ok && data.status === 'success') {
                showDashMessage(data.message, false);
                formProfile2fa.reset();
                if (boxManage2fa) boxManage2fa.classList.add('hidden');
                naloziProfil();
            } else {
                showDashMessage(data.message || (jeAnglescina ? 'Failed to update 2FA.' : 'Napaka pri posodobitvi 2FA.'), true);
            }
        } catch (err) {
            showDashMessage(t.conn_error, true);
        }
    });
}

if (btnDisableProfile2fa) {
    btnDisableProfile2fa.addEventListener('click', async () => {
        const pass = prompt(jeAnglescina ? 'Enter your current password to disable 2FA:' : 'Vnesite vaše trenutno geslo za izklop 2FA:');
        if (!pass) return;

        const user = JSON.parse(localStorage.getItem('banka_user') || '{}');
        try {
            const res = await fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'disable_2fa',
                    user_id: user.id,
                    geslo: pass
                })
            });
            const data = await res.json();
            if (res.ok && data.status === 'success') {
                showDashMessage(data.message, false);
                if (boxManage2fa) boxManage2fa.classList.add('hidden');
                naloziProfil();
            } else {
                showDashMessage(data.message || (jeAnglescina ? 'Failed to disable 2FA.' : 'Napaka pri izklopu 2FA.'), true);
            }
        } catch (err) {
            showDashMessage(t.conn_error, true);
        }
    });
}

// 11. SPREMEMBA IMENA IN PRIIMKA
if (btnShowNameBox) {
    btnShowNameBox.addEventListener('click', () => {
        const user = JSON.parse(localStorage.getItem('banka_user') || '{}');
        if (profNovoIme && user.ime) profNovoIme.value = user.ime;
        boxChangeName.classList.remove('hidden');
        btnShowNameBox.classList.add('hidden');
    });
}

if (btnCancelName) {
    btnCancelName.addEventListener('click', () => {
        boxChangeName.classList.add('hidden');
        btnShowNameBox.classList.remove('hidden');
        if (formChangeName) formChangeName.reset();
    });
}

if (formChangeName) {
    formChangeName.addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = JSON.parse(localStorage.getItem('banka_user') || '{}');
        const novoIme = profNovoIme ? profNovoIme.value.trim() : '';

        if (!novoIme) return;

        try {
            const res = await fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_name',
                    user_id: user.id,
                    ime: novoIme
                })
            });
            const data = await res.json();

            if (res.ok && data.status === 'success') {
                showDashMessage(data.message, false);
                user.ime = novoIme;
                localStorage.setItem('banka_user', JSON.stringify(user));
                if (document.getElementById('disp-ime')) document.getElementById('disp-ime').textContent = novoIme;
                if (document.getElementById('user-greeting')) document.getElementById('user-greeting').textContent = `${t.greeting}, ${novoIme}!`;
                boxChangeName.classList.add('hidden');
                btnShowNameBox.classList.remove('hidden');
            } else {
                showDashMessage(data.message || (jeAnglescina ? 'Failed to update name.' : 'Napaka pri posodobitvi imena.'), true);
            }
        } catch (err) {
            showDashMessage(jeAnglescina ? 'System error updating name.' : 'Sistemska napaka pri posodobitvi imena.', true);
        }
    });
}

// 12. SPREMEMBA E-POŠTE
if (btnShowEmailBox) {
    btnShowEmailBox.addEventListener('click', () => {
        boxChangeEmail.classList.remove('hidden');
        btnShowEmailBox.classList.add('hidden');
    });
}

if (btnCancelEmail) {
    btnCancelEmail.addEventListener('click', () => {
        boxChangeEmail.classList.add('hidden');
        btnShowEmailBox.classList.remove('hidden');
        if (formChangeEmail) formChangeEmail.reset();
    });
}

if (formChangeEmail) {
    formChangeEmail.addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = JSON.parse(localStorage.getItem('banka_user') || '{}');
        const noviEmail = document.getElementById('prof-novi-email').value.trim();

        try {
            const res = await fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'request_email_change',
                    user_id: user.id,
                    novi_email: noviEmail
                })
            });
            const data = await res.json();

            if (res.ok && data.status === 'success') {
                showDashMessage(data.message, false);
                formChangeEmail.reset();
                boxChangeEmail.classList.add('hidden');
                btnShowEmailBox.classList.remove('hidden');
            } else {
                showDashMessage(data.message || (jeAnglescina ? 'Email change request failed.' : 'Napaka pri zahtevi za spremembo e-pošte.'), true);
            }
        } catch (err) {
            showDashMessage(jeAnglescina ? 'System error submitting request.' : 'Sistemska napaka pri pošiljanju zahteve.', true);
        }
    });
}

// 11. SPREMEMBA GESLA
if (profilePasswordForm) {
    profilePasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = JSON.parse(localStorage.getItem('banka_user') || '{}');
        const staroGeslo = document.getElementById('prof-staro-geslo').value;
        const novoGeslo = document.getElementById('prof-novo-geslo').value;
        const potrdiNovo = document.getElementById('prof-potrdi-geslo').value;

        if (novoGeslo !== potrdiNovo) {
            showDashMessage(t.pass_mismatch, true);
            return;
        }

        try {
            const res = await fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'request_password_change',
                    user_id: user.id,
                    staro_geslo: staroGeslo,
                    novo_geslo: novoGeslo,
                    potrdi_novo_geslo: potrdiNovo
                })
            });
            const data = await res.json();

            if (res.ok && data.status === 'success') {
                showDashMessage(data.message, false);
                profilePasswordForm.reset();
            } else {
                showDashMessage(data.message || (jeAnglescina ? 'Password change failed.' : 'Napaka pri spremembi gesla.'), true);
            }
        } catch (err) {
            showDashMessage(jeAnglescina ? 'System error submitting request.' : 'Sistemska napaka pri spremembi gesla.', true);
        }
    });
}

// 12. ODDAJA NOVE TRANSAKCIJE
const transMesec = document.getElementById('trans-mesec');
const transDatum = document.getElementById('trans-datum');
const transKategorija = document.getElementById('trans-kategorija');
const btnToggleCustomCat = document.getElementById('btn-toggle-custom-cat');
const boxCustomCat = document.getElementById('box-custom-cat');
const transNovaKat = document.getElementById('trans-nova-kategorija');
const btnSaveCustomCat = document.getElementById('btn-save-custom-cat');

// Sinhronizacija med izbirnikom meseca in datumom v oknu za vpis
if (transMesec && transDatum) {
    transMesec.addEventListener('change', () => {
        const izbranM = transMesec.value;
        const val = transDatum.value || new Date().toISOString().split('T')[0];
        const parts = val.split('-');
        const y = parts[0] || new Date().getFullYear();
        let d = parts[2] || '01';

        const maxDni = new Date(parseInt(y, 10), parseInt(izbranM, 10), 0).getDate();
        if (parseInt(d, 10) > maxDni) {
            d = String(maxDni).padStart(2, '0');
        }
        transDatum.value = `${y}-${izbranM}-${d}`;
    });

    transDatum.addEventListener('change', () => {
        if (transDatum.value) {
            const m = transDatum.value.split('-')[1];
            if (m && transMesec) {
                transMesec.value = m;
            }
        }
    });
}

// Upravljanje dodajanja lastne kategorije
if (btnToggleCustomCat) {
    btnToggleCustomCat.addEventListener('click', () => {
        if (boxCustomCat) {
            boxCustomCat.classList.toggle('hidden');
            if (!boxCustomCat.classList.contains('hidden') && transNovaKat) {
                transNovaKat.focus();
            }
        }
    });
}

if (transKategorija) {
    transKategorija.addEventListener('change', () => {
        if (transKategorija.value === '__nova__') {
            if (boxCustomCat) {
                boxCustomCat.classList.remove('hidden');
                if (transNovaKat) transNovaKat.focus();
            }
        } else {
            if (boxCustomCat) {
                boxCustomCat.classList.add('hidden');
            }
        }
    });
}

function shraniNovoKategorijoPoMeri() {
    if (!transNovaKat) return;
    const naziv = transNovaKat.value.trim();
    if (!naziv) return;

    const customCats = pridobiCustomKategorije();
    // Preveri, če že obstaja
    let obstojeca = customCats.find(c => c.name.toLowerCase() === naziv.toLowerCase());
    let catId = obstojeca ? obstojeca.id : null;

    if (!catId) {
        catId = 'cat_' + Date.now();
        customCats.push({
            id: catId,
            name: naziv,
            icon: '🏷️'
        });
        shraniCustomKategorije(customCats);
    }

    posodobiSidebarNazive();

    if (transKategorija) {
        transKategorija.value = catId;
    }

    transNovaKat.value = '';
    if (boxCustomCat) {
        boxCustomCat.classList.add('hidden');
    }
}

if (btnSaveCustomCat) {
    btnSaveCustomCat.addEventListener('click', (e) => {
        e.preventDefault();
        shraniNovoKategorijoPoMeri();
    });
}

if (transNovaKat) {
    transNovaKat.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            shraniNovoKategorijoPoMeri();
        }
    });
}

if (formTrans) {
    formTrans.addEventListener('submit', async (e) => {
        e.preventDefault();

        const user = pridobiTrenutnegaUporabnika() || {};
        if (!user.id) return;

        let katVal = transKategorija ? transKategorija.value : 'ostalo';
        if (katVal === '__nova__') {
            katVal = 'ostalo';
        }

        let datumVal = transDatum ? transDatum.value : '';
        if (!datumVal) {
            const y = new Date().getFullYear();
            const m = transMesec ? transMesec.value : String(new Date().getMonth() + 1).padStart(2, '0');
            const d = String(new Date().getDate()).padStart(2, '0');
            datumVal = `${y}-${m}-${d}`;
        }

        const opisVal = document.getElementById('trans-opis').value.trim();
        const znesekVal = pocistiZnesek(document.getElementById('trans-znesek').value);
        const vrstaVal = document.getElementById('trans-vrsta').value;

        try {
            // Zero-Knowledge šifriranje transakcije na odjemalcu
            const enc = await ZKCrypto.encrypt({
                opis: opisVal,
                znesek: znesekVal.toFixed(2),
                vrsta: vrstaVal,
                kategorija: katVal,
                datum: datumVal
            });

            const podatki = {
                action: 'add_transaction',
                user_id: user.id,
                encrypted_data: enc.encrypted_data,
                iv: enc.iv,
                datum: datumVal
            };

            const res = await fetch('transactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(podatki)
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                transMsg.textContent = data.message;
                transMsg.className = 'message error';
                transMsg.style.display = 'block';
                return;
            }

            transMsg.textContent = t.trans_saved;
            transMsg.className = 'message success';
            transMsg.style.display = 'block';

            await naloziTransakcije();

            setTimeout(() => {
                const modalTrans = document.getElementById('modal-transaction');
                if (modalTrans) modalTrans.classList.add('hidden');
                formTrans.reset();
            }, 800);

        } catch (err) {
            transMsg.textContent = t.conn_error;
            transMsg.className = 'message error';
            transMsg.style.display = 'block';
        }
    });
}

// 12.1 UREJANJE IN BRISANJE TRANSAKCIJE
const modalEditTrans = document.getElementById('modal-edit-transaction');
const formEditTrans = document.getElementById('form-edit-transaction');
const editTransId = document.getElementById('edit-trans-id');
const editTransOpis = document.getElementById('edit-trans-opis');
const editTransZnesek = document.getElementById('edit-trans-znesek');
const editTransVrsta = document.getElementById('edit-trans-vrsta');
const editTransKategorija = document.getElementById('edit-trans-kategorija');
const editTransDatum = document.getElementById('edit-trans-datum');
const editTransMsg = document.getElementById('edit-trans-msg');
const btnDeleteTrans = document.getElementById('btn-delete-trans');
const btnToggleEditCustomCat = document.getElementById('btn-toggle-edit-custom-cat');
const boxEditCustomCat = document.getElementById('box-edit-custom-cat');
const editTransNovaKat = document.getElementById('edit-trans-nova-kategorija');
const btnSaveEditCustomCat = document.getElementById('btn-save-edit-custom-cat');

if (btnToggleEditCustomCat) {
    btnToggleEditCustomCat.addEventListener('click', () => {
        if (boxEditCustomCat) {
            boxEditCustomCat.classList.toggle('hidden');
            if (!boxEditCustomCat.classList.contains('hidden') && editTransNovaKat) {
                editTransNovaKat.focus();
            }
        }
    });
}

if (editTransKategorija) {
    editTransKategorija.addEventListener('change', () => {
        if (editTransKategorija.value === '__nova__') {
            if (boxEditCustomCat) {
                boxEditCustomCat.classList.remove('hidden');
                if (editTransNovaKat) editTransNovaKat.focus();
            }
        } else {
            if (boxEditCustomCat) {
                boxEditCustomCat.classList.add('hidden');
            }
        }
    });
}

function shraniNovoKategorijoPoMeriUrejanje() {
    if (!editTransNovaKat) return;
    const naziv = editTransNovaKat.value.trim();
    if (!naziv) return;

    const customCats = pridobiCustomKategorije();
    let obstojeca = customCats.find(c => c.name.toLowerCase() === naziv.toLowerCase());
    let catId = obstojeca ? obstojeca.id : null;

    if (!catId) {
        catId = 'cat_' + Date.now();
        customCats.push({
            id: catId,
            name: naziv,
            icon: '🏷️'
        });
        shraniCustomKategorije(customCats);
    }

    posodobiSidebarNazive();

    if (editTransKategorija) {
        editTransKategorija.value = catId;
    }

    editTransNovaKat.value = '';
    if (boxEditCustomCat) {
        boxEditCustomCat.classList.add('hidden');
    }
}

if (btnSaveEditCustomCat) {
    btnSaveEditCustomCat.addEventListener('click', (e) => {
        e.preventDefault();
        shraniNovoKategorijoPoMeriUrejanje();
    });
}

if (editTransNovaKat) {
    editTransNovaKat.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            shraniNovoKategorijoPoMeriUrejanje();
        }
    });
}

function odpriUrejanjeTransakcije(transId) {
    const tItem = vseTransakcije.find(t => String(t.id) === String(transId));
    if (!tItem || !modalEditTrans) return;

    if (formEditTrans) formEditTrans.reset();
    if (editTransMsg) editTransMsg.style.display = 'none';
    if (boxEditCustomCat) boxEditCustomCat.classList.add('hidden');

    posodobiSidebarNazive();

    if (editTransId) editTransId.value = tItem.id;
    if (editTransOpis) editTransOpis.value = tItem.opis || '';
    if (editTransZnesek) editTransZnesek.value = parseFloat(tItem.znesek || 0).toFixed(2);
    if (editTransVrsta) editTransVrsta.value = tItem.vrsta || 'odliv';
    if (editTransKategorija) {
        editTransKategorija.value = tItem.kategorija || 'ostalo';
        if (!editTransKategorija.value) editTransKategorija.value = 'ostalo';
    }
    if (editTransDatum) {
        const datumVal = tItem.datum ? tItem.datum.split(' ')[0] : '';
        editTransDatum.value = datumVal;
    }

    modalEditTrans.classList.remove('hidden');
}

if (formEditTrans) {
    formEditTrans.addEventListener('submit', async (e) => {
        e.preventDefault();

        const user = pridobiTrenutnegaUporabnika() || {};
        if (!user.id) return;

        let katVal = editTransKategorija ? editTransKategorija.value : 'ostalo';
        if (katVal === '__nova__') {
            katVal = 'ostalo';
        }

        const transId = editTransId ? editTransId.value : 0;
        const opisVal = editTransOpis ? editTransOpis.value.trim() : '';
        const znesekVal = pocistiZnesek(editTransZnesek ? editTransZnesek.value : '0');
        const vrstaVal = editTransVrsta ? editTransVrsta.value : 'odliv';
        const datumVal = editTransDatum ? editTransDatum.value : '';

        try {
            // Zero-Knowledge šifriranje posodobljenih podatkov transakcije
            const enc = await ZKCrypto.encrypt({
                opis: opisVal,
                znesek: znesekVal.toFixed(2),
                vrsta: vrstaVal,
                kategorija: katVal,
                datum: datumVal
            });

            const podatki = {
                action: 'edit_transaction',
                user_id: user.id,
                id: transId,
                encrypted_data: enc.encrypted_data,
                iv: enc.iv,
                datum: datumVal
            };

            const res = await fetch('transactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(podatki)
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                if (editTransMsg) {
                    editTransMsg.textContent = data.message || (jeAnglescina ? 'Error saving transaction.' : 'Napaka pri shranjevanju transakcije.');
                    editTransMsg.className = 'message error';
                    editTransMsg.style.display = 'block';
                }
                return;
            }

            if (editTransMsg) {
                editTransMsg.textContent = (typeof I18N !== 'undefined' && I18N.trans_updated) ? I18N.trans_updated : (data.message || (jeAnglescina ? 'Transaction successfully updated!' : 'Transakcija uspešno posodobljena!'));
                editTransMsg.className = 'message success';
                editTransMsg.style.display = 'block';
            }

            await naloziTransakcije();

            setTimeout(() => {
                if (modalEditTrans) modalEditTrans.classList.add('hidden');
            }, 800);

        } catch (err) {
            if (editTransMsg) {
                editTransMsg.textContent = (typeof I18N !== 'undefined' && I18N.conn_error) ? I18N.conn_error : (jeAnglescina ? 'Connection error.' : 'Napaka pri povezavi.');
                editTransMsg.className = 'message error';
                editTransMsg.style.display = 'block';
            }
        }
    });
}

if (btnDeleteTrans) {
    btnDeleteTrans.addEventListener('click', async (e) => {
        e.preventDefault();

        const user = pridobiTrenutnegaUporabnika() || {};
        if (!user.id) return;

        const transId = editTransId ? editTransId.value : 0;
        if (!transId) return;

        const potrdiVprasanje = (typeof I18N !== 'undefined' && I18N.confirm_delete_trans)
            ? I18N.confirm_delete_trans
            : (jeAnglescina ? 'Are you sure you want to delete this transaction?' : 'Ali ste prepričani, da želite izbrisati to transakcijo?');

        if (!confirm(potrdiVprasanje)) {
            return;
        }

        try {
            const res = await fetch('transactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_transaction',
                    user_id: user.id,
                    id: transId
                })
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                if (editTransMsg) {
                    editTransMsg.textContent = data.message || (jeAnglescina ? 'Error deleting transaction.' : 'Napaka pri brisanju transakcije.');
                    editTransMsg.className = 'message error';
                    editTransMsg.style.display = 'block';
                }
                return;
            }

            if (editTransMsg) {
                editTransMsg.textContent = (typeof I18N !== 'undefined' && I18N.trans_deleted) ? I18N.trans_deleted : (data.message || (jeAnglescina ? 'Transaction successfully deleted!' : 'Transakcija uspešno izbrisana!'));
                editTransMsg.className = 'message success';
                editTransMsg.style.display = 'block';
            }

            naloziTransakcije();

            setTimeout(() => {
                if (modalEditTrans) modalEditTrans.classList.add('hidden');
            }, 800);

        } catch (err) {
            if (editTransMsg) {
                editTransMsg.textContent = (typeof I18N !== 'undefined' && I18N.conn_error) ? I18N.conn_error : (jeAnglescina ? 'Connection error.' : 'Napaka pri povezavi.');
                editTransMsg.className = 'message error';
                editTransMsg.style.display = 'block';
            }
        }
    });
}

// 13. PRENOS POLOŽNIC IN NAROČNIN IZ PREJŠNJEGA MESECA
const imenaMesecev = {
    sl: {
        '01': 'Januar', '02': 'Februar', '03': 'Marec', '04': 'April',
        '05': 'Maj', '06': 'Junij', '07': 'Julij', '08': 'Avgust',
        '09': 'September', '10': 'Oktober', '11': 'November', '12': 'December'
    },
    en: {
        '01': 'January', '02': 'February', '03': 'March', '04': 'April',
        '05': 'May', '06': 'June', '07': 'July', '08': 'August',
        '09': 'September', '10': 'October', '11': 'November', '12': 'December'
    }
};

let najdeneTransZaPrenos = [];

function posodobiGumbZaPrenos() {
    if (!btnCopyPrevMonth || !lblBtnCopy) return;

    if (aktivnaKategorija === 'poloznice') {
        lblBtnCopy.textContent = (typeof I18N !== 'undefined' && I18N.btn_copy_bills) ? I18N.btn_copy_bills : (jeAnglescina ? '📥 Copy bills from previous month' : '📥 Prenesi položnice iz prejšnjega meseca');
        btnCopyPrevMonth.classList.remove('hidden');
        btnCopyPrevMonth.style.display = 'inline-flex';
    } else if (aktivnaKategorija === 'narocnine') {
        lblBtnCopy.textContent = (typeof I18N !== 'undefined' && I18N.btn_copy_subs) ? I18N.btn_copy_subs : (jeAnglescina ? '📥 Copy subscriptions from previous month' : '📥 Prenesi naročnine iz prejšnjega meseca');
        btnCopyPrevMonth.classList.remove('hidden');
        btnCopyPrevMonth.style.display = 'inline-flex';
    } else {
        btnCopyPrevMonth.classList.add('hidden');
        btnCopyPrevMonth.style.display = 'none';
    }
}

if (btnCopyPrevMonth) {
    btnCopyPrevMonth.addEventListener('click', () => {
        odpriModalZaPrenos();
    });
}

function odpriModalZaPrenos() {
    if (!modalCopyPrev) return;

    // Prenos je omogočen le za Položnice ali Naročnine
    if (aktivnaKategorija !== 'poloznice' && aktivnaKategorija !== 'narocnine') {
        return;
    }

    const langK = jeAnglescina ? 'en' : 'sl';
    const monthNames = imenaMesecev[langK];

    const currentMonthNum = String(new Date().getMonth() + 1).padStart(2, '0');
    const izbranMesec = (selectMonthFilter && selectMonthFilter.value !== 'vse') ? selectMonthFilter.value : currentMonthNum;

    const targetMonthInt = parseInt(izbranMesec, 10);
    const sourceMonthInt = (targetMonthInt === 1) ? 12 : (targetMonthInt - 1);
    const sourceMonthStr = String(sourceMonthInt).padStart(2, '0');

    const sourceMonthName = monthNames[sourceMonthStr] || sourceMonthStr;
    const targetMonthName = monthNames[izbranMesec] || izbranMesec;

    if (copyModalTitle) {
        if (aktivnaKategorija === 'poloznice') {
            copyModalTitle.textContent = (typeof I18N !== 'undefined' && I18N.modal_copy_bills_title) ? I18N.modal_copy_bills_title : (jeAnglescina ? 'Copy bills from previous month' : 'Prenos položnic iz prejšnjega meseca');
        } else if (aktivnaKategorija === 'narocnine') {
            copyModalTitle.textContent = (typeof I18N !== 'undefined' && I18N.modal_copy_subs_title) ? I18N.modal_copy_subs_title : (jeAnglescina ? 'Copy subscriptions from previous month' : 'Prenos naročnin iz prejšnjega meseca');
        }
    }

    if (copyModalDesc) {
        const itemTypeDesc = (aktivnaKategorija === 'poloznice')
            ? (jeAnglescina ? 'bills' : 'položnic')
            : (jeAnglescina ? 'subscriptions' : 'naročnin');

        copyModalDesc.innerHTML = jeAnglescina
            ? `Transferring ${itemTypeDesc} from <strong>${sourceMonthName}</strong> to <strong>${targetMonthName}</strong>:`
            : `Prenos ${itemTypeDesc} iz meseca <strong>${sourceMonthName}</strong> v mesec <strong>${targetMonthName}</strong>:`;
    }

    if (copyModalMsg) {
        copyModalMsg.style.display = 'none';
        copyModalMsg.textContent = '';
    }

    // Poiščemo transakcije v vseTransakcije
    najdeneTransZaPrenos = vseTransakcije.filter(tItem => {
        if (!tItem.datum) return false;
        const parts = tItem.datum.split('-');
        if (parts.length < 2) return false;
        const m = parts[1];
        if (m !== sourceMonthStr) return false;

        return tItem.kategorija === aktivnaKategorija;
    });

    if (copyItemsContainer) {
        if (najdeneTransZaPrenos.length === 0) {
            const noItemsTxt = (aktivnaKategorija === 'poloznice')
                ? ((typeof I18N !== 'undefined' && I18N.copy_no_bills_found) ? I18N.copy_no_bills_found : (jeAnglescina ? 'No bills found in the previous month.' : 'V prejšnjem mesecu ni bilo najdenih položnic za prenos.'))
                : ((typeof I18N !== 'undefined' && I18N.copy_no_subs_found) ? I18N.copy_no_subs_found : (jeAnglescina ? 'No subscriptions found in the previous month.' : 'V prejšnjem mesecu ni bilo najdenih naročnin za prenos.'));
            copyItemsContainer.innerHTML = `
                <div style="text-align: center; padding: 25px 15px; color: var(--text-muted);">
                    <div style="font-size: 28px; margin-bottom: 8px;">📭</div>
                    <div>${noItemsTxt}</div>
                </div>
            `;
            if (btnSubmitCopyPrev) btnSubmitCopyPrev.disabled = true;
            if (btnCopyToggleAll) btnCopyToggleAll.style.display = 'none';
            if (copySelectedCount) copySelectedCount.textContent = '';
        } else {
            if (btnSubmitCopyPrev) btnSubmitCopyPrev.disabled = false;
            if (btnCopyToggleAll) {
                btnCopyToggleAll.style.display = 'inline-block';
                btnCopyToggleAll.textContent = (typeof I18N !== 'undefined' && I18N.copy_deselect_all) ? I18N.copy_deselect_all : (jeAnglescina ? 'Deselect all' : 'Odznači vse');
            }

            let itemsHtml = '';
            najdeneTransZaPrenos.forEach(item => {
                const znesekVal = parseFloat(item.znesek).toFixed(2);
                const isPriliv = item.vrsta === 'priliv';
                const znesekColor = isPriliv ? '#16a34a' : '#dc2626';
                const znesekZnak = isPriliv ? '+' : '-';
                const katNaziv = pridobiNazivKategorije(item.kategorija);
                const katIkona = pridobiIkonoKategorije(item.kategorija);

                itemsHtml += `
                    <label class="copy-item-row" style="display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; margin-bottom: 6px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                        <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                            <input type="checkbox" class="copy-item-checkbox" value="${item.id}" checked style="width: 17px; height: 17px; cursor: pointer; accent-color: var(--primary-color);">
                            <div style="min-width: 0;">
                                <div style="font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.opis}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">${katIkona} ${katNaziv} &bull; ${item.datum ? item.datum.split(' ')[0] : ''}</div>
                            </div>
                        </div>
                        <div style="font-weight: bold; color: ${znesekColor}; margin-left: 10px; white-space: nowrap;">
                            ${znesekZnak} ${znesekVal} €
                        </div>
                    </label>
                `;
            });
            copyItemsContainer.innerHTML = itemsHtml;
            posodobiStevecIzbranih();

            // Poslušalci na posamezne checkboxe
            copyItemsContainer.querySelectorAll('.copy-item-checkbox').forEach(cb => {
                cb.addEventListener('change', posodobiStevecIzbranih);
            });
        }
    }

    modalCopyPrev.classList.remove('hidden');
}

function posodobiStevecIzbranih() {
    if (!copyItemsContainer) return;
    const checkboxes = copyItemsContainer.querySelectorAll('.copy-item-checkbox');
    const checked = copyItemsContainer.querySelectorAll('.copy-item-checkbox:checked');

    if (copySelectedCount) {
        copySelectedCount.textContent = jeAnglescina
            ? `Selected: ${checked.length} / ${checkboxes.length}`
            : `Izbranih: ${checked.length} / ${checkboxes.length}`;
    }

    if (btnSubmitCopyPrev) {
        btnSubmitCopyPrev.disabled = (checked.length === 0);
        btnSubmitCopyPrev.textContent = jeAnglescina
            ? `✅ Copy selected (${checked.length})`
            : `✅ Prenesi izbrane (${checked.length})`;
    }

    if (btnCopyToggleAll) {
        const allChecked = (checkboxes.length > 0 && checked.length === checkboxes.length);
        btnCopyToggleAll.textContent = allChecked
            ? ((typeof I18N !== 'undefined' && I18N.copy_deselect_all) ? I18N.copy_deselect_all : (jeAnglescina ? 'Deselect all' : 'Odznači vse'))
            : ((typeof I18N !== 'undefined' && I18N.copy_select_all) ? I18N.copy_select_all : (jeAnglescina ? 'Select all' : 'Izberi vse'));
    }
}

if (btnCopyToggleAll) {
    btnCopyToggleAll.addEventListener('click', () => {
        if (!copyItemsContainer) return;
        const checkboxes = copyItemsContainer.querySelectorAll('.copy-item-checkbox');
        const checked = copyItemsContainer.querySelectorAll('.copy-item-checkbox:checked');
        const shouldCheck = (checked.length !== checkboxes.length);
        checkboxes.forEach(cb => { cb.checked = shouldCheck; });
        posodobiStevecIzbranih();
    });
}

// Potrditev prenosa
if (btnSubmitCopyPrev) {
    btnSubmitCopyPrev.addEventListener('click', async () => {
        const user = pridobiTrenutnegaUporabnika() || {};
        if (!user.id) return;

        const checkboxes = copyItemsContainer ? copyItemsContainer.querySelectorAll('.copy-item-checkbox:checked') : [];
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);

        if (selectedIds.length === 0) {
            if (copyModalMsg) {
                copyModalMsg.className = 'message error';
                copyModalMsg.textContent = (typeof I18N !== 'undefined' && I18N.copy_no_selection)
                    ? I18N.copy_no_selection
                    : (jeAnglescina ? 'Please select at least one transaction.' : 'Izberite vsaj eno transakcijo za prenos.');
                copyModalMsg.style.display = 'block';
            }
            return;
        }

        const currentMonthNum = String(new Date().getMonth() + 1).padStart(2, '0');
        const izbranMesec = (selectMonthFilter && selectMonthFilter.value !== 'vse') ? selectMonthFilter.value : currentMonthNum;

        btnSubmitCopyPrev.disabled = true;
        btnSubmitCopyPrev.textContent = jeAnglescina ? 'Transferring...' : 'Prenašam...';

        try {
            const currentYear = new Date().getFullYear();
            const toEncryptItems = [];

            for (const idStr of selectedIds) {
                const item = vseTransakcije.find(t => String(t.id) === String(idStr));
                if (item) {
                    const originalDay = (item.datum && item.datum.split('-')[2]) ? item.datum.split('-')[2].split(' ')[0] : '15';
                    const targetDate = `${currentYear}-${izbranMesec}-${originalDay.padStart(2, '0')}`;
                    const enc = await ZKCrypto.encrypt({
                        opis: item.opis,
                        znesek: item.znesek,
                        vrsta: item.vrsta,
                        kategorija: item.kategorija,
                        datum: targetDate
                    });
                    toEncryptItems.push({
                        encrypted_data: enc.encrypted_data,
                        iv: enc.iv,
                        datum: targetDate
                    });
                }
            }

            const res = await fetch('transactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'batch_add',
                    user_id: user.id,
                    items: toEncryptItems
                })
            });
            const data = await res.json();

            if (res.ok && data.status === 'success') {
                if (modalCopyPrev) modalCopyPrev.classList.add('hidden');

                // Če smo bili na 'vse', lahko preklopimo na ta mesec
                if (selectMonthFilter && selectMonthFilter.value === 'vse') {
                    selectMonthFilter.value = izbranMesec;
                }

                showDashMessage(data.message || (jeAnglescina ? 'Transactions successfully copied!' : 'Transakcije uspešno prenesene!'), false);
                await naloziTransakcije();
            } else {
                if (copyModalMsg) {
                    copyModalMsg.className = 'message error';
                    copyModalMsg.textContent = data.message || (jeAnglescina ? 'Error copying transactions.' : 'Napaka pri prenosu transakcij.');
                    copyModalMsg.style.display = 'block';
                }
                btnSubmitCopyPrev.disabled = false;
                posodobiStevecIzbranih();
            }
        } catch (e) {
            if (copyModalMsg) {
                copyModalMsg.className = 'message error';
                copyModalMsg.textContent = t.conn_error;
                copyModalMsg.style.display = 'block';
            }
            btnSubmitCopyPrev.disabled = false;
            posodobiStevecIzbranih();
        }
    });
}

// 14. GLOBALNI POSLUŠALEC KLIKOV ZA VSA MODALNA OKNA IN IKONE ✏️
document.addEventListener('click', (e) => {
    const langKey = jeAnglescina ? 'en' : 'sl';

    // Odpri modal za Urejanje transakcije
    const btnEdit = e.target.closest('.btn-edit-trans');
    if (btnEdit) {
        const transId = btnEdit.dataset.id;
        if (transId) {
            odpriUrejanjeTransakcije(transId);
            return;
        }
    }

    // Odpri modal za Vnos transakcije (+ Vpis)
    if (e.target.closest('#btn-add-transaction')) {
        const modalTrans = document.getElementById('modal-transaction');
        if (modalTrans) {
            if (formTrans) formTrans.reset();
            if (transMsg) transMsg.style.display = 'none';
            if (boxCustomCat) boxCustomCat.classList.add('hidden');

            const now = new Date();
            const year = now.getFullYear();
            const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const sidebarMonth = selectMonthFilter ? selectMonthFilter.value : 'vse';
            const defaultMonth = (sidebarMonth !== 'vse') ? sidebarMonth : currentMonth;

            if (transMesec) transMesec.value = defaultMonth;
            if (transDatum) transDatum.value = `${year}-${defaultMonth}-${day}`;

            if (transKategorija && aktivnaKategorija !== 'vse') {
                if (transKategorija.querySelector(`option[value="${aktivnaKategorija}"]`)) {
                    transKategorija.value = aktivnaKategorija;
                }
            }

            modalTrans.classList.remove('hidden');
        }
    }

    // Odpri modal za Bančni profil (✏️)
    if (e.target.closest('#btn-edit-bank')) {
        const modalB = document.getElementById('modal-bank-profile');
        if (modalB) {
            const user = pridobiTrenutnegaUporabnika() || {};
            const uId = user.id ? `_${user.id}` : '';
            const bData = { ...privzetiBancniProfil[langKey], ...JSON.parse(localStorage.getItem(`banka_profile${uId}_${langKey}`) || localStorage.getItem(`banka_profile_${langKey}`) || '{}') };
            const savedAmounts = JSON.parse(localStorage.getItem(`banka_amounts${uId}`) || localStorage.getItem('banka_amounts') || '{}');
            const formatInputVal = (val) => (val !== undefined && val !== null && val !== '' && Number(val) !== 0) ? val : '';

            if (document.getElementById('label-zacetno')) document.getElementById('label-zacetno').value = bData.lbl_zacetno || '';
            if (document.getElementById('bank-zacetno')) document.getElementById('bank-zacetno').value = formatInputVal(savedAmounts.zacetno ?? bData.zacetno);
            if (document.getElementById('label-placa')) document.getElementById('label-placa').value = bData.lbl_placa || '';
            if (document.getElementById('bank-placa')) document.getElementById('bank-placa').value = formatInputVal(savedAmounts.placa ?? bData.placa);
            if (document.getElementById('label-krediti')) document.getElementById('label-krediti').value = bData.lbl_krediti || '';
            if (document.getElementById('bank-krediti')) document.getElementById('bank-krediti').value = formatInputVal(savedAmounts.krediti ?? bData.krediti);
            if (document.getElementById('label-stroski')) document.getElementById('label-stroski').value = bData.lbl_stroski || '';
            if (document.getElementById('bank-stroski')) document.getElementById('bank-stroski').value = formatInputVal(savedAmounts.stroski ?? bData.stroski);
            if (document.getElementById('label-obresti')) document.getElementById('label-obresti').value = bData.lbl_obresti || '';
            if (document.getElementById('bank-obresti')) document.getElementById('bank-obresti').value = formatInputVal(savedAmounts.obresti ?? bData.obresti);
            if (document.getElementById('label-varcevanje')) document.getElementById('label-varcevanje').value = bData.lbl_varcevanje || '';
            if (document.getElementById('bank-varcevanje')) document.getElementById('bank-varcevanje').value = formatInputVal(savedAmounts.varcevanje ?? bData.varcevanje);

            modalB.classList.remove('hidden');
        }
    }

    // Odpri modal za Filtre v sidebaru (✏️)
    if (e.target.closest('#btn-edit-filters')) {
        const modalF = document.getElementById('modal-sidebar-filters');
        if (modalF) {
            const fData = pridobiFiltre(langKey);
            if (document.getElementById('filter-lbl-vse')) document.getElementById('filter-lbl-vse').value = fData.vse;
            if (document.getElementById('filter-lbl-poloznice')) document.getElementById('filter-lbl-poloznice').value = fData.poloznice;
            if (document.getElementById('filter-lbl-nakupi')) document.getElementById('filter-lbl-nakupi').value = fData.nakupi;
            if (document.getElementById('filter-lbl-kreditna')) document.getElementById('filter-lbl-kreditna').value = fData.kreditna;
            if (document.getElementById('filter-lbl-narocnine')) document.getElementById('filter-lbl-narocnine').value = fData.narocnine;
            if (document.getElementById('filter-lbl-avto')) document.getElementById('filter-lbl-avto').value = fData.avto;
            if (document.getElementById('filter-lbl-ostalo')) document.getElementById('filter-lbl-ostalo').value = fData.ostalo;
            modalF.classList.remove('hidden');
        }
    }

    // Zapiranje modala za Transakcije
    if (e.target.closest('.close-modal:not(.close-bank-modal):not(.close-filters-modal):not(.close-edit-modal):not(.close-copy-modal)') || e.target.id === 'modal-transaction') {
        const modalTrans = document.getElementById('modal-transaction');
        if (modalTrans) modalTrans.classList.add('hidden');
    }

    // Zapiranje modala za Prenos transakcij iz prejšnjega meseca
    if (e.target.closest('.close-copy-modal') || e.target.id === 'modal-copy-prev-month') {
        const modalCopy = document.getElementById('modal-copy-prev-month');
        if (modalCopy) modalCopy.classList.add('hidden');
    }

    // Zapiranje modala za Urejanje transakcije
    if (e.target.closest('.close-edit-modal') || e.target.id === 'modal-edit-transaction') {
        const modalE = document.getElementById('modal-edit-transaction');
        if (modalE) modalE.classList.add('hidden');
    }

    // Zapiranje modala za Bančni profil
    if (e.target.closest('.close-bank-modal') || e.target.id === 'modal-bank-profile') {
        const modalB = document.getElementById('modal-bank-profile');
        if (modalB) modalB.classList.add('hidden');
    }

    // Zapiranje modala za Filtre
    if (e.target.closest('.close-filters-modal') || e.target.id === 'modal-sidebar-filters') {
        const modalF = document.getElementById('modal-sidebar-filters');
        if (modalF) modalF.classList.add('hidden');
    }
});

// 14. SHRANJEVANJE BANČNEGA PROFILA
const fBank = document.getElementById('form-bank-profile');
if (fBank) {
    fBank.addEventListener('submit', (e) => {
        e.preventDefault();
        const langKey = jeAnglescina ? 'en' : 'sl';
        const dflt = privzetiBancniProfil[langKey];

        const bankData = {
            lbl_zacetno: document.getElementById('label-zacetno').value.trim() || dflt.lbl_zacetno,
            zacetno: pocistiZnesek(document.getElementById('bank-zacetno').value),
            lbl_placa: document.getElementById('label-placa').value.trim() || dflt.lbl_placa,
            placa: pocistiZnesek(document.getElementById('bank-placa').value),
            lbl_krediti: document.getElementById('label-krediti').value.trim() || dflt.lbl_krediti,
            krediti: pocistiZnesek(document.getElementById('bank-krediti').value),
            lbl_stroski: document.getElementById('label-stroski').value.trim() || dflt.lbl_stroski,
            stroski: pocistiZnesek(document.getElementById('bank-stroski').value),
            lbl_obresti: document.getElementById('label-obresti').value.trim() || dflt.lbl_obresti,
            obresti: pocistiZnesek(document.getElementById('bank-obresti').value),
            lbl_varcevanje: document.getElementById('label-varcevanje').value.trim() || dflt.lbl_varcevanje,
            varcevanje: pocistiZnesek(document.getElementById('bank-varcevanje').value)
        };

        const user = pridobiTrenutnegaUporabnika() || {};
        const uId = user.id ? `_${user.id}` : '';
        const amountsObj = {
            zacetno: bankData.zacetno,
            placa: bankData.placa,
            krediti: bankData.krediti,
            stroski: bankData.stroski,
            obresti: bankData.obresti,
            varcevanje: bankData.varcevanje
        };
        localStorage.setItem(`banka_profile${uId}_${langKey}`, JSON.stringify(bankData));
        localStorage.setItem(`banka_amounts${uId}`, JSON.stringify(amountsObj));
        localStorage.setItem(`banka_profile_${langKey}`, JSON.stringify(bankData));
        localStorage.setItem('banka_amounts', JSON.stringify(amountsObj));

        const modalB = document.getElementById('modal-bank-profile');
        if (modalB) modalB.classList.add('hidden');

        posodobiBancniPrikaz();
        naloziTransakcije();
        shraniBancniProfilStrežnik();
    });
}

// 15. SHRANJEVANJE FILTROV V STRANSKEM MENIJU
const fFilters = document.getElementById('form-sidebar-filters');
if (fFilters) {
    fFilters.addEventListener('submit', (e) => {
        e.preventDefault();
        const langKey = jeAnglescina ? 'en' : 'sl';
        const dflt = privzetiFiltri[langKey];

        const fData = {
            vse: document.getElementById('filter-lbl-vse').value.trim() || dflt.vse,
            poloznice: document.getElementById('filter-lbl-poloznice').value.trim() || dflt.poloznice,
            nakupi: document.getElementById('filter-lbl-nakupi').value.trim() || dflt.nakupi,
            kreditna: document.getElementById('filter-lbl-kreditna').value.trim() || dflt.kreditna,
            narocnine: document.getElementById('filter-lbl-narocnine').value.trim() || dflt.narocnine,
            avto: document.getElementById('filter-lbl-avto').value.trim() || dflt.avto,
            ostalo: document.getElementById('filter-lbl-ostalo').value.trim() || dflt.ostalo
        };

        const user = pridobiTrenutnegaUporabnika() || {};
        const uId = user.id ? `_${user.id}` : '';
        localStorage.setItem(`banka_filters${uId}_${langKey}`, JSON.stringify(fData));
        localStorage.setItem(`banka_filters_${langKey}`, JSON.stringify(fData));

        const modalF = document.getElementById('modal-sidebar-filters');
        if (modalF) modalF.classList.add('hidden');

        posodobiSidebarNazive();
        shraniBancniProfilStrežnik();
    });
}

// 16. POSODOBITEV NAZIVOV IN IZRAČUNOV V VMESNIKU
function posodobiBancniPrikaz(transactions = []) {
    const langKey = jeAnglescina ? 'en' : 'sl';
    const user = pridobiTrenutnegaUporabnika() || {};
    const uId = user.id ? `_${user.id}` : '';
    const bData = { ...privzetiBancniProfil[langKey], ...JSON.parse(localStorage.getItem(`banka_profile${uId}_${langKey}`) || localStorage.getItem(`banka_profile_${langKey}`) || '{}') };
    const savedAmounts = JSON.parse(localStorage.getItem(`banka_amounts${uId}`) || localStorage.getItem('banka_amounts') || '{}');

    const zacetno = savedAmounts.zacetno ?? (bData.zacetno || 0);
    const placa = savedAmounts.placa ?? (bData.placa || 0);
    const krediti = savedAmounts.krediti ?? (bData.krediti || 0);
    const stroski = savedAmounts.stroski ?? (bData.stroski || 0);
    const obresti = savedAmounts.obresti ?? (bData.obresti || 0);
    const varcevanje = savedAmounts.varcevanje ?? (bData.varcevanje || 0);

    if (document.getElementById('disp-lbl-zacetno')) document.getElementById('disp-lbl-zacetno').textContent = bData.lbl_zacetno + ':';
    if (document.getElementById('disp-lbl-placa')) document.getElementById('disp-lbl-placa').textContent = bData.lbl_placa + ':';
    if (document.getElementById('disp-lbl-krediti')) document.getElementById('disp-lbl-krediti').textContent = bData.lbl_krediti + ':';
    if (document.getElementById('disp-lbl-stroski')) document.getElementById('disp-lbl-stroski').textContent = bData.lbl_stroski + ':';
    if (document.getElementById('disp-lbl-obresti')) document.getElementById('disp-lbl-obresti').textContent = bData.lbl_obresti + ':';
    if (document.getElementById('disp-lbl-varcevanje')) document.getElementById('disp-lbl-varcevanje').textContent = bData.lbl_varcevanje + ':';

    const statZacetno = document.getElementById('stat-zacetno');
    if (statZacetno) {
        if (zacetno < 0) {
            statZacetno.textContent = '- ' + Math.abs(zacetno).toFixed(2) + ' €';
            statZacetno.className = 'stat-value text-danger';
        } else {
            statZacetno.textContent = zacetno.toFixed(2) + ' €';
            statZacetno.className = 'stat-value text-success';
        }
    }
    if (document.getElementById('stat-placa')) document.getElementById('stat-placa').textContent = placa.toFixed(2) + ' €';
    if (document.getElementById('stat-krediti')) document.getElementById('stat-krediti').textContent = '- ' + krediti.toFixed(2) + ' €';
    if (document.getElementById('stat-stroski')) document.getElementById('stat-stroski').textContent = '- ' + stroski.toFixed(2) + ' €';
    if (document.getElementById('stat-obresti')) document.getElementById('stat-obresti').textContent = '- ' + obresti.toFixed(2) + ' €';
    if (document.getElementById('stat-varcevanje')) document.getElementById('stat-varcevanje').textContent = '- ' + varcevanje.toFixed(2) + ' €';

    let transOdlivi = 0;
    let transPrilivi = 0;

    transactions.forEach(tItem => {
        const val = parseFloat(tItem.znesek) || 0;
        if (tItem.vrsta === 'odliv') transOdlivi += val;
        if (tItem.vrsta === 'priliv') transPrilivi += val;
    });

    const fiksnaBaza = zacetno + placa - krediti - stroski - obresti - varcevanje;
    const koncnoProsto = fiksnaBaza - transOdlivi + transPrilivi;

    const statProsto = document.getElementById('stat-prosto');
    if (statProsto) {
        statProsto.textContent = koncnoProsto.toFixed(2) + ' €';
        statProsto.className = 'stat-value ' + (koncnoProsto >= 0 ? 'text-success' : 'text-danger');
    }
}

function posodobiSidebarNazive() {
    const langKey = jeAnglescina ? 'en' : 'sl';
    const fData = pridobiFiltre(langKey);
    const customCats = pridobiCustomKategorije();

    // 1. Posodobitev desktop stranskega traku z vsemi privzetimi in lastnimi kategorijami
    const desktopNav = document.querySelector('.quick-links.desktop-filters');
    if (desktopNav) {
        let html = `
            <button class="filter-btn ${aktivnaKategorija === 'vse' ? 'active' : ''}" data-category="vse">
                <span class="icon">📊</span> <span id="lbl-f-vse">${fData.vse}</span>
            </button>
            <button class="filter-btn ${aktivnaKategorija === 'poloznice' ? 'active' : ''}" data-category="poloznice">
                <span class="icon">📄</span> <span id="lbl-f-poloznice">${fData.poloznice}</span>
            </button>
            <button class="filter-btn ${aktivnaKategorija === 'nakupi' ? 'active' : ''}" data-category="nakupi">
                <span class="icon">🛒</span> <span id="lbl-f-nakupi">${fData.nakupi}</span>
            </button>
            <button class="filter-btn ${aktivnaKategorija === 'kreditna' ? 'active' : ''}" data-category="kreditna">
                <span class="icon">💳</span> <span id="lbl-f-kreditna">${fData.kreditna}</span>
            </button>
            <button class="filter-btn ${aktivnaKategorija === 'narocnine' ? 'active' : ''}" data-category="narocnine">
                <span class="icon">🔄</span> <span id="lbl-f-narocnine">${fData.narocnine}</span>
            </button>
            <button class="filter-btn ${aktivnaKategorija === 'avto' ? 'active' : ''}" data-category="avto">
                <span class="icon">⛽</span> <span id="lbl-f-avto">${fData.avto}</span>
            </button>
            <button class="filter-btn ${aktivnaKategorija === 'ostalo' ? 'active' : ''}" data-category="ostalo">
                <span class="icon">🏷️</span> <span id="lbl-f-ostalo">${fData.ostalo}</span>
            </button>
        `;

        customCats.forEach(cat => {
            const isActive = aktivnaKategorija === cat.id ? 'active' : '';
            html += `
                <button class="filter-btn ${isActive}" data-category="${cat.id}">
                    <span class="icon">${cat.icon || '🏷️'}</span> <span>${cat.name}</span>
                </button>
            `;
        });

        desktopNav.innerHTML = html;
        poveziGumbeFiltrov();
    }

    // 2. Mobilni dropdown
    if (mobileFilterSelect) {
        let mobileHtml = `
            <option value="vse" ${aktivnaKategorija === 'vse' ? 'selected' : ''}>📊 ${fData.vse}</option>
            <option value="poloznice" ${aktivnaKategorija === 'poloznice' ? 'selected' : ''}>📄 ${fData.poloznice}</option>
            <option value="nakupi" ${aktivnaKategorija === 'nakupi' ? 'selected' : ''}>🛒 ${fData.nakupi}</option>
            <option value="kreditna" ${aktivnaKategorija === 'kreditna' ? 'selected' : ''}>💳 ${fData.kreditna}</option>
            <option value="narocnine" ${aktivnaKategorija === 'narocnine' ? 'selected' : ''}>🔄 ${fData.narocnine}</option>
            <option value="avto" ${aktivnaKategorija === 'avto' ? 'selected' : ''}>⛽ ${fData.avto}</option>
            <option value="ostalo" ${aktivnaKategorija === 'ostalo' ? 'selected' : ''}>🏷️ ${fData.ostalo}</option>
        `;

        customCats.forEach(cat => {
            const isSel = aktivnaKategorija === cat.id ? 'selected' : '';
            mobileHtml += `<option value="${cat.id}" ${isSel}>${cat.icon || '🏷️'} ${cat.name}</option>`;
        });

        mobileFilterSelect.innerHTML = mobileHtml;
    }

    // 3. Dropdown v oknu za vnos transakcije
    const transKatSelect = document.getElementById('trans-kategorija');
    if (transKatSelect) {
        const trenutnaIzbira = transKatSelect.value || 'poloznice';
        let katHtml = `
            <option value="poloznice">${fData.poloznice}</option>
            <option value="nakupi">${fData.nakupi}</option>
            <option value="kreditna">${fData.kreditna}</option>
            <option value="narocnine">${fData.narocnine}</option>
            <option value="avto">${fData.avto}</option>
            <option value="ostalo">${fData.ostalo}</option>
        `;

        customCats.forEach(cat => {
            katHtml += `<option value="${cat.id}">${cat.name}</option>`;
        });

        katHtml += `<option value="__nova__">${jeAnglescina ? '+ Add custom category...' : '+ Dodaj svojo kategorijo...'}</option>`;

        transKatSelect.innerHTML = katHtml;
        if (trenutnaIzbira && transKatSelect.querySelector(`option[value="${trenutnaIzbira}"]`)) {
            transKatSelect.value = trenutnaIzbira;
        }
    }

    // 4. Dropdown v oknu za urejanje transakcije
    const editKatSelect = document.getElementById('edit-trans-kategorija');
    if (editKatSelect) {
        const trenutnaIzbira = editKatSelect.value || 'poloznice';
        let katHtml = `
            <option value="poloznice">${fData.poloznice}</option>
            <option value="nakupi">${fData.nakupi}</option>
            <option value="kreditna">${fData.kreditna}</option>
            <option value="narocnine">${fData.narocnine}</option>
            <option value="avto">${fData.avto}</option>
            <option value="ostalo">${fData.ostalo}</option>
        `;

        customCats.forEach(cat => {
            katHtml += `<option value="${cat.id}">${cat.name}</option>`;
        });

        katHtml += `<option value="__nova__">${jeAnglescina ? '+ Add custom category...' : '+ Dodaj svojo kategorijo...'}</option>`;

        editKatSelect.innerHTML = katHtml;
        if (trenutnaIzbira && editKatSelect.querySelector(`option[value="${trenutnaIzbira}"]`)) {
            editKatSelect.value = trenutnaIzbira;
        }
    }
}
