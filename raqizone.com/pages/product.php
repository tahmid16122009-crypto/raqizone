<?php
require_once __DIR__ . '/../templates/layout.php';

$u  = get_current_user();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /home'); exit; }

$p = DB::row(
    "SELECT * FROM products WHERE id = ? AND is_active = 1",
    [$id]
);
if (!$p) { header('Location: /home'); exit; }

// Images
$images = DB::rows(
    "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC",
    [$id]
);

// Options
$options_raw = DB::rows(
    "SELECT * FROM product_options WHERE product_id = ?",
    [$id]
);
$options = [];
foreach ($options_raw as $opt) {
    $vals = DB::rows(
        "SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order ASC",
        [$opt['id']]
    );
    $opt['values'] = $vals;
    $options[] = $opt;
}

$pay_opt   = $cfg['payment_options'] ?? 'cod';
$bkash_num = $cfg['bkash_number']    ?? '';
$nagad_num = $cfg['nagad_number']    ?? '';

render_head(htmlspecialchars($p['name']) . ' — ' . ($cfg['site_name'] ?? ''), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st">পণ্যের বিবরণ</span>
    <a href="/cart" class="ib">
      <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg>
    </a>
  </div>

  <!-- Image Carousel -->
  <div class="car" id="car">
    <?php if ($images): ?>
    <div class="ct" id="ct">
      <?php foreach ($images as $img): ?>
      <div class="cs">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
      </div>
      <?php endforeach; ?>
    </div>
    <div class="cd">
      <?php foreach ($images as $i => $img): ?>
      <span class="dot<?= $i===0?' on':'' ?>" onclick="gs(<?= $i ?>)"></span>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="ce">🛍️</div>
    <?php endif; ?>
    <?php if ($p['video_url']): ?>
    <button class="vb2" onclick="ov('<?= htmlspecialchars($p['video_url']) ?>')">▶ পণ্যের ভিডিও</button>
    <?php endif; ?>
  </div>

  <div class="pdi">
    <h1 class="pdn"><?= htmlspecialchars($p['name']) ?></h1>
    <?php if ($p['description']): ?>
    <p class="pdd"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
    <?php endif; ?>
    <div class="pdr">
      <span class="pdp">৳<?= number_format($p['base_price'], 0) ?></span>
    </div>
    <p class="pdc">🚚 ডেলিভারি চার্জ: ৳<?= number_format($p['delivery_charge'], 0) ?></p>
    <?php if ($p['has_custom_design']): ?>
    <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(201,168,76,.1);border:1px solid var(--g);border-radius:50px;padding:4px 12px;margin-top:6px;font-size:.78rem;color:var(--g);font-weight:600">
      🎨 কাস্টম ডিজাইন সমর্থিত
    </div>
    <?php endif; ?>
  </div>
  <div style="height:80px"></div>
</div>

<!-- Action Buttons -->
<div class="pdacts">
  <button class="bca" onclick="qc()">🛒 কার্টে যোগ</button>
  <button class="boa" onclick="op()">📦 অর্ডার করুন</button>
</div>

<!-- Order Panel -->
<div class="pov" id="pov" onclick="if(event.target===this)cp()"></div>
<div class="panel" id="panel">
  <div class="ph2"></div>
  <div class="phd">
    <h3>অর্ডার করুন</h3>
    <button class="pcl" onclick="cp()">✕</button>
  </div>

  <!-- Step 1: Select variant -->
  <div id="s1" class="pst">
    <p class="slbl">ধাপ ১ — পণ্য নির্বাচন করুন</p>
    <div class="isg" id="ig">
      <?php foreach ($images as $i => $img): ?>
      <div class="isi" id="ic<?= $i ?>"
        onclick="ti(<?= $i ?>,'<?= $img['id'] ?>','<?= htmlspecialchars($img['image_path']) ?>',<?= $img['price'] ?: $p['base_price'] ?>)">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <div class="ick">✓</div>
        <div class="isp">৳<?= number_format($img['price'] ?: $p['base_price'], 0) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div id="sit"></div>
    <div id="pb" class="pbx" style="display:none">
      <div class="prow"><span>পণ্যের মূল্য:</span><span id="ps2">৳0</span></div>
      <div class="prow"><span>ডেলিভারি:</span><span>৳<?= number_format($p['delivery_charge'], 0) ?></span></div>
      <div class="ptot"><span>মোট:</span><span id="pt2">৳0</span></div>
    </div>
    <button class="bn" id="bn" onclick="g2()" disabled>পরবর্তী ধাপ →</button>
  </div>

  <!-- Step 2: Custom Design (if enabled) -->
  <?php if ($p['has_custom_design']): ?>
  <div id="s2cd" class="pst" style="display:none">
    <button class="bbk" onclick="b1cd()">← পিছনে</button>
    <p class="slbl">ধাপ ২ — কাস্টম ডিজাইন</p>
    <p style="font-size:.78rem;color:var(--gray);margin-bottom:14px;line-height:1.6">ছবি ও লেখা দিন। না দিলে খালি রাখুন।</p>
    <div id="cdItemsContainer"></div>
    <button class="bn" onclick="g3()">পরবর্তী ধাপ →</button>
  </div>
  <?php endif; ?>

  <!-- Step 3: Delivery Info -->
  <div id="s3" class="pst" style="display:none">
    <button class="bbk" onclick="<?= $p['has_custom_design']?'b2cd()':'b1()' ?>">← পিছনে</button>
    <p class="slbl">ধাপ <?= $p['has_custom_design']?'৩':'২' ?> — ডেলিভারি তথ্য</p>
    <div id="s2sum" class="s2s"></div>
    <div class="fs">
      <div class="fd"><label>নাম *</label><input id="oN" class="inp" placeholder="পুরো নাম" value="<?= htmlspecialchars($u['name'] ?? '') ?>"></div>
      <div class="fd"><label>মোবাইল *</label><input id="oM" class="inp" type="tel" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($u['mobile'] ?? '') ?>"></div>
      <div class="fd"><label>জেলা *</label><input id="oDi" class="inp" placeholder="যেমন: ঢাকা"></div>
      <div class="fd"><label>উপজেলা *</label><input id="oUp" class="inp" placeholder="উপজেলার নাম"></div>
      <div class="fd"><label>ইউনিয়ন *</label><input id="oUn" class="inp" placeholder="ইউনিয়নের নাম"></div>
      <div class="fd"><label>গ্রাম *</label><input id="oVi" class="inp" placeholder="গ্রামের নাম"></div>
      <div class="fd"><label>রাস্তা <small>(ঐচ্ছিক)</small></label><input id="oRo" class="inp" placeholder="রাস্তার নাম"></div>
      <div class="fd"><label>হোল্ডিং <small>(ঐচ্ছিক)</small></label><input id="oHo" class="inp" placeholder="হোল্ডিং নম্বর"></div>
    </div>

    <!-- Payment -->
    <div class="pay-section" style="margin-top:14px">
      <?php if ($pay_opt === 'cod'): ?>
      <div class="pay-cod-box">
        <span class="pico2">💵</span>
        <div class="ptxt2">
          <span class="ptitle2">Cash on Delivery</span>
          <span class="psub2">পণ্য পেলে টাকা দিন</span>
        </div>
      </div>
      <button class="bpl" onclick="po('cod',null,this)">✅ অর্ডার নিশ্চিত করুন</button>

      <?php else: ?>
      <p class="pay-title">পেমেন্ট পদ্ধতি নির্বাচন করুন</p>
      <div class="pay-methods">
        <?php if ($pay_opt === 'all'): ?>
        <button class="pmb" onclick="selPM('cod',this)">
          <div class="pmb-ico">💵</div>
          <div class="pmb-txt"><span class="pmb-title">Cash on Delivery</span><span class="pmb-sub">পণ্য পেলে টাকা দিন</span></div>
        </button>
        <?php endif; ?>
        <?php if ($bkash_num): ?>
        <button class="pmb" onclick="selPM('bkash',this)">
          <div class="pmb-ico">🏦</div>
          <div class="pmb-txt">
            <span class="pmb-title">বিকাশ</span>
            <span class="pmb-sub"><?= $pay_opt==='delivery_only'?'শুধু ডেলিভারি চার্জ':'সম্পূর্ণ পেমেন্ট' ?></span>
          </div>
          <span class="pmb-amt" id="bkashAmtBadge">৳—</span>
        </button>
        <?php endif; ?>
        <?php if ($nagad_num): ?>
        <button class="pmb" onclick="selPM('nagad',this)">
          <div class="pmb-ico">📱</div>
          <div class="pmb-txt">
            <span class="pmb-title">নগদ</span>
            <span class="pmb-sub"><?= $pay_opt==='delivery_only'?'শুধু ডেলিভারি চার্জ':'সম্পূর্ণ পেমেন্ট' ?></span>
          </div>
          <span class="pmb-amt" id="nagadAmtBadge">৳—</span>
        </button>
        <?php endif; ?>
      </div>

      <?php if ($bkash_num): ?>
      <div id="bkashBox" class="smbox" style="display:none">
        <p class="smbox-title">নিচের নম্বরে <strong id="bkashAmtTxt"></strong> <strong>Send Money</strong> করুন:</p>
        <div class="smnum"><?= htmlspecialchars($bkash_num) ?></div>
        <div class="fd"><label>পাঠানো নম্বরের শেষ ৪ সংখ্যা *</label>
          <input type="text" id="bkashLast4" class="inp last4" placeholder="৪ সংখ্যা" maxlength="4" inputmode="numeric">
        </div>
        <button class="sm-confirm" onclick="po('bkash','bkashLast4',this)">✅ বিকাশে পাঠিয়েছি — অর্ডার করুন</button>
      </div>
      <?php endif; ?>

      <?php if ($nagad_num): ?>
      <div id="nagadBox" class="smbox" style="display:none">
        <p class="smbox-title">নিচের নম্বরে <strong id="nagadAmtTxt"></strong> <strong>Send Money</strong> করুন:</p>
        <div class="smnum"><?= htmlspecialchars($nagad_num) ?></div>
        <div class="fd"><label>পাঠানো নম্বরের শেষ ৪ সংখ্যা *</label>
          <input type="text" id="nagadLast4" class="inp last4" placeholder="৪ সংখ্যা" maxlength="4" inputmode="numeric">
        </div>
        <button class="sm-confirm" onclick="po('nagad','nagadLast4',this)">✅ নগদে পাঠিয়েছি — অর্ডার করুন</button>
      </div>
      <?php endif; ?>

      <?php if ($pay_opt === 'all'): ?>
      <div id="codBox" style="display:none;margin-top:8px">
        <div class="pay-cod-box" style="margin-bottom:10px">
          <span class="pico2">💵</span>
          <div class="ptxt2"><span class="ptitle2">Cash on Delivery</span><span class="psub2">পণ্য পেলে টাকা দিন</span></div>
        </div>
        <button class="bpl" onclick="po('cod',null,this)">✅ অর্ডার নিশ্চিত করুন</button>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Video overlay -->
<div class="vov" id="vov">
  <button class="vcl" onclick="cv()">✕</button>
  <iframe id="vf" src="" frameborder="0" allowfullscreen></iframe>
</div>

<div class="toast" id="t1">✅ অর্ডার সম্পন্ন!</div>
<div class="toast" id="t2">🛒 কার্টে যোগ হয়েছে!</div>
<div class="toast" id="tSess" style="border-color:#F44336;color:#F44336">⚠️ Session শেষ। আবার লগিন করুন।</div>

<script>
const PD = {
  id: <?= $p['id'] ?>,
  name: <?= json_encode($p['name']) ?>,
  base: <?= $p['base_price'] ?>,
  del: <?= $p['delivery_charge'] ?>,
  hasCustomDesign: <?= $p['has_custom_design'] ? 'true' : 'false' ?>,
  imgs: <?= json_encode(array_map(fn($img) => [
    'id'    => (string)$img['id'],
    'path'  => $img['image_path'],
    'price' => (float)($img['price'] ?: $p['base_price'])
  ], $images)) ?>,
  opts: <?= json_encode(array_map(fn($o) => [
    'name' => $o['option_name'],
    'vals' => array_column($o['values'], 'value')
  ], $options)) ?>,
  payOpt: <?= json_encode($pay_opt) ?>,
  bkashNum: <?= json_encode($bkash_num) ?>,
  nagadNum: <?= json_encode($nagad_num) ?>
};
let cur=0, sel={}, cdData={};

// Carousel
function gs(n){cur=n;const t=document.getElementById('ct');if(t)t.style.transform='translateX(-'+(n*100)+'%)';document.querySelectorAll('.dot').forEach((d,i)=>d.classList.toggle('on',i===n));}
(function(){const c=document.getElementById('car');let sx=0;if(!c)return;c.addEventListener('touchstart',e=>{sx=e.touches[0].clientX;},{passive:true});c.addEventListener('touchend',e=>{const d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>50)gs(d>0?Math.min(cur+1,PD.imgs.length-1):Math.max(cur-1,0));},{passive:true});})();

