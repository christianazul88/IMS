<button id="prototype-message" class="btn btn-primary d-none" type="button" data-bs-toggle="modal" data-bs-target="#message-modal">Chanchan</button>
<div class="modal fade" id="message-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Prototype Inventory Management System for Laptop PC Outlet (LPO)</h4>
        </div>
        <div class="p-4 pb-0">
        <p>Dear User,</p>
        <p>Welcome to the prototype version of the Inventory Management System (IMS) for Laptop PC Outlet (LPO). This website is intended for testing purposes only. Kindly refrain from uploading any sensitive information during this phase.</p>

        <h5>Instructions:</h5>
        <ol>
          <li><strong>Warehouse Setup:</strong>
            <p>To begin, please create a warehouse on the <em>Warehouses</em> page under the Administration section in the side navigation. If a warehouse has already been created, proceed to the <em>Access Levels</em> page to add a user position.</p>
          </li>
          <li><strong>User Setup:</strong>
            <p>After adding a user position, you can proceed to the <em>Users</em> page under the side navigation to manage users.</p>
          </li>
          <li><strong>Product Creation:</strong>
            <ul>
              <li><strong>Category Setup:</strong> Before adding a product, ensure that a category has been created on the <em>Category</em> page under the Administration section.</li>
              <li><strong>Brand Setup:</strong> Similarly, create a brand on the <em>Brand</em> page under the Administration section.</li>
              <li>Once both the category and brand are set up, you can proceed to the <em>Product List</em> page to add a product. You may also edit the product details by clicking the pencil icon on the product table.</li>
            </ul>
          </li>
          <li><strong>Stock Management:</strong>
            <ul>
              <li><strong>Item Location:</strong> Before adding stock, create an item location on the <em>Item Destination</em> page under the Administration section.</li>
              <li><strong>Supplier Setup:</strong> Add supplier information on the <em>Supplier</em> page under the Administration section.</li>
              <li>After completing the above steps, you can proceed to add stock.</li>
            </ul>
            <p><strong>Note:</strong> By uploading a CSV on the <em>Inbound</em> page, products will be automatically added, allowing you to skip some of the setup steps.</p>
          </li>
          <li><strong>Stock Transfers:</strong>
            <p>To transfer stocks between warehouses, visit the <em>Create Stock Transfer</em> page under the <em>Stock Transfer</em> section in the side navigation. After submitting the form, you will be redirected to the <em>Stock Transfer Logs</em> page, where you can edit and complete the transfer.</p>
            <p>On receiving a stock transfer, the receiving user can simply click on the corresponding log and submit the form.</p>
          </li>
          <li><strong>Outbound Items:</strong>
            <p>Before processing outbound items, ensure that you have added platform data on the <em>Platform</em> page and courier information on the <em>Courier</em> page, both located under the Administration section. After completing this, you can proceed to the <em>Outbound Form</em> page under <em>Logistics</em> in the side navigation. Be sure to fill out all required fields before submitting the form.</p>
          </li>
          <li><strong>Transaction Overview:</strong>
            <p>Once outbound transactions are completed, you can view them on the <em>Transaction Overview</em> page, accessible via the <em>Outbound Logs</em>.</p>
          </li>
          <li><strong>Product History:</strong>
            <p>To view the history of a product, navigate to <em>Inventory</em> > <em>Inventory/Stock</em> in the side navigation. Select the relevant warehouse, product, batch code, and barcode. This will redirect you to the product's history page. Please note that the design of the product history page is still under revision.</p>
          </li>
        </ol>

        <h5>Ongoing Developments:</h5>
        <ul>
          <li>Stock transfer to different item locations is currently under development. We apologize for the delay.</li>
          <li>The <em>Dashboard</em> page is incomplete and will only display accurate data once the other sections are fully implemented.</li>
        </ul>

        <h5>Timeline & Feedback:</h5>
        <p>Following the realignment meeting, the estimated time for completing the system is 1 to 3 weeks, depending on any necessary adjustments. As the developer, I welcome any constructive criticism to improve the system.</p>

        <p>I would like to extend my gratitude to Laptop PC Outlet (LPO) for the opportunity to develop this system. I strive to provide the best possible solution and appreciate your patience and support during this testing phase. Thank you again.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

