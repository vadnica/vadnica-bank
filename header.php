<?php require_once "lang_init.php"; ?>
<!DOCTYPE html>
<html lang="<?php echo $txt['lang_code']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $txt['title']; ?></title>
    <meta name="description" content="<?php echo $txt['meta_desc']; ?>">
    <meta name="author" content="Borut Bukovnik">
    <meta name="robots" content="index, follow">

    <!-- Ikone za zavihek brskalnika, zaznamke (bookmarks) in mobilne naprave -->
    <link rel="icon" type="image/x-icon" href="icons/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="icons/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/apple-touch-icon.png">
    <link rel="manifest" href="icons/site.webmanifest">

    <link rel="alternate" hreflang="sl" href="https://bancni.stroski.vadnica.org/?lang=sl" />
    <link rel="alternate" hreflang="en" href="https://bancni.stroski.vadnica.org/?lang=en" />
    <link rel="alternate" hreflang="x-default" href="https://bancni.stroski.vadnica.org/" />

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@type": "Person",
                    "@id": "https://vadnica.org/#author",
                    "name": "Borut Bukovnik",
                    "url": "https://vadnica.org",
                    "sameAs": [
                        "https://blog.vadnica.org",
                        "https://store.vadnica.org",
                        "https://password.manager.vadnica.org",
                        "https://bancni.stroski.vadnica.org"
                    ]
                },
                {
                    "@type": "WebApplication",
                    "name": "Moje finance",
                    "url": "https://bancni.stroski.vadnica.org",
                    "applicationCategory": "FinanceApplication",
                    "author": { "@id": "https://vadnica.org/#author" }
                }
            ]
        }
    </script>

    <link rel="stylesheet" href="style.css">
    <script>
        const I18N = <?php echo json_encode($txt); ?>;
    </script>
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="main-content">
    <div class="container">
