<?php
$nav_lang = $lang ?? ($_SESSION['lang'] ?? 'sl');
$logo_light = ($nav_lang === 'en') ? 'img/tutorial-from-html-to-arduino.svg' : 'img/vadnica-od-html-do-arduino.svg';
$logo_dark = ($nav_lang === 'en') ? 'img/tutorial-from-html-to-arduino-dark.svg' : 'img/vadnica-od-html-do-arduino-dark.svg';
?>
<header class="site-header" id="navbar">
    <div class="header-inner">
        <!-- Levi prostor z logotipom Vadnica.org -->
        <div class="header-left">
            <a href="https://vadnica.org" target="_blank" rel="noopener noreferrer" class="navbar-logo-link" title="Vadnica.org">
                <img src="<?php echo $logo_light; ?>" alt="Vadnica.org Logo" class="nav-logo logo-light">
                <img src="<?php echo $logo_dark; ?>" alt="Vadnica.org Logo" class="nav-logo logo-dark">
            </a>
        </div>

        <!-- Sredina: Logotip in Naslov aplikacije -->
        <div class="brand">
            <span class="logo-icon">🏦</span>
            <h1><?php echo $txt['brand']; ?></h1>
        </div>

        <!-- Desna stran: Izbira jezika, About Me in Tema -->
        <div class="header-controls">
            <!-- Namizni pogled kontrolnikov -->
            <div class="header-desktop-controls">
                <div class="lang-switcher">
                    <a href="?lang=sl" class="<?php echo $lang === 'sl' ? 'active' : ''; ?>">SL</a> |
                    <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
                </div>
                <a href="<?php echo $txt['about_me_url'] ?? ($lang === 'sl' ? 'https://vadnica.org/index.php?id=about_me&lang=sl' : 'https://vadnica.org/index.php?id=about_me&lang=en'); ?>" target="_blank" rel="noopener" class="about-me-btn" title="<?php echo $txt['about_me'] ?? 'About me'; ?>">👤</a>
                <button id="theme-toggle" class="theme-toggle-btn" aria-label="Preklopi temo" title="<?php echo $txt['menu_appearance'] ?? 'Preklopi temo'; ?>">🌙</button>
            </div>

            <!-- Mobilni meni / Dropdown z nastavitvami -->
            <div class="header-menu-container">
                <button id="header-menu-toggle" class="header-menu-btn" aria-label="<?php echo $txt['menu_title'] ?? 'Meni z nastavitvami'; ?>" aria-expanded="false" title="<?php echo $txt['menu_title'] ?? 'Meni'; ?>">
                    <span class="menu-icon">⚙️</span>
                </button>
                <div id="header-menu-dropdown" class="header-dropdown-menu hidden">
                    <div class="dropdown-section">
                        <span class="dropdown-label"><?php echo $txt['menu_language'] ?? 'Jezik / Language:'; ?></span>
                        <div class="dropdown-lang-group">
                            <a href="?lang=sl" class="dropdown-lang-btn <?php echo $lang === 'sl' ? 'active' : ''; ?>">
                                <span>🇸🇮</span> <span>Slovenščina (SL)</span>
                            </a>
                            <a href="?lang=en" class="dropdown-lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>">
                                <span>🇬🇧</span> <span>English (EN)</span>
                            </a>
                        </div>
                    </div>
                    <hr class="dropdown-divider">
                    <div class="dropdown-section">
                        <span class="dropdown-label"><?php echo $txt['menu_appearance'] ?? 'Videz / Tema:'; ?></span>
                        <button id="theme-toggle-mobile" class="dropdown-menu-item btn-theme-choice" type="button">
                            <span class="theme-mode-icon">🌙</span>
                            <span id="theme-mode-text"><?php echo $txt['theme_dark'] ?? 'Temni način'; ?></span>
                        </button>
                    </div>
                    <hr class="dropdown-divider">
                    <div class="dropdown-section">
                        <a href="<?php echo $txt['about_me_url'] ?? ($lang === 'sl' ? 'https://vadnica.org/index.php?id=about_me&lang=sl' : 'https://vadnica.org/index.php?id=about_me&lang=en'); ?>" target="_blank" rel="noopener" class="dropdown-menu-item" title="<?php echo $txt['about_me'] ?? 'About me'; ?>">
                            <span>👤</span>
                            <span><?php echo $txt['menu_about_me'] ?? 'O avtorju (Vadnica.org)'; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
