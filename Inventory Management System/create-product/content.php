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
<form action="../config/add-product.php" method="POST">
<div class="pf-wrap">
  <div class="pf-card">
    <div class="pf-head">
      <h2>Add new product</h2>
      <p>Fields marked with an asterisk build the item name automatically.</p>
    </div>

    <div class="pf-body">

      <div class="pf-label-preview">
        <div class="pf-label-tag">
          <div class="eyebrow">Item name preview</div>
          <div class="name is-empty" id="item_name_display">Select a category to begin</div>
        </div>
        <div class="pf-label-caption">This updates live as you choose category, brand, and specs below — it's exactly what prints on the shelf tag.</div>
      </div>
      <small class="pf-barcode-msg" id="duplicate-message" style="display:block;">This item name + brand combination already exists.</small>

      </div>
      <input type="hidden" id="item_name" name="product_description">


      <div class="pf-section">
        <div class="pf-section-label">Photo (optional)</div>
        <div class="pf-dropzone" id="pf_dropzone">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 16V4M12 4l-4 4M12 4l4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
          <div class="cta">Click to upload product photos</div>
          <div class="hint">PNG or JPG, multiple files not allowed</div>
        </div>
        <input type="file" class="d-none" name="product_image[]" id="product_image" accept="image/*" multiple>
        <div class="pf-thumbs" id="pf_thumbs"></div>
      </div>

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
                      echo '<option value="' . $row['hashed_id'] . '">' . $row['category_name'] . '</option>';
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
                      echo '<option value="' . $row['hashed_id'] . '">' . $row['brand_name'] . '</option>';
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
          <div class="pf-spec">
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
          <div class="pf-spec">
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
          <div class="pf-spec">
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

      <div class="pf-section">
        <div class="pf-section-label">Inventory identification</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="pf-label">Parent barcode</label>
            <input type="text" class="form-control" id="parent_barcode" name="parent_barcode"
              placeholder="Enter barcode" oninput="this.value = this.value.replace(/\s/g, '')">
            <small class="pf-barcode-msg" id="barcode-message">This barcode already exists.</small>
          </div>
          <div class="col-md-6">
            <label class="pf-label">Minimum stock</label>
            <input type="number" min="2" max="1000" name="safety" class="form-control" required placeholder="e.g. 10">
            <div class="pf-hint">Reorder alert triggers below this quantity.</div>
          </div>
        </div>
      </div>

    </div>

    <div class="pf-footer">
      <button type="button" class="btn-pf-secondary">Cancel</button>
      <button type="submit" class="btn-pf-primary" id="submit-btn">Save product</button>
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
    select: document.getElementById(prefix + '_select'),
    input: document.getElementById(prefix + '_input')
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

  function isDesktopCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'desktop';
  }

  function isLaptopCategory() {
    const text = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    return text.trim().toLowerCase() === 'laptop';
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

    electricalSection.style.display = (desktop || laptop) ? 'none' : '';
    desktopSection.style.display = desktop ? '' : 'none';
    laptopSection.style.display = laptop ? '' : 'none';
    processorGenSection.style.display = (desktop || laptop) ? '' : 'none';

    if (!desktop && !laptop) {
      processorGenField.select.value = '';
      processorGenField.input.classList.add('d-none');
      processorGenField.input.disabled = true;
      processorGenField.input.value = '';
    }

    if (desktop || laptop) {
      // clear electrical fields so they don't leak into the name or the POST body
      fields.forEach(({ select, input }) => {
        select.value = '';
        input.disabled = true;
        input.value = '';
      });
    }

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
})();
</script>
