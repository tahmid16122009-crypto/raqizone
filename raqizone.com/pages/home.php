<?php
require_once __DIR__ . '/../templates/layout.php';

$u      = get_current_user();
$q      = trim($_GET['q']      ?? '');
$cat    = trim($_GET['cat']    ?? '');
$gender = trim($_GET['gender'] ?? '');

// Products query
$sql    = "SELECT p.*, GROUP_CONCAT(pi.image_path ORDER BY pi.sort_order SEPARATOR '||') AS img_paths,
           GROUP_CONCAT(pi.price ORDER BY pi.sort_order SEPARATOR '||') AS img_prices,
           GROUP_CONCAT(pi.id ORDER BY pi.sort_order SEPARATOR '||') AS img_ids,
           COUNT(pi.id) AS img_count
           FROM products p
           LEFT JOIN product_images pi ON pi.product_id = p.id
           WHERE p.is_active = 1";
$params = [];

if ($q) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$q%";
}
if ($cat) {
    $sql .= " AND p.category = ?";
    $params[] = $cat;
}
if ($gender && $gender !== 'all') {
    $sql .= " AND p.gender = ?";
    $params[] = $gender;
}
$sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

$products = DB::rows($sql, $params);

// Parse images
foreach ($products as &$p) {
    $paths  = $p['img_paths']  ? explode('||', $p['img_paths'])  : [];
    $prices = $p['img_prices'] ? explode('||', $p['img_prices']) : [];
    $ids    = $p['img_ids']    ? explode('||', $p['img_ids'])    : [];
    $imgs   = [];
    foreach ($paths as $i => $path) {
        $imgs[] = [
            'id'         => $ids[$i] ?? '',
            'image_path' => $path,
            'price'      => (float)($prices[$i] ?? $p['base_price']),
        ];
    }
    $p['product_images'] = $imgs;
}
unset($p);

// Categories
$cats_raw  = $cfg['product_categories'] ?? '[]';
$categories = json_decode($cats_raw, true) ?: [];

// Banners
$mob_bnrs = json_decode($cfg['mobile_banners']  ?? '[]', true) ?: [];
$dsk_bnrs = json_decode($cfg['desktop_banners'] ?? '[]', true) ?: [];
$anim     = $cfg['banner_animation'] ?? 'slide';

// Search history
if ($q && $u) {
    DB::run("INSERT INTO search_history (user_id, query, created_at) VALUES (?, ?, NOW())",
        [$u['user_id'], $q]);
}

