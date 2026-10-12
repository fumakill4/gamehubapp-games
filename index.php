<?php
date_default_timezone_set('Asia/Makassar');

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === '/guides' || $requestPath === '/guides/') {
  require __DIR__ . '/guides.php';
  exit;
}
if (preg_match('#^/guides/([a-z0-9-]+)/?$#', $requestPath, $guideMatch)) {
  $_GET['slug'] = $guideMatch[1];
  require __DIR__ . '/guide.php';
  exit;
}

require __DIR__ . '/guide-data.php';

require_once __DIR__ . '/security.php';
$adminRequested = isset($_GET['ywag_admin']) && $_GET['ywag_admin'] === '1';
$adminSecret = (string)(getenv('VISIT_ADMIN_KEY') ?: '');
$adminProvided = (string)($_SERVER['HTTP_X_VISIT_ADMIN_KEY'] ?? $_SERVER['PHP_AUTH_PW'] ?? '');
$isYwagAdmin = $adminRequested && visitor_admin_authorized($adminSecret, $adminProvided);
if ($adminRequested && !$isYwagAdmin) {
  header('WWW-Authenticate: Basic realm="Visitor statistics"');
  http_response_code(401);
  exit('Administrator authentication required');
}
$privateStatsDir = dirname(__DIR__) . '/private_bot';
$analyticsEnabled = is_dir($privateStatsDir) && is_writable($privateStatsDir);
$visitStatsPath = $privateStatsDir . '/ywag-visits.json';
$todayKey = date('Y-m-d');
$visitStats = [
  'total' => 0,
  'today' => [],
  'devices' => ['mobile' => 0, 'desktop' => 0],
  'last_visit' => null,
  'last_device' => null,
];

if (is_file($visitStatsPath)) {
  $loadedStats = json_decode((string) file_get_contents($visitStatsPath), true);
  if (is_array($loadedStats)) {
    $visitStats = array_replace_recursive($visitStats, $loadedStats);
  }
}

$isMobileVisit = preg_match('/Android|iPhone|iPad|iPod|Mobile/i', $_SERVER['HTTP_USER_AGENT'] ?? '') === 1;
$visitDevice = $isMobileVisit ? 'mobile' : 'desktop';

