<?php require_once "header.php"; ?>

    <!-- Gumb za hitri vpis -->
    <div id="action-bar" class="hidden">
        <div>
            <h2 id="user-greeting"><?php echo $txt['greeting'] ?? 'Pozdravljeni'; ?>!</h2>
        </div>
        <div class="action-container">
            <button id="btn-add-transaction" class="btn-primary" data-sl="+ Vpis" data-en="+ Add Record"><?php echo $txt['btn_add_record'] ?? '+ Vpis'; ?></button>
        </div>
    </div>

    <!-- Modalno okno za vnos transakcije -->
    <div id="modal-transaction" class="modal hidden">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 id="modal-title"><?php echo $txt['modal_trans_title'] ?? 'Vpis nove transakcije'; ?></h3>
            <form id="form-transaction">
                <div class="form-group">
                    <label for="trans-opis"><?php echo $txt['th_desc'] ?? 'Opis transakcije'; ?></label>
                    <input type="text" id="trans-opis" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label for="trans-znesek"><?php echo $txt['th_amount'] ?? 'Znesek (€)'; ?></label>
                    <input type="text" id="trans-znesek" inputmode="decimal" placeholder="0.00" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label for="trans-vrsta"><?php echo $txt['th_type'] ?? 'Vrsta'; ?></label>
                    <select id="trans-vrsta" autocomplete="off" required>
                        <option value="odliv"><?php echo $txt['type_expense'] ?? 'Odliv (-)'; ?></option>
                        <option value="priliv"><?php echo $txt['type_income'] ?? 'Priliv (+)'; ?></option>
                    </select>
                </div>

                <!-- IZBIRA MESECA -->
                <div class="form-group">
                    <label for="trans-mesec"><?php echo $txt['lbl_trans_month'] ?? 'Mesec za vpis:'; ?></label>
                    <?php $currentMonth = date('m'); ?>
                    <select id="trans-mesec" autocomplete="off">
                        <option value="01" <?php echo ($currentMonth === '01') ? 'selected' : ''; ?>>01 - <?php echo $txt['month_01'] ?? 'Januar'; ?></option>
                        <option value="02" <?php echo ($currentMonth === '02') ? 'selected' : ''; ?>>02 - <?php echo $txt['month_02'] ?? 'Februar'; ?></option>
                        <option value="03" <?php echo ($currentMonth === '03') ? 'selected' : ''; ?>>03 - <?php echo $txt['month_03'] ?? 'Marec'; ?></option>
                        <option value="04" <?php echo ($currentMonth === '04') ? 'selected' : ''; ?>>04 - <?php echo $txt['month_04'] ?? 'April'; ?></option>
                        <option value="05" <?php echo ($currentMonth === '05') ? 'selected' : ''; ?>>05 - <?php echo $txt['month_05'] ?? 'Maj'; ?></option>
                        <option value="06" <?php echo ($currentMonth === '06') ? 'selected' : ''; ?>>06 - <?php echo $txt['month_06'] ?? 'Junij'; ?></option>
                        <option value="07" <?php echo ($currentMonth === '07') ? 'selected' : ''; ?>>07 - <?php echo $txt['month_07'] ?? 'Julij'; ?></option>
                        <option value="08" <?php echo ($currentMonth === '08') ? 'selected' : ''; ?>>08 - <?php echo $txt['month_08'] ?? 'Avgust'; ?></option>
                        <option value="09" <?php echo ($currentMonth === '09') ? 'selected' : ''; ?>>09 - <?php echo $txt['month_09'] ?? 'September'; ?></option>
                        <option value="10" <?php echo ($currentMonth === '10') ? 'selected' : ''; ?>>10 - <?php echo $txt['month_10'] ?? 'Oktober'; ?></option>
                        <option value="11" <?php echo ($currentMonth === '11') ? 'selected' : ''; ?>>11 - <?php echo $txt['month_11'] ?? 'November'; ?></option>
                        <option value="12" <?php echo ($currentMonth === '12') ? 'selected' : ''; ?>>12 - <?php echo $txt['month_12'] ?? 'December'; ?></option>
                    </select>
                </div>

                <!-- IZBIRA KATEGORIJE / FILTRA -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label for="trans-kategorija" style="margin-bottom: 0;"><?php echo $txt['th_category'] ?? 'Kategorija'; ?></label>
                        <button type="button" id="btn-toggle-custom-cat" style="background:none; border:none; color:var(--link-color); font-size:12px; font-weight:bold; cursor:pointer; padding:0;"><?php echo $txt['btn_add_custom_category'] ?? '+ Dodaj svojo'; ?></button>
                    </div>
                    <select id="trans-kategorija" autocomplete="off" required>
                        <option value="poloznice"><?php echo $txt['cat_poloznice'] ?? 'Položnice'; ?></option>
                        <option value="nakupi"><?php echo $txt['cat_nakupi'] ?? 'Nakupi & Hrana'; ?></option>
                        <option value="kreditna"><?php echo $txt['cat_kreditna'] ?? 'Kreditna kartica'; ?></option>
                        <option value="narocnine"><?php echo $txt['cat_narocnine'] ?? 'Naročnine'; ?></option>
                        <option value="avto"><?php echo $txt['cat_avto'] ?? 'Vozilo & Gorivo'; ?></option>
                        <option value="ostalo"><?php echo $txt['cat_ostalo'] ?? 'Ostalo'; ?></option>
                        <option value="__nova__"><?php echo $txt['opt_add_new_category'] ?? '+ Dodaj svojo kategorijo...'; ?></option>
                    </select>
                </div>

                <!-- Polje za vnos nove kategorije po meri -->
                <div id="box-custom-cat" class="form-group hidden" style="padding: 12px; background: var(--hover-bg); border-radius: 6px; border: 1px dashed var(--border-color); margin-bottom: 14px;">
                    <label for="trans-nova-kategorija" style="font-size: 13px;"><?php echo $txt['lbl_new_custom_category'] ?? 'Naziv nove kategorije:'; ?></label>
                    <div style="display: flex; gap: 8px; margin-top: 5px;">
                        <input type="text" id="trans-nova-kategorija" placeholder="<?php echo $txt['ph_new_category'] ?? 'Npr. Zavarovanja, Hobi...'; ?>" autocomplete="off" style="flex: 1;">
                        <button type="button" id="btn-save-custom-cat" class="btn-secondary" style="width: auto; padding: 8px 14px;"><?php echo $txt['btn_save_cat'] ?? 'Dodaj'; ?></button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="trans-datum"><?php echo $txt['th_date'] ?? 'Datum (opcijsko)'; ?></label>
                    <input type="date" id="trans-datum" autocomplete="off">
                    <small><?php echo $txt['date_hint'] ?? 'Pusti prazno za trenutni čas'; ?></small>
                </div>

                <div id="trans-msg" class="message"></div>

                <button type="submit" class="btn-primary btn-full"><?php echo $txt['btn_save'] ?? 'Shrani'; ?></button>
            </form>
        </div>
    </div>

    <!-- Modalno okno za urejanje transakcije -->
    <div id="modal-edit-transaction" class="modal hidden">
        <div class="modal-content">
            <span class="close-modal close-edit-modal">&times;</span>
            <h3 id="edit-modal-title"><?php echo $txt['modal_edit_trans_title'] ?? 'Urejanje transakcije'; ?></h3>
            <form id="form-edit-transaction">
                <input type="hidden" id="edit-trans-id">

                <div class="form-group">
                    <label for="edit-trans-opis"><?php echo $txt['th_desc'] ?? 'Opis transakcije'; ?></label>
                    <input type="text" id="edit-trans-opis" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label for="edit-trans-znesek"><?php echo $txt['th_amount'] ?? 'Znesek (€)'; ?></label>
                    <input type="text" id="edit-trans-znesek" inputmode="decimal" placeholder="0.00" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label for="edit-trans-vrsta"><?php echo $txt['th_type'] ?? 'Vrsta'; ?></label>
                    <select id="edit-trans-vrsta" autocomplete="off" required>
                        <option value="odliv"><?php echo $txt['type_expense'] ?? 'Odliv (-)'; ?></option>
                        <option value="priliv"><?php echo $txt['type_income'] ?? 'Priliv (+)'; ?></option>
                    </select>
                </div>

                <!-- IZBIRA KATEGORIJE -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label for="edit-trans-kategorija" style="margin-bottom: 0;"><?php echo $txt['th_category'] ?? 'Kategorija'; ?></label>
                        <button type="button" id="btn-toggle-edit-custom-cat" style="background:none; border:none; color:var(--link-color); font-size:12px; font-weight:bold; cursor:pointer; padding:0;"><?php echo $txt['btn_add_custom_category'] ?? '+ Dodaj svojo'; ?></button>
                    </div>
                    <select id="edit-trans-kategorija" autocomplete="off" required>
                        <option value="poloznice"><?php echo $txt['cat_poloznice'] ?? 'Položnice'; ?></option>
                        <option value="nakupi"><?php echo $txt['cat_nakupi'] ?? 'Nakupi & Hrana'; ?></option>
                        <option value="kreditna"><?php echo $txt['cat_kreditna'] ?? 'Kreditna kartica'; ?></option>
                        <option value="narocnine"><?php echo $txt['cat_narocnine'] ?? 'Naročnine'; ?></option>
                        <option value="avto"><?php echo $txt['cat_avto'] ?? 'Vozilo & Gorivo'; ?></option>
                        <option value="ostalo"><?php echo $txt['cat_ostalo'] ?? 'Ostalo'; ?></option>
                        <option value="__nova__"><?php echo $txt['opt_add_new_category'] ?? '+ Dodaj svojo kategorijo...'; ?></option>
                    </select>
                </div>

                <!-- Polje za vnos nove kategorije po meri v urejanju -->
                <div id="box-edit-custom-cat" class="form-group hidden" style="padding: 12px; background: var(--hover-bg); border-radius: 6px; border: 1px dashed var(--border-color); margin-bottom: 14px;">
                    <label for="edit-trans-nova-kategorija" style="font-size: 13px;"><?php echo $txt['lbl_new_custom_category'] ?? 'Naziv nove kategorije:'; ?></label>
                    <div style="display: flex; gap: 8px; margin-top: 5px;">
                        <input type="text" id="edit-trans-nova-kategorija" placeholder="<?php echo $txt['ph_new_category'] ?? 'Npr. Zavarovanja, Hobi...'; ?>" autocomplete="off" style="flex: 1;">
                        <button type="button" id="btn-save-edit-custom-cat" class="btn-secondary" style="width: auto; padding: 8px 14px;"><?php echo $txt['btn_save_cat'] ?? 'Dodaj'; ?></button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit-trans-datum"><?php echo $txt['th_date'] ?? 'Datum'; ?></label>
                    <input type="date" id="edit-trans-datum" autocomplete="off" required>
                </div>

                <div id="edit-trans-msg" class="message"></div>

                <div class="modal-actions" style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                    <button type="submit" class="btn-primary" style="flex: 1; min-width: 140px;"><?php echo $txt['btn_save_changes'] ?? 'Shrani spremembe'; ?></button>
                    <button type="button" id="btn-delete-trans" class="btn-danger" style="flex: 1; min-width: 140px;"><?php echo $txt['btn_delete_trans'] ?? '🗑️ Izbriši transakcijo'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modalno okno za urejanje bančnega profila -->
    <div id="modal-bank-profile" class="modal hidden">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close-modal close-bank-modal">&times;</span>
            <h3><?php echo $txt['edit_bank_title'] ?? 'Uredi bančni profil'; ?></h3>
            <form id="form-bank-profile">
                <div class="form-group">
                    <div style="display: flex; gap: 8px;">
                        <label for="label-zacetno"></label>
                        <input type="text" id="label-zacetno"
                               placeholder="<?php echo $txt['ph_label_starting_balance'] ?? 'Naziv začetnega stanja'; ?>" style="width: 55%;">
                        <label for="bank-zacetno"></label>
                        <input type="text" id="bank-zacetno" inputmode="decimal"
                               placeholder="0.00" style="width: 45%;">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; gap: 8px;">
                        <label for="label-placa"></label>
                        <input type="text" id="label-placa"
                               placeholder="<?php echo $txt['ph_label_income'] ?? 'Naziv priliva'; ?>" style="width: 55%;">
                        <label for="bank-placa"></label>
                        <input type="text" id="bank-placa" inputmode="decimal"
                               placeholder="0.00" style="width: 45%;">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; gap: 8px;">
                        <label for="label-krediti"></label>
                        <input type="text" id="label-krediti"
                               placeholder="<?php echo $txt['ph_label_loans'] ?? 'Naziv obveznosti'; ?>" style="width: 55%;">
                        <label for="bank-krediti"></label>
                        <input type="text" id="bank-krediti" inputmode="decimal"
                               placeholder="0.00" style="width: 45%;">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; gap: 8px;">
                        <label for="label-stroski"></label>
                        <input type="text" id="label-stroski"
                               placeholder="<?php echo $txt['ph_label_fees'] ?? 'Naziv stroška vodenja'; ?>" style="width: 55%;">
                        <label for="bank-stroski"></label>
                        <input type="text" id="bank-stroski" inputmode="decimal"
                               placeholder="0.00" style="width: 45%;">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; gap: 8px;">
                        <label for="label-obresti"></label>
                        <input type="text" id="label-obresti"
                               placeholder="<?php echo $txt['ph_label_interest'] ?? 'Naziv obresti limita'; ?>" style="width: 55%;">
                        <label for="bank-obresti"></label>
                        <input type="text" id="bank-obresti" inputmode="decimal"
                               placeholder="0.00" style="width: 45%;">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; gap: 8px;">
                        <label for="label-varcevanje"></label>
                        <input type="text" id="label-varcevanje"
                               placeholder="<?php echo $txt['ph_label_savings'] ?? 'Naziv varčevanja'; ?>" style="width: 55%;">
                        <label for="bank-varcevanje"></label>
                        <input type="text" id="bank-varcevanje" inputmode="decimal" placeholder="0.00" style="width: 45%;">
                    </div>
                </div>

                <div id="bank-msg" class="message"></div>
                <button type="submit" class="btn-primary btn-full"><?php echo $txt['btn_save_bank'] ?? 'Shrani bančne podatke'; ?></button>
            </form>
        </div>
    </div>

    <!-- Modalno okno za urejanje filtrov v stranskem meniju -->
    <div id="modal-sidebar-filters" class="modal hidden">
        <div class="modal-content" style="max-width: 450px;">
            <span class="close-modal close-filters-modal">&times;</span>
            <h3><?php echo $txt['edit_filters_title'] ?? 'Uredi hitre filtre'; ?></h3>
            <form id="form-sidebar-filters">
                <div class="form-group">
                    <label for="filter-lbl-vse"><?php echo $txt['filter_btn_1'] ?? 'Gumb 1 (Vse):'; ?></label>
                    <input type="text" id="filter-lbl-vse" required>
                </div>
                <div class="form-group">
                    <label for="filter-lbl-poloznice"><?php echo $txt['filter_btn_2'] ?? 'Gumb 2:'; ?></label>
                    <input type="text" id="filter-lbl-poloznice" required>
                </div>
                <div class="form-group">
                    <label for="filter-lbl-nakupi"><?php echo $txt['filter_btn_3'] ?? 'Gumb 3:'; ?></label>
                    <input type="text" id="filter-lbl-nakupi" required>
                </div>
                <div class="form-group">
                    <label for="filter-lbl-kreditna"><?php echo $txt['filter_btn_4'] ?? 'Gumb 4:'; ?></label>
                    <input type="text" id="filter-lbl-kreditna" required>
                </div>
                <div class="form-group">
                    <label for="filter-lbl-narocnine"><?php echo $txt['filter_btn_5'] ?? 'Gumb 5:'; ?></label>
                    <input type="text" id="filter-lbl-narocnine" required>
                </div>
                <div class="form-group">
                    <label for="filter-lbl-avto"><?php echo $txt['filter_btn_6'] ?? 'Gumb 6:'; ?></label>
                    <input type="text" id="filter-lbl-avto" required>
                </div>
                <div class="form-group">
                    <label for="filter-lbl-ostalo"><?php echo $txt['filter_btn_7'] ?? 'Gumb 7:'; ?></label>
                    <input type="text" id="filter-lbl-ostalo" required>
                </div>
                <button type="submit" class="btn-primary btn-full"><?php echo $txt['btn_save_filters'] ?? 'Shrani nazive filtrov'; ?></button>
            </form>
        </div>
    </div>

    <!-- Modalno okno za prenos položnic in naročnin iz prejšnjega meseca -->
    <div id="modal-copy-prev-month" class="modal hidden">
        <div class="modal-content" style="max-width: 520px;">
            <span class="close-modal close-copy-modal">&times;</span>
            <h3 id="copy-modal-title"><?php echo $txt['modal_copy_title'] ?? 'Prenos transakcij iz prejšnjega meseca'; ?></h3>
            <p id="copy-modal-desc" style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px;"></p>

            <div id="copy-items-container" style="max-height: 280px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; margin-bottom: 14px; background: var(--bg-color);">
                <!-- Dinamični seznam elementov s kljukicami -->
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <button type="button" id="btn-copy-toggle-all" class="btn-link" style="background:none; border:none; color:var(--link-color); font-size:13px; font-weight:600; cursor:pointer; padding:0;"><?php echo $txt['copy_deselect_all'] ?? 'Prekliči izbiro'; ?></button>
                <span id="copy-selected-count" style="font-size: 13px; color: var(--text-muted);"></span>
            </div>

            <div id="copy-modal-msg" class="message"></div>

            <div class="modal-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="btn-submit-copy-prev" class="btn-primary" style="flex: 1; min-width: 140px;"><?php echo $txt['copy_btn_confirm'] ?? '✅ Prenesi izbrane'; ?></button>
                <button type="button" id="btn-cancel-copy-prev" class="btn-secondary close-copy-modal" style="width: auto;"><?php echo $txt['btn_cancel'] ?? 'Prekliči'; ?></button>
            </div>
        </div>
    </div>

    <!-- OBRAZEC ZA PRIJAVO / REGISTRACIJO -->
    <div id="auth-section">
        <!-- 1. PRIJAVNI OBRAZEC (LOGIN) -->
        <div id="login-box">
            <h2 id="login-title"><?php echo $txt['login_title']; ?></h2>
            <div id="login-msg" class="message"></div>

            <form id="login-form" method="POST" action="login.php" autocomplete="on">
                <div class="form-group">
                    <label for="login-email"><?php echo $txt['email']; ?></label>
                    <input type="email" id="login-email" name="email" autocomplete="username" required>
                </div>

                <div class="form-group">
                    <label for="login-geslo"><?php echo $txt['password']; ?></label>
                    <input type="password" id="login-geslo" name="password" autocomplete="current-password" required minlength="8">
                </div>

                <!-- 2FA POLJE PRI PRIJAVI (Če je za račun omogočen 2FA) -->
                <div id="polje-2fa-login" class="form-group hidden">
                    <div class="two-fa-box">
                        <div class="two-fa-header">
                            <span>🛡️</span> <span><?php echo $txt['two_fa_login_prompt'] ?? 'Vnesite 6-mestno 2FA kodo:'; ?></span>
                        </div>
                        <label for="login-two-fa-code" style="display:none;"></label>
                        <input type="text" id="login-two-fa-code" name="two_fa_code" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="one-time-code" class="two-fa-code-input">
                        <small style="color:var(--text-muted); display:block; margin-top:6px; text-align:center;"><?php echo $txt['two_fa_code_hint'] ?? 'Vnesite 6-mestno številko iz aplikacije Google Authenticator.'; ?></small>
                    </div>
                </div>

                <div style="display:none;">
                    <label for="login-website"></label>
                    <input type="text" id="login-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit" id="btn-login-submit" class="btn-primary btn-full"><?php echo $txt['btn_login']; ?></button>

                <p class="switch-link">
                    <span><?php echo $txt['no_account']; ?></span>
                    <a href="#" id="switch-to-register"><?php echo $txt['link_register']; ?></a>
                </p>
            </form>
        </div>

        <!-- 2. REGISTRACIJSKI OBRAZEC (REGISTER) -->
        <div id="register-box" class="hidden">
            <h2 id="register-title"><?php echo $txt['register_title']; ?></h2>
            <div id="reg-msg" class="message"></div>

            <form id="register-form" method="POST" action="register.php" autocomplete="on">
                <div class="form-group">
                    <label for="reg-ime"><?php echo $txt['fullname']; ?></label>
                    <input type="text" id="reg-ime" name="name" autocomplete="name" required>
                </div>

                <div class="form-group">
                    <label for="reg-email"><?php echo $txt['email']; ?></label>
                    <input type="email" id="reg-email" name="email" autocomplete="email username" required>
                </div>

                <div class="form-group">
                    <label for="reg-geslo"><?php echo $txt['password']; ?></label>
                    <input type="password" id="reg-geslo" name="password" autocomplete="new-password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="reg-potrdi"><?php echo $txt['confirm_password']; ?></label>
                    <input type="password" id="reg-potrdi" name="confirm_password" autocomplete="new-password" required minlength="8">
                </div>

                <!-- 2FA NASTAVITEV PRI REGISTRACIJI NOVEGA RAČUNA -->
                <div id="box-2fa-register" class="form-group">
                    <div class="two-fa-box">
                        <div class="two-fa-header">
                            <span>🛡️</span> <span><?php echo $txt['two_fa_title'] ?? 'Dvostopenjska avtentikacija (2FA)'; ?></span>
                        </div>
                        <p class="two-fa-desc"><?php echo $txt['two_fa_secret_desc'] ?? 'Spodnji skrivni ključ vnesite v aplikacijo Google Authenticator:'; ?></p>

                        <div class="secret-key-wrapper">
                            <code id="reg-secret-display" class="secret-key-code">--------</code>
                            <input type="hidden" id="reg-two-fa-secret" name="reg_two_fa_secret">
                            <button type="button" id="reg-btn-copy-secret" class="btn-copy-secret"><?php echo $txt['two_fa_copy_key'] ?? '📋 Kopiraj ključ'; ?></button>
                            <button type="button" id="reg-btn-regen-secret" class="btn-regen-secret" title="<?php echo $txt['two_fa_regen_key'] ?? 'Nov ključ'; ?>">🔄</button>
                        </div>

                        <div style="margin-top: 10px;">
                            <label for="reg-two-fa-code" style="font-size: 13px; margin-bottom: 4px;"><?php echo $txt['two_fa_enter_code'] ?? '6-mestna koda iz aplikacije:'; ?></label>
                            <input type="text" id="reg-two-fa-code" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="one-time-code" class="two-fa-code-input">
                            <small style="color:var(--text-muted); display:block; margin-top:4px; text-align:center;"><?php echo $txt['two_fa_code_hint'] ?? 'Vnesite trenutno 6-mestno številko iz avtentikatorja.'; ?></small>
                        </div>
                    </div>
                </div>

                <div style="display:none;">
                    <label for="reg-website"></label>
                    <input type="text" id="reg-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit" id="btn-register-submit" class="btn-primary btn-full"><?php echo $txt['btn_register']; ?></button>

                <p class="switch-link">
                    <span><?php echo $txt['have_account']; ?></span>
                    <a href="#" id="switch-to-login"><?php echo $txt['link_login']; ?></a>
                </p>
            </form>
        </div>
    </div>

    <!-- NADZORNA PLOŠČA (VIDNA PO PRIJAVI) -->
    <div id="dashboard-section" class="hidden">
        <div class="header">
            <div class="header-actions">
                <button id="btn-nav-trans" class="btn-secondary"><?php echo $txt['btn_transactions']; ?></button>
                <button id="btn-nav-profile" class="btn-secondary"><?php echo $txt['btn_profile']; ?></button>
                <button id="btn-logout" class="btn-danger"><?php echo $txt['btn_logout']; ?></button>
            </div>
        </div>

        <div id="dashboard-msg" class="message"></div>

        <!-- GLAVNA POSTAVITEV Z LOČENIM STRANSKIM TRAKOM -->
        <div class="app-layout">

            <!-- 1. SAMOSTOJNI LEVI STRANSKI TRAK (SIDEBAR) -->
            <aside class="app-sidebar">
                <!-- IZBIRA MESECA -->
                <div class="month-filter-container">
                    <label for="select-month-filter" class="month-filter-label">
                        <?php echo $txt['filter_month_label'] ?? 'Obdobje / Mesec:'; ?></label>
                    <select id="select-month-filter" class="month-dropdown">
                        <option value="vse"><?php echo $txt['all_months'] ?? '📅 Vsi meseci (celo leto)'; ?></option>
                        <option value="01">01 - <?php echo $txt['month_01'] ?? 'Januar'; ?></option>
                        <option value="02">02 - <?php echo $txt['month_02'] ?? 'Februar'; ?></option>
                        <option value="03">03 - <?php echo $txt['month_03'] ?? 'Marec'; ?></option>
                        <option value="04">04 - <?php echo $txt['month_04'] ?? 'April'; ?></option>
                        <option value="05">05 - <?php echo $txt['month_05'] ?? 'Maj'; ?></option>
                        <option value="06">06 - <?php echo $txt['month_06'] ?? 'Junij'; ?></option>
                        <option value="07">07 - <?php echo $txt['month_07'] ?? 'Julij'; ?></option>
                        <option value="08">08 - <?php echo $txt['month_08'] ?? 'Avgust'; ?></option>
                        <option value="09">09 - <?php echo $txt['month_09'] ?? 'September'; ?></option>
                        <option value="10">10 - <?php echo $txt['month_10'] ?? 'Oktober'; ?></option>
                        <option value="11">11 - <?php echo $txt['month_11'] ?? 'November'; ?></option>
                        <option value="12">12 - <?php echo $txt['month_12'] ?? 'December'; ?></option>
                    </select>
                </div>

                <hr class="card-divider" style="margin: 14px 0;">

                <div class="sidebar-header">
                    <h4 class="card-title" style="margin-bottom:0;"><?php echo $txt['quick_filters_title'] ?? 'Hitri filtri'; ?></h4>
                    <button id="btn-edit-filters" class="btn-icon" title="<?php echo $txt['title_edit_filters'] ?? 'Uredi filtre'; ?>">✏️</button>
                </div>

                <!-- Desktop gumbi -->
                <nav class="quick-links desktop-filters">
                    <button class="filter-btn active" data-category="vse">
                        <span class="icon">📊</span> <span id="lbl-f-vse"></span>
                    </button>
                    <button class="filter-btn" data-category="poloznice">
                        <span class="icon">📄</span> <span id="lbl-f-poloznice"></span>
                    </button>
                    <button class="filter-btn" data-category="nakupi">
                        <span class="icon">🛒</span> <span id="lbl-f-nakupi"></span>
                    </button>
                    <button class="filter-btn" data-category="kreditna">
                        <span class="icon">💳</span> <span id="lbl-f-kreditna"></span>
                    </button>
                    <button class="filter-btn" data-category="narocnine">
                        <span class="icon">🔄</span> <span id="lbl-f-narocnine"></span>
                    </button>
                    <button class="filter-btn" data-category="avto">
                        <span class="icon">⛽</span> <span id="lbl-f-avto"></span>
                    </button>
                    <button class="filter-btn" data-category="ostalo">
                        <span class="icon">🏷️</span> <span id="lbl-f-ostalo"></span>
                    </button>
                </nav>

                <!-- Mobilni Dropdown meni -->
                <div class="mobile-filter-wrapper">
                    <label for="mobile-filter-select"></label>
                    <select id="mobile-filter-select" class="mobile-dropdown">
                        <option value="vse"></option>
                        <option value="poloznice"></option>
                        <option value="nakupi"></option>
                        <option value="kreditna"></option>
                        <option value="narocnine"></option>
                        <option value="avto"></option>
                        <option value="ostalo"></option>
                    </select>
                </div>
            </aside>

            <!-- 2. GLAVNI DESNI DEL -->
            <main class="app-main-content">
                <div id="view-transactions">

                    <!-- KARTICA ZGORAJ: BANČNI PROFIL & MESEČNI PREGLED -->
                    <section class="grid-card card-bank-profile">
                        <div class="card-header-flex">
                            <h4 class="card-title"><?php echo $txt['bank_profile_overview'] ?? 'Bančni profil & Mesečni pregled'; ?></h4>
                            <button id="btn-edit-bank" class="btn-icon" title="<?php echo $txt['title_edit_bank'] ?? 'Uredi bančne podatke'; ?>">✏️</button>
                        </div>

                        <div class="bank-stats-grid">
                            <div class="bank-stat-box">
                                <span class="stat-label" id="disp-lbl-zacetno"></span>
                                <span class="stat-value text-success" id="stat-zacetno">0.00 €</span>
                            </div>
                            <div class="bank-stat-box">
                                <span class="stat-label" id="disp-lbl-placa"></span>
                                <span class="stat-value text-success" id="stat-placa">0.00 €</span>
                            </div>
                            <div class="bank-stat-box">
                                <span class="stat-label" id="disp-lbl-krediti"></span>
                                <span class="stat-value text-danger" id="stat-krediti">0.00 €</span>
                            </div>
                            <div class="bank-stat-box">
                                <span class="stat-label" id="disp-lbl-stroski"></span>
                                <span class="stat-value text-danger" id="stat-stroski">0.00 €</span>
                            </div>
                            <div class="bank-stat-box">
                                <span class="stat-label" id="disp-lbl-obresti"></span>
                                <span class="stat-value text-danger" id="stat-obresti">0.00 €</span>
                            </div>
                            <div class="bank-stat-box">
                                <span class="stat-label" id="disp-lbl-varcevanje"></span>
                                <span class="stat-value text-danger" id="stat-varcevanje">0.00 €</span>
                            </div>
                            <div class="bank-stat-box stat-highlight-box">
                                <span class="stat-label"><strong id="disp-lbl-prosto"><?php echo $txt['free_budget'] ?? 'Prosti proračun:'; ?></strong></span>
                                <span class="stat-value" id="stat-prosto"><strong>0.00 €</strong></span>
                            </div>
                        </div>
                    </section>

                    <!-- KARTICA SPODAJ: TRANSAKCIJE (POLNA ŠIRINA) -->
                    <section class="grid-card card-transactions-table" style="margin-top: 20px;">
                        <div class="card-header-flex" style="flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h4 class="card-title" style="margin-bottom: 0;"><?php echo $txt['dashboard_title'] ?? 'Zgodovina transakcij'; ?></h4>
                                <span id="active-filter-badge" class="badge"><?php echo $txt['filter_all'] ?? 'Vse'; ?></span>
                            </div>
                            <div class="header-actions" style="margin-left: auto;">
                                <button id="btn-copy-prev-month" type="button" class="btn-secondary hidden">
                                    <span id="lbl-btn-copy"><?php echo $txt['btn_copy_bills'] ?? '📥 Prenesi položnice iz prejšnjega meseca'; ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="trans-table">
                                <thead>
                                <tr>
                                    <th><?php echo $txt['th_desc']; ?></th>
                                    <th><?php echo $txt['th_amount']; ?></th>
                                    <th><?php echo $txt['th_type']; ?></th>
                                    <th><?php echo $txt['th_category'] ?? 'Kategorija'; ?></th>
                                    <th><?php echo $txt['th_date']; ?></th>
                                </tr>
                                </thead>
                                <tbody id="trans-body">
                                <tr><td colspan="5" style="text-align:center;"><?php echo $txt['no_transactions']; ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                </div>

                <!-- POGLED: MOJ PROFIL -->
                <div id="view-profile" class="hidden">
                    <h3><?php echo $txt['profile_title'] ?? 'Moj Profil'; ?></h3>

                    <div class="profile-info-card">
                        <p style="margin-bottom: 8px;"><strong><?php echo $txt['lbl_name'] ?? 'Ime in priimek:'; ?></strong> <span id="disp-ime" style="font-weight: 600; color: var(--primary-color);">-</span></p>
                        <p style="margin-bottom: 8px;"><strong><?php echo $txt['lbl_current_email'] ?? 'Trenutni E-naslov:'; ?></strong> <span id="disp-email" style="font-weight: 600; color: var(--primary-color);">-</span></p>
                        <p class="subtext" style="margin-bottom: 14px;"><span id="profile-created"></span></p>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" id="btn-show-name-box" class="btn-secondary"><?php echo $txt['btn_change_name'] ?? 'Spremeni ime in priimek'; ?></button>
                            <button type="button" id="btn-show-email-box" class="btn-secondary"><?php echo $txt['btn_change_email'] ?? 'Spremeni e-poštni naslov'; ?></button>
                        </div>
                    </div>

                    <!-- Obrazec za novo ime -->
                    <div id="box-change-name" class="hidden" style="margin-top: 15px; margin-bottom: 20px; padding: 15px; border: 1px dashed var(--border-color); border-radius: 6px;">
                        <h4><?php echo $txt['title_new_name'] ?? 'Sprememba imena in priimka'; ?></h4>
                        <form id="form-change-name">
                            <div class="form-group">
                                <label for="prof-novo-ime"><?php echo $txt['lbl_new_name'] ?? 'Novo ime in priimek:'; ?></label>
                                <input type="text" id="prof-novo-ime" placeholder="<?php echo $txt['ph_new_name'] ?? 'Vnesite novo ime in priimek'; ?>" autocomplete="name" required>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="submit" class="btn-primary" style="width: auto; padding: 8px 18px;"><?php echo $txt['btn_save'] ?? 'Shrani'; ?></button>
                                <button type="button" id="btn-cancel-name" class="btn-secondary" style="width: auto;"><?php echo $txt['btn_cancel'] ?? 'Prekliči'; ?></button>
                            </div>
                        </form>
                    </div>

                    <!-- Obrazec za nov email -->
                    <div id="box-change-email" class="hidden" style="margin-top: 15px; padding: 15px; border: 1px dashed var(--border-color); border-radius: 6px;">
                        <h4><?php echo $txt['title_new_email'] ?? 'Vnos novega e-poštnega naslova'; ?></h4>
                        <form id="form-change-email">
                            <div class="form-group">
                                <label for="prof-novi-email"><?php echo $txt['lbl_new_email'] ?? 'Nov e-poštni naslov:'; ?></label>
                                <input type="email" id="prof-novi-email" autocomplete="email" required>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="submit"><?php echo $txt['btn_confirm_change'] ?? 'Potrdi spremembo'; ?></button>
                                <button type="button" id="btn-cancel-email" class="btn-secondary"><?php echo $txt['btn_cancel'] ?? 'Prekliči'; ?></button>
                            </div>
                        </form>
                    </div>

                    <hr class="divider">

                    <h3><?php echo $txt['two_fa_profile_title'] ?? 'Varnost: Google Authenticator (2FA)'; ?></h3>
                    <div class="profile-info-card" id="profile-2fa-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <p style="margin: 0 0 4px 0;"><strong><?php echo $txt['two_fa_title'] ?? 'Dvostopenjska avtentikacija:'; ?></strong></p>
                                <span id="disp-2fa-badge" class="two-fa-badge inactive"><?php echo $txt['two_fa_status_inactive'] ?? '2FA ni nastavljen'; ?></span>
                            </div>
                            <button type="button" id="btn-toggle-profile-2fa" class="btn-secondary"><?php echo $txt['btn_setup_2fa'] ?? 'Nastavi / Posodobi 2FA'; ?></button>
                        </div>

                        <!-- Obrazec za nastavitev 2FA v profilu -->
                        <div id="box-manage-2fa" class="hidden" style="margin-top: 15px; padding: 15px; border: 1px dashed var(--border-color); border-radius: 6px;">
                            <p class="two-fa-desc"><?php echo $txt['two_fa_secret_desc'] ?? 'Spodnji skrivni ključ vnesite v aplikacijo Google Authenticator:'; ?></p>

                            <div class="secret-key-wrapper">
                                <code id="prof-secret-display" class="secret-key-code">--------</code>
                                <input type="hidden" id="prof-two-fa-secret">
                                <button type="button" id="prof-btn-copy-secret" class="btn-copy-secret"><?php echo $txt['two_fa_copy_key'] ?? '📋 Kopiraj ključ'; ?></button>
                                <button type="button" id="prof-btn-regen-secret" class="btn-regen-secret" title="<?php echo $txt['two_fa_regen_key'] ?? 'Nov ključ'; ?>">🔄</button>
                            </div>

                            <form id="form-profile-2fa">
                                <div class="form-group">
                                    <label for="prof-2fa-code"><?php echo $txt['two_fa_enter_code'] ?? '6-mestna koda iz aplikacije:'; ?></label>
                                    <input type="text" id="prof-2fa-code" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="one-time-code" class="two-fa-code-input" required>
                                </div>
                                <div class="form-group">
                                    <label for="prof-2fa-pass"><?php echo $txt['lbl_current_pass'] ?? 'Trenutno geslo za potrditev:'; ?></label>
                                    <input type="password" id="prof-2fa-pass" autocomplete="current-password" required>
                                </div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="submit" class="btn-primary" style="width: auto; flex: 1;"><?php echo $txt['btn_save'] ?? 'Shrani 2FA'; ?></button>
                                    <button type="button" id="btn-cancel-profile-2fa" class="btn-secondary" style="width: auto;"><?php echo $txt['btn_cancel'] ?? 'Prekliči'; ?></button>
                                    <button type="button" id="btn-disable-profile-2fa" class="btn-danger hidden" style="width: auto;"><?php echo $txt['two_fa_disabled'] ?? 'Izklopi 2FA'; ?></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <hr class="divider">

                    <h3><?php echo $txt['security_title'] ?? 'Sprememba gesla'; ?></h3>
                    <form id="profile-password-form">
                        <label for="prof-username-hidden"></label>
                        <input type="text" id="prof-username-hidden" autocomplete="username" style="display:none;" aria-hidden="true">

                        <div class="form-group">
                            <label for="prof-staro-geslo"><?php echo $txt['lbl_current_pass'] ?? 'Trenutno geslo:'; ?></label>
                            <input type="password" id="prof-staro-geslo" autocomplete="current-password" required>
                        </div>
                        <div class="form-group">
                            <label for="prof-novo-geslo"><?php echo $txt['lbl_new_pass'] ?? 'Novo geslo (vsaj 8 znakov):'; ?></label>
                            <input type="password" id="prof-novo-geslo" autocomplete="new-password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="prof-potrdi-geslo"><?php echo $txt['lbl_repeat_pass'] ?? 'Ponovite novo geslo:'; ?></label>
                            <input type="password" id="prof-potrdi-geslo" autocomplete="new-password" required minlength="8">
                        </div>
                        <button type="submit"><?php echo $txt['btn_submit_pass'] ?? 'Zahtevaj spremembo gesla'; ?></button>
                    </form>
                </div>
            </main>

        </div>
    </div>

<?php require_once "footer.php"; ?>
