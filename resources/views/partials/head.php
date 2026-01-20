<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p%20logo.jpeg') ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Wish2Padel - Join padel tournaments, leagues, and community events. Manage team, and compete in padel across Saudi Arabia." />
    <meta name="keywords" content="padel, wish2padel, tournamnet padel, padel arab, league padel, play padel, league padel saudi, liga padel arab, league padel saudi" />
    <meta name="author" content="Wish2Padel Team" />
    <meta property="og:title" content="Wish2Padel - Dashboard" />
    <meta property="og:description" content="Join padel tournaments, leagues, and community events. Manage team, and compete in padel across Saudi Arabia." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://www.wish2padel.com/" />
    <meta property="og:image" content="<?= asset('assets/image/w2p%20logo.jpeg') ?>" />

    <title><?= isset($title) ? htmlspecialchars($title) : 'Wish2Padel' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <?php if (isset($css)): ?>
        <link rel="stylesheet" href="<?= asset($css) ?>">
    <?php else: ?>
        <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <?php endif; ?>
</head>
