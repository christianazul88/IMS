<?php 
if(isset($_SESSION['inbound_warehouse'])){
    $selected_warehouse_SIL = $_SESSION['inbound_warehouse'];
} else {
    echo "NOT SET";
}
?>
    <div class="overflow-hidden p-lg-6">
        <div class="row">
            <div class="col-lg-12">
                <h4>Please set the Item Location</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <form id="myForm" action="../config/set-item-loc.php" method="POST">
                    <div class="row justify-content-end">
                        <div class="col-auto mb-3">
                            <a href="../Inventory-stock/" class="btn btn-info">Skip</a>
                        </div>
                        <div class="col-auto mb-3">
                            <button class="btn btn-primary" id="submitBTN" type="submit">Submit</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table bordered-table table-sm">
                            <thead class="table-info">
                                <tr>
                                    <th style="min-width: 75px;"></th>
                                    <th style="min-width: 300px;">Product Description</th>
                                    <th style="min-width: 200px;">Keyword</th>
                                    <th style="min-width: 150px;">Price</th>
                                    <th style="min-width: 200px;">Supplier</th>
                                    <th style="min-width: 200px;">Barcode</th>
                                    <th style="min-width: 200px;">Batch no.</th>
                                    <th style="min-width: 200px;">Brand</th>
                                    <th style="min-width: 200px;">Category</th>
                                    <th style="min-width: 200px;">Safety</th>
                                    <th style="min-width: 300px;">Item Location</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php include "tbody.php";?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>