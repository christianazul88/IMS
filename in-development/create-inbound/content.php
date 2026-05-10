<ul class="nav nav-pills" id="pill-myTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="pill-home-tab" data-bs-toggle="tab" href="#pill-tab-home" role="tab" aria-controls="pill-tab-home" aria-selected="true">Existing product</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="pill-new-tab" data-bs-toggle="tab" href="#pill-tab-new" role="tab" aria-controls="pill-tab-new" aria-selected="false">New product</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="pill-profile-tab" data-bs-toggle="tab" href="#pill-tab-profile" role="tab" aria-controls="pill-tab-profile" aria-selected="false">View</a>
  </li>
</ul>

<div class="tab-content border py-0 px-0 mt-3" id="pill-myTabContent">
  <div class="tab-pane fade show active" id="pill-tab-home" role="tabpanel" aria-labelledby="home-tab">
    <div class="card">
      <div class="card-body overflow-hidden p-6">
        <form action="" class="needs-validation" novalidate="novalidate">
          <div class="row">
            <div class="col-lg-9 mb-2">
              <label for="organizerSingle2">Description</label>
              <select class="form-select js-choice" id="organizerSingle2" size="1" required="required" name="organizerSingle" data-options='{"removeItemButton":true,"placeholder":true}'>
                <option value="">Select organizer...</option>
                <option>Massachusetts Institute of Technology</option>
                <option>University of Chicago</option>
                <option>GSAS Open Labs At Harvard</option>
                <option>California Institute of Technology</option>
                <option>Massachusetts Institute of Technology</option>
                <option>University of Chicago</option>
                <option>GSAS Open Labs At Harvard</option>
                <option>California Institute of Technology</option>
                <option>Massachusetts Institute of Technology</option>
                <option>University of Chicago</option>
                <option>GSAS Open Labs At Harvard</option>
                <option>California Institute of Technology</option>
                <option>Massachusetts Institute of Technology</option>
                <option>University of Chicago</option>
                <option>GSAS Open Labs At Harvard</option>
                <option>California Institute of Technology</option>
              </select>
              <div class="invalid-feedback">Please select one</div>
            </div>

            <div class="col-lg-3 mb-2">
              <label for="price">Price</label>
              <input class="form-control" type="number" step="0.01" min="0" placeholder="Enter price" required>
            </div>

            <div class="col-lg-3 mb-2">
              <label for="qty">Quantity</label>
              <input type="number" class="form-control" placeholder="enter qty" required>
            </div>

            <div class="col-lg-3 mb-2">
              <label for="safety">Safety stock</label>
              <input type="number" class="form-control" placeholder="enter stock" required>
            </div>

            <div class="col-lg-5 mb-2">
              <label for="ItemDestination">Item destination</label>
              <select class="form-select js-choice" id="ItemDestination" size="1" required="required" name="ItemDestination" data-options='{"removeItemButton":true,"placeholder":true}'>
                <option value="">Select item destination...</option>
                <option>Shelf A</option>
                <option>Shelf B</option>
                <option>Shelf C</option>
                <option>Shelf D</option>
                <option>Shelf E</option>
                <option>Shelf F</option>
              </select>
              <div class="invalid-feedback">Please select one</div>
            </div>

            <div class="col-lg-3 mb-2">
              <label for="batch-code">Batch code</label>
              <input type="text" class="form-control" id="batch-code" placeholder="enter batch code" required>
            </div>

            <div class="col-lg-4 mb-2">
              <label for="keyword">Keyword</label>
              <input type="text" class="form-control" id="keyword" placeholder="enter keyword" required>
            </div>

            <div class="col-lg-12 mb-2 text-end">
              <button class="btn btn-primary" type="submit">Submit form</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="pill-tab-new" role="tabpanel" aria-labelledby="profile-tab">
    <div class="card">  
      <div class="card-body">
        <div class="row">
            <div class="col-lg-4">
                <label for="">Category</label>
                <select class="form-select js-choice" id="ItemDestination" size="1" required="required" name="ItemDestination" data-options='{"removeItemButton":true,"placeholder":true}'>
                  <option value="">Select category...</option>
                  <option>Category A</option>
                  <option>Category B</option>
                  <option>Category C</option>
                  <option>Category D</option>
                  <option>Category E</option>
                  <option>Category F</option>
                </select>
                <div class="invalid-feedback">Please select one</div>
            </div>
            <div class="col-lg-4">
                <label for="">Brand</label>
                <select class="form-select js-choice" id="ItemDestination" size="1" required="required" name="ItemDestination" data-options='{"removeItemButton":true,"placeholder":true}'>
                  <option value="">Select brand...</option>
                  <option>Brand A</option>
                  <option>Brand B</option>
                  <option>Brand C</option>
                  <option>Brand D</option>
                  <option>Brand E</option>
                  <option>Brand F</option>
                </select>
                <div class="invalid-feedback">Please select one</div>
            </div>
            <div class="col-lg-4">
                <label for="">Product Name</label>
                <input type="text" class="form-control" placeholder="Enter product name">
            </div>

            <div class="col-lg-4 mb-2">
                <label for="price">Price</label>
                <input class="form-control" type="number" step="0.01" min="0" placeholder="Enter price" required>
              </div>

              <div class="col-lg-4 mb-2">
                <label for="qty">Quantity</label>
                <input type="number" class="form-control" placeholder="enter qty" required>
              </div>

              <div class="col-lg-4 mb-2">
                <label for="safety">Safety stock</label>
                <input type="number" class="form-control" placeholder="enter stock" required>
              </div>

              <div class="col-lg-5 mb-2">
                <label for="ItemDestination">Item destination</label>
                <select class="form-select js-choice" id="ItemDestination" size="1" required="required" name="ItemDestination" data-options='{"removeItemButton":true,"placeholder":true}'>
                  <option value="">Select item destination...</option>
                  <option>Shelf A</option>
                  <option>Shelf B</option>
                  <option>Shelf C</option>
                  <option>Shelf D</option>
                  <option>Shelf E</option>
                  <option>Shelf F</option>
                </select>
                <div class="invalid-feedback">Please select one</div>
              </div>

              <div class="col-lg-3 mb-2">
                <label for="batch-code">Batch code</label>
                <input type="text" class="form-control" id="batch-code" placeholder="enter batch code" required>
              </div>

              <div class="col-lg-4 mb-2">
                <label for="keyword">Keyword</label>
                <input type="text" class="form-control" id="keyword" placeholder="enter keyword" required>
              </div>
              <div class="col-lg-12 text-end">
                <button type="submit" class="btn btn-primary">Submit form</button>
              </div>
        </div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade p-0" id="pill-tab-profile" role="tabpanel" aria-labelledby="profile-tab">
    <div class="card m-0">
      <div class="card-body overflow-hidden">
        <div class="row">
          <div class="col-lg-12 text-end">
            <small>Sales Invoice: 8009-00001</small><br>
            <small>From: Supplier International 1</small><br>
            <small>Date: January 1, 2024</small>
          </div>
          <div class="col-lg-12">
            <small>Received by: Warehouse 1</small><br>
            <small>P.O no: PO-1001</small>
          </div>
          <div class="col-lg-12" style="min-height: 300px;">
            <div class="table-responsive">
              <table class="table">
                <tr>
                  <th><small>Description</small></th>
                  <th><small>Quantity</small></th>
                  <th class="text-end"><small>Price</small></th>
                </tr>
                <tbody>
                  <tr>
                    <td><small>Laptop Charger 1</small></td>
                    <td><small>3</small></td>
                    <td class="text-end"><small>160.00</small></td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="2" class="text-end"><small><b>Total:</b></small></td>
                    <td class="text-end"><small><b>160.00</b></small></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div class="col-lg-6">
            <small>Received by:</small><br>
            <br>
            <small><b><u>Juan Dela Cruz</u></b></small><br>
            <small>Position</small>
          </div>
          <div class="col-lg-6 text-end pt-6">
            <button class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
