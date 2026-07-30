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
  .pf-wrap{ max-width:960px; margin:2.5rem auto; padding:0 1rem; }
  .pf-card{
    background:var(--surface); border:1px solid var(--border); border-radius:14px;
    overflow:hidden;
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
    padding:.6rem .9rem; min-width:280px;
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
        <div class="pf-label-caption">This updates live as you choose category, brand, and electrical specs below — it's exactly what prints on the shelf tag.</div>
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
        </div>
      </div>

      <div class="pf-section">
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
      <button type="submit" class="btn-pf-primary">Save product</button>
    </div>
  </div>
</div>
</form>

<script>
(function () {
  const categorySelect = document.getElementById('category');
  const itemNameField = document.getElementById('item_name');
  const itemNameDisplay = document.getElementById('item_name_display');

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

  categorySelect.addEventListener('change', updateItemName);

  function valueFor(select, input) {
    if (select.value === 'new') return input.value.trim();
    if (select.value === 'NA') return 'NA';
    return '';
  }

  function updateItemName() {
    const categoryText = categorySelect.options[categorySelect.selectedIndex]?.text || '';
    const parts = [
      categorySelect.value ? categoryText : '',
      valueFor(fields[0].select, fields[0].input),
      valueFor(fields[1].select, fields[1].input),
      valueFor(fields[2].select, fields[2].input)
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