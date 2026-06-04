<?php
require_once __DIR__ . '/../templates/layout.php';

$u = get_current_user();
if (!$u) {
    // No account view
    render_head('কার্ট', $cfg);
    echo '<div class="page"><div class="sbar"><a href="/home" class="bk"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></a><span class="st">কার্ট</span></div>';
    echo '<div class="nacc"><div class="ni">🛒</div><h2>লগিন করুন</h2><p>কার্ট দেখতে লগিন করুন</p><div class="nacc-b"><a href="/" class="bg">লগিন করুন</a></div></div></div>';
    render_nav('cart');
    render_foot();
    exit;
}

$items = DB::rows(
    "SELECT * FROM cart_items WHERE user_id = ? ORDER BY created_at DESC",
    [$u['user_id']]
);

render_head('কার্ট', $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st" data-bn="কার্ট" data-en="Cart">কার্ট</span>
  </div>

  <?php if ($items): ?>
  <div class="cl">
    <?php
    $total = 0;
    foreach ($items as $item):
      $opts = json_decode($item['selected_options'] ?? '{}', true) ?: [];
      $subtotal = $item['price'] * $item['quantity'];
      $total += $subtotal;
    ?>
    <div class="ci" id="ci<?= $item['id'] ?>">
      <?php if ($item['image_path']): ?>
      <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
      <?php endif; ?>
      <div class="cdt">
        <p class="cn"><?= htmlspecialchars($item['product_name']) ?></p>
        <?php if ($opts): ?>
        <div class="cps">
          <?php foreach ($opts as $k => $v): ?>
          <span class="cp"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="cpr2">
          <span class="cprc">৳<?= number_format($subtotal, 0) ?></span>
          <div class="qr">
            <button class="qb" onclick="updateCart(<?= $item['id'] ?>, <?= $item['quantity']-1 ?>)">−</button>
            <span class="qn" id="qty<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
            <button class="qb" onclick="updateCart(<?= $item['id'] ?>, <?= $item['quantity']+1 ?>)">+</button>
          </div>
          <button class="crm" onclick="removeCart(<?= $item['id'] ?>)">🗑 সরান</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="csum">
    <div class="ctrow">
      <span>মোট:</span>
      <span id="cartTotal">৳<?= number_format($total, 0) ?></span>
    </div>
    <p class="cnote">ডেলিভারি চার্জ অর্ডারে যোগ হবে</p>
  </div>

  <?php else: ?>
  <div class="emp">
    <div class="ei">🛒</div>
    <h3 data-bn="কার্ট খালি" data-en="Cart is empty">কার্ট খালি</h3>
    <p data-bn="কোনো পণ্য যোগ করা হয়নি" data-en="No items added yet">কোনো পণ্য যোগ করা হয়নি</p>
    <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">পণ্য দেখুন</a>
  </div>
  <?php endif; ?>
</div>

<div class="toast" id="t1">✅ কার্ট আপডেট হয়েছে!</div>

<script>
async function removeCart(id) {
  const r = await fetch('/api/cart.php?action=remove&id='+id, {method:'POST'});
  const d = await r.json();
  if (d.ok) { document.getElementById('ci'+id)?.remove(); recalc(); }
}
async function updateCart(id, qty) {
  if (qty < 0) return;
  const fd = new FormData(); fd.append('quantity', qty);
  const r = await fetch('/api/cart.php?action=update&id='+id, {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    if (qty === 0) { document.getElementById('ci'+id)?.remove(); }
    else { const el=document.getElementById('qty'+id); if(el)el.textContent=qty; }
    recalc();
    const t=document.getElementById('t1'); t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),2000);
  }
}
function recalc() {
  let total = 0;
  document.querySelectorAll('.ci').forEach(ci => {
    const qty = parseInt(ci.querySelector('.qn')?.textContent || '0');
    const priceEl = ci.querySelector('.cprc');
    if (priceEl) {
      const price = parseFloat(priceEl.textContent.replace('৳','').replace(',',''));
      total += price;
    }
  });
  const el = document.getElementById('cartTotal');
  if (el) el.textContent = '৳' + total.toLocaleString('en-IN');
}
</script>

<?php render_nav('cart'); render_foot(); ?>