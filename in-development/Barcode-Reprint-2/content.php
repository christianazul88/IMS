<div class="card">
    <div class="card-body overflow-hidden py-6 px-2">
            <h5 class="ms-3">Finish download before downloading the next</h5>
            <div class="card shadow-none">
                <div class="card-body p-0 pb-3">
                    <div class="d-flex align-items-center justify-content-end my-3">
                        <div class="col-auto text-end mb-3 me-1">
                            <div id="bulk-select-replace-element">
                                <button class="btn btn-falcon-success btn-sm" type="submit">
                                    <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                    <span class="ms-1">Submit</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive scrollbar">
                        <table class="table mb-0 table-sm">
                            <thead class="bg-200">
                                <tr>
                                    <th class="text-black dark__text-white align-middle">Description</th>
                                    <th class="text-black dark__text-white align-middle">Barcode Range</th>
                                    <th class="text-black dark__text-white align-middle">Reprint Link</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-select-body" class="list">
                                <?php 
                                ini_set('max_input_vars', '100000');
                                ini_set('max_input_time', '300');
                                ini_set('memory_limit', '512M');
                                
                                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && is_array($_POST['product_id'])) {
                                    $product_groups = array_chunk($_POST['parent_barcode'], 300);
                                    $total_products = count($_POST['parent_barcode']);
                                    $start = 1;

                                    foreach ($product_groups as $group) {
                                        $end = $start + count($group) - 1;
                                        $query_string = http_build_query(['product_id' => $group]);

                                        echo '<tr>';
                                        echo '<td class="align-middle">Selected Products</td>';
                                        echo '<td class="align-middle">Barcode ' . $start . '-' . $end . '</td>';
                                        // echo '<td class="align-middle"><a href="../config/reprint.php?' . $query_string . '" target="_blank">Reprint Batch</a></td>';
                                        echo '<td class="align-middle">
                                        <form action="../config/reprint.php" method="POST" target="_blank">';

                                        foreach ($group as $id) {
                                            echo '<input type="hidden" name="product_id[]" value="'.$id.'">';
                                        }

                                        echo '<button class="btn btn-sm btn-primary">Reprint Batch</button>
                                        </form>
                                        </td>';
                                        echo '</tr>';

                                        $start = $end + 1;
                                    }
                                }

                                unset($_SESSION['stored_data']);
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="pagination d-none"></div>
                        <div class="col">
                            <p class="mb-0 fs-10">
                                <a class="fw-semi-bold" href="#" data-list-view="*">View all</a>
                                <a class="fw-semi-bold d-none" href="#" data-list-view="less">View Less</a>
                            </p>
                        </div>
                        <div class="col-auto d-flex">
                            <button class="btn btn-sm btn-primary" type="button" data-list-pagination="prev">Previous</button>
                            <button class="btn btn-sm btn-primary px-4 ms-2" type="button" data-list-pagination="next">Next</button>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>