// Panel
function op(){document.getElementById('pov').classList.add('show');document.getElementById('panel').classList.add('show');document.body.style.overflow='hidden';}
function cp(){document.getElementById('pov').classList.remove('show');document.getElementById('panel').classList.remove('show');document.body.style.overflow='';}

// Image select
function ti(idx,id,path,price){
  if(sel[id]){delete sel[id];document.getElementById('ic'+idx).classList.remove('pk');}
  else{sel[id]={idx,id,path,price,qty:1,opts:{}}; document.getElementById('ic'+idx).classList.add('pk');}
  rs(); calcTotal();
}
function rs(){
  const keys=Object.keys(sel);
  document.getElementById('bn').disabled=!keys.length;
  if(!keys.length){document.getElementById('sit').innerHTML='';document.getElementById('pb').style.display='none';return;}
  document.getElementById('pb').style.display='block';
  document.getElementById('sit').innerHTML='<p class="selbl">নির্বাচিত পণ্য:</p>'+
    keys.map(id=>{const it=sel[id];
      const oh=PD.opts.map(o=>`<div class="or"><label>${o.name}:</label><select class="os" onchange="so('${id}','${o.name}',this.value)"><option value="">-- নির্বাচন --</option>${o.vals.map(v=>`<option value="${v}"${it.opts[o.name]===v?' selected':''}>${v}</option>`).join('')}</select></div>`).join('');
      return `<div class="sec"><img src="${it.path}" alt=""><div class="seci"><span class="secp">৳${it.price.toFixed(0)}/পিস</span>${oh}<div class="qr"><button class="qb" onclick="cq('${id}',-1)">−</button><span class="qn" id="q${id}">${it.qty}</span><button class="qb" onclick="cq('${id}',1)">+</button><button class="qd" onclick="ri('${id}',${it.idx})">🗑</button></div></div></div>`;
    }).join('');
}
function so(id,n,v){if(sel[id])sel[id].opts[n]=v;}
function cq(id,d){if(!sel[id])return;sel[id].qty=Math.max(1,sel[id].qty+d);document.getElementById('q'+id).textContent=sel[id].qty;calcTotal();}
function ri(id,idx){delete sel[id];document.getElementById('ic'+idx)?.classList.remove('pk');rs();calcTotal();}
function calcTotal(){
  let s=0;Object.values(sel).forEach(i=>s+=i.price*i.qty);
  document.getElementById('ps2').textContent='৳'+s.toFixed(0);
  document.getElementById('pt2').textContent='৳'+(s+PD.del).toFixed(0);
  const isDelOnly=PD.payOpt==='delivery_only';
  const amt=isDelOnly?PD.del:s+PD.del;
  const amtStr='৳'+amt.toFixed(0);
  const b1=document.getElementById('bkashAmtBadge');if(b1)b1.textContent=amtStr;
  const b2=document.getElementById('nagadAmtBadge');if(b2)b2.textContent=amtStr;
}