render_head('হোম — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>

<div class="page">
  <div class="tbar">
    <span class="tt"><?= htmlspecialchars($cfg['site_name'] ?? 'Raqizone') ?></span>
    <?php if ($u): ?>
      <a href="/me" class="av"><?= strtoupper(mb_substr($u['name'], 0, 1)) ?></a>
    <?php else: ?>
      <button class="bsm" onclick="os()">লগিন</button>
    <?php endif; ?>
  </div>

  <!-- Mobile Banner -->
  <div class="bnr-wrap d-mobile" id="mBnrWrap">
    <?php if ($mob_bnrs): ?>
    <div class="bnr-track" id="mBnrTrack">
      <?php foreach ($mob_bnrs as $b): ?>
      <div class="bnr-slide">
        <img src="<?= htmlspecialchars($b) ?>" alt="Banner" loading="lazy">
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($mob_bnrs) > 1): ?>
    <div class="bnr-dots" id="mBnrDots">
      <?php foreach ($mob_bnrs as $i => $b): ?>
      <button class="bnr-dot<?= $i===0?' on':'' ?>" onclick="goBnr(<?= $i ?>,false)"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="bnr-e"><span>🛍️</span><p><?= htmlspecialchars($cfg['home_tagline'] ?? '') ?></p></div>
    <?php endif; ?>
  </div>

  <!-- Desktop Banner -->
  <div class="bnr-wrap d-desktop" id="dBnrWrap" style="height:320px">
    <?php if ($dsk_bnrs): ?>
    <div class="bnr-track" id="dBnrTrack">
      <?php foreach ($dsk_bnrs as $b): ?>
      <div class="bnr-slide">
        <img src="<?= htmlspecialchars($b) ?>" alt="Desktop Banner" loading="lazy">
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($dsk_bnrs) > 1): ?>
    <div class="bnr-dots" id="dBnrDots">
      <?php foreach ($dsk_bnrs as $i => $b): ?>
      <button class="bnr-dot<?= $i===0?' on':'' ?>" onclick="goBnr(<?= $i ?>,true)"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="bnr-e"><span>🛍️</span><p><?= htmlspecialchars($cfg['home_tagline'] ?? '') ?></p></div>
    <?php endif; ?>
  </div>

  <!-- Search + Filter -->
  <div class="sw" style="padding:8px 14px">
    <div style="display:flex;gap:7px;align-items:center">
      <button type="button" onclick="toggleFilter()" id="filterBtn"
        style="padding:9px 11px;background:<?= ($cat||($gender&&$gender!='all'))?'linear-gradient(135deg,var(--g),var(--gd));color:var(--k)':'var(--k3);color:var(--gray)' ?>;border:2px solid var(--bdr2);border-radius:50px;cursor:pointer;font-size:1rem;flex-shrink:0">⚙️</button>
      <div class="sb" style="flex:1">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" id="searchQ" value="<?= htmlspecialchars($q) ?>" placeholder="পণ্য খুঁজুন..." autocomplete="off"
          onkeydown="if(event.key==='Enter')doSearch()"
          oninput="if(this.value==='')doSearch()">
        <?php if ($q): ?><button onclick="clearSearch()" style="background:none;border:none;color:var(--gray);cursor:pointer;font-size:.9rem;padding:0">✕</button><?php endif; ?>
      </div>
      <button onclick="doSearch()" style="padding:9px 13px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border:none;border-radius:50px;cursor:pointer;font-weight:700;font-family:inherit;font-size:.82rem">🔍</button>
    </div>

    <div id="filterPanel" style="display:none;margin-top:10px;padding:12px;background:var(--k3);border-radius:var(--r);border:1px solid var(--bdr2)">
      <p style="font-size:.72rem;color:var(--gray);margin-bottom:8px;font-weight:600;text-transform:uppercase">📂 ক্যাটাগরি</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
        <button type="button" class="fcat<?= !$cat?' fcat-on':'' ?>" onclick="quickCat('')">সব</button>
        <?php foreach ($categories as $c): ?>
        <button type="button" class="fcat<?= $cat===$c?' fcat-on':'' ?>" onclick="quickCat('<?= htmlspecialchars($c) ?>')">
          <?= htmlspecialchars($c) ?>
        </button>
        <?php endforeach; ?>
      </div>
      <p style="font-size:.72rem;color:var(--gray);margin-bottom:8px;font-weight:600;text-transform:uppercase">👥 লিঙ্গ</p>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button type="button" class="fcat<?= (!$gender||$gender==='all')?' fcat-on':'' ?>" onclick="quickGender('')">🌟 সব</button>
        <button type="button" class="fcat<?= $gender==='male'?' fcat-on':'' ?>" onclick="quickGender('male')">👨 Male</button>
        <button type="button" class="fcat<?= $gender==='female'?' fcat-on':'' ?>" onclick="quickGender('female')">👩 Female</button>
      </div>
    </div>
  </div>

  <!-- Active filters -->
  <?php if ($cat || ($gender && $gender !== 'all')): ?>
  <div style="padding:5px 14px;display:flex;gap:6px;flex-wrap:wrap">
    <?php if ($cat): ?>
    <a href="/home<?= $gender&&$gender!=='all'?'?gender='.$gender:'' ?>"
      style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:var(--gl);color:var(--g);border-radius:50px;font-size:.74rem;font-weight:600;text-decoration:none">
      📂 <?= htmlspecialchars($cat) ?> ✕
    </a>
    <?php endif; ?>
    <?php if ($gender && $gender !== 'all'): ?>
    <a href="/home<?= $cat?'?cat='.$cat:'' ?>"
      style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:var(--gl);color:var(--g);border-radius:50px;font-size:.74rem;font-weight:600;text-decoration:none">
      <?= $gender==='male'?'👨':'👩' ?> <?= $gender ?> ✕
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Products -->
  <div class="psec">
    <?php if ($q): ?>
    <p style="color:var(--gray);font-size:.8rem;margin-bottom:9px">"<?= htmlspecialchars($q) ?>" এর ফলাফল</p>
    <?php endif; ?>

    <?php if ($products): ?>
    <div class="pg">
      <?php foreach ($products as $p): ?>
      <a href="/product/<?= $p['id'] ?>" class="pc">
        <div class="pci">
          <?php if ($p['product_images']): ?>
          <img src="<?= htmlspecialchars($p['product_images'][0]['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
          <?php if (count($p['product_images']) > 1): ?>
          <span class="pb">+<?= count($p['product_images'])-1 ?></span>
          <?php endif; ?>
          <?php else: ?>
          <div class="ni">🛍️</div>
          <?php endif; ?>
          <?php if ($p['video_url']): ?><span class="vb">▶</span><?php endif; ?>
          <?php if ($p['gender']==='male'): ?><span class="gender-badge">👨</span>
          <?php elseif ($p['gender']==='female'): ?><span class="gender-badge">👩</span><?php endif; ?>
        </div>
        <div class="pin">
          <p class="pn"><?= htmlspecialchars($p['name']) ?></p>
          <div class="pr">
            <?php
            $minPrice = (float)$p['base_price'];
            foreach ($p['product_images'] as $img) {
                if ($img['price'] && $img['price'] < $minPrice) $minPrice = $img['price'];
            }
            ?>
            <span class="pp">৳<?= number_format($minPrice, 0) ?></span>
            <span class="pd">🚚৳<?= number_format($p['delivery_charge'], 0) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="emp">
      <div class="ei"><?= ($q||$cat||$gender)?'🔍':'🛍️' ?></div>
      <h3><?= ($q||$cat||$gender)?'কোনো ফলাফল নেই':'কোনো পণ্য নেই' ?></h3>
      <?php if ($q||$cat||$gender): ?>
      <p>ফিল্টার পরিবর্তন করুন</p>
      <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">সব পণ্য</a>
      <?php else: ?>
      <p>এখনো কোনো পণ্য যোগ করা হয়নি</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <div style="height:8px"></div>
