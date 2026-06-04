<?php
require_once __DIR__ . '/../templates/layout.php';

$u  = get_current_user();
if (!$u) { header('Location: /'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /orders'); exit; }

$order = DB::row(
    "SELECT * FROM orders WHERE id = ? AND user_id = ?",
    [$id, $u['user_id']]
);
if (!$order) { header('Location: /orders'); exit; }

$items = DB::rows(
    "SELECT * FROM order_items WHERE order_id = ?",
    [$id]
);

$created = strtotime($order['created_at']);
$editable = (time() - $created) < 86400 && $order['status'] === 'pending';

render_head('অর্ডার বিস্তারিত', $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/orders" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st">অর্ডার বিস্তারিত</span>
  </div>

  <div class="odw">
    <!-- Status + Serial -->
    <div class="odst">
      <span class="sb s-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
      <?php if ($order['serial_number']): ?>
      <div style="display:flex;flex-direction:column;align-items:center;gap:6px;margin-top:6px;width:100%">
        <span style="font-size:.72rem;color:var(--gray)">অর্ডার নম্বর</span>
        <div style="display:flex;align-items:center;gap:8px;background:var(--k3);border:2px solid var(--g);border-radius:10px;padding:10px 16px;width:100%;justify-content:space-between">
          <span style="font-size:1rem;font-weight:700;color:var(--g);letter-spacing:1px" id="serialNum">
            <?= htmlspecialchars($order['serial_number']) ?>
          </span>
          <button id="copyBtn" onclick="copySerial()" style="background:var(--gl);border:1px solid var(--g);color:var(--g);border-radius:6px;padding:5px 12px;font-size:.76rem;cursor:pointer;font-family:inherit;font-weight:700;white-space:nowrap;flex-shrink:0">
            📋 কপি
          </button>
        </div>
      </div>
      <?php endif; ?>
      <span class="odid"><?= date('Y-m-d', strtotime($order['created_at'])) ?></span>
    </div>

    <!-- Delivery Info -->
    <div class="odsc">
      <p class="odst2">📍 ডেলিভারি ঠিকানা</p>
      <div class="odig">
        <div class="odr"><span class="odl">নাম:</span><span class="odv"><?= htmlspecialchars($order['name']) ?></span></div>
        <div class="odr"><span class="odl">মোবাইল:</span><span class="odv"><?= htmlspecialchars($order['mobile']) ?></span></div>
        <div class="odr"><span class="odl">জেলা:</span><span class="odv"><?= htmlspecialchars($order['district']) ?></span></div>
        <div class="odr"><span class="odl">উপজেলা:</span><span class="odv"><?= htmlspecialchars($order['upazila']) ?></span></div>
        <div class="odr"><span class="odl">ইউনিয়ন:</span><span class="odv"><?= htmlspecialchars($order['union_name']) ?></span></div>
        <div class="odr"><span class="odl">গ্রাম:</span><span class="odv"><?= htmlspecialchars($order['village']) ?></span></div>
        <?php if ($order['road_name']): ?>
        <div class="odr"><span class="odl">রাস্তা:</span><span class="odv"><?= htmlspecialchars($order['road_name']) ?></span></div>
        <?php endif; ?>
        <?php if ($order['holding_number']): ?>
        <div class="odr"><span class="odl">হোল্ডিং:</span><span class="odv"><?= htmlspecialchars($order['holding_number']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Order Items -->
    <div class="odsc">
      <p class="odst2">📦 অর্ডার করা পণ্য</p>
      <div class="odit">
        <?php foreach ($items as $item): ?>
        <?php $opts = json_decode($item['selected_options'] ?? '{}', true) ?: []; ?>
        <div class="odim">
          <?php if ($item['image_path']): ?>
          <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
          <?php endif; ?>
          <div class="odii">
            <span class="odin"><?= htmlspecialchars($item['product_name']) ?></span>
            <?php if ($opts): ?>
            <div class="odop">
              <?php foreach ($opts as $k => $v): ?>
              <span class="odc"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <span class="odiq"><?= $item['quantity'] ?> × ৳<?= number_format($item['price'], 0) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="odp">
        <div class="odpr"><span>পণ্যের মূল্য:</span><span>৳<?= number_format($order['total_amount'] - $order['delivery_charge'], 0) ?></span></div>
        <div class="odpr"><span>ডেলিভারি:</span><span>৳<?= number_format($order['delivery_charge'], 0) ?></span></div>
        <div class="odpt"><span>মোট:</span><strong>৳<?= number_format($order['total_amount'], 0) ?></strong></div>
      </div>
    </div>

    <!-- Edit / Cancel -->
    <?php if ($editable): ?>
    <div class="odsc">
      <p class="edit-24-note">⏰ ২৪ ঘণ্টার মধ্যে পরিবর্তন বা বাতিল করা যাবে</p>
      <button class="bed" onclick="document.getElementById('editForm').style.display='block';this.style.display='none'">✏️ ঠিকানা পরিবর্তন</button>
      <button class="bcn" onclick="cancelOrder()">❌ অর্ডার বাতিল</button>
      <form id="editForm" action="/api/order.php?action=edit&id=<?= $id ?>" method="POST" style="display:none;margin-top:12px" class="fs">
        <div class="fd"><label>নাম</label><input name="name" class="inp" value="<?= htmlspecialchars($order['name']) ?>"></div>
        <div class="fd"><label>মোবাইল</label><input name="mobile" class="inp" value="<?= htmlspecialchars($order['mobile']) ?>"></div>
        <div class="fd"><label>জেলা</label><input name="district" class="inp" value="<?= htmlspecialchars($order['district']) ?>"></div>
        <div class="fd"><label>উপজেলা</label><input name="upazila" class="inp" value="<?= htmlspecialchars($order['upazila']) ?>"></div>
        <div class="fd"><label>ইউনিয়ন</label><input name="union_name" class="inp" value="<?= htmlspecialchars($order['union_name']) ?>"></div>
        <div class="fd"><label>গ্রাম</label><input name="village" class="inp" value="<?= htmlspecialchars($order['village']) ?>"></div>
        <div class="fd"><label>রাস্তা</label><input name="road_name" class="inp" value="<?= htmlspecialchars($order['road_name'] ?? '') ?>"></div>
        <div class="fd"><label>হোল্ডিং</label><input name="holding_number" class="inp" value="<?= htmlspecialchars($order['holding_number'] ?? '') ?>"></div>
        <button type="submit" class="bg" style="margin-top:5px">✅ সংরক্ষণ</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function copySerial(){
  const text=document.getElementById('serialNum').textContent.trim();
  const btn=document.getElementById('copyBtn');
  const orig=btn.textContent;
  if(navigator.clipboard&&window.isSecureContext){
    navigator.clipboard.writeText(text).then(()=>{btn.textContent='✅ কপি!';setTimeout(()=>btn.textContent=orig,2000);}).catch(()=>fallbackCopy(text,btn,orig));
  }else{fallbackCopy(text,btn,orig);}
}
function fallbackCopy(text,btn,orig){
  const inp=document.createElement('input');inp.value=text;inp.style.cssText='position:fixed;top:0;left:0;opacity:0';
  document.body.appendChild(inp);inp.focus();inp.select();inp.setSelectionRange(0,99999);
  try{document.execCommand('copy');btn.textContent='✅ কপি!';}catch(e){btn.textContent='❌ ব্যর্থ';}
  document.body.removeChild(inp);setTimeout(()=>btn.textContent=orig,2000);
}
async function cancelOrder(){
  if(!confirm('অর্ডারটি বাতিল করতে চান?'))return;
  const r=await fetch('/api/order.php?action=cancel&id=<?= $id ?>',{method:'POST'});
  const d=await r.json();
  if(d.ok)window.location.reload();
  else alert('সমস্যা: '+(d.error||''));
}
</script>

<?php render_nav('orders'); render_foot(); ?>