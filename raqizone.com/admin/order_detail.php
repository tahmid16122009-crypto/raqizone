<?php
require_once __DIR__ . '/base.php';

$id    = (int)($_GET['id'] ?? 0);
$order = DB::row("SELECT * FROM orders WHERE id = ?", [$id]);
if (!$order) { header('Location: /admin/orders'); exit; }

$items = DB::rows("SELECT * FROM order_items WHERE order_id = ?", [$id]);

admin_head('অর্ডার বিস্তারিত');
admin_nav('orders');
?>

<div class="aph">
  <a href="/admin/orders" class="abl">← অর্ডার তালিকা</a>
  <h1>📋 অর্ডার বিস্তারিত</h1>
</div>

<div class="aod2">

  <!-- গ্রাহকের তথ্য -->
  <div class="aodc">
    <h3>👤 গ্রাহকের তথ্য</h3>
    <div class="aig">
      <div class="air"><span>নাম:</span><strong><?= htmlspecialchars($order['name']) ?></strong></div>
      <div class="air"><span>মোবাইল:</span><strong><?= htmlspecialchars($order['mobile']) ?></strong></div>
      <?php if ($order['serial_number']): ?>
      <div class="air"><span>অর্ডার নম্বর:</span><strong style="color:var(--g)"><?= htmlspecialchars($order['serial_number']) ?></strong></div>
      <?php endif; ?>
      <div class="air"><span>জেলা:</span><strong><?= htmlspecialchars($order['district']) ?></strong></div>
      <div class="air"><span>উপজেলা:</span><strong><?= htmlspecialchars($order['upazila']) ?></strong></div>
      <div class="air"><span>ইউনিয়ন:</span><strong><?= htmlspecialchars($order['union_name']) ?></strong></div>
      <div class="air"><span>গ্রাম:</span><strong><?= htmlspecialchars($order['village']) ?></strong></div>
      <?php if ($order['road_name']): ?>
      <div class="air"><span>রাস্তা:</span><strong><?= htmlspecialchars($order['road_name']) ?></strong></div>
      <?php endif; ?>
      <?php if ($order['holding_number']): ?>
      <div class="air"><span>হোল্ডিং:</span><strong><?= htmlspecialchars($order['holding_number']) ?></strong></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- পণ্য ও কাস্টম ডিজাইন -->
  <div class="aodc">
    <h3>📦 অর্ডার করা পণ্য</h3>
    <?php foreach ($items as $item):
      $opts = json_decode($item['selected_options'] ?? '{}', true) ?: [];
    ?>
    <div class="aoi" style="flex-direction:column;align-items:flex-start">
      <div style="display:flex;gap:10px;width:100%">
        <?php if ($item['image_path']): ?>
        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:8px;flex-shrink:0">
        <?php endif; ?>
        <div class="aoii" style="flex:1">
          <span class="aoin"><?= htmlspecialchars($item['product_name']) ?></span>
          <?php if ($opts): ?>
          <div class="aoiop">
            <?php foreach ($opts as $k => $v): ?>
            <span class="aocp"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <span style="font-size:.82rem;color:var(--gray)"><?= $item['quantity'] ?> × ৳<?= number_format($item['price'], 0) ?> = <strong style="color:var(--g)">৳<?= number_format($item['quantity'] * $item['price'], 0) ?></strong></span>
        </div>
      </div>

      <!-- Custom Design -->
      <?php if ($item['custom_design_text'] || $item['custom_design_image1'] || $item['custom_design_image2']): ?>
      <div style="width:100%;margin-top:10px;padding-top:10px;border-top:1px dashed var(--bdr2)">
        <p style="font-size:.76rem;font-weight:700;color:var(--g);margin-bottom:8px">🎨 কাস্টম ডিজাইন তথ্য:</p>
        <?php if ($item['custom_design_text']): ?>
        <div style="background:var(--k3);border-radius:7px;padding:9px 12px;margin-bottom:8px">
          <p style="font-size:.72rem;color:var(--gray);margin-bottom:3px">📝 কাস্টম লেখা:</p>
          <p style="font-size:.88rem;font-weight:600;color:var(--w)"><?= htmlspecialchars($item['custom_design_text']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($item['custom_design_image1'] || $item['custom_design_image2']): ?>
        <p style="font-size:.72rem;color:var(--gray);margin-bottom:7px">🖼️ কাস্টম ছবি:</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <?php if ($item['custom_design_image1']): ?>
          <div style="text-align:center">
            <p style="font-size:.68rem;color:var(--gray);margin-bottom:4px">ছবি ১</p>
            <a href="<?= htmlspecialchars($item['custom_design_image1']) ?>" target="_blank">
              <img src="<?= htmlspecialchars($item['custom_design_image1']) ?>" alt="Custom 1" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:2px solid var(--g);display:block">
              <p style="font-size:.66rem;color:var(--g);margin-top:3px">🔍 বড় করে দেখুন</p>
            </a>
          </div>
          <?php endif; ?>
          <?php if ($item['custom_design_image2']): ?>
          <div style="text-align:center">
            <p style="font-size:.68rem;color:var(--gray);margin-bottom:4px">ছবি ২</p>
            <a href="<?= htmlspecialchars($item['custom_design_image2']) ?>" target="_blank">
              <img src="<?= htmlspecialchars($item['custom_design_image2']) ?>" alt="Custom 2" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:2px solid var(--g);display:block">
              <p style="font-size:.66rem;color:var(--g);margin-top:3px">🔍 বড় করে দেখুন</p>
            </a>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="aps">
      <div class="apr"><span>পণ্যের মূল্য:</span><span>৳<?= number_format($order['total_amount'] - $order['delivery_charge'], 0) ?></span></div>
      <div class="apr"><span>ডেলিভারি:</span><span>৳<?= number_format($order['delivery_charge'], 0) ?></span></div>
      <div class="apt"><span>মোট:</span><strong>৳<?= number_format($order['total_amount'], 0) ?></strong></div>
    </div>
  </div>

  <!-- Payment তথ্য -->
  <div class="aodc">
    <h3>💳 Payment তথ্য</h3>
    <div class="aig">
      <div class="air">
        <span>পদ্ধতি:</span>
        <strong>
          <?php if ($order['payment_method'] === 'cod'): ?>
          <span class="pay-badge pay-cod">💵 Cash on Delivery</span>
          <?php elseif ($order['payment_method'] === 'bkash'): ?>
          <span class="pay-badge pay-bkash">🏦 বিকাশ</span>
          <?php elseif ($order['payment_method'] === 'nagad'): ?>
          <span class="pay-badge pay-nagad">📱 নগদ</span>
          <?php else: ?><?= htmlspecialchars($order['payment_method'] ?? 'COD') ?><?php endif; ?>
        </strong>
      </div>
      <div class="air">
        <span>Status:</span>
        <strong>
          <?php if (in_array($order['payment_status'], ['paid','cod'])): ?>
          <span class="pay-badge pay-paid">✅ পরিশোধ হয়েছে</span>
          <?php elseif ($order['payment_status'] === 'pending_verification'): ?>
          <span class="pay-badge pay-pending">⏳ যাচাই বাকি</span>
          <?php else: ?>
          <span class="pay-badge pay-pending">⏳ অপেক্ষায়</span>
          <?php endif; ?>
        </strong>
      </div>
      <?php if ($order['sender_last4']): ?>
      <div class="air">
        <span>পাঠানো নম্বরের শেষ ৪:</span>
        <strong style="font-size:1.1rem;letter-spacing:3px;color:var(--g)">****<?= htmlspecialchars($order['sender_last4']) ?></strong>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Status Change -->
  <div class="aodc">
    <h3>📊 অর্ডার স্ট্যাটাস পরিবর্তন</h3>
    <p style="font-size:.82rem;color:var(--gray);margin-bottom:10px">বর্তমান: <span class="sb s-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></p>
    <div class="sbg">
      <?php
      $statuses = [
        ['pending',    '⏳ Pending',    ''],
        ['accepted',   '✅ Accepted',   ''],
        ['processing', '⚙️ Processing', ''],
        ['delivering', '🚚 Delivering', ''],
        ['delivered',  '📦 Delivered',  ''],
        ['cancelled',  '❌ Cancelled',  ' sbt-c'],
      ];
      foreach ($statuses as [$s, $label, $extra]):
      ?>
      <button class="sbt<?= $order['status']===$s?' on':'' ?><?= $extra ?>" onclick="us('<?= $s ?>')">
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>
    <div id="sm" class="smsg" style="display:none">✅ স্ট্যাটাস আপডেট হয়েছে!</div>
  </div>

</div>

<script>
async function us(s) {
  const fd = new FormData(); fd.append('status', s);
  const r  = await fetch('/api/order.php?action=status&id=<?= $id ?>', {method:'POST', body:fd});
  const d  = await r.json();
  if (d.ok) {
    document.querySelectorAll('.sbt').forEach(b => b.classList.remove('on'));
    event.target.classList.add('on');
    const m = document.getElementById('sm');
    m.style.display = 'block';
    setTimeout(() => m.style.display = 'none', 2500);
  }
}
</script>

<?php admin_foot(); ?>