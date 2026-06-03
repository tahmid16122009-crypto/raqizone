<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

$cfg = get_all_settings();

function theme_class(array $cfg): string {
    return 'theme-' . ($cfg['site_theme'] ?? 'golden');
}

function mobile_bg(array $cfg): string {
    return $cfg['mobile_bg_image'] ?? '';
}

function desktop_bg(array $cfg): string {
    return $cfg['desktop_bg_image'] ?? '';
}

function render_head(string $title, array $cfg): void {
    $theme = theme_class($cfg);
    $mbg   = mobile_bg($cfg);
    $dbg   = desktop_bg($cfg);
    echo <<<HTML
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>{$title}</title>
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body id="bodyRoot" class="{$theme}"
  data-mobile-bg="{$mbg}"
  data-desktop-bg="{$dbg}">
HTML;
}

function render_nav(string $page = ''): void {
    $pages = [
        'home'   => ['/home',   'হোম',   'Home',   'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z'],
        'cart'   => ['/cart',   'কার্ট', 'Cart',   'M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z'],
        'orders' => ['/orders', 'অর্ডার','Orders', 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z'],
        'me'     => ['/me',     'আমি',   'Me',     'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'],
    ];
    echo '<nav class="bnav">';
    foreach ($pages as $key => [$url, $bn, $en, $path]) {
        $active = $key === $page ? ' class="active"' : '';
        echo <<<HTML
<a href="{$url}"{$active}>
  <svg viewBox="0 0 24 24"><path d="{$path}"/></svg>
  <span data-bn="{$bn}" data-en="{$en}">{$bn}</span>
</a>
HTML;
    }
    echo '</nav>';
}

function render_foot(): void {
    echo <<<'HTML'
<script>
(function(){
  const body=document.getElementById('bodyRoot');
  const isMobile=window.innerWidth<=768;
  const mbg=body.dataset.mobileBg||'';
  const dbg=body.dataset.desktopBg||'';
  const bg=isMobile?mbg:(dbg||mbg);
  if(bg){body.style.setProperty('--bg-img','url("'+bg+'")');body.classList.add('has-bg-img');}
  const lang=localStorage.getItem('lang')||(document.cookie.match(/(?:^|;\s*)lang=([^;]+)/)||[])[1]||'bn';
  applyLang(lang);
  function applyLang(l){
    document.getElementById('htmlRoot').lang=l;
    body.classList.remove('lang-en');
    if(l==='en')body.classList.add('lang-en');
    document.querySelectorAll('[data-bn]').forEach(el=>{
      el.textContent=l==='en'?(el.dataset.en||el.textContent):(el.dataset.bn||el.textContent);
    });
    document.querySelectorAll('.lang-btn-item').forEach(b=>{
      const on=b.dataset.lang===l;
      b.classList.toggle('active',on);
      b.style.borderColor=on?'var(--g)':'';
      b.style.color=on?'var(--g)':'';
    });
  }
  window.setLang=function(l){
    localStorage.setItem('lang',l);
    document.cookie='lang='+l+';path=/;max-age='+(365*86400);
    applyLang(l);
  };
  const srvTheme=body.className.match(/theme-(\S+)/);
  if(srvTheme)localStorage.setItem('site_theme',srvTheme[1]);
})();
</script>
</body>
</html>
HTML;
}