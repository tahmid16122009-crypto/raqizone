<?php
require_once __DIR__ . '/../templates/layout.php';

$u = get_current_user();
if ($u) { header('Location: /home'); exit; }

render_head('স্বাগতম — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>
<div class="wp">
  <div class="wp::before"></div>
  <div class="wl">
    <span class="ic">🛍️</span>
    <h1 data-bn="<?= htmlspecialchars($cfg['welcome_title'] ?? 'Raqizone') ?>"
        data-en="<?= htmlspecialchars($cfg['welcome_title'] ?? 'Raqizone') ?>">
      <?= htmlspecialchars($cfg['welcome_title'] ?? 'Raqizone') ?>
    </h1>
    <p data-bn="<?= htmlspecialchars($cfg['welcome_subtitle'] ?? 'সেরা পণ্য, সেরা দামে') ?>"
       data-en="Best products, best prices">
      <?= htmlspecialchars($cfg['welcome_subtitle'] ?? 'সেরা পণ্য, সেরা দামে') ?>
    </p>
  </div>

  <div class="lang-sel" style="margin-bottom:20px">
    <button class="lang-btn lang-btn-item active" data-lang="bn" onclick="setLang('bn')">বাংলা</button>
    <button class="lang-btn lang-btn-item" data-lang="en" onclick="setLang('en')">English</button>
  </div>

  <div class="wbtns">
    <button class="bg" onclick="om('ml')" data-bn="🔐 লগিন করুন" data-en="🔐 Login">🔐 লগিন করুন</button>
    <button class="bo" onclick="om('mr')" data-bn="✨ অ্যাকাউন্ট খুলুন" data-en="✨ Create Account">✨ অ্যাকাউন্ট খুলুন</button>
    <a href="/home" class="bgh" data-bn="→ এখন না" data-en="→ Skip">→ এখন না, পরে করব</a>
  </div>
</div>

<!-- Login Modal -->
<div class="overlay" id="ml">
  <div class="modal">
    <button class="mc" onclick="cm('ml')">✕</button>
    <div class="mt">
      <span class="ic">🔐</span>
      <h3 data-bn="লগিন করুন" data-en="Login">লগিন করুন</h3>
      <p data-bn="নাম ও মোবাইল দিন" data-en="Enter name & mobile">নাম ও মোবাইল দিন</p>
    </div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/home">
      <div class="fd">
        <label data-bn="আপনার নাম" data-en="Your Name">আপনার নাম</label>
        <input type="text" name="name" class="inp" placeholder="পুরো নাম" required>
      </div>
      <div class="fd">
        <label data-bn="মোবাইল নম্বর" data-en="Mobile Number">মোবাইল নম্বর</label>
        <input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required>
      </div>
      <button type="submit" class="bg" data-bn="লগিন করুন →" data-en="Login →">লগিন করুন →</button>
    </form>
    <p class="mn" data-bn="* নতুন হলে অ্যাকাউন্ট তৈরি হবে" data-en="* New account will be created if needed">
      * নতুন হলে অ্যাকাউন্ট তৈরি হবে
    </p>
  </div>
</div>

<!-- Register Modal -->
<div class="overlay" id="mr">
  <div class="modal">
    <button class="mc" onclick="cm('mr')">✕</button>
    <div class="mt">
      <span class="ic">✨</span>
      <h3 data-bn="অ্যাকাউন্ট খুলুন" data-en="Create Account">অ্যাকাউন্ট খুলুন</h3>
      <p data-bn="নাম ও মোবাইল দিন" data-en="Enter name & mobile">নাম ও মোবাইল দিন</p>
    </div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/home">
      <div class="fd">
        <label data-bn="আপনার নাম" data-en="Your Name">আপনার নাম</label>
        <input type="text" name="name" class="inp" placeholder="পুরো নাম" required>
      </div>
      <div class="fd">
        <label data-bn="মোবাইল নম্বর" data-en="Mobile Number">মোবাইল নম্বর</label>
        <input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required>
      </div>
      <button type="submit" class="bg" data-bn="অ্যাকাউন্ট খুলুন ✨" data-en="Create Account ✨">অ্যাকাউন্ট খুলুন ✨</button>
    </form>
    <p class="mn" data-bn="* আগে থাকলে লগিন হবে" data-en="* Existing account will be used">
      * আগে থাকলে লগিন হবে
    </p>
  </div>
</div>

<script>
function om(id){document.getElementById(id).classList.add('show');document.body.style.overflow='hidden';}
function cm(id){document.getElementById(id).classList.remove('show');document.body.style.overflow='';}
document.querySelectorAll('.overlay').forEach(el=>{
  el.addEventListener('click',function(e){if(e.target===this)cm(this.id);});
});
// Apply saved language
const sl=localStorage.getItem('lang')||(document.cookie.match(/(?:^|;\s*)lang=([^;]+)/)||[])[1]||'bn';
if(typeof setLang==='function')setLang(sl);
</script>
<?php render_foot(); ?>