<?php 
if(isset($_GET['update'])){
  $product_id = $_GET['update'];

  $product_details_query = "SELECT p.*, c.category_name, b.brand_name FROM product p
                            INNER JOIN category c ON p.category = c.hashed_id
                            INNER JOIN brand b ON p.brand = b.hashed_id
                            WHERE p.id = ?";
  $stmt = $conn->prepare($product_details_query);
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $product = $result->fetch_assoc();
  $product_name = $product['description'];
  $category_id = $product['category'];
  $brand_id = $product['brand'];
  $parent_barcode = $product['parent_barcode'];
  $minimum_stock = $product['safety'];
  
}

?>
<style>
  :root{
    --ink:#1C2321;
    --muted:#667066;
    --surface:#FFFFFF;
    --page:#F6F7F5;
    --border:#E1E4DF;
    --accent:#2F6F4E;
    --accent-tint:#E7F1EC;
    --warn:#C17817;
    --warn-tint:#FCF1E2;
    --radius:10px;
  }
  .pf-wrap{ max-width:100%; margin:2.5rem auto; padding:0 1rem; }
  .pf-card{
    background:var(--surface); border:1px solid var(--border); border-radius:14px;
    overflow:hidden; padding: 10px;
  }
  .pf-head{ padding:1.5rem 1.75rem; border-bottom:1px solid var(--border); }
  .pf-head h2{
    font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:1.35rem; margin:0;
    letter-spacing:-0.01em;
  }
  .pf-head p{ margin:.25rem 0 0; color:var(--muted); font-size:.9rem; }
  .pf-body{ padding:1.75rem; }

  .pf-section{ margin-bottom:2rem; }
  .pf-section:last-child{ margin-bottom:0; }
  .pf-section-label{
    font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:.78rem;
    text-transform:uppercase; letter-spacing:.06em; color:var(--accent);
    display:flex; align-items:center; gap:.5rem; margin-bottom:1rem;
  }
  .pf-section-label::after{
    content:""; flex:1; height:1px; background:var(--border);
  }

  label.pf-label{ font-size:.82rem; font-weight:500; color:var(--ink); margin-bottom:.35rem; display:block; }
  .form-control, .form-select{
    border-color:var(--border); border-radius:8px; font-size:.92rem;
  }
  .form-control:focus, .form-select:focus{
    border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-tint);
  }

  /* Live label preview — signature element */
  .pf-label-preview{
    background:var(--accent-tint); border:1px dashed #B7D2C4; border-radius:10px;
    padding:1.1rem 1.25rem; display:flex; align-items:center; justify-content:space-between;
    gap:1rem; margin-bottom:1.75rem;
  }
  .pf-label-tag{
    background:var(--surface); border:1px solid var(--border); border-radius:6px;
    padding:.6rem .9rem; min-width:80%;
  }
  .pf-label-tag .eyebrow{ font-size:.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:.15rem; }
  .pf-label-tag .name{
    font-family:'IBM Plex Mono',monospace; font-weight:500; font-size:1rem; color:var(--ink);
    min-height:1.3em;
  }
  .pf-label-tag .name.is-empty{ color:var(--muted); font-weight:400; font-style:italic; }
  .pf-label-caption{ font-size:.78rem; color:var(--muted); max-width:220px; }

  /* Image dropzone */
  .pf-dropzone{
    border:1.5px dashed var(--border); border-radius:10px; padding:1.5rem;
    text-align:center; cursor:pointer; transition:border-color .15s, background .15s;
  }
  .pf-dropzone:hover{ border-color:var(--accent); background:var(--accent-tint); }
  .pf-dropzone svg{ color:var(--muted); margin-bottom:.4rem; }
  .pf-dropzone .cta{ font-size:.85rem; font-weight:500; color:var(--accent); }
  .pf-dropzone .hint{ font-size:.75rem; color:var(--muted); margin-top:.15rem; }
  .pf-thumbs{ display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.75rem; }
  .pf-thumbs img{
    width:56px; height:56px; object-fit:cover; border-radius:6px; border:1px solid var(--border);
  }

  /* Electrical specs — schematic-style grouped block */
  .pf-spec-grid{
    display:grid; grid-template-columns:repeat(3, 1fr); gap:1rem;
    background:var(--page); border:1px solid var(--border); border-radius:10px; padding:1.1rem;
  }
  .pf-spec{ position:relative; }
  .pf-spec-glyph{
    display:inline-flex; align-items:center; justify-content:center;
    width:22px; height:22px; border-radius:5px; background:var(--warn-tint); color:var(--warn);
    font-family:'IBM Plex Mono',monospace; font-size:.72rem; font-weight:500; margin-right:.4rem;
  }
  .pf-spec-title{ display:flex; align-items:center; font-size:.82rem; font-weight:500; margin-bottom:.5rem; }
  .pf-spec select{ margin-bottom:.5rem; }
  .pf-spec input:disabled{ background:var(--page); color:var(--muted); }

  .pf-barcode-msg{ color:#B3261E; font-size:.78rem; display:none; margin-top:.35rem; }
  .pf-hint{ font-size:.76rem; color:var(--muted); margin-top:.35rem; }

  .pf-footer{
    padding:1.25rem 1.75rem; border-top:1px solid var(--border);
    display:flex; justify-content:flex-end; gap:.6rem; background:var(--page);
  }
  .btn-pf-primary{
    background:var(--accent); border-color:var(--accent); color:#fff; font-weight:500;
    padding:.55rem 1.4rem; border-radius:8px; font-size:.9rem;
  }
  .btn-pf-primary:hover{ background:#255a3f; border-color:#255a3f; color:#fff; }
  .btn-pf-secondary{
    background:transparent; border:1px solid var(--border); color:var(--ink);
    padding:.55rem 1.2rem; border-radius:8px; font-size:.9rem;
  }

  @media (max-width:768px){
    .pf-spec-grid{ grid-template-columns:1fr; }
    .pf-label-preview{ flex-direction:column; align-items:flex-start; }
  }
</style>
<?php $is_update = isset($_GET['update']) && !empty($product); ?>
<form action="<?php echo $is_update ? '../config/update-product.php' : '../config/add-product.php'; ?>" method="POST" enctype="multipart/form-data">
<?php if ($is_update): ?>
<input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
<?php endif; ?>
<div class="pf-wrap">
  <div class="pf-card">
    <div class="pf-head">
      <h2><?php echo $is_update ? 'Edit product' : 'Add new product'; ?></h2>
      <p>Fields marked with an asterisk build the item name automatically.</p>
    </div>

    <div class="pf-body">

      <div class="pf-label-preview">
        <div class="pf-label-tag">
          <div class="eyebrow">Item name preview</div>
          <div class="name<?php echo empty($product_name) ? ' is-empty' : ''; ?>" id="item_name_display"><?php echo !empty($product_name) ? htmlspecialchars($product_name) : 'Select a category to begin'; ?></div>
        </div>
        <div class="pf-label-caption">This updates live as you choose category, brand, and specs below — it's exactly what prints on the shelf tag.</div>
      </div>
      <small class="pf-barcode-msg" id="duplicate-message" style="display:block;">This item name + brand combination already exists.</small>

      </div>
      <input type="hidden" id="item_name" name="product_description" value="<?php echo isset($product_name) ? htmlspecialchars($product_name) : ''; ?>">


      

      <div class="pf-section">
        <div class="pf-section-label">Classification</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Category *</label>
            <select class="form-select" name="category" id="category" required>
              <option value="">Select category</option>
              <?php
              $category_selection = "SELECT * FROM category ORDER BY category_name ASC";
              $category_result = $conn->query($category_selection);
              if ($category_result->num_rows > 0) {
                  while ($row = $category_result->fetch_assoc()) {
                    if(isset($category_id) && $category_id === $row['hashed_id']){
                        echo '<option value="' . $row['hashed_id'] . '" selected>' . $row['category_name'] . '</option>';

                    } else {
                      echo '<option value="' . $row['hashed_id'] . '">' . $row['category_name'] . '</option>';
                    }
                  }
              } else {
                  echo '<option value="">No category found</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Brand *</label>
            <select class="form-select" name="brand" id="brand" required>
              <?php
              $brand_selection = "SELECT * FROM brand ORDER BY brand_name ASC";
              $brand_result = $conn->query($brand_selection);
              if ($brand_result->num_rows > 0) {
                  while ($row = $brand_result->fetch_assoc()) {
                    if(isset($brand_id) && $brand_id === $row['hashed_id']){
                        echo '<option value="' . $row['hashed_id'] . '" selected>' . $row['brand_name'] . '</option>';
                    } else {
                      echo '<option value="' . $row['hashed_id'] . '">' . $row['brand_name'] . '</option>';
                    }
                  }
              } else {
                  echo '<option value="">No brand found</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Model name/ Other classification</label>
            <input type="text" class="form-control" name="model_name" id="model_name_input" placeholder="e.g. ThinkPad X1 Carbon">
          </div>
        </div>
        <div class="row g-3 mt-0" id="condition-row" style="display:none;">
          <div class="col-md-6">
            <label class="pf-label">Condition *</label>
            <select class="form-select" name="condition" id="condition_select">
              <option value="">Select condition</option>
              <option value="Brand New">Brand New</option>
              <option value="Pre Owned">Pre Owned</option>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-0" id="source-row" style="display:none;">
          <div class="col-md-6">
            <label class="pf-label">Source *</label>
            <select class="form-select" name="source" id="source_select">
              <option value="">Select source</option>
              <option value="Local">Local</option>
              <option value="Import">Import</option>
            </select>
            <div class="pf-hint">"Local" is added to the end of the item name. "Import" isn't included in the name.</div>
          </div>
        </div>
      </div>

      <div class="pf-section" id="processor-generation-section" style="display:none;">
        <div class="pf-section-label">Processor generation</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Generation</label>
            <select class="form-select" name="processor_generation" id="processor_generation_select">
              <option value="">Select generation</option>
              <?php
                ensureAndSeedLookupTable($conn, 'processor_generation', 'generation_value', [
                    '10th Gen (Intel)', '11th Gen (Intel)', '12th Gen (Intel)', '13th Gen (Intel)', '14th Gen (Intel)',
                    'Core Ultra Series 1 (Intel)', 'Core Ultra Series 2 (Intel)',
                    'Ryzen 3000 Series (AMD)', 'Ryzen 4000 Series (AMD)', 'Ryzen 5000 Series (AMD)',
                    'Ryzen 6000 Series (AMD)', 'Ryzen 7000 Series (AMD)', 'Ryzen 8000 Series (AMD)', 'Ryzen 9000 Series (AMD)',
                ]);
                $gen_res = $conn->query("SELECT generation_value AS value FROM processor_generation ORDER BY id ASC");
                if ($gen_res && $gen_res->num_rows > 0) {
                    while ($row = $gen_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="processor_generation_input"
                   name="processor_generation_custom" placeholder="Type generation" disabled />
          </div>
        </div>
        <div class="pf-hint">Shown for Desktop and Laptop only. Added to the item name right after the processor/CPU.</div>
      </div>

      <div class="pf-section" id="electrical-spec-section">
        <div class="pf-section-label">Electrical specifications</div>
        <div class="pf-spec-grid">
          <div class="pf-spec" id="volt_spec_wrap">
            <div class="pf-spec-title"><span class="pf-spec-glyph">V</span>Voltage</div>
            <select class="form-select" name="volt" id="volt_select">
                <option value="">Select voltage</option>
                <?php 
                    $volt_query = "SELECT * FROM voltage ORDER BY volt_value ASC";
                    $volt_res = $conn->query($volt_query);
                    if($volt_res->num_rows>0){
                        while($row=$volt_res->fetch_assoc()){
                            $volt_value = $row['volt_value'];
                            echo '<option value="' . $volt_value . '">' . $volt_value . '</option>';
                        }
                    }
                ?>
                <option value="">Not available</option>
                <option value="new">New value</option>
            </select>
            <input type="text" name="volt" id="volt_input" class="form-control" placeholder="e.g. 12v" disabled>
          </div>
          <div class="pf-spec" id="amp_spec_wrap">
            <div class="pf-spec-title"><span class="pf-spec-glyph">A</span>Amperage</div>
            <select class="form-select" name="amp" id="amp_select">
                <option value="">Select amperage</option>
                <?php
                    $amp_query = "
                        SELECT * FROM amperage ORDER BY amp_value ASC
                    ";
                    $amp_res = $conn->query($amp_query);
                    if($amp_res->num_rows>0){
                        while($row=$amp_res->fetch_assoc()){
                            $amperage = $row['amp_value'];
                            echo '<option value="' . $amperage . '">' . $amperage . '</option>';
                        }
                    }
                ?>
                <option value="">Not available</option>
                <option value="new">New value</option>
            </select>
            <input type="text" name="amp" id="amp_input" class="form-control" placeholder="e.g. 2a" disabled>
          </div>
          <div class="pf-spec" id="pin_spec_wrap">
            <div class="pf-spec-title"><span class="pf-spec-glyph">P</span>Pin size</div>
            <select class="form-select" name="pin" id="pin_select">
                <option value="">Select pin size</option>
                <?php 
                    $pin_query = "SELECT * FROM pin ORDER BY pin_size_value ASC";
                    $pin_res = $conn->query($pin_query);
                    if($pin_res->num_rows>0){
                        while($row=$pin_res->fetch_assoc()){
                            $pin = $row['pin_size_value'];
                            echo '<option value="' . $pin . '">' . $pin . '</option>';
                        }
                    }
                ?>
                <option value="">Not available</option>
                <option value="new">New value</option>
            </select>
            <input type="text" name="pin" id="pin_input" class="form-control" placeholder="e.g. 5.5mm" disabled>
          </div>
        </div>
      </div>

      <div class="pf-section" id="desktop-spec-section" style="display:none;">
        <div class="pf-section-label">Desktop specifications</div>
        <div class="row g-3">
          <?php
            $desktop_spec_fields = [
                'processor'    => ['label' => 'Processor',    'categories' => ['Processor']],
                'memory'       => ['label' => 'Memory (RAM)', 'categories' => ['Memory']],
                'hdd'          => ['label' => 'HDD',          'categories' => ['HDD', 'STORAGE']],
                'ssd'          => ['label' => 'SSD',          'categories' => ['SSD', 'STORAGE']],
                'videocard'    => ['label' => 'Videocard',    'categories' => ['Videocard']],
                'motherboard'  => ['label' => 'Mother Board', 'categories' => ['Mother Board']],
                'psu'          => ['label' => 'PSU',          'categories' => ['PSU', 'Power Supply']],
            ];

            foreach ($desktop_spec_fields as $field_key => $field) {
                $placeholders = implode(',', array_fill(0, count($field['categories']), '?'));
                $types = str_repeat('s', count($field['categories']));

                $spec_stmt = $conn->prepare("
                    SELECT p.hashed_id, p.description
                    FROM product p
                    INNER JOIN category c ON c.hashed_id = p.category
                    WHERE c.category_name IN ($placeholders)
                    ORDER BY p.description ASC
                ");
                $spec_stmt->bind_param($types, ...$field['categories']);
                $spec_stmt->execute();
                $spec_result = $spec_stmt->get_result();
          ?>
          <div class="col-md-6">
            <label class="pf-label"><?php echo htmlspecialchars($field['label']); ?></label>
            <select class="form-select desktop-spec-select" name="desktop_<?php echo $field_key; ?>" id="desktop_<?php echo $field_key; ?>_select">
              <option value="">Select <?php echo htmlspecialchars($field['label']); ?></option>
              <?php
                if ($spec_result->num_rows > 0) {
                    while ($row = $spec_result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['hashed_id']) . '" data-description="' . htmlspecialchars($row['description']) . '">' . htmlspecialchars($row['description']) . '</option>';
                    }
                } else {
                    echo '<option value="">No ' . htmlspecialchars($field['label']) . ' products found</option>';
                }
              ?>
              <option value="not_available">Not available</option>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input
              type="text"
              class="form-control mt-2 d-none"
              id="desktop_<?php echo $field_key; ?>_input"
              name="desktop_<?php echo $field_key; ?>_custom"
              placeholder="Type <?php echo htmlspecialchars($field['label']); ?>"
              disabled
            />
          </div>
          <?php
                $spec_stmt->close();
            }
          ?>
        </div>
        <div class="pf-hint">These pull from products you've already added under Processor, Memory, HDD, SSD, Videocard, Mother Board, and PSU categories. Pick "New / not listed" to type a value that won't be saved as its own product but will still appear in the item name.</div>
      </div>

      <div class="pf-section" id="laptop-spec-section" style="display:none;">
        <div class="pf-section-label">Laptop specifications</div>
        <div class="row g-3">
          <?php
            function ensureAndSeedLookupTable($conn, $tableName, $columnName, array $defaults) {
                $createSql = "CREATE TABLE IF NOT EXISTS `$tableName` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `$columnName` VARCHAR(100) NULL
                )";
                $conn->query($createSql);

                $count = 0;
                $countResult = $conn->query("SELECT COUNT(*) AS cnt FROM `$tableName`");
                if ($countResult) {
                    $countRow = $countResult->fetch_assoc();
                    $count = (int)($countRow['cnt'] ?? 0);
                }

                if ($count === 0 && !empty($defaults)) {
                    $insertStmt = $conn->prepare("INSERT INTO `$tableName` (`$columnName`) VALUES (?)");
                    if ($insertStmt) {
                        foreach ($defaults as $defaultValue) {
                            $insertStmt->bind_param('s', $defaultValue);
                            $insertStmt->execute();
                        }
                        $insertStmt->close();
                    }
                }
            }

            $laptop_spec_fields = [
                'cpu' => [
                    'label' => 'Processor (CPU)',
                    'table' => 'laptop_cpu',
                    'column' => 'cpu_value',
                    'defaults' => [
                        'Intel Celeron', 'Intel Pentium',
                        'Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9',
                        'AMD Athlon', 'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9',
                        'Apple M1', 'Apple M2', 'Apple M3', 'Apple M4',
                    ],
                ],
                'ram' => [
                    'label' => 'RAM',
                    'table' => 'laptop_ram',
                    'column' => 'ram_value',
                    'defaults' => ['4GB', '8GB', '12GB', '16GB', '24GB', '32GB', '64GB'],
                ],
                'gpu' => [
                    'label' => 'GPU / iGPU',
                    'table' => 'laptop_gpu',
                    'column' => 'gpu_value',
                    'defaults' => [
                        'Intel UHD Graphics', 'Intel Iris Xe Graphics', 'Intel Arc Graphics',
                        'AMD Radeon Graphics (Integrated)',
                        'NVIDIA GeForce MX550', 'NVIDIA GeForce MX570',
                        'NVIDIA GeForce RTX 3050', 'NVIDIA GeForce RTX 4050',
                        'NVIDIA GeForce RTX 4060', 'NVIDIA GeForce RTX 4070', 'NVIDIA GeForce RTX 4080',
                        'AMD Radeon RX 7600M', 'Apple Integrated GPU',
                    ],
                ],
                'screen_size' => [
                    'label' => 'Screen Size',
                    'table' => 'laptop_screen_size',
                    'column' => 'screen_size_value',
                    'defaults' => ['11.6"', '13.3"', '14"', '15.6"', '16"', '17.3"'],
                ],
                'color' => [
                    'label' => 'Color',
                    'table' => 'laptop_color',
                    'column' => 'color_value',
                    'defaults' => ['Black', 'Silver', 'Space Gray', 'White', 'Blue', 'Gold'],
                ],
            ];

            foreach ($laptop_spec_fields as $field_key => $field) {
                ensureAndSeedLookupTable($conn, $field['table'], $field['column'], $field['defaults']);
                $lookup_res = $conn->query("SELECT `{$field['column']}` AS value FROM `{$field['table']}` ORDER BY `{$field['column']}` ASC");
          ?>
          <div class="col-md-6">
            <label class="pf-label"><?php echo htmlspecialchars($field['label']); ?></label>
            <select class="form-select laptop-spec-select" name="laptop_<?php echo $field_key; ?>" id="laptop_<?php echo $field_key; ?>_select">
              <option value="">Select <?php echo htmlspecialchars($field['label']); ?></option>
              <?php
                if ($lookup_res && $lookup_res->num_rows > 0) {
                    while ($row = $lookup_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input
              type="text"
              class="form-control mt-2 d-none"
              id="laptop_<?php echo $field_key; ?>_input"
              name="laptop_<?php echo $field_key; ?>_custom"
              placeholder="Type <?php echo htmlspecialchars($field['label']); ?>"
              disabled
            />
          </div>
          <?php } ?>
          <?php
            $laptop_storage_fields = [
                'hdd' => ['label' => 'HDD', 'categories' => ['HDD', 'STORAGE']],
                'ssd' => ['label' => 'SSD', 'categories' => ['SSD', 'STORAGE']],
            ];

            foreach ($laptop_storage_fields as $field_key => $field) {
                $placeholders = implode(',', array_fill(0, count($field['categories']), '?'));
                $types = str_repeat('s', count($field['categories']));

                $storage_stmt = $conn->prepare("
                    SELECT p.hashed_id, p.description
                    FROM product p
                    INNER JOIN category c ON c.hashed_id = p.category
                    WHERE c.category_name IN ($placeholders)
                    ORDER BY p.description ASC
                ");
                $storage_stmt->bind_param($types, ...$field['categories']);
                $storage_stmt->execute();
                $storage_result = $storage_stmt->get_result();
          ?>
          <div class="col-md-6">
            <label class="pf-label"><?php echo htmlspecialchars($field['label']); ?></label>
            <select class="form-select laptop-spec-select" name="laptop_<?php echo $field_key; ?>" id="laptop_<?php echo $field_key; ?>_select">
              <option value="">Select <?php echo htmlspecialchars($field['label']); ?></option>
              <?php
                if ($storage_result->num_rows > 0) {
                    while ($row = $storage_result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['hashed_id']) . '" data-description="' . htmlspecialchars($row['description']) . '">' . htmlspecialchars($row['description']) . '</option>';
                    }
                } else {
                    echo '<option value="">No ' . htmlspecialchars($field['label']) . ' products found</option>';
                }
              ?>
              <option value="not_available">Not available</option>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input
              type="text"
              class="form-control mt-2 d-none"
              id="laptop_<?php echo $field_key; ?>_input"
              name="laptop_<?php echo $field_key; ?>_custom"
              placeholder="Type <?php echo htmlspecialchars($field['label']); ?>"
              disabled
            />
          </div>
          <?php
                $storage_stmt->close();
            }
          ?>
        </div>
        <div class="pf-hint">CPU/RAM/GPU/Screen size/Color are typed &amp; saved for reuse. HDD/SSD pull from products you've already added under those categories, same as Desktop &mdash; pick "New / not listed" to type one that won't be saved as its own product.</div>
      </div>

      <div class="pf-section" id="laptop-battery-spec-section" style="display:none;">
        <div class="pf-section-label">Laptop battery specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Voltage</label>
            <select class="form-select" name="battery_voltage" id="battery_voltage_select">
              <option value="">Select voltage</option>
              <?php
                ensureAndSeedLookupTable($conn, 'battery_voltage', 'voltage_value', [
                    '3.7V', '7.2V', '7.4V', '10.8V', '11.1V', '14.4V', '14.8V',
                ]);
                $bv_res = $conn->query("SELECT voltage_value AS value FROM battery_voltage ORDER BY id ASC");
                if ($bv_res && $bv_res->num_rows > 0) {
                    while ($row = $bv_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="battery_voltage_input" name="battery_voltage_custom" placeholder="Type voltage" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Capacity (mAh)</label>
            <select class="form-select" name="battery_mah" id="battery_mah_select">
              <option value="">Select capacity</option>
              <?php
                ensureAndSeedLookupTable($conn, 'battery_mah', 'mah_value', [
                    '2200mAh', '2600mAh', '3000mAh', '3500mAh', '3800mAh', '4000mAh', '4400mAh', '5000mAh', '6000mAh',
                ]);
                $bm_res = $conn->query("SELECT mah_value AS value FROM battery_mah ORDER BY id ASC");
                if ($bm_res && $bm_res->num_rows > 0) {
                    while ($row = $bm_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="battery_mah_input" name="battery_mah_custom" placeholder="Type capacity" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Type</label>
            <select class="form-select" name="battery_type" id="battery_type_select">
              <option value="">Select type</option>
              <option value="Internal">Internal</option>
              <option value="External">External</option>
            </select>
          </div>
        </div>
      </div>

      <div class="pf-section" id="laptop-keyboard-spec-section" style="display:none;">
        <div class="pf-section-label">Laptop keyboard specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Panel type</label>
            <select class="form-select" name="keyboard_panel_type" id="keyboard_panel_type_select">
              <option value="">Select panel type</option>
              <?php
                ensureAndSeedLookupTable($conn, 'keyboard_panel_type', 'panel_type_value', [
                    'Standard (Non-backlit)', 'Backlit (White)', 'Backlit (RGB)',
                ]);
                $kp_res = $conn->query("SELECT panel_type_value AS value FROM keyboard_panel_type ORDER BY id ASC");
                if ($kp_res && $kp_res->num_rows > 0) {
                    while ($row = $kp_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="keyboard_panel_type_input" name="keyboard_panel_type_custom" placeholder="Type panel type" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Color</label>
            <select class="form-select" name="keyboard_color" id="keyboard_color_select">
              <option value="">Select color</option>
              <?php
                $kc_res = $conn->query("SELECT color_value AS value FROM laptop_color ORDER BY id ASC");
                if ($kc_res && $kc_res->num_rows > 0) {
                    while ($row = $kc_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="keyboard_color_input" name="keyboard_color_custom" placeholder="Type color" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Layout</label>
            <select class="form-select" name="keyboard_layout" id="keyboard_layout_select">
              <option value="">Select layout</option>
              <?php
                ensureAndSeedLookupTable($conn, 'keyboard_layout', 'layout_value', [
                    'US Layout', 'UK Layout', 'JP Layout', 'Arabic Layout', 'Spanish Layout', 'French Layout', 'German Layout',
                ]);
                $kl_res = $conn->query("SELECT layout_value AS value FROM keyboard_layout ORDER BY id ASC");
                if ($kl_res && $kl_res->num_rows > 0) {
                    while ($row = $kl_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="keyboard_layout_input" name="keyboard_layout_custom" placeholder="Type layout" disabled />
          </div>
        </div>
        <div class="pf-hint">Color pulls from the same seeded list used for Laptop color.</div>
      </div>

      <div class="pf-section" id="lcd-spec-section" style="display:none;">
        <div class="pf-section-label">LCD specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Screen size</label>
            <select class="form-select" name="lcd_screen_size" id="lcd_screen_size_select">
              <option value="">Select screen size</option>
              <?php
                $ls_res = $conn->query("SELECT screen_size_value AS value FROM laptop_screen_size ORDER BY id ASC");
                if ($ls_res && $ls_res->num_rows > 0) {
                    while ($row = $ls_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="lcd_screen_size_input" name="lcd_screen_size_custom" placeholder="Type screen size" disabled />
          </div>
        </div>
        <div class="pf-hint">Pulls from the same seeded list used for Laptop screen size.</div>
      </div>

      <div class="pf-section" id="cp-battery-spec-section" style="display:none;">
        <div class="pf-section-label">CP battery specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Code / keyword</label>
            <input type="text" class="form-control" name="cp_battery_code" id="cp_battery_code_input" placeholder="e.g. CMOS-CR2032">
          </div>
          <div class="col-md-6">
            <label class="pf-label">Voltage</label>
            <select class="form-select" name="cp_battery_voltage" id="cp_battery_voltage_select">
              <option value="">Select voltage</option>
              <?php
                $cpv_res = $conn->query("SELECT voltage_value AS value FROM battery_voltage ORDER BY id ASC");
                if ($cpv_res && $cpv_res->num_rows > 0) {
                    while ($row = $cpv_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="cp_battery_voltage_input" name="cp_battery_voltage_custom" placeholder="Type voltage" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Capacity (mAh)</label>
            <select class="form-select" name="cp_battery_mah" id="cp_battery_mah_select">
              <option value="">Select capacity</option>
              <?php
                $cpm_res = $conn->query("SELECT mah_value AS value FROM battery_mah ORDER BY id ASC");
                if ($cpm_res && $cpm_res->num_rows > 0) {
                    while ($row = $cpm_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="cp_battery_mah_input" name="cp_battery_mah_custom" placeholder="Type capacity" disabled />
          </div>
        </div>
        <div class="pf-hint">Voltage and capacity share the same seeded lists used for Laptop battery.</div>
      </div>

      <div class="pf-section" id="powercord-spec-section" style="display:none;">
        <div class="pf-section-label">Power cord specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Type</label>
            <select class="form-select" name="powercord_type" id="powercord_type_select">
              <option value="">Select type</option>
              <option value="2 Prongs">2 Prongs</option>
              <option value="3 Prongs">3 Prongs</option>
              <option value="Monitor Cord">Monitor Cord</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Cable length</label>
            <select class="form-select" name="powercord_length" id="powercord_length_select">
              <option value="">Select length</option>
              <?php
                ensureAndSeedLookupTable($conn, 'cable_length', 'length_value', [
                    '0.5m', '1m', '1.5m', '2m', '3m', '5m', '10m',
                ]);
                $cl_res = $conn->query("SELECT length_value AS value FROM cable_length ORDER BY id ASC");
                if ($cl_res && $cl_res->num_rows > 0) {
                    while ($row = $cl_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="powercord_length_input" name="powercord_length_custom" placeholder="Type cable length" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Voltage</label>
            <select class="form-select" name="powercord_voltage" id="powercord_voltage_select">
              <option value="">Select voltage</option>
              <?php
                $pcv_res = $conn->query("SELECT volt_value AS value FROM voltage ORDER BY volt_value ASC");
                if ($pcv_res && $pcv_res->num_rows > 0) {
                    while ($row = $pcv_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New value</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="powercord_voltage_input" name="powercord_voltage_custom" placeholder="e.g. 12v" disabled>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Amperage</label>
            <select class="form-select" name="powercord_amperage" id="powercord_amperage_select">
              <option value="">Select amperage</option>
              <?php
                $pca_res = $conn->query("SELECT amp_value AS value FROM amperage ORDER BY amp_value ASC");
                if ($pca_res && $pca_res->num_rows > 0) {
                    while ($row = $pca_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New value</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="powercord_amperage_input" name="powercord_amperage_custom" placeholder="e.g. 2a" disabled>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Compatibility</label>
            <input type="text" class="form-control" name="powercord_compatibility" id="powercord_compatibility_input" placeholder="e.g. Dell / HP / Lenovo laptops">
          </div>
        </div>
        <div class="pf-hint">Voltage and amperage share the same seeded lists used for Electrical specifications.</div>
      </div>

      <div class="pf-section" id="power-socket-spec-section" style="display:none;">
        <div class="pf-section-label">Power socket specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Watts</label>
            <select class="form-select" name="socket_watts" id="socket_watts_select">
              <option value="">Select watts</option>
              <?php
                ensureAndSeedLookupTable($conn, 'watts', 'watts_value', [
                    '45W', '65W', '90W', '120W', '150W', '180W', '230W', '280W', '330W',
                ]);
                $sw_res = $conn->query("SELECT watts_value AS value FROM watts ORDER BY id ASC");
                if ($sw_res && $sw_res->num_rows > 0) {
                    while ($row = $sw_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="socket_watts_input" name="socket_watts_custom" placeholder="Type watts" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Amperage</label>
            <select class="form-select" name="socket_amperage" id="socket_amperage_select">
              <option value="">Select amperage</option>
              <?php
                $sa_res = $conn->query("SELECT amp_value AS value FROM amperage ORDER BY amp_value ASC");
                if ($sa_res && $sa_res->num_rows > 0) {
                    while ($row = $sa_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New value</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="socket_amperage_input" name="socket_amperage_custom" placeholder="e.g. 2a" disabled>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Voltage</label>
            <select class="form-select" name="socket_voltage" id="socket_voltage_select">
              <option value="">Select voltage</option>
              <?php
                $sv_res = $conn->query("SELECT volt_value AS value FROM voltage ORDER BY volt_value ASC");
                if ($sv_res && $sv_res->num_rows > 0) {
                    while ($row = $sv_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New value</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="socket_voltage_input" name="socket_voltage_custom" placeholder="e.g. 12v" disabled>
          </div>
        </div>
        <div class="pf-hint">Amperage and voltage share the same seeded lists used for Electrical specifications.</div>
      </div>

      <div class="pf-section" id="wallmount-spec-section" style="display:none;">
        <div class="pf-section-label">Wall mount specifications</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Item type</label>
            <select class="form-select" name="wallmount_item_type" id="wallmount_item_type_select">
              <option value="">Select item type</option>
              <?php
                ensureAndSeedLookupTable($conn, 'wallmount_item_type', 'item_type_value', [
                    'Fixed', 'Tilting', 'Full-Motion', 'Ceiling Mount',
                ]);
                $wit_res = $conn->query("SELECT item_type_value AS value FROM wallmount_item_type ORDER BY id ASC");
                if ($wit_res && $wit_res->num_rows > 0) {
                    while ($row = $wit_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="wallmount_item_type_input" name="wallmount_item_type_custom" placeholder="Type item type" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">VESA</label>
            <select class="form-select" name="wallmount_vesa" id="wallmount_vesa_select">
              <option value="">Select VESA</option>
              <?php
                ensureAndSeedLookupTable($conn, 'vesa', 'vesa_value', [
                    '75x75mm', '100x100mm', '200x100mm', '200x200mm', '300x300mm', '400x400mm', '600x400mm',
                ]);
                $vesa_res = $conn->query("SELECT vesa_value AS value FROM vesa ORDER BY id ASC");
                if ($vesa_res && $vesa_res->num_rows > 0) {
                    while ($row = $vesa_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="wallmount_vesa_input" name="wallmount_vesa_custom" placeholder="Type VESA pattern" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Suitable size</label>
            <select class="form-select" name="wallmount_suitable_size" id="wallmount_suitable_size_select">
              <option value="">Select suitable size</option>
              <?php
                ensureAndSeedLookupTable($conn, 'wallmount_suitable_size', 'size_range_value', [
                    '13"-27"', '32"-55"', '55"-80"', '80"-100"',
                ]);
                $wss_res = $conn->query("SELECT size_range_value AS value FROM wallmount_suitable_size ORDER BY id ASC");
                if ($wss_res && $wss_res->num_rows > 0) {
                    while ($row = $wss_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="wallmount_suitable_size_input" name="wallmount_suitable_size_custom" placeholder="Type suitable size" disabled />
          </div>
          <div class="col-md-6">
            <label class="pf-label">Load capacity</label>
            <select class="form-select" name="wallmount_load_capacity" id="wallmount_load_capacity_select">
              <option value="">Select load capacity</option>
              <?php
                ensureAndSeedLookupTable($conn, 'load_capacity', 'capacity_value', [
                    '8kg', '15kg', '25kg', '35kg', '50kg', '75kg', '100kg',
                ]);
                $lc_res = $conn->query("SELECT capacity_value AS value FROM load_capacity ORDER BY id ASC");
                if ($lc_res && $lc_res->num_rows > 0) {
                    while ($row = $lc_res->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['value']) . '</option>';
                    }
                }
              ?>
              <option value="new">New / not listed&hellip;</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="wallmount_load_capacity_input" name="wallmount_load_capacity_custom" placeholder="Type load capacity" disabled />
          </div>
        </div>
      </div>

      <div class="pf-section">
        <div class="pf-section-label">Product images</div>
        <div class="pf-dropzone" id="pf_dropzone">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M12 15V4M12 4L8 8M12 4l4 4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="cta">Click to choose images</div>
          <div class="hint">Up to 10 images</div>
        </div>
        <input type="file" id="product_image" name="product_image[]" accept="image/*" multiple class="d-none">
        <div class="pf-thumbs" id="pf_thumbs">
          <?php
            if ($is_update && !empty($product['product_img'])) {
                $existing_images = @unserialize($product['product_img']);
                if (is_array($existing_images)) {
                    foreach ($existing_images as $b64) {
                        echo '<img src="data:image/*;base64,' . htmlspecialchars($b64) . '" alt="">';
                    }
                }
            }
          ?>
        </div>
        <?php if ($is_update): ?>
          <div class="pf-hint">These are the current images. Choosing new files above will replace all of them.</div>
        <?php endif; ?>
      </div>

      <div class="pf-section">
        <div class="pf-section-label">Inventory identification</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Parent barcode</label>
            <input type="text" class="form-control" id="parent_barcode" name="parent_barcode"
              placeholder="Enter barcode" oninput="this.value = this.value.replace(/\s/g, '')"
              value="<?php echo isset($parent_barcode) ? htmlspecialchars($parent_barcode) : ''; ?>"
              <?php echo $is_update ? 'readonly' : ''; ?>>
            <small class="pf-barcode-msg" id="barcode-message">This barcode already exists.</small>
            <?php if ($is_update): ?><div class="pf-hint">Barcode is fixed once a product is created.</div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Minimum stock</label>
            <input type="number" min="2" max="1000" name="safety" class="form-control" required placeholder="e.g. 10"
              value="<?php echo isset($minimum_stock) ? htmlspecialchars($minimum_stock) : ''; ?>">
            <div class="pf-hint">Reorder alert triggers below this quantity.</div>
          </div>
        </div>
      </div>

    </div>

    <div class="pf-footer">
      <button type="button" class="btn-pf-secondary">Cancel</button>
      <button type="submit" class="btn-pf-primary" id="submit-btn"><?php echo $is_update ? 'Update product' : 'Save product'; ?></button>
    </div>
  </div>
</div>
</form>


<script>
(function () {
  const categorySelect = document.getElementById('category');
  const itemNameField = document.getElementById('item_name');
  const itemNameDisplay = document.getElementById('item_name_display');
  const brandSelect = document.getElementById('brand');
  const duplicateMsg = document.getElementById('duplicate-message');
  const submitBtn = document.getElementById('submit-btn');

  const fields = ['volt', 'amp', 'pin'].map(prefix => ({
    key: prefix,
    select: document.getElementById(prefix + '_select'),
    input: document.getElementById(prefix + '_input'),
    wrap: document.getElementById(prefix + '_spec_wrap')
  }));

  fields.forEach(({ select, input }) => {
    select.addEventListener('change', () => {
      if (select.value === 'new') {
        input.disabled = false;
        input.focus();
      } else {
        input.disabled = true;
        input.value = '';
      }
      updateItemName();
    });
    input.addEventListener('input', updateItemName);
  });

  const electricalSection = document.getElementById('electrical-spec-section');
  const desktopSection = document.getElementById('desktop-spec-section');
  const laptopSection = document.getElementById('laptop-spec-section');

  // Shared show/hide-on-'new' wiring used by every "pick existing product
  // or type your own" style select (Desktop specs, Laptop HDD/SSD).
  function wireToggleField({ select, input }) {
    select.addEventListener('change', () => {
      if (select.value === 'new') {
        input.classList.remove('d-none');
        input.disabled = false;
        input.focus();
      } else {
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      }
      updateItemName();
    });
    input.addEventListener('input', updateItemName);
  }

  const desktopFieldKeys = ['processor', 'memory', 'hdd', 'ssd', 'videocard', 'motherboard', 'psu'];
  const desktopFields = desktopFieldKeys.map(key => ({
    key,
    select: document.getElementById('desktop_' + key + '_select'),
    input: document.getElementById('desktop_' + key + '_input')
  }));
  desktopFields.forEach(wireToggleField);

  const laptopSpecFieldKeys = ['cpu', 'ram', 'gpu', 'screen_size', 'color'];
  const laptopSpecFields = laptopSpecFieldKeys.map(key => ({
    key,
    select: document.getElementById('laptop_' + key + '_select'),
    input: document.getElementById('laptop_' + key + '_input')
  }));
  laptopSpecFields.forEach(wireToggleField);

  const laptopStorageFieldKeys = ['hdd', 'ssd'];
  const laptopStorageFields = laptopStorageFieldKeys.map(key => ({
    key,
    select: document.getElementById('laptop_' + key + '_select'),
    input: document.getElementById('laptop_' + key + '_input')
  }));
  laptopStorageFields.forEach(wireToggleField);

  const processorGenSection = document.getElementById('processor-generation-section');
  const processorGenField = {
    select: document.getElementById('processor_generation_select'),
    input: document.getElementById('processor_generation_input')
  };
  wireToggleField(processorGenField);

  const laptopBatterySection = document.getElementById('laptop-battery-spec-section');
  const laptopBatteryFields = {
    voltage: { select: document.getElementById('battery_voltage_select'), input: document.getElementById('battery_voltage_input') },
    mah: { select: document.getElementById('battery_mah_select'), input: document.getElementById('battery_mah_input') }
  };
  Object.values(laptopBatteryFields).forEach(wireToggleField);
  const batteryTypeSelect = document.getElementById('battery_type_select');
  batteryTypeSelect.addEventListener('change', updateItemName);

  const laptopKeyboardSection = document.getElementById('laptop-keyboard-spec-section');
  const laptopKeyboardFields = {
    panelType: { select: document.getElementById('keyboard_panel_type_select'), input: document.getElementById('keyboard_panel_type_input') },
    color: { select: document.getElementById('keyboard_color_select'), input: document.getElementById('keyboard_color_input') },
    layout: { select: document.getElementById('keyboard_layout_select'), input: document.getElementById('keyboard_layout_input') }
  };
  Object.values(laptopKeyboardFields).forEach(wireToggleField);

  const lcdSection = document.getElementById('lcd-spec-section');
  const lcdFields = {
    screenSize: { select: document.getElementById('lcd_screen_size_select'), input: document.getElementById('lcd_screen_size_input') }
  };
  Object.values(lcdFields).forEach(wireToggleField);

  const cpBatterySection = document.getElementById('cp-battery-spec-section');
  const cpBatteryCodeInput = document.getElementById('cp_battery_code_input');
  cpBatteryCodeInput.addEventListener('input', updateItemName);
  const cpBatteryFields = {
    voltage: { select: document.getElementById('cp_battery_voltage_select'), input: document.getElementById('cp_battery_voltage_input') },
    mah: { select: document.getElementById('cp_battery_mah_select'), input: document.getElementById('cp_battery_mah_input') }
  };
  Object.values(cpBatteryFields).forEach(wireToggleField);

  const powercordSection = document.getElementById('powercord-spec-section');
  const powercordTypeSelect = document.getElementById('powercord_type_select');
  powercordTypeSelect.addEventListener('change', updateItemName);
  const powercordCompatibilityInput = document.getElementById('powercord_compatibility_input');
  powercordCompatibilityInput.addEventListener('input', updateItemName);
  const powercordFields = {
    length: { select: document.getElementById('powercord_length_select'), input: document.getElementById('powercord_length_input') },
    voltage: { select: document.getElementById('powercord_voltage_select'), input: document.getElementById('powercord_voltage_input') },
    amperage: { select: document.getElementById('powercord_amperage_select'), input: document.getElementById('powercord_amperage_input') }
  };
  Object.values(powercordFields).forEach(wireToggleField);

  const powerSocketSection = document.getElementById('power-socket-spec-section');
  const powerSocketFields = {
    watts: { select: document.getElementById('socket_watts_select'), input: document.getElementById('socket_watts_input') },
    amperage: { select: document.getElementById('socket_amperage_select'), input: document.getElementById('socket_amperage_input') },
    voltage: { select: document.getElementById('socket_voltage_select'), input: document.getElementById('socket_voltage_input') }
  };
  Object.values(powerSocketFields).forEach(wireToggleField);

  const wallmountSection = document.getElementById('wallmount-spec-section');
  const wallmountFields = {
    itemType: { select: document.getElementById('wallmount_item_type_select'), input: document.getElementById('wallmount_item_type_input') },
    vesa: { select: document.getElementById('wallmount_vesa_select'), input: document.getElementById('wallmount_vesa_input') },
    suitableSize: { select: document.getElementById('wallmount_suitable_size_select'), input: document.getElementById('wallmount_suitable_size_input') },
    loadCapacity: { select: document.getElementById('wallmount_load_capacity_select'), input: document.getElementById('wallmount_load_capacity_input') }
  };
  Object.values(wallmountFields).forEach(wireToggleField);

  function categoryTextLower() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase();
  }

  function isDesktopCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'desktop';
  }

  function isLaptopCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'laptop';
  }

  function isLaptopBatteryCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'laptop battery';
  }

  function isLaptopKeyboardCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'laptop keyboard';
  }

  function isLcdCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'lcd';
  }

  function isCpBatteryCategory() {
    // Handles both "CP Battery" and the "Cellphone Battery" name seen in the
    // live category list -- same spec fields either way.
    const text = categoryTextLower();
    return text === 'cp battery' || text === 'cellphone battery';
  }

  function isPowercordCategory() {
    return categoryTextLower() === 'power cord';
  }

  function isPowerSocketCategory() {
    return categoryTextLower() === 'power socket';
  }

  function isWallmountCategory() {
    return categoryTextLower() === 'wall mount';
  }

  // Categories where Voltage / Amperage / Pin size actually apply, and which
  // subset applies. Anything not listed here (and not one of the dedicated
  // sections above) hides the whole Electrical specifications block.
  const ELECTRICAL_FIELDS_BY_CATEGORY = {
    'charger': ['volt', 'amp', 'pin'],
    'laptop charger': ['volt', 'amp', 'pin'],
    'cord': ['volt', 'amp', 'pin'],
    'power supply': ['volt', 'amp'],
    'psu': ['volt', 'amp'],
    'ups': ['volt', 'amp']
  };

  function getApplicableElectricalFields() {
    return ELECTRICAL_FIELDS_BY_CATEGORY[categoryTextLower()] || [];
  }

  const conditionRow = document.getElementById('condition-row');
  const conditionSelect = document.getElementById('condition_select');

  function needsCondition() {
    return isDesktopCategory() || isLaptopCategory();
  }

  function toggleConditionRow() {
    const show = needsCondition();
    conditionRow.style.display = show ? '' : 'none';
    conditionSelect.required = show;
    if (!show) {
      conditionSelect.value = '';
    }
  }

  conditionSelect.addEventListener('change', updateItemName);

  function isLaptopChargerCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'laptop charger';
  }

  const sourceRow = document.getElementById('source-row');
  const sourceSelect = document.getElementById('source_select');

  function toggleSourceRow() {
    const show = isLaptopChargerCategory();
    sourceRow.style.display = show ? '' : 'none';
    sourceSelect.required = show;
    if (!show) {
      sourceSelect.value = '';
    }
  }

  sourceSelect.addEventListener('change', updateItemName);

  function toggleSpecSections() {
    const desktop = isDesktopCategory();
    const laptop = isLaptopCategory();
    const laptopBattery = isLaptopBatteryCategory();
    const laptopKeyboard = isLaptopKeyboardCategory();
    const lcd = isLcdCategory();
    const cpBattery = isCpBatteryCategory();
    const powercord = isPowercordCategory();
    const powerSocket = isPowerSocketCategory();
    const wallmount = isWallmountCategory();
    const anySpecial = desktop || laptop || laptopBattery || laptopKeyboard || lcd || cpBattery || powercord || powerSocket || wallmount;
    const applicableElectrical = anySpecial ? [] : getApplicableElectricalFields();

    electricalSection.style.display = applicableElectrical.length > 0 ? '' : 'none';
    desktopSection.style.display = desktop ? '' : 'none';
    laptopSection.style.display = laptop ? '' : 'none';
    processorGenSection.style.display = (desktop || laptop) ? '' : 'none';
    laptopBatterySection.style.display = laptopBattery ? '' : 'none';
    laptopKeyboardSection.style.display = laptopKeyboard ? '' : 'none';
    lcdSection.style.display = lcd ? '' : 'none';
    cpBatterySection.style.display = cpBattery ? '' : 'none';
    powercordSection.style.display = powercord ? '' : 'none';
    powerSocketSection.style.display = powerSocket ? '' : 'none';
    wallmountSection.style.display = wallmount ? '' : 'none';

    if (!desktop && !laptop) {
      processorGenField.select.value = '';
      processorGenField.input.classList.add('d-none');
      processorGenField.input.disabled = true;
      processorGenField.input.value = '';
    }

    // Show/hide and clear each Voltage/Amperage/Pin size field individually,
    // based on which of them actually apply to the selected category.
    fields.forEach(({ key, select, input, wrap }) => {
      const show = applicableElectrical.includes(key);
      wrap.style.display = show ? '' : 'none';
      if (!show) {
        select.value = '';
        input.disabled = true;
        input.value = '';
      }
    });

    if (!desktop) {
      desktopFields.forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
    }

    if (!laptop) {
      laptopSpecFields.forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
      laptopStorageFields.forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
    }

    if (!laptopBattery) {
      Object.values(laptopBatteryFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
      batteryTypeSelect.value = '';
    }

    if (!laptopKeyboard) {
      Object.values(laptopKeyboardFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
    }

    if (!lcd) {
      Object.values(lcdFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
    }

    if (!cpBattery) {
      Object.values(cpBatteryFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
      cpBatteryCodeInput.value = '';
    }

    if (!powercord) {
      Object.values(powercordFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
      powercordTypeSelect.value = '';
      powercordCompatibilityInput.value = '';
    }

    if (!powerSocket) {
      Object.values(powerSocketFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
    }

    if (!wallmount) {
      Object.values(wallmountFields).forEach(({ select, input }) => {
        select.value = '';
        input.classList.add('d-none');
        input.disabled = true;
        input.value = '';
      });
    }
  }

  categorySelect.addEventListener('change', () => {
    toggleSpecSections();
    toggleConditionRow();
    toggleSourceRow();
    updateItemName();
  });
  brandSelect.addEventListener('change', updateItemName); // brand affects the check too

  const modelNameInput = document.getElementById('model_name_input');
  modelNameInput.addEventListener('input', updateItemName);

  function valueFor(select, input) {
    if (select.value === 'new') return input.value.trim();
    if (select.value === '') return ''; // placeholder or "Not available"
    return select.value; // an existing lookup value was picked
  }

  let debounceTimer;

  function checkDuplicate() {
    clearTimeout(debounceTimer);
    const description = itemNameField.value.trim();
    const brand = brandSelect.value;

    if (!description || !brand) {
      duplicateMsg.style.display = 'none';
      submitBtn.disabled = false;
      return;
    }

    debounceTimer = setTimeout(() => {
      const body = new URLSearchParams({ product_description: description, brand });
      fetch('check_duplicate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      })
        .then(res => res.json())
        .then(data => {
          duplicateMsg.style.display = data.exists ? 'block' : 'none';
          submitBtn.disabled = data.exists;
        })
        .catch(() => {
          duplicateMsg.style.display = 'none';
          submitBtn.disabled = false;
        });
    }, 350);
  }

  function updateItemName() {
    const categoryText = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    const desktop = isDesktopCategory();
    const laptop = isLaptopCategory();
    const laptopBattery = isLaptopBatteryCategory();
    const laptopKeyboard = isLaptopKeyboardCategory();
    const lcd = isLcdCategory();
    const cpBattery = isCpBatteryCategory();
    const powercord = isPowercordCategory();
    const powerSocket = isPowerSocketCategory();
    const wallmount = isWallmountCategory();

    function productPickerValue(select, input) {
      if (select.value === 'new') return input.value.trim();
      if (!select.value || select.value === 'not_available') return '';
      const opt = select.options[select.selectedIndex];
      return opt?.dataset.description || opt?.text || '';
    }

    let specParts;
    if (desktop) {
      specParts = desktopFields.map(({ select, input }) => productPickerValue(select, input));
      specParts.splice(1, 0, valueFor(processorGenField.select, processorGenField.input));
    } else if (laptop) {
      const laptopFieldMap = {};
      laptopSpecFields.forEach(f => { laptopFieldMap[f.key] = { field: f, type: 'typed' }; });
      laptopStorageFields.forEach(f => { laptopFieldMap[f.key] = { field: f, type: 'product' }; });
      laptopFieldMap['generation'] = { field: processorGenField, type: 'typed' };

      const laptopSpecOrder = ['cpu', 'generation', 'ram', 'hdd', 'ssd', 'gpu', 'screen_size', 'color'];
      specParts = laptopSpecOrder.map(key => {
        const entry = laptopFieldMap[key];
        if (!entry) return '';
        const { select, input } = entry.field;
        return entry.type === 'product' ? productPickerValue(select, input) : valueFor(select, input);
      });
    } else if (laptopBattery) {
      specParts = [
        valueFor(laptopBatteryFields.voltage.select, laptopBatteryFields.voltage.input),
        valueFor(laptopBatteryFields.mah.select, laptopBatteryFields.mah.input),
        batteryTypeSelect.value
      ];
    } else if (laptopKeyboard) {
      specParts = [
        valueFor(laptopKeyboardFields.panelType.select, laptopKeyboardFields.panelType.input),
        valueFor(laptopKeyboardFields.color.select, laptopKeyboardFields.color.input),
        valueFor(laptopKeyboardFields.layout.select, laptopKeyboardFields.layout.input)
      ];
    } else if (lcd) {
      specParts = [
        valueFor(lcdFields.screenSize.select, lcdFields.screenSize.input)
      ];
    } else if (cpBattery) {
      specParts = [
        cpBatteryCodeInput.value.trim(),
        valueFor(cpBatteryFields.voltage.select, cpBatteryFields.voltage.input),
        valueFor(cpBatteryFields.mah.select, cpBatteryFields.mah.input)
      ];
    } else if (powercord) {
      specParts = [
        powercordTypeSelect.value,
        valueFor(powercordFields.length.select, powercordFields.length.input),
        valueFor(powercordFields.voltage.select, powercordFields.voltage.input),
        valueFor(powercordFields.amperage.select, powercordFields.amperage.input),
        powercordCompatibilityInput.value.trim()
      ];
    } else if (powerSocket) {
      specParts = [
        valueFor(powerSocketFields.watts.select, powerSocketFields.watts.input),
        valueFor(powerSocketFields.amperage.select, powerSocketFields.amperage.input),
        valueFor(powerSocketFields.voltage.select, powerSocketFields.voltage.input)
      ];
    } else if (wallmount) {
      specParts = [
        valueFor(wallmountFields.itemType.select, wallmountFields.itemType.input),
        valueFor(wallmountFields.vesa.select, wallmountFields.vesa.input),
        valueFor(wallmountFields.suitableSize.select, wallmountFields.suitableSize.input),
        valueFor(wallmountFields.loadCapacity.select, wallmountFields.loadCapacity.input)
      ];
    } else {
      specParts = [
        valueFor(fields[0].select, fields[0].input),
        valueFor(fields[1].select, fields[1].input),
        valueFor(fields[2].select, fields[2].input)
      ];
    }

    const conditionText = needsCondition() ? conditionSelect.value : '';
    const sourceText = (isLaptopChargerCategory() && sourceSelect.value === 'Local') ? 'Local' : '';
    const modelText = modelNameInput.value.trim();
    const brandText = laptop ? (brandSelect.options[brandSelect.selectedIndex]?.text || '') : '';

    const parts = [
      conditionText,
      brandText,
      categorySelect.value ? categoryText : '',
      modelText,
      ...specParts,
      sourceText
    ].filter(Boolean);

    const joined = parts.join(' ');
    itemNameField.value = joined;

    if (joined) {
      itemNameDisplay.textContent = joined;
      itemNameDisplay.classList.remove('is-empty');
    } else {
      itemNameDisplay.textContent = 'Select a category to begin';
      itemNameDisplay.classList.add('is-empty');
    }

    checkDuplicate();
  }

  const dropzone = document.getElementById('pf_dropzone');
  const fileInput = document.getElementById('product_image');
  const thumbs = document.getElementById('pf_thumbs');

  dropzone.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    thumbs.innerHTML = '';
    Array.from(fileInput.files).forEach(file => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = document.createElement('img');
        img.src = e.target.result;
        thumbs.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });
  // When editing an existing product, the category select already has a
  // value set by PHP on page load — but toggleSpecSections/toggleConditionRow/
  // toggleSourceRow/updateItemName only ever ran on the select's 'change'
  // event, which never fires for a value set server-side. Run them once now
  // so the correct spec section (LCD, Laptop, etc.) shows immediately
  // instead of staying on whatever section is visible by default.
  if (categorySelect.value) {
    toggleSpecSections();
    toggleConditionRow();
    toggleSourceRow();
    updateItemName();
  }
})();


<?php 
if(isset($_GET['success']) && $_GET['success'] === 'true') {
?>
// form successfully inserted
window.onload = function () {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo isset($_GET['update']) ? 'Product has been updated successfully.' : 'Product has been added successfully.'; ?>',
        confirmButtonText: 'OK'
    });
};
<?php 
} elseif(isset($_GET['success']) && $_GET['success'] === 'false') {
  $error = $_GET['err'] = isset($_GET['err']) ? $_GET['err'] : 'Unknown error occurred.';
?>

// error submitting form
window.onload = function () {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: '<?php echo htmlspecialchars($error); ?>',
        confirmButtonText: 'OK'
    });
};
<?php 
}
?>
</script>
