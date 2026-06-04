<?php
require_once __DIR__ . '/base.php';

$pid     = (int)($_GET['id'] ?? 0);
$product = null;
$images  = [];
$options = [];
$error   = '';

try {
    $cats_raw   = $cfg['product_categories'] ?? '[]';
    $categories = json_decode($cats_raw, true) ?: [];
} catch (Exception $e) {
    $categories = [];
}

if ($pid) {
    $product = DB::row("SELECT * FROM products WHERE id = ?", [$pid]);
    if (!$product) { header('Location: /admin/products'); exit; }
    $images = DB::rows("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$pid]);
    $opts_raw = DB::rows("SELECT * FROM product_options WHERE product_id = ?", [$pid]);
    foreach ($opts_raw as $opt) {
        $opt['values'] = DB::rows("SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order ASC", [$opt['id']]);
        $options[] = $opt;
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name              = trim($_POST['name']        ?? '');
    $description       = trim($_POST['description'] ?? '');
    $video_url         = trim($_POST['video_url']   ?? '');
    $gender            = trim($_POST['gender']      ?? 'all');
    $category          = trim($_POST['category']    ?? '');
    $has_custom_design = isset($_POST['has_custom_design']) ? 1 : 0;
    $base_price        = (float)($_POST['base_price']      ?? 0);
    $delivery_charge   = (float)($_POST['delivery_charge'] ?? 60);
    $options_data      = $_POST['options_data']     ?? '[]';
    $deleted_ids_raw   = $_POST['deleted_image_ids'] ?? '[]';

    try {
        $img_prices = json_decode($_POST['image_prices'] ?? '[]', true) ?: [];
    } catch (Exception $e) { $img_prices = []; }
    try {
        $deleted_ids = array_filter(json_decode($deleted_ids_raw, true) ?: []);
    } catch (Exception $e) { $deleted_ids = []; }

    if (!$name) {
        $error = 'পণ্যের নাম দিন';
    } else {
        if ($pid) {
            // Update
            DB::run(
                "UPDATE products SET name=?, description=?, base_price=?, delivery_charge=?, video_url=?, gender=?, category=?, has_custom_design=? WHERE id=?",
                [$name, $description, $base_price, $delivery_charge, $video_url ?: null, $gender, $category, $has_custom_design, $pid]
            );

            // Delete images
            foreach ($deleted_ids as $did) {
                DB::run("DELETE FROM product_images WHERE id = ? AND product_id = ?", [(int)$did, $pid]);
            }

            // Get current max sort_order
            $mo = DB::row("SELECT MAX(sort_order) AS mo FROM product_images WHERE product_id = ?", [$pid]);
            $sort_idx = ((int)($mo['mo'] ?? -1)) + 1;

            // New images
            $new_img_prices = json_decode($_POST['new_image_prices'] ?? '[]', true) ?: [];
            $files = $_FILES['new_images'] ?? [];
            if (!empty($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = [
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                    $url = upload_image($file, 'prod', 'products');
                    if ($url) {
                        $price = (float)($new_img_prices[$i] ?? $base_price) ?: $base_price;
                        DB::run(
                            "INSERT INTO product_images (product_id, image_path, price, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())",
                            [$pid, $url, $price, $sort_idx++]
                        );
                    }
                }
            }

        } else {
            // Insert
            $new_pid = DB::exec(
                "INSERT INTO products (name, description, base_price, delivery_charge, video_url, gender, category, has_custom_design, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                [$name, $description, $base_price, $delivery_charge, $video_url ?: null, $gender, $category, $has_custom_design]
            );

            // Images
            $files = $_FILES['images'] ?? [];
            if (!empty($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = [
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                    $url = upload_image($file, 'prod', 'products');
                    if ($url) {
                        $price = (float)($img_prices[$i] ?? $base_price) ?: $base_price;
                        DB::run(
                            "INSERT INTO product_images (product_id, image_path, price, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())",
                            [$new_pid, $url, $price, $i]
                        );
                    }
                }
            }
            $pid = $new_pid;
        }

        // Options
        $opts = json_decode($options_data, true) ?: [];
        $existing_opts = DB::rows("SELECT id FROM product_options WHERE product_id = ?", [$pid]);
        foreach ($existing_opts as $eo) {
            DB::run("DELETE FROM product_option_values WHERE option_id = ?", [$eo['id']]);
        }
        DB::run("DELETE FROM product_options WHERE product_id = ?", [$pid]);
        foreach ($opts as $opt) {
            if (empty($opt['name'])) continue;
            $oid = DB::exec("INSERT INTO product_options (product_id, option_name) VALUES (?, ?)", [$pid, $opt['name']]);
            foreach (($opt['values'] ?? []) as $j => $v) {
                if (trim($v) === '') continue;
                DB::run("INSERT INTO product_option_values (option_id, value, sort_order) VALUES (?, ?, ?)", [$oid, trim($v), $j]);
            }
        }

        header('Location: /admin/products'); exit;
    }
}

admin_head($product ? 'পণ্য সম্পাদনা' : 'নতুন পণ্য');
admin_nav('products');
?>

<div class="aph">
  <a href="/admin/products" class="abl">← পণ্য তালিকা</a>
  <h1><?= $product ? '✏️ পণ্য সম্পাদনা' : '📦 নতুন পণ্য' ?></h1>
</div>

<?php if ($error): ?>
<div style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.84rem"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form action="<?= $product ? '/admin/products/'.$product['id'].'/edit' : '/admin/products/add' ?>"
      method="POST" enctype="multipart/form-data" class="aform" id="pf">

  <!-- Basic Info -->
  <div class="afs">
    <h3>মূল তথ্য</h3>
    <div class="frow"><label>পণ্যের নাম *</label><input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required class="ai" placeholder="পণ্যের নাম লিখুন"></div>
    <div class="frow"><label>বিবরণ</label><textarea name="description" class="ata" placeholder="পণ্যের বিবরণ"><?= htmlspecialchars($product['description'] ?? '') ?></textarea></div>
    <div class="fr2">
      <div class="frow"><label>মূল মূল্য (৳) *</label><input type="number" name="base_price" value="<?= $product['base_price'] ?? '0' ?>" required class="ai" min="0" step="0.01"></div>
      <div class="frow"><label>ডেলিভারি চার্জ (৳)</label><input type="number" name="delivery_charge" value="<?= $product['delivery_charge'] ?? '60' ?>" class="ai" min="0" step="0.01"></div>
    </div>
    <div class="frow"><label>YouTube ভিডিও URL</label><input type="url" name="video_url" value="<?= htmlspecialchars($product['video_url'] ?? '') ?>" class="ai" placeholder="https://www.youtube.com/watch?v=..."></div>
  </div>

  <!-- Gender & Category -->
  <div class="afs">
    <h3>👥 লিঙ্গ ও ক্যাটাগরি</h3>
    <div class="fr2">
      <div class="frow">
        <label>লিঙ্গ নির্বাচন</label>
        <?php $cur_gender = $product['gender'] ?? 'all'; ?>
        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:4px">
          <button type="button" class="gender-btn<?= $cur_gender==='all'?' gender-on':'' ?>" onclick="setGender('all',this)">🌟 All</button>
          <button type="button" class="gender-btn<?= $cur_gender==='male'?' gender-on':'' ?>" onclick="setGender('male',this)">👨 Male</button>
          <button type="button" class="gender-btn<?= $cur_gender==='female'?' gender-on':'' ?>" onclick="setGender('female',this)">👩 Female</button>
        </div>
        <input type="hidden" name="gender" id="genderInput" value="<?= htmlspecialchars($cur_gender) ?>">
      </div>
      <div class="frow">
        <label>ক্যাটাগরি</label>
        <select name="category" class="ai">
          <option value="">-- ক্যাটাগরি নির্বাচন --</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category'] ?? '') === $cat ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p style="font-size:.72rem;color:var(--gray);margin-top:4px">UI Settings এ ক্যাটাগরি যোগ করুন</p>
      </div>
    </div>
  </div>

  <!-- Custom Design -->
  <div class="afs">
    <h3>🎨 কাস্টম ডিজাইন অপশন</h3>
    <p class="afn">চালু করলে ইউজার অর্ডারের সময় ২টি ছবি ও লেখা যোগ করতে পারবে।</p>
    <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--k3);border-radius:var(--r);border:1px solid var(--bdr2)">
      <div style="flex:1">
        <p style="font-weight:700;font-size:.88rem;margin-bottom:3px">🎨 কাস্টম ডিজাইন</p>
        <p style="font-size:.76rem;color:var(--gray)">ইউজার ছবি ও লেখা দিয়ে কাস্টম অর্ডার করতে পারবে</p>
      </div>
      <label style="position:relative;width:50px;height:27px;cursor:pointer;flex-shrink:0">
        <input type="checkbox" name="has_custom_design" id="customDesignToggle"
          style="opacity:0;width:0;height:0;position:absolute"
          <?= ($product['has_custom_design'] ?? 0) ? 'checked' : '' ?>
          onchange="toggleCD(this)">
        <span id="toggleTrack" style="position:absolute;inset:0;border-radius:50px;background:<?= ($product['has_custom_design'] ?? 0) ? 'var(--g)' : 'var(--bdr2)' ?>;transition:background .3s">
          <span id="toggleKnob" style="position:absolute;top:3px;left:<?= ($product['has_custom_design'] ?? 0) ? '26px' : '3px' ?>;width:21px;height:21px;border-radius:50%;background:white;transition:left .3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span>
        </span>
      </label>
    </div>
  </div>

  <!-- Images -->
  <div class="afs">
    <h3>পণ্যের ছবি</h3>
    <p class="afn">একাধিক ছবি যোগ করুন। ✕ চাপলে মুছে যাবে।</p>

    <?php if ($images): ?>
    <p style="font-size:.78rem;color:var(--gray);margin-bottom:8px">বর্তমান ছবি:</p>
    <div class="ig" id="eig">
      <?php foreach ($images as $img): ?>
      <div class="ipc" id="eiw<?= $img['id'] ?>">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <div class="iov">
          <input type="number" class="ip" placeholder="মূল্য ৳" value="<?= $img['price'] ?: '' ?>">
        </div>
        <button type="button" class="img-del-btn" onclick="markDelete(<?= $img['id'] ?>)" title="মুছুন">✕</button>
      </div>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="deleted_image_ids" id="deletedIds" value="[]">
    <?php endif; ?>

    <div class="aup" onclick="document.getElementById('imgFileInput').click()" style="margin-top:10px">
      <div class="ui">📷</div>
      <p>নতুন ছবি যোগ করতে ক্লিক করুন</p>
      <p class="us">একাধিক ছবি একসাথে নির্বাচন করুন</p>
    </div>
    <input type="file" id="imgFileInput" multiple accept="image/*" style="display:none" onchange="handleNewImages(this)">
    <div class="ig" id="newImgGrid" style="margin-top:10px"></div>
    <div id="newImgHiddenContainer"></div>
    <input type="hidden" name="<?= $product ? 'new_image_prices' : 'image_prices' ?>" id="newImgPrices" value="[]">
  </div>

  <!-- Options -->
  <div class="afs">
    <h3>পণ্যের অপশন <small style="font-weight:400;color:var(--gray)">(যেমন: সাইজ, রঙ)</small></h3>
    <div id="optContainer">
      <?php foreach ($options as $i => $opt): ?>
      <div class="og" id="og<?= $i ?>">
        <div class="ohd">
          <input type="text" class="ai on-i" placeholder="অপশনের নাম" value="<?= htmlspecialchars($opt['option_name']) ?>">
          <button type="button" class="bro" onclick="removeOpt(this)">✕ সরান</button>
        </div>
        <div class="ovs" id="ov<?= $i ?>">
          <?php foreach ($opt['values'] as $val): ?>
          <div class="ovr">
            <input type="text" class="ai ov-i" placeholder="মান" value="<?= htmlspecialchars($val['value']) ?>">
            <button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="bav" onclick="addOptVal(<?= $i ?>)">+ মান যোগ করুন</button>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" style="margin-top:10px" onclick="addOpt()">+ নতুন অপশন যোগ করুন</button>
    <input type="hidden" name="options_data" id="optionsData" value="[]">
  </div>

  <div class="afact">
    <button type="submit" class="abg" onclick="prepareSubmit()">
      <?= $product ? '✅ পরিবর্তন সংরক্ষণ' : '✅ পণ্য যোগ করুন' ?>
    </button>
    <a href="/admin/products" class="abs">বাতিল</a>
  </div>
</form>

<script>
function setGender(v,btn){document.getElementById('genderInput').value=v;document.querySelectorAll('.gender-btn').forEach(b=>b.classList.remove('gender-on'));btn.classList.add('gender-on');}
function toggleCD(cb){const t=document.getElementById('toggleTrack');const k=document.getElementById('toggleKnob');if(cb.checked){t.style.background='var(--g)';k.style.left='26px';}else{t.style.background='var(--bdr2)';k.style.left='3px';}}

const deletedIds=[];
function markDelete(id){if(!deletedIds.includes(id))deletedIds.push(id);document.getElementById('deletedIds').value=JSON.stringify(deletedIds);const w=document.getElementById('eiw'+id);if(w){w.style.opacity='0.2';w.style.pointerEvents='none';}}

let newImgFiles=[];
function handleNewImages(input){Array.from(input.files).forEach(f=>{newImgFiles.push({file:f,price:'',objectUrl:URL.createObjectURL(f),removed:false});});input.value='';renderNewImgGrid();}
function renderNewImgGrid(){const g=document.getElementById('newImgGrid');g.innerHTML='';newImgFiles.forEach((item,idx)=>{if(item.removed)return;const d=document.createElement('div');d.className='ipc';d.style.position='relative';d.innerHTML=`<img src="${item.objectUrl}" alt="" style="width:100%;height:100%;object-fit:cover"><div class="iov"><input type="number" class="ip" placeholder="মূল্য ৳" value="${item.price}" onchange="newImgFiles[${idx}].price=this.value"><span style="font-size:.6rem;color:#fff;opacity:.8">#${idx+1}</span></div><button type="button" class="img-del-btn" onclick="removeNewImg(${idx})">✕</button>`;g.appendChild(d);});}
function removeNewImg(idx){newImgFiles[idx].removed=true;renderNewImgGrid();}

let optCount=<?= count($options) ?>;
function addOpt(){const idx=optCount++;const d=document.createElement('div');d.className='og';d.id='og'+idx;d.innerHTML=`<div class="ohd"><input type="text" class="ai on-i" placeholder="অপশনের নাম (যেমন: সাইজ)"><button type="button" class="bro" onclick="removeOpt(this)">✕ সরান</button></div><div class="ovs" id="ov${idx}"><div class="ovr"><input type="text" class="ai ov-i" placeholder="মান (যেমন: M)"><button type="button" class="brv" onclick="this.parentElement.remove()">✕</button></div></div><button type="button" class="bav" onclick="addOptVal(${idx})">+ মান যোগ করুন</button>`;document.getElementById('optContainer').appendChild(d);}
function removeOpt(btn){btn.closest('.og').remove();}
function addOptVal(gi){const c=document.getElementById('ov'+gi);const r=document.createElement('div');r.className='ovr';r.innerHTML=`<input type="text" class="ai ov-i" placeholder="মান লিখুন"><button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>`;c.appendChild(r);}

function prepareSubmit(){
  const opts=[];document.querySelectorAll('.og').forEach(g=>{const n=g.querySelector('.on-i')?.value?.trim();if(!n)return;const vals=Array.from(g.querySelectorAll('.ov-i')).map(i=>i.value.trim()).filter(Boolean);opts.push({name:n,values:vals});});
  document.getElementById('optionsData').value=JSON.stringify(opts);
  const prices=newImgFiles.filter(i=>!i.removed).map(i=>i.price||'');
  document.getElementById('newImgPrices').value=JSON.stringify(prices);
  const container=document.getElementById('newImgHiddenContainer');container.innerHTML='';
  const validFiles=newImgFiles.filter(i=>!i.removed);
  if(validFiles.length>0){try{const dt=new DataTransfer();validFiles.forEach(item=>dt.items.add(item.file));const fi=document.createElement('input');fi.type='file';fi.name='<?= $product ? 'new_images' : 'images' ?>';fi.multiple=true;fi.style.display='none';fi.files=dt.files;container.appendChild(fi);}catch(e){console.warn(e);}}
}
</script>

<?php admin_foot(); ?>