</div>

<!-- Desktop Nav -->
<div class="desk-nav d-desktop">
  <a href="/home" class="active">🏠 হোম</a>
  <a href="/cart">🛒 কার্ট</a>
  <a href="/orders">📦 অর্ডার</a>
  <a href="/me">👤 আমি</a>
</div>

<!-- Login Sheet -->
<div class="overlay" id="ls">
  <div class="modal">
    <button class="mc" onclick="cs2()">✕</button>
    <div class="mt"><span class="ic">🔐</span><h3>লগিন করুন</h3></div>
    <div id="lb" style="display:flex;flex-direction:column;gap:11px">
      <button class="bg" onclick="ss('sl')">লগিন করুন</button>
      <button class="bo" onclick="ss('sr')">অ্যাকাউন্ট খুলুন</button>
    </div>
    <div id="sl" style="display:none">
      <form action="/auth/login" method="POST" class="fs">
        <input type="hidden" name="next" value="/home">
        <input type="text" name="name" placeholder="আপনার নাম" required class="inp">
        <input type="tel" name="mobile" placeholder="মোবাইল নম্বর" required class="inp">
        <button type="submit" class="bg">লগিন →</button>
      </form>
      <button onclick="sb2()" style="background:none;border:none;color:var(--gray);cursor:pointer;margin-top:7px;font-family:inherit;font-size:.82rem">← পিছনে</button>
    </div>
    <div id="sr" style="display:none">
      <form action="/auth/login" method="POST" class="fs">
        <input type="hidden" name="next" value="/home">
        <input type="text" name="name" placeholder="আপনার নাম" required class="inp">
        <input type="tel" name="mobile" placeholder="মোবাইল নম্বর" required class="inp">
        <button type="submit" class="bg">অ্যাকাউন্ট খুলুন ✨</button>
      </form>
      <button onclick="sb2()" style="background:none;border:none;color:var(--gray);cursor:pointer;margin-top:7px;font-family:inherit;font-size:.82rem">← পিছনে</button>
    </div>
  </div>
</div>

<script>
// Login
function os(){document.getElementById('ls').classList.add('show');document.body.style.overflow='hidden';}
function cs2(){document.getElementById('ls').classList.remove('show');document.body.style.overflow='';sb2();}
function ss(id){document.getElementById('lb').style.display='none';document.getElementById(id).style.display='block';}
function sb2(){document.getElementById('lb').style.display='flex';['sl','sr'].forEach(i=>document.getElementById(i).style.display='none');}
document.getElementById('ls').addEventListener('click',function(e){if(e.target===this)cs2();});

// Search
function doSearch(){const q=document.getElementById('searchQ').value.trim();const p=new URLSearchParams(window.location.search);if(q)p.set('q',q);else p.delete('q');window.location.href='/home'+(p.toString()?'?'+p.toString():'');}
function clearSearch(){const p=new URLSearchParams(window.location.search);p.delete('q');window.location.href='/home'+(p.toString()?'?'+p.toString():'');}

// Filter
function toggleFilter(){const p=document.getElementById('filterPanel');p.style.display=p.style.display==='none'?'block':'none';}
function quickCat(cat){const p=new URLSearchParams(window.location.search);if(cat)p.set('cat',cat);else p.delete('cat');const g=p.get('gender')||'';if(g&&g!=='all')p.set('gender',g);window.location.href='/home'+(p.toString()?'?'+p.toString():'');}
function quickGender(g){const p=new URLSearchParams(window.location.search);if(g&&g!=='all')p.set('gender',g);else p.delete('gender');const cat=p.get('cat')||'';if(cat)p.set('cat',cat);window.location.href='/home'+(p.toString()?'?'+p.toString():'');}