// Step navigation
function g2(){
  const keys=Object.keys(sel);if(!keys.length)return;
  for(const id of keys){for(const o of PD.opts){if(!sel[id].opts[o.name]){alert('"'+o.name+'" নির্বাচন করুন');return;}}}
  document.getElementById('s1').style.display='none';
  if(PD.hasCustomDesign){buildCDForm();document.getElementById('s2cd').style.display='block';}
  else{buildDeliverySum();document.getElementById('s3').style.display='block';}
}
function b1(){document.getElementById('s3').style.display='none';document.getElementById('s1').style.display='block';}
function b1cd(){document.getElementById('s2cd').style.display='none';document.getElementById('s1').style.display='block';}
function b2cd(){document.getElementById('s3').style.display='none';document.getElementById('s2cd').style.display='block';}
function g3(){document.getElementById('s2cd').style.display='none';buildDeliverySum();document.getElementById('s3').style.display='block';}

// Custom Design
function buildCDForm(){
  const container=document.getElementById('cdItemsContainer');
  container.innerHTML='';
  Object.values(sel).forEach(it=>{
    const div=document.createElement('div');
    div.style.cssText='background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px;margin-bottom:12px';
    div.innerHTML=`<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><img src="${it.path}" style="width:48px;height:48px;object-fit:cover;border-radius:7px;flex-shrink:0"><div><p style="font-weight:700;font-size:.86rem">${PD.name}</p><p style="font-size:.74rem;color:var(--g)">৳${it.price.toFixed(0)} × ${it.qty}</p></div></div>
    <div class="fd" style="margin-bottom:10px"><label style="font-size:.78rem">📝 কাস্টম লেখা <small style="color:var(--gray)">(ঐচ্ছিক)</small></label><input type="text" class="inp" placeholder="যা লিখতে চান..." id="cdtext_${it.id}" oninput="saveCDText('${it.id}',this.value)"></div>
    <div class="fd" style="margin-bottom:10px"><label style="font-size:.78rem">🖼️ ছবি ১ <small style="color:var(--gray)">(ঐচ্ছিক)</small></label><div id="cdimg1prev_${it.id}" style="display:none;margin-bottom:6px"><img id="cdimg1prevImg_${it.id}" src="" style="max-width:100%;max-height:120px;border-radius:7px;object-fit:cover"><button type="button" onclick="clearCDImg('${it.id}',1)" style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);border-radius:6px;padding:3px 10px;font-size:.74rem;cursor:pointer;margin-top:5px;display:block">✕ ছবি সরান</button></div><div class="aup" style="padding:14px" onclick="document.getElementById('cdimg1_${it.id}').click()"><p style="font-size:.8rem">📷 ছবি ১ নির্বাচন করুন</p></div><input type="file" id="cdimg1_${it.id}" accept="image/*" style="display:none" onchange="handleCDImg('${it.id}',1,this)"></div>
    <div class="fd"><label style="font-size:.78rem">🖼️ ছবি ২ <small style="color:var(--gray)">(ঐচ্ছিক)</small></label><div id="cdimg2prev_${it.id}" style="display:none;margin-bottom:6px"><img id="cdimg2prevImg_${it.id}" src="" style="max-width:100%;max-height:120px;border-radius:7px;object-fit:cover"><button type="button" onclick="clearCDImg('${it.id}',2)" style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);border-radius:6px;padding:3px 10px;font-size:.74rem;cursor:pointer;margin-top:5px;display:block">✕ ছবি সরান</button></div><div class="aup" style="padding:14px" onclick="document.getElementById('cdimg2_${it.id}').click()"><p style="font-size:.8rem">📷 ছবি ২ নির্বাচন করুন</p></div><input type="file" id="cdimg2_${it.id}" accept="image/*" style="display:none" onchange="handleCDImg('${it.id}',2,this)"></div>`;
    container.appendChild(div);
    if(!cdData[it.id])cdData[it.id]={text:'',img1:'',img2:''};
    if(cdData[it.id].text)document.getElementById('cdtext_'+it.id).value=cdData[it.id].text;
    if(cdData[it.id].img1Preview){document.getElementById('cdimg1prevImg_'+it.id).src=cdData[it.id].img1Preview;document.getElementById('cdimg1prev_'+it.id).style.display='block';}
    if(cdData[it.id].img2Preview){document.getElementById('cdimg2prevImg_'+it.id).src=cdData[it.id].img2Preview;document.getElementById('cdimg2prev_'+it.id).style.display='block';}
  });
}
function saveCDText(id,v){if(!cdData[id])cdData[id]={text:'',img1:'',img2:''};cdData[id].text=v;}
async function handleCDImg(id,slot,input){
  if(!input.files||!input.files[0])return;
  const file=input.files[0];
  const reader=new FileReader();
  reader.onload=e=>{
    if(!cdData[id])cdData[id]={text:'',img1:'',img2:''};
    cdData[id]['img'+slot+'Preview']=e.target.result;
    const prevDiv=document.getElementById('cdimg'+slot+'prev_'+id);
    const prevImg=document.getElementById('cdimg'+slot+'prevImg_'+id);
    if(prevDiv&&prevImg){prevImg.src=e.target.result;prevDiv.style.display='block';}
  };
  reader.readAsDataURL(file);
  const fd=new FormData();fd.append('image',file);
  try{
    const r=await fetch('/api/upload.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.ok){if(!cdData[id])cdData[id]={text:'',img1:'',img2:''};cdData[id]['img'+slot]=d.url;}
    else alert('ছবি upload হয়নি');
  }catch(e){alert('ছবি upload সমস্যা');}
  input.value='';
}
function clearCDImg(id,slot){
  if(!cdData[id])return;
  cdData[id]['img'+slot]='';cdData[id]['img'+slot+'Preview']='';
  const prevDiv=document.getElementById('cdimg'+slot+'prev_'+id);
  if(prevDiv)prevDiv.style.display='none';
}

// Delivery summary
function buildDeliverySum(){
  let s=0;Object.values(sel).forEach(i=>s+=i.price*i.qty);
  document.getElementById('s2sum').innerHTML=`<div class="s2i">${Object.values(sel).map(it=>{
    const cd=cdData[it.id]||{};
    const cdHtml=PD.hasCustomDesign&&cd?`${cd.text?'<span style="font-size:.72rem;color:var(--gray)">📝 '+cd.text+'</span>':''}${cd.img1?'<img src="'+cd.img1+'" style="width:32px;height:32px;object-fit:cover;border-radius:4px;margin-top