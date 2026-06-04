<?php
require_once __DIR__ . '/../templates/layout.php';

$u = get_current_user();
if (!$u) {
    render_head('আমি', $cfg);
    echo '<div class="page"><div class="sbar"><a href="/home" class="bk"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></a><span class="st">আমার প্রোফাইল</span></div>';
    echo '<div class="nacc"><div class="ni">👤</div><h2>লগিন করুন</h2><p>প্রোফাইল দেখতে লগিন করুন</p><div class="nacc-b"><a href="/" class="bg">লগিন করুন</a></div></div></div>';
    render_nav('me'); render_foot(); exit;
}

render_head('আমার প্রোফাইল', $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st" data-bn="আমার প্রোফাইল" data-en="My Profile">আমার প্রোফাইল</span>
  </div>

  <div class="mw">
    <!-- Profile Card -->
    <div class="pcd">
      <div class="pav"><?= strtoupper(mb_substr($u['name'], 0, 1)) ?></div>
      <p class="pnm"><?= htmlspecialchars($u['name']) ?></p>
      <p class="pmb2">📱 <?= htmlspecialchars($u['mobile']) ?></p>
    </div>

    <div class="mlit">
      <!-- Quick Links -->
      <a href="/orders" class="mei">
        <span class="mico">📦</span>
        <div class="mtx">
          <span class="mtt" data-bn="আমার অর্ডার" data-en="My Orders">আমার অর্ডার</span>
          <span class="mts" data-bn="সব অর্ডার দেখুন" data-en="View all orders">সব অর্ডার দেখুন</span>
        </div>
        <span class="mar">›</span>
      </a>
      <a href="/cart" class="mei">
        <span class="mico">🛒</span>
        <div class="mtx">
          <span class="mtt" data-bn="আমার কার্ট" data-en="My Cart">আমার কার্ট</span>
          <span class="mts" data-bn="কার্টের পণ্য দেখুন" data-en="View cart">কার্টের পণ্য দেখুন</span>
        </div>
        <span class="mar">›</span>
      </a>

      <!-- Language -->
      <p class="msl" data-bn="ভাষা" data-en="Language">ভাষা</p>
      <div class="mei" style="cursor:default">
        <span class="mico">🌐</span>
        <div class="mtx"><span class="mtt" data-bn="ভাষা নির্বাচন" data-en="Select Language">ভাষা নির্বাচন</span></div>
        <div style="display:flex;gap:7px">
          <button class="lang-btn lang-btn-item" data-lang="bn" onclick="setLang('bn')" style="padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;cursor:pointer;border:2px solid var(--bdr2);background:transparent;color:var(--gray);font-family:inherit">বাংলা</button>
          <button class="lang-btn lang-btn-item" data-lang="en" onclick="setLang('en')" style="padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;cursor:pointer;border:2px solid var(--bdr2);background:transparent;color:var(--gray);font-family:inherit">EN</button>
        </div>
      </div>

      <!-- Contact -->
      <?php if ($cfg['contact_phone'] || $cfg['contact_whatsapp'] || $cfg['contact_facebook'] || $cfg['contact_website']): ?>
      <p class="msl" data-bn="যোগাযোগ করুন" data-en="Contact Us">যোগাযোগ করুন</p>
      <?php if ($cfg['contact_phone']): ?>
      <a href="tel:<?= htmlspecialchars($cfg['contact_phone']) ?>" class="mei">
        <span class="mico">📞</span>
        <div class="mtx"><span class="mtt" data-bn="ফোন করুন" data-en="Call Us">ফোন করুন</span><span class="mts"><?= htmlspecialchars($cfg['contact_phone']) ?></span></div>
        <span class="mar">›</span>
      </a>
      <?php endif; ?>
      <?php if ($cfg['contact_whatsapp']): ?>
      <a href="https://wa.me/<?= htmlspecialchars($cfg['contact_whatsapp']) ?>" target="_blank" class="mei">
        <span class="mico">💬</span>
        <div class="mtx"><span class="mtt">WhatsApp</span><span class="mts" data-bn="অ্যাডমিনের সাথে কথা বলুন" data-en="Chat with admin">অ্যাডমিনের সাথে কথা বলুন</span></div>
        <span class="mar">›</span>
      </a>
      <?php endif; ?>
      <?php if ($cfg['contact_facebook']): ?>
      <a href="<?= htmlspecialchars($cfg['contact_facebook']) ?>" target="_blank" class="mei">
        <span class="mico">📘</span>
        <div class="mtx"><span class="mtt">Facebook</span><span class="mts" data-bn="আমাদের পেজে যান" data-en="Visit our page">আমাদের পেজে যান</span></div>
        <span class="mar">›</span>
      </a>
      <?php endif; ?>
      <?php if ($cfg['contact_website']): ?>
      <a href="<?= htmlspecialchars($cfg['contact_website']) ?>" target="_blank" class="mei">
        <span class="mico">🌐</span>
        <div class="mtx"><span class="mtt" data-bn="ওয়েবসাইট" data-en="Website">ওয়েবসাইট</span><span class="mts"><?= htmlspecialchars($cfg['contact_website']) ?></span></div>
        <span class="mar">›</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <!-- Info & Terms -->
      <?php if ($cfg['about_us'] || $cfg['terms_and_conditions'] || $cfg['return_policy'] || $cfg['extra_info']): ?>
      <p class="msl" data-bn="তথ্য ও শর্তাবলী" data-en="Info & Terms">তথ্য ও শর্তাবলী</p>
      <?php foreach ([
        ['about_us', 'ℹ️', 'আমাদের সম্পর্কে', 'About Us'],
        ['terms_and_conditions', '📋', 'শর্তাবলী', 'Terms'],
        ['return_policy', '🔄', 'রিটার্ন পলিসি', 'Return Policy'],
        ['extra_info', '📌', 'অতিরিক্ত তথ্য', 'Extra Info'],
      ] as [$key, $ico, $bn, $en]):
        if (!$cfg[$key]) continue; ?>
      <div class="mei" style="flex-direction:column;align-items:flex-start;gap:8px;cursor:default">
        <div style="display:flex;align-items:center;gap:11px;width:100%">
          <span class="mico"><?= $ico ?></span>
          <span class="mtt" data-bn="<?= $bn ?>" data-en="<?= $en ?>"><?= $bn ?></span>
        </div>
        <div class="info-box"><p><?= nl2br(htmlspecialchars($cfg[$key])) ?></p></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <a href="/logout" class="blo" data-bn="লগআউট করুন" data-en="Logout">লগআউট করুন</a>
    <div style="height:8px"></div>
  </div>
</div>

<script>
(function(){
  const lang=localStorage.getItem('lang')||(document.cookie.match(/(?:^|;\s*)lang=([^;]+)/)||[])[1]||'bn';
  document.querySelectorAll('.lang-btn-item').forEach(b=>{
    const on=b.dataset.lang===lang;
    b.style.borderColor=on?'var(--g)':'';
    b.style.color=on?'var(--g)':'';
  });
})();
</script>

<?php render_nav('me'); render_foot(); ?>