// Banner
function BannerSlider(wrapId,trackId,dotsWrapId,animType){
  const wrap=document.getElementById(wrapId);const track=document.getElementById(trackId);
  if(!wrap||!track)return null;
  const slides=Array.from(track.querySelectorAll('.bnr-slide'));if(!slides.length)return null;
  const dotsWrap=dotsWrapId?document.getElementById(dotsWrapId):null;
  const dots=dotsWrap?Array.from(dotsWrap.querySelectorAll('.bnr-dot')):[];
  let cur=0;let timer=null;
  const wrapH=wrap.offsetHeight||180;
  wrap.style.height=wrapH+'px';wrap.style.overflow='hidden';wrap.style.position='relative';
  function initSlide(){track.style.cssText='display:flex;width:100%;height:100%;transition:transform .7s cubic-bezier(.25,1,.5,1);transform:translateX(0%)';slides.forEach(s=>{s.style.cssText='min-width:100%;width:100%;height:100%;flex-shrink:0;position:relative;overflow:hidden';});}
  function initFade(){track.style.cssText='position:relative;width:100%;height:'+wrapH+'px;display:block';slides.forEach((s,i)=>{s.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;opacity:'+(i===0?'1':'0')+';transition:opacity .9s ease;z-index:'+(i===0?'2':'1')+';overflow:hidden';});}
  function initFlip(){track.style.cssText='position:relative;width:100%;height:'+wrapH+'px;display:block;perspective:1200px';slides.forEach((s,i)=>{s.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;opacity:'+(i===0?'1':'0')+';transform:'+(i===0?'rotateY(0deg)':'rotateY(90deg)')+';transition:transform .65s ease,opacity .45s ease;backface-visibility:hidden;z-index:'+(i===0?'2':'1')+';overflow:hidden';});}
  function initZoom(){initSlide();slides.forEach(s=>{const img=s.querySelector('img');if(img){img.style.transition='transform 5s ease';img.style.transform='scale(1)';img.style.width='100%';img.style.height='100%';img.style.objectFit='cover';}});}
  switch(animType){case 'fade':initFade();break;case 'flip':initFlip();break;case 'zoom':initZoom();break;default:initSlide();}
  function go(n){const prev=cur;cur=((n%slides.length)+slides.length)%slides.length;if(prev===cur)return;
    switch(animType){
      case 'fade':slides[prev].style.opacity='0';slides[prev].style.zIndex='1';slides[cur].style.opacity='1';slides[cur].style.zIndex='2';break;
      case 'flip':{const dir=cur>prev?-1:1;slides[prev].style.transform='rotateY('+(dir*90)+'deg)';slides[prev].style.opacity='0';slides[prev].style.zIndex='1';slides[cur].style.transition='none';slides[cur].style.transform='rotateY('+(-dir*90)+'deg)';slides[cur].style.opacity='0';slides[cur].style.zIndex='2';requestAnimationFrame(()=>requestAnimationFrame(()=>{slides[cur].style.transition='transform .65s ease,opacity .45s ease';slides[cur].style.transform='rotateY(0deg)';slides[cur].style.opacity='1';}));break;}
      case 'zoom':track.style.transform='translateX(-'+(cur*100)+'%)';slides.forEach((s,i)=>{const img=s.querySelector('img');if(!img)return;if(i===cur){img.style.transform='scale(1)';setTimeout(()=>{img.style.transform='scale(1.08)';},80);}else img.style.transform='scale(1)';});break;
      default:track.style.transform='translateX(-'+(cur*100)+'%)';
    }
    dots.forEach((d,i)=>d.classList.toggle('on',i===cur));
  }
  function startAuto(){if(slides.length>1)timer=setInterval(()=>go(cur+1),4000);}
  function stopAuto(){clearInterval(timer);}
  startAuto();
  let sx=0;
  wrap.addEventListener('touchstart',e=>{sx=e.touches[0].clientX;stopAuto();},{passive:true});
  wrap.addEventListener('touchend',e=>{const d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>40)go(d>0?cur+1:cur-1);startAuto();},{passive:true});
  return go;
}

const ANIM='<?= $anim ?>';
let mSlider=null,dSlider=null;
requestAnimationFrame(()=>requestAnimationFrame(()=>{
  mSlider=BannerSlider('mBnrWrap','mBnrTrack','mBnrDots',ANIM);
  dSlider=BannerSlider('dBnrWrap','dBnrTrack','dBnrDots',ANIM);
}));
function goBnr(n,isD){if(isD&&dSlider)dSlider(n);else if(!isD&&mSlider)mSlider(n);}
</script>

<?php render_foot(); ?>