<?php
require_once __DIR__ . '/base.php';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Mobile banners
    $mobile_banners = json_decode($_POST['existing_mobile_banners'] ?? '[]', true) ?: [];
    $files_m = $_FILES['new_mobile_banner_images'] ?? [];
    if (!empty($files_m['name'])) {
        $count = count($files_m['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files_m['error'][$i] !== UPLOAD_ERR_OK) continue;
            $file = ['name'=>$files_m['name'][$i],'type'=>$files_m['type'][$i],'tmp_name'=>$files_m['tmp_name'][$i],'error'=>$files_m['error'][$i],'size'=>$files_m['size'][$i]];
            $url = upload_image($file, 'mbnr', 'banners');
            if ($url) $mobile_banners[] = $url;
        }
    }

    // Desktop banners
    $desktop_banners = json_decode($_POST['existing_desktop_banners'] ?? '[]', true) ?: [];
    $files_d = $_FILES['new_desktop_banner_images'] ?? [];
    if (!empty($files_d['name'])) {
        $count = count($files_d['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files_d['error'][$i] !== UPLOAD_ERR_OK) continue;
            $file = ['name'=>$files_d['name'][$i],'type'=>$files_d['type'][$i],'tmp_name'=>$files_d['tmp_name'][$i],'error'=>$files_d['error'][$i],'size'=>$files_d['size'][$i]];
            $url = upload_image($file, 'dbnr', 'banners');
            if ($url) $desktop_banners[] = $url;
        }
    }

    // Mobile BG
    if (!empty($_FILES['mobile_bg_image']['name']) && $_FILES['mobile_bg_image']['error'] === UPLOAD_ERR_OK) {
        $url = upload_image($_FILES['mobile_bg_image'], 'mbg', 'backgrounds');
        if ($url) save_setting('mobile_bg_image', $url);
    }

    // Desktop BG
    if (!empty($_FILES['desktop_bg_image']['name']) && $_FILES['desktop_bg_image']['error'] === UPLOAD_ERR_OK) {
        $url = upload_image($_FILES['desktop_bg_image'], 'dbg', 'backgrounds');
        if ($url) save_setting('desktop_bg_image', $url);
    }

    // Categories
    $cats = json_decode($_POST['product_categories_json'] ?? '[]', true) ?: [];

    // Save all settings
    $keys = [
        'site_name','welcome_title','welcome_subtitle','home_tagline',
        'contact_phone','contact_whatsapp','contact_facebook','contact_website',
        'delivery_charge_default','payment_options','bkash_number','nagad_number',
        'bkash_app_key','bkash_app_secret','about_us','terms_and_conditions',
        'return_policy','extra_info','footer_note','banner_animation','site_theme',
    ];
    foreach ($keys as $k) {
        save_setting($k, trim($_POST[$k] ?? ''));
    }
    save_setting('payment_method', trim($_POST['payment_options'] ?? 'cod'));
    save_setting('mobile_banners',  json_encode($mobile_banners,  JSON_UNESCAPED_UNICODE));
    save_setting('desktop_banners', json_encode($desktop_banners, JSON_UNESCAPED_UNICODE));
    save_setting('banners',         json_encode($mobile_banners,  JSON_UNESCAPED_UNICODE));
    save_setting('product_categories', json_encode($cats,         JSON_UNESCAPED_UNICODE));

    header('Location: /admin/edit-ui'); exit;
}

$mobile_banners  = json_decode($cfg['mobile_banners']  ?? '[]', true) ?: [];
$desktop_banners = json_decode($cfg['desktop_banners'] ?? '[]', true) ?: [];
$categories      = json_decode($cfg['product_categories'] ?? '[]', true) ?: [];
$anim            = $cfg['banner_animation'] ?? 'slide';

admin_head('UI ও Settings');
admin_nav('edit-ui');
?>

<div class="aph"><h1>🎨 UI ও Settings সম্পাদনা</h1></div>

<form action="/admin/edit-ui" method="POST" enctype="multipart/form-data" class="aform" id="uiForm">

  <!-- THEME -->
  <div class="afs">
    <h3>🎨 UI থিম</h3>
    <p class="afn">থিম নির্বাচন করুন — সম্পূর্ণ ওয়েবসাইটের রং বদলে যাবে।</p>
    <div id="themeGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;margin-top:4px"></div>
    <input type="hidden" name="site_theme" id="siteTheme" value="<?= htmlspecialchars($cfg['site_theme'] ?? 'golden') ?>">
  </div>

  <!-- MOBILE BANNER -->
  <div class="afs">
    <h3>📱 মোবাইল ব্যানার</h3>
    <p class="afn">শুধু মোবাইলে দেখাবে।</p>
    <div id="mBnrList"></div>
    <input type="hidden" name="existing_mobile_banners" id="exMBnr" value="<?= htmlspecialchars(json_encode($mobile_banners, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="aup" onclick="document.getElementById('mBnrInp').click()" style="margin-top:8px">
      <div class="ui">📷</div><p>মোবাইল ব্যানার যোগ করুন</p><p class="us">একাধিক ছবি একসাথে</p>
    </div>
    <input type="file" name="new_mobile_banner_images[]" id="mBnrInp" accept="image/*" multiple style="display:none" onchange="addBnr(this,'mBnrPrev')">
    <div id="mBnrPrev" style="display:flex;flex-direction:column;gap:7px;margin-top:8px"></div>
  </div>

  <!-- DESKTOP BANNER -->
  <div class="afs">
    <h3>🖥️ ডেস্কটপ ব্যানার</h3>
    <p class="afn">শুধু ডেস্কটপে দেখাবে।</p>
    <div id="dBnrList"></div>
    <input type="hidden" name="existing_desktop_banners" id="exDBnr" value="<?= htmlspecialchars(json_encode($desktop_banners, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="aup" onclick="document.getElementById('dBnrInp').click()" style="margin-top:8px">
      <div class="ui">🖥️</div><p>ডেস্কটপ ব্যানার যোগ করুন</p><p class="us">ওয়াইডস্ক্রিন সাইজ ভালো</p>
    </div>
    <input type="file" name="new_desktop_banner_images[]" id="dBnrInp" accept="image/*" multiple style="display:none" onchange="addBnr(this,'dBnrPrev')">
    <div id="dBnrPrev" style="display:flex;flex-direction:column;gap:7px;margin-top:8px"></div>

    <div class="frow" style="margin-top:12px">
      <label>Banner Animation</label>
      <select name="banner_animation" class="ai">
        <?php foreach (['slide'=>'➡️ Slide','fade'=>'✨ Fade','zoom'=>'🔍 Zoom','flip'=>'🔄 Flip','cube'=>'🎲 Cube'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= $anim===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- MOBILE BG -->
  <div class="afs">
    <h3>📱 মোবাইল Background</h3>
    <?php if ($cfg['mobile_bg_image'] ?? ''): ?>
    <img src="<?= htmlspecialchars($cfg['mobile_bg_image']) ?>" class="bpv">
    <?php endif; ?>
    <div class="aup" onclick="document.getElementById('mBgInp').click()">
      <div class="ui">🖼️</div><p>মোবাইল Background আপলোড করুন</p>
    </div>
    <input type="file" name="mobile_bg_image" id="mBgInp" accept="image/*" style="display:none" onchange="prevImg(this,'mBgPrev')">
    <img id="mBgPrev" src="" alt="" style="display:none;max-width:100%;margin-top:8px;border-radius:8px;border:1px solid var(--bdr)">
  </div>

  <!-- DESKTOP BG -->
  <div class="afs">
    <h3>🖥️ ডেস্কটপ Background</h3>
    <?php if ($cfg['desktop_bg_image'] ?? ''): ?>
    <img src="<?= htmlspecialchars($cfg['desktop_bg_image']) ?>" class="bpv">
    <?php endif; ?>
    <div class="aup" onclick="document.getElementById('dBgInp').click()">
      <div class="ui">🖥️</div><p>ডেস্কটপ Background আপলোড করুন</p>
    </div>
    <input type="file" name="desktop_bg_image" id="dBgInp" accept="image/*" style="display:none" onchange="prevImg(this,'dBgPrev')">
    <img id="dBgPrev" src="" alt="" style="display:none;max-width:100%;margin-top:8px;border-radius:8px;border:1px solid var(--bdr)">
  </div>

  <!-- PRODUCT CATEGORIES -->
  <div class="afs">
    <h3>📂 পণ্য ক্যাটাগরি</h3>
    <p class="afn">পণ্য এড করার সময় এই ক্যাটাগরি দেখাবে।</p>
    <div id="catList" style="display:flex;flex-direction:column;gap:7px;margin-bottom:10px"></div>
    <div style="display:flex;gap:8px">
      <input type="text" id="catInput" class="ai" placeholder="নতুন ক্যাটাগরি লিখুন..." style="flex:1">
      <button type="button" class="abg" onclick="addCat()">+ যোগ করুন</button>
    </div>
    <input type="hidden" name="product_categories_json" id="catsJson" value="<?= htmlspecialchars(json_encode($categories, JSON_UNESCAPED_UNICODE)) ?>">
  </div>

  <!-- SITE IDENTITY -->
  <div class="afs">
    <h3>🏪 সাইটের পরিচয়</h3>
    <div class="frow"><label>সাইটের নাম</label><input type="text" name="site_name" value="<?= htmlspecialchars($cfg['site_name'] ?? '') ?>" class="ai"></div>
    <div class="frow"><label>Welcome শিরোনাম</label><input type="text" name="welcome_title" value="<?= htmlspecialchars($cfg['welcome_title'] ?? '') ?>" class="ai"></div>
    <div class="frow"><label>Welcome ট্যাগলাইন</label><input type="text" name="welcome_subtitle" value="<?= htmlspecialchars($cfg['welcome_subtitle'] ?? '') ?>" class="ai"></div>
    <div class="frow"><label>হোম ট্যাগলাইন</label><input type="text" name="home_tagline" value="<?= htmlspecialchars($cfg['home_tagline'] ?? '') ?>" class="ai"></div>
  </div>

  <!-- DELIVERY -->
  <div class="afs">
    <h3>🚚 ডেলিভারি</h3>
    <div class="frow"><label>ডিফল্ট ডেলিভারি চার্জ (৳)</label><input type="number" name="delivery_charge_default" value="<?= htmlspecialchars($cfg['delivery_charge_default'] ?? '60') ?>" class="ai" min="0"></div>
  </div>

  <!-- PAYMENT -->
  <div class="afs">
    <h3>💳 Payment System</h3>
    <div class="frow">
      <label>Payment অপশন</label>
      <select name="payment_options" class="ai" id="payOptSel" onchange="showPaySec()">
        <?php foreach (['cod'=>'💵 শুধু Cash on Delivery','delivery_only'=>'🏦 শুধু ডেলিভারি চার্জ (অনলাইন)','full'=>'🏦 Full Payment (অনলাইন)','all'=>'🔀 সব অপশন (COD + অনলাইন)'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= ($cfg['payment_options']??'cod')===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="codInfo"><p style="font-size:.8rem;background:rgba(76,175,80,.1);color:#4CAF50;padding:10px;border-radius:8px;border:1px solid rgba(76,175,80,.2)">✅ পণ্য পেলে টাকা দেবে।</p></div>
    <div id="onlineInfo" style="display:none">
      <div class="frow"><label>বিকাশ নম্বর</label><input type="text" name="bkash_number" value="<?= htmlspecialchars($cfg['bkash_number'] ?? '') ?>" class="ai" placeholder="01XXXXXXXXX"></div>
      <div class="frow"><label>নগদ নম্বর</label><input type="text" name="nagad_number" value="<?= htmlspecialchars($cfg['nagad_number'] ?? '') ?>" class="ai" placeholder="01XXXXXXXXX"></div>
      <div class="frow"><label>বিকাশ App Key</label><input type="text" name="bkash_app_key" value="<?= htmlspecialchars($cfg['bkash_app_key'] ?? '') ?>" class="ai"></div>
      <div class="frow"><label>বিকাশ App Secret</label><input type="password" name="bkash_app_secret" value="<?= htmlspecialchars($cfg['bkash_app_secret'] ?? '') ?>" class="ai"></div>
    </div>
  </div>

  <!-- CONTACT -->
  <div class="afs">
    <h3>📞 যোগাযোগ</h3>
    <div class="frow"><label>ফোন নম্বর</label><input type="text" name="contact_phone" value="<?= htmlspecialchars($cfg['contact_phone'] ?? '') ?>" class="ai" placeholder="01XXXXXXXXX"></div>
    <div class="frow"><label>WhatsApp</label><input type="text" name="contact_whatsapp" value="<?= htmlspecialchars($cfg['contact_whatsapp'] ?? '') ?>" class="ai" placeholder="8801XXXXXXXXX"></div>
    <div class="frow"><label>Facebook</label><input type="url" name="contact_facebook" value="<?= htmlspecialchars($cfg['contact_facebook'] ?? '') ?>" class="ai" placeholder="https://facebook.com/..."></div>
    <div class="frow"><label>ওয়েবসাইট</label><input type="url" name="contact_website" value="<?= htmlspecialchars($cfg['contact_website'] ?? '') ?>" class="ai" placeholder="https://..."></div>
  </div>

  <!-- INFO & TERMS -->
  <div class="afs">
    <h3>📋 তথ্য ও শর্তাবলী</h3>
    <div class="frow"><label>আমাদের সম্পর্কে</label><textarea name="about_us" class="ata"><?= htmlspecialchars($cfg['about_us'] ?? '') ?></textarea></div>
    <div class="frow"><label>শর্তাবলী</label><textarea name="terms_and_conditions" class="ata"><?= htmlspecialchars($cfg['terms_and_conditions'] ?? '') ?></textarea></div>
    <div class="frow"><label>রিটার্ন পলিসি</label><textarea name="return_policy" class="ata"><?= htmlspecialchars($cfg['return_policy'] ?? '') ?></textarea></div>
    <div class="frow"><label>অতিরিক্ত তথ্য</label><textarea name="extra_info" class="ata"><?= htmlspecialchars($cfg['extra_info'] ?? '') ?></textarea></div>
    <div class="frow"><label>Footer নোট</label><input type="text" name="footer_note" value="<?= htmlspecialchars($cfg['footer_note'] ?? '') ?>" class="ai"></div>
  </div>

  <div class="afact">
    <button type="submit" class="abg">✅ সব সংরক্ষণ করুন</button>
  </div>
</form>

<script>
// Theme grid
const THEMES=[{id:'golden',label:'🌟 Golden',g:'#C9A84C',k:'#080808'},{id:'black',label:'⬛ Black',g:'#FFF',k:'#000'},{id:'white',label:'⬜ White',g:'#333',k:'#F0F0F0'},{id:'red',label:'🔴 Red',g:'#E53935',k:'#0A0000'},{id:'blue',label:'🔵 Blue',g:'#1E88E5',k:'#020810'},{id:'skyblue',label:'🩵 Sky',g:'#29B6F6',k:'#020A10'},{id:'green',label:'🟢 Green',g:'#43A047',k:'#020A02'},{id:'purple',label:'🟣 Purple',g:'#8E24AA',k:'#08020A'},{id:'orange',label:'🟠 Orange',g:'#FB8C00',k:'#0A0500'},{id:'brown',label:'🟤 Brown',g:'#795548',k:'#080402'},{id:'pink',label:'🩷 Pink',g:'#E91E63',k:'#0A0205'},{id:'cyan',label:'🩵 Cyan',g:'#00BCD4',k:'#00080A'},{id:'yellow',label:'🟡 Yellow',g:'#F9A825',k:'#080600'}];
const curT=document.getElementById('siteTheme').value;
const grid=document.getElementById('themeGrid');
THEMES.forEach(t=>{const btn=document.createElement('button');btn.type='button';btn.style.cssText=`padding:9px 6px;border-radius:9px;border:3px solid ${t.id===curT?t.g:'var(--bdr2)'};background:${t.k};color:${t.g};font-size:.72rem;font-weight:700;cursor:pointer;font-family:inherit;text-align:center`;btn.textContent=t.label;btn.onclick=()=>{document.getElementById('siteTheme').value=t.id;grid.querySelectorAll('button').forEach(b=>b.style.borderColor='var(--bdr2)');btn.style.borderColor=t.g;};grid.appendChild(btn);});

// BG preview
function prevImg(inp,prevId){if(inp.files&&inp.files[0]){const r=new FileReader();r.onload=e=>{const p=document.getElementById(prevId);p.src=e.target.result;p.style.display='block';};r.readAsDataURL(inp.files[0]);}}

// Banner list
function renderBnrList(containerId,hiddenId){
  let banners=[];try{banners=JSON.parse(document.getElementById(hiddenId).value||'[]');}catch(e){}
  const list=document.getElementById(containerId);list.innerHTML='';
  banners.forEach((url,i)=>{const d=document.createElement('div');d.style.cssText='display:flex;align-items:center;gap:9px;background:var(--k3);border:1px solid var(--bdr);border-radius:8px;padding:8px;margin-bottom:7px';d.innerHTML=`<img src="${url}" style="width:80px;height:50px;object-fit:cover;border-radius:6px;flex-shrink:0"><span style="flex:1;font-size:.78rem;color:var(--gray)">ব্যানার ${i+1}</span><button type="button" class="idl" onclick="rmBnr(${i},'${hiddenId}','${containerId}')">✕</button>`;list.appendChild(d);});
}
function rmBnr(i,hiddenId,containerId){let b=[];try{b=JSON.parse(document.getElementById(hiddenId).value||'[]');}catch(e){}b.splice(i,1);document.getElementById(hiddenId).value=JSON.stringify(b);renderBnrList(containerId,hiddenId);}
function addBnr(inp,prevId){const c=document.getElementById(prevId);c.innerHTML='';Array.from(inp.files).forEach(f=>{const r=new FileReader();r.onload=e=>{const d=document.createElement('div');d.style.cssText='display:flex;align-items:center;gap:9px;background:var(--k3);border:1px solid var(--bdr);border-radius:8px;padding:8px';d.innerHTML=`<img src="${e.target.result}" style="width:80px;height:50px;object-fit:cover;border-radius:6px;flex-shrink:0"><span style="flex:1;font-size:.78rem;color:var(--gray)">${f.name}</span><span style="font-size:.72rem;color:#4CAF50">✓</span>`;c.appendChild(d);};r.readAsDataURL(f);});}
renderBnrList('mBnrList','exMBnr');
renderBnrList('dBnrList','exDBnr');

// Categories
let cats=[];try{cats=JSON.parse(document.getElementById('catsJson').value||'[]');}catch(e){}
function renderCats(){const list=document.getElementById('catList');list.innerHTML='';cats.forEach((cat,i)=>{const d=document.createElement('div');d.style.cssText='display:flex;align-items:center;gap:8px;background:var(--k3);border:1px solid var(--bdr);border-radius:8px;padding:8px 12px';d.innerHTML=`<span style="flex:1;font-size:.86rem">📂 ${cat}</span><button type="button" class="idl" onclick="rmCat(${i})">✕</button>`;list.appendChild(d);});document.getElementById('catsJson').value=JSON.stringify(cats);}
function addCat(){const inp=document.getElementById('catInput');const v=inp.value.trim();if(!v||cats.includes(v))return;cats.push(v);inp.value='';renderCats();}
function rmCat(i){cats.splice(i,1);renderCats();}
renderCats();

// Payment
function showPaySec(){const v=document.getElementById('payOptSel').value;document.getElementById('codInfo').style.display=v==='cod'?'block':'none';document.getElementById('onlineInfo').style.display=v!=='cod'?'block':'none';}
showPaySec();
</script>

<?php admin_foot(); ?>