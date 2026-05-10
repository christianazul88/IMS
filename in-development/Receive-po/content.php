<!-- <div class="card">
<div class="card-body overflow-hidden p-lg-6">
    <div class="row align-items-center">
    <div class="col-lg-6"><img class="img-fluid" src="../assets/img/icons/spot-illustrations/21.png" alt="" /></div>
    <div class="col-lg-6 ps-lg-4 my-5 text-center text-lg-start">
        <h3 class="text-primary">Edit me!</h3>
        <p class="lead">Create Something Beautiful.</p><a class="btn btn-falcon-primary" href="../documentation/getting-started.html">Getting started</a>
    </div>
    </div>
</div>
</div> -->

<div class="card">
    <div class="card-body overflow-hidden py-6 px-2">
      <h5>Inbound</h5>
    <div class="card shadow-none">
  <div class="card-body p-0 pb-3"  data-list='{"valueNames":["desc","barcode","brand","cat","qty","trans"]}'>
    <div class="d-flex align-items-center justify-content-end my-3">
      <div id="bulk-select-replace-element">
        <!-- <button class="btn btn-falcon-success btn-sm" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="ms-1">Submit</span></button> -->
        <a href="../config/generate-inbound-receipt.php" class="btn btn-falcon-success btn-sm" ><!--<span class="fas fa-plus" data-fa-transform="shrink-3 down-2">--></span><span class="ms-1">Submit</span></a>
    </div>
    </div>
    <div class="table-responsive scrollbar">
      <table class="table mb-0">
        <thead class="bg-200">
          <tr>
            <th width="50"></th>
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Description</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Parent Barcode</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="barcode">Brand </th>
            <th class="text-black dark__text-white align-middle sort" data-sort="cat">Category</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="">Quantity Received</th>
            <th class="text-black dark__text-white align-middle white-space-nowrap pe-3 sort" data-sort="qty">Ordered Quantity</th>
          </tr>
        </thead>
        <tbody id="bulk-select-body" class="list">
          <tr>
            <td>
                <img src="../../assets/img/def_img.png" alt="" height="50">
            </td>
            <th class="align-middle desc">Laptop Charger 1</th>
            <th class="align-middle barcode">490000891</th>
            <td class="align-middle brand">British</td>
            <td class="align-middle cat">Male</td>
            
            <td>
                <input type="number" class="form-control" placeholder="Input Qty">
            </td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty">32</td>
          </tr>
          <tr>
            <td>
                <img src="../../assets/img/def_img.png" alt="" height="50">
            </td>
            <th class="align-middle desc">Laptop Charger 2</th>
            <th class="align-middle barcode">490000895</th>
            <td class="align-middle brand">MSI</td>
            <td class="align-middle cat">Female</td>
            
            <td>
                <input type="number" class="form-control" placeholder="Input Qty">
            </td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty">32</td>
          </tr>
          <tr>
            <td>
                <img src="../../assets/img/def_img.png" alt="" height="50">
            </td>
            <th class="align-middle desc">Laptop Charger 3</th>
            <th class="align-middle barcode">490000896</th>
            <td class="align-middle brand">MSI</td>
            <td class="align-middle cat">Male</td>
            
            <td>
                <input type="number" class="form-control" placeholder="Input Qty">
            </td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty">49</td>
          </tr>
          <tr>
            <td>
                <img src="../../assets/img/def_img.png" alt="" height="50">
            </td>
            <th class="align-middle desc">Laptop Charger 4</th>
            <th class="align-middle barcode">490000897</th>
            <td class="align-middle brand">Acer</td>
            <td class="align-middle cat">Male</td>
            
            <td>
                <input type="number" class="form-control" placeholder="Input Qty">
            </td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty">59</td>
          </tr>
          <tr>
            <td>
                <img src="../../assets/img/def_img.png" alt="" height="50">
            </td>
            <th class="align-middle desc">Laptop Charger 5</th>
            <th class="align-middle barcode">490000898</th>
            <td class="align-middle brand">Acer</td>
            <td class="align-middle cat">Female</td>
            
            <td>
                <input type="number" class="form-control" placeholder="Input Qty">
            </td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty">21</td>
          </tr>
          <tr>
            <td>
                <img src="../../assets/img/def_img.png" alt="" height="50">
            </td>
            <th class="align-middle desc">Laptop Charger 6</th>
            <th class="align-middle barcode">490000899</th>
            <td class="align-middle brand">Acer</td>
            <td class="align-middle cat">Female</td>
            
            <td>
                <input type="number" class="form-control" placeholder="Input Qty">
            </td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty">23</td>
          </tr>
        </tbody>
      </table>
      <p class="mt-3 mb-2 d-none">Click the button to get selected rows</p><button class="btn btn-warning d-none" data-selected-rows="data-selected-rows">Get Selected Rows</button><pre id="selectedRows"></pre>
    </div>
  </div>
</div>
    </div>
</div>