if ($analyticsEnabled && !$isYwagAdmin && empty($_COOKIE['ywag_visit_seen'])) {
  $visitStats['total'] = (int) ($visitStats['total'] ?? 0) + 1;
  $visitStats['today'][$todayKey] = (int) ($visitStats['today'][$todayKey] ?? 0) + 1;
  $visitStats['devices'][$visitDevice] = (int) ($visitStats['devices'][$visitDevice] ?? 0) + 1;
  $visitStats['last_visit'] = date('Y-m-d H:i:s');
  $visitStats['last_device'] = $visitDevice;

  file_put_contents($visitStatsPath, json_encode($visitStats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  setcookie('ywag_visit_seen', '1', [
    'expires' => time() + 21600,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

$todayVisits = (int) ($visitStats['today'][$todayKey] ?? 0);
$mobileVisits = (int) ($visitStats['devices']['mobile'] ?? 0);
$desktopVisits = (int) ($visitStats['devices']['desktop'] ?? 0);

if (!function_exists('asset_url')) {
  function asset_url(string $path): string {
    $fullPath = __DIR__ . $path;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : '1';
    return $path . '?v=' . $version;
  }
}

if (!function_exists('esc')) {
  function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }
}

$schemaBaseUrl = 'https://gamehubapp.games';
$schemaLogoUrl = $schemaBaseUrl . '/assets/img/gamehub-games-logo.webp';
$schemaGuideItems = [];
foreach (array_slice($article_cards, 0, 8) as $position => $article) {
  $guide = $guides[$article['slug']] ?? null;
  if (!$guide) {
    continue;
  }
  $schemaGuideItems[] = [
    '@type' => 'ListItem',
    'position' => $position + 1,
    'name' => $guide['title'],
    'url' => $schemaBaseUrl . '/guides/' . $article['slug'] . '/',
  ];
}
$schemaCategoryItems = [];
foreach (array_slice($category_cards, 0, 8) as $position => $category) {
  $schemaCategoryItems[] = [
    '@type' => 'ListItem',
    'position' => $position + 1,
    'name' => $category['title'],
    'url' => $schemaBaseUrl . '/guides/' . $category['slug'] . '/',
  ];
}
$homepageSchema = [
  '@context' => 'https://schema.org',
  '@graph' => [
    [
      '@type' => 'WebSite',
      '@id' => $schemaBaseUrl . '/#website',
      'name' => 'gamehub Games',
      'alternateName' => ['gamehub Games India', 'gamehub Cricket Games', 'gamehub Game Guides'],
      'url' => $schemaBaseUrl . '/',
      'inLanguage' => 'en-IN',
      'description' => 'gamehub Games is an India-focused cricket culture and game guide hub covering live cricket context, Teen Patti, Rummy, Andar Bahar, quick-play formats, strategy guides and responsible play reminders.',
      'publisher' => ['@id' => $schemaBaseUrl . '/#organization'],
    ],
    [
      '@type' => 'Organization',
      '@id' => $schemaBaseUrl . '/#organization',
      'name' => 'gamehub Games',
      'url' => $schemaBaseUrl . '/',
      'logo' => [
        '@type' => 'ImageObject',
        'url' => $schemaLogoUrl,
        'width' => 260,
        'height' => 105,
      ],
      'image' => $schemaLogoUrl,
      'description' => 'gamehub Games publishes mobile-friendly cricket culture updates, game guides, card game explainers, quick-play overviews and responsible entertainment notes for Indian users.',
    ],
    [
      '@type' => 'BreadcrumbList',
      '@id' => $schemaBaseUrl . '/#breadcrumb',
      'itemListElement' => [
        [
          '@type' => 'ListItem',
          'position' => 1,
          'name' => 'Home',
          'item' => $schemaBaseUrl . '/',
        ],
      ],
    ],
    [
      '@type' => ['WebApplication', 'SoftwareApplication'],
      '@id' => $schemaBaseUrl . '/#webapp',
      'name' => 'gamehub Games Guide Hub',
      'url' => $schemaBaseUrl . '/',
      'applicationCategory' => 'GameApplication',
      'operatingSystem' => 'Android, iOS, Web',
      'browserRequirements' => 'Requires a modern mobile or desktop browser.',
      'description' => 'A mobile-first guide hub for cricket culture, live score context, Indian card classics, quick-play guides, strategy articles and safer entertainment habits.',
      'image' => $schemaLogoUrl,
      'publisher' => ['@id' => $schemaBaseUrl . '/#organization'],
      'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'INR',
      ],
      'audience' => [
        '@type' => 'Audience',
        'audienceType' => 'Indian users interested in cricket culture and game guide content',
      ],
      'featureList' => [
        'Cricket games India guide',
        'Live match hub and IPL score context',
        'Teen Patti and Rummy guide articles',
        'Andar Bahar beginner guide',
        'Quick-play and strategy game explainers',
        'Responsible play reminders',
      ],
    ],
    [
      '@type' => 'Article',
      '@id' => $schemaBaseUrl . '/#homepage-article',
      'headline' => 'gamehub Games India Cricket Culture and Game Guide Hub',
      'description' => 'Explore gamehub Games guides for cricket culture, live match context, IPL 2026 updates, Teen Patti, Rummy, Andar Bahar, quick-play formats and responsible entertainment habits.',
      'url' => $schemaBaseUrl . '/',
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $schemaBaseUrl . '/',
      ],
      'image' => [
        '@type' => 'ImageObject',
        'url' => $schemaBaseUrl . '/assets/img/hero_bg.webp',
        'width' => 1600,
        'height' => 900,
      ],
      'author' => [
        '@type' => 'Organization',
        'name' => 'gamehub Games Editorial Team',
        'url' => $schemaBaseUrl . '/',
      ],
      'publisher' => ['@id' => $schemaBaseUrl . '/#organization'],
      'datePublished' => '2026-05-18T00:00:00+08:00',
      'dateModified' => '2026-05-19T03:45:00+08:00',
      'inLanguage' => 'en-IN',
      'about' => [
        'cricket games India',
        'IPL 2026 live score context',
        'Teen Patti guide India',
        'Rummy guide India',
        'Andar Bahar guide India',
        'quick-play games India',
        'responsible play India',
      ],
    ],
    [
      '@type' => 'ItemList',
      '@id' => $schemaBaseUrl . '/#trending-category-list',
      'name' => 'Trending gamehub Games India Categories',
      'itemListElement' => $schemaCategoryItems,
    ],
    [
      '@type' => 'ItemList',
      '@id' => $schemaBaseUrl . '/#latest-guide-list',
      'name' => 'Fresh gamehub Games and Cricket Guides',
      'itemListElement' => $schemaGuideItems,
    ],
    [
      '@type' => 'FAQPage',
      '@id' => $schemaBaseUrl . '/#faq',
      'mainEntity' => [
        [
          '@type' => 'Question',
          'name' => 'What is gamehub Games?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'gamehub Games is an India-focused cricket culture and game information hub with guides for cricket context, Teen Patti, Rummy, Andar Bahar, quick-play formats and responsible entertainment habits.',
          ],
        ],
        [
          '@type' => 'Question',
          'name' => 'Does gamehub Games cover cricket updates?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. The site covers cricket culture, IPL 2026 score context, live match hub guidance, upcoming match watchlists and cricket guide articles for Indian readers.',
          ],
        ],
        [
          '@type' => 'Question',
          'name' => 'Which game guides are available?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Readers can explore guides for cricket games, Teen Patti, Rummy, Andar Bahar, Aviator-style quick play, Wingo-style quick play, strategy games and fan-favorite Indian game categories.',
          ],
        ],
        [
          '@type' => 'Question',
          'name' => 'Is gamehub Games built for mobile users?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. gamehub Games is designed as a mobile-first guide hub so Indian readers can scan cricket updates, game articles and safety notes comfortably on phone screens.',
          ],
        ],
        [
          '@type' => 'Question',
          'name' => 'Does the site encourage responsible play?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. The content focuses on informed choices, session limits, clearer game literacy and responsible entertainment reminders across cricket and game guide topics.',
          ],
        ],
      ],
    ],
  ],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=AW-18157597075"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'AW-18157597075');
    gtag('event', 'conversion', {'send_to': 'AW-18157597075/R3ERCL-34q8cEJPjm9JD'});
  </script>
  <title>gamehub Games | Cricket Culture & Trending Game Guides for India</title>
  <meta name="description" content="Explore cricket culture, trending game guides, popular card classics, quick-play formats, and responsible entertainment insights for Indian users.">
  <meta name="robots" content="<?= $isYwagAdmin ? 'noindex, nofollow' : 'index, follow' ?>">
  <link rel="canonical" href="https://gamehubapp.games/">
  <meta property="og:title" content="gamehub Games | Cricket Culture & Trending Game Guides for India">
  <meta property="og:description" content="Explore cricket culture, trending game guides, popular card classics, quick-play formats, and responsible entertainment insights for Indian users.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://gamehubapp.games/">
  <meta property="og:site_name" content="gamehub Games">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#07111F">
  <link rel="icon" href="<?= esc(asset_url('/assets/img/favicon.png')) ?>" type="image/png">
  <link rel="preload" href="<?= esc(asset_url('/assets/img/hero_bg.webp')) ?>" as="image" fetchpriority="high">
  <link rel="preload" href="<?= esc(asset_url('/assets/img/bat_ball_transparent.webp')) ?>" as="image" fetchpriority="high">
  <link rel="preload" href="<?= esc(asset_url('/assets/css/styles.css')) ?>" as="style">
  <link rel="stylesheet" href="<?= esc(asset_url('/assets/css/styles.css')) ?>">
  <script type="application/ld+json" id="gamehubGamesRichResultSchema"><?= json_encode($homepageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header" id="top">
    <nav class="nav-shell" aria-label="Primary navigation">
      <a class="brand" href="/" aria-label="gamehub Games home">
        <img class="brand-logo" src="<?= esc(asset_url('/assets/img/gamehub-games-logo.webp')) ?>" alt="gamehub Games" width="224" height="90" decoding="async">
      </a>
      <button class="menu-toggle" type="button" aria-controls="primary-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <div class="nav-links" id="primary-menu">
        <a href="/">Home</a>
        <a href="#cricket">Cricket</a>
        <a href="#trending">Trending Games</a>
        <a href="#guides">Guides</a>
        <a href="#safety">Safety</a>
        <a href="#blog">Blog</a>
      </div>
      <div class="nav-actions">
        <a class="search-btn" href="#blog" aria-label="Search guides">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.8 18.1a7.3 7.3 0 1 1 5.2-2.1l4 4-1.6 1.6-4-4a7.2 7.2 0 0 1-3.6.5Zm0-2.4a4.9 4.9 0 1 0 0-9.8 4.9 4.9 0 0 0 0 9.8Z"/></svg>
        </a>
        <a class="btn btn-primary" href="#trending">Explore Games <span aria-hidden="true" class="btn-arrow">→</span></a>
      </div>
    </nav>
  </header>

  <main id="main">
    <section class="hero section-frame">
      <div class="mandala mandala-left" aria-hidden="true"></div>
      <div class="stadium-lights" aria-hidden="true"></div>
      <div class="hero-content">
        <p class="eyebrow">Your Ultimate Hub</p>
        <h1>Cricket Fever Meets <span class="text-orange">India&rsquo;s</span> <span class="text-green">Hottest</span> Games</h1>
        <p class="hero-copy">Explore cricket culture, trending play styles, popular card classics, and fast-paced digital entertainment — all in one modern hub for Indian users.</p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="#trending">Explore Trending Games <span aria-hidden="true" class="btn-arrow">→</span></a>
          <a class="btn btn-outline" href="#cricket">View Cricket Guides</a>
        </div>
        <div class="hero-trust" aria-label="gamehub Games highlights">
          <div><span class="mini-icon shield"></span><strong>Latest Updates</strong><small>Stay ahead always.</small></div>
          <div><span class="mini-icon medal"></span><strong>Expert Guides</strong><small>Learn & improve.</small></div>
          <div><span class="mini-icon safe"></span><strong>Safe & Responsible</strong><small>Play smart, always.</small></div>
          <div><span class="mini-icon india"></span><strong>Made for India</strong><small>For every fan.</small></div>
        </div>
      </div>
      <div class="hero-visual" aria-label="Cricket bat, ball and guide cards">
        <div class="bat-ball-art" aria-hidden="true">
          <img src="<?= esc(asset_url('/assets/img/bat_ball_transparent.webp')) ?>" class="bat-img" alt="Cricket Bat and Ball" width="420" height="900" fetchpriority="high" decoding="async">
        </div>
        <div class="floating-cards">
          <?php foreach ($hero_cards as $card): ?>
            <a class="float-card <?= esc($card['tone']) ?>" href="/guides/<?= esc($card['slug']) ?>/">
              <div class="card-text"><h3><?= esc($card['title']) ?></h3><p><?= esc($card['copy']) ?></p></div>
              <img src="<?= esc(asset_url('/assets/img/thumb_' . $card['thumb'] . '.webp')) ?>" alt="<?= esc($card['title']) ?>" class="thumb" width="120" height="76" decoding="async">
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section-panel" id="trending">
      <div class="section-heading">
        <div>
          <p class="accent-line"></p>
          <h2>Trending in India Right Now</h2>
        </div>
        <a class="btn btn-small" href="/guides/">View All Categories</a>
      </div>
      <div class="category-grid">
        <?php
          foreach ($category_cards as $cat):
            $catSlug = preg_replace('/[^a-z0-9_-]/', '', $cat['thumb']);
        ?>
        <a class="category-card" href="/guides/<?= esc($cat['slug']) ?>/">
          <div class="card-art <?= esc($catSlug) ?>" role="img" aria-label="<?= esc($cat['title']) ?> illustration">
            <img src="<?= esc(asset_url('/assets/img/thumb_' . $catSlug . '.webp')) ?>" alt="" loading="lazy" decoding="async" width="640" height="360">
          </div>
          <div class="card-content">
            <h3><?= esc($cat['title']) ?></h3>
            <p><?= esc($cat['copy']) ?></p>
            <span class="arrow" aria-hidden="true">→</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="heartbeat section-panel" id="cricket">
      <div class="heartbeat-copy">
        <p class="accent-line"></p>
        <h2>Cricket is the Heartbeat</h2>
        <p>From electrifying matches to iconic moments, we bring you closer to the spirit of Indian cricket.</p>
        <div class="feature-grid">
          <article><span>⚡</span><h3>Match Energy</h3><p>Feel the excitement with every over.</p></article>
          <article><span>🎯</span><h3>Big Chase Moments</h3><p>Relive India’s most thrilling run chases.</p></article>
          <article><span>📊</span><h3>Player Form Watch</h3><p>Track form, stats & standout players.</p></article>
          <article><span>📖</span><h3>Cricket Game Guides</h3><p>Tips, strategies & how-to guides.</p></article>
        </div>
      </div>
      <aside class="scoreboard" aria-label="Sample editorial cricket dashboard">
        <div class="score-top">
          <span class="live-badge">Latest</span>
          <span>IPL 2026</span>
          <span>Match 64</span>
          <span>Jaipur, May 19</span>
        </div>
        <div class="score-team">
          <div><span class="team-badge mumbai">LSG</span><strong>Lucknow Super Giants</strong></div>
          <div><strong>220/5</strong><small>20 overs</small></div>
        </div>
        <div class="score-team">
          <div><span class="team-badge chennai">RR</span><strong>Rajasthan Royals</strong></div>
          <div><strong>225/3</strong><small>19.1 overs</small></div>
        </div>
        <p class="result-bar">Rajasthan Royals beat Lucknow Super Giants by 7 wickets</p>
        <div class="score-stats">
          <div><small>Latest Result</small><strong>RR won</strong><span>May 19, Jaipur</span></div>
          <div><small>Next Match</small><strong>KKR vs MI</strong><span>May 20, 7:30 PM IST</span></div>
          <div><small>Watchlist</small><strong>GT vs CSK</strong><span>May 21, 7:30 PM IST</span></div>
        </div>
      </aside>
    </section>

    <section class="section-panel love-section">
      <div class="section-heading">
        <div>
          <p class="accent-line"></p>
          <h2>Why Users Love gamehub Games India</h2>
        </div>
      </div>
      <div class="love-grid">
        <article><span class="big-icon">⚡</span><h3>Fast Updates</h3><p>Live scores, game trends & hot picks — always fresh.</p></article>
        <article><span class="big-icon">🃏</span><h3>Familiar Games</h3><p>India’s favorite card & casual games, all in one place.</p></article>
        <article><span class="big-icon">📱</span><h3>Mobile First</h3><p>Smooth experience anytime, anywhere.</p></article>
        <article><span class="big-icon">📄</span><h3>Fresh Guides</h3><p>Helpful how-to guides, tips & winning strategies.</p></article>
      </div>
    </section>

    <section class="section-panel" id="guides">
      <span class="anchor-offset" id="blog" aria-hidden="true"></span>
      <div class="section-heading">
        <div>
          <p class="accent-line"></p>
          <h2>Fresh Game Guides & Cricket Buzz</h2>
        </div>
        <a class="btn btn-small" href="/guides/">Read All Articles</a>
      </div>
      <div class="article-grid">
        <?php
          foreach ($article_cards as $article):
            $guide = $guides[$article['slug']];
            $articleSlug = preg_replace('/[^a-z0-9_-]/', '', $guide['thumb']);
        ?>
        <a class="article-card" href="/guides/<?= esc($article['slug']) ?>/">
          <div class="article-art <?= esc($articleSlug) ?>" role="img" aria-label="<?= esc($guide['title']) ?> thumbnail">
            <img src="<?= esc(asset_url('/assets/img/thumb_' . $articleSlug . '.webp')) ?>" alt="" loading="lazy" decoding="async" width="640" height="360">
          </div>
          <div class="article-body">
            <span class="badge"><?= esc($article['type']) ?></span>
            <h3><?= esc($guide['title']) ?></h3>
            <p><?= esc($guide['excerpt']) ?></p>
            <small><?= esc($article['slug'] === 'top-10-cricket-tips' ? 'May 15, 2026 • 5 min read' : ($article['slug'] === 'teen-patti-vs-rummy' ? 'May 12, 2026 • 4 min read' : ($article['slug'] === 'why-quick-play-games-are-popular-in-india' ? 'May 10, 2026 • 4 min read' : ($article['slug'] === 'beginners-guide-to-andar-bahar' ? 'May 8, 2026 • 6 min read' : 'May 6, 2026 • 4 min read')))) ?></small>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="smart-section section-panel" id="safety">
      <div class="mandala mandala-right" aria-hidden="true"></div>
      <h2>Explore Better. Play Smarter.</h2>
      <p>We promote informed choices, responsible play & a safe digital experience for all users.</p>
      <div class="badge-grid">
        <article><span>📚</span><h3>Game Guides</h3><p>Well-researched guides to help you play better.</p></article>
        <article><span>🛡️</span><h3>Responsible Play</h3><p>Entertainment only. Play responsibly always.</p></article>
        <article><span>📱</span><h3>Mobile Friendly</h3><p>Optimized for all devices & screens.</p></article>
        <article><span>🔄</span><h3>Updated Content</h3><p>Fresh updates, latest trends & real insights.</p></article>
        <article><span>✅</span><h3>Clear Reviews</h3><p>Honest opinions for smarter decisions.</p></article>
      </div>
    </section>

    <?php if ($isYwagAdmin): ?>
    <section class="admin-visit-panel section-panel" aria-label="Private gamehub Games visit counter">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Private Analytics</p>
          <h2>gamehub Games Visit Counter</h2>
        </div>
        <span class="admin-pill">Admin only</span>
      </div>
      <div class="admin-stat-grid">
        <article class="admin-stat-card">
          <span>Total visits</span>
          <strong><?= number_format((int) ($visitStats['total'] ?? 0)) ?></strong>
        </article>
        <article class="admin-stat-card">
          <span>Today</span>
          <strong><?= number_format($todayVisits) ?></strong>
        </article>
        <article class="admin-stat-card">
          <span>Mobile</span>
          <strong><?= number_format($mobileVisits) ?></strong>
        </article>
        <article class="admin-stat-card">
          <span>Desktop</span>
          <strong><?= number_format($desktopVisits) ?></strong>
        </article>
        <article class="admin-stat-card">
          <span>Last visit</span>
          <strong><?= esc((string) ($visitStats['last_visit'] ?? 'No visits yet')) ?></strong>
          <small><?= esc((string) ($visitStats['last_device'] ?? 'unknown')) ?></small>
        </article>
      </div>
      <p class="admin-note">This private panel is hidden from public visitors. Opening the admin URL does not increase the visit count.</p>
    </section>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="footer-brand">
      <a class="brand footer-logo-link" href="#top" aria-label="gamehub Games home">
        <img class="brand-logo footer-logo" src="<?= esc(asset_url('/assets/img/gamehub-games-logo.webp')) ?>" alt="gamehub Games" width="260" height="105" loading="lazy" decoding="async">
      </a>
      <p>Your independent hub for cricket culture, trending games & digital entertainment content — made for India.</p>
      <div class="socials" aria-label="Social links">
        <a href="#" aria-label="Facebook">f</a>
        <a href="#" aria-label="X">𝕏</a>
        <a href="#" aria-label="Instagram">◎</a>
        <a href="#" aria-label="YouTube">▶</a>
      </div>
    </div>
    <div class="footer-col">
      <h2>Explore</h2>
      <a href="#cricket">Cricket</a>
      <a href="#trending">Trending Games</a>
      <a href="#guides">Game Guides</a>
      <a href="#cricket">Cricket Guides</a>
    </div>
    <div class="footer-col">
      <h2>Company</h2>
      <a href="#top">About Us</a>
      <a href="#top">Contact Us</a>
      <a href="#blog">Blog</a>
    </div>
    <div class="footer-col">
      <h2>Support</h2>
      <a href="#safety">Safety</a>
      <a href="#safety">Responsible Play</a>
      <a href="#top">Privacy Policy</a>
      <a href="#top">Terms & Conditions</a>
    </div>
    <div class="note-box">
      <h2>Important Note</h2>
      <p>gamehub Games is an independent entertainment and game-information hub. We do not operate games, account systems, payment flows, or prize services.</p>
    </div>
    <p class="copyright">© 2026 gamehub Games. All rights reserved.</p>
  </footer>
  <script src="<?= esc(asset_url('/assets/js/site.js')) ?>" defer></script>
</body>
</html>
