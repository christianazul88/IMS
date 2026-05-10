<div class="row">
    <div class="col-lg-12">
        <form class="dropzone dropzone-multiple p-0" id="my-dropzone-form" data-dropzone="data-dropzone" action="../config/initial_proofs.php" enctype="multipart/form-data">

            <!-- Fallback for older browsers -->
            <div class="fallback">
                <input name="file" type="file" multiple="multiple" />
            </div>

            <!-- Dropzone message -->
            <div class="dz-message" data-dz-message="data-dz-message">
                <img class="me-2" src="../assets/img/icons/cloud-upload.svg" width="25" alt="" />
                Drop your files here
            </div>

            <!-- File preview area -->
            <div class="dz-preview dz-preview-multiple m-0 d-flex flex-column">
                <div class="d-flex media mb-3 pb-3 border-bottom btn-reveal-trigger">
                    <img class="dz-image" src="../assets/img/generic/image-file-2.png" alt="..." data-dz-thumbnail="data-dz-thumbnail" />
                    
                    <div class="flex-1 d-flex flex-between-center">
                        <div>
                            <h6 data-dz-name="data-dz-name"></h6>
                            <div class="d-flex align-items-center">
                                <p class="mb-0 fs-10 text-400 lh-1" data-dz-size="data-dz-size"></p>
                                <div class="dz-progress">
                                    <span class="dz-upload" data-dz-uploadprogress=""></span>
                                </div>
                            </div>
                            <span class="fs-11 text-danger" data-dz-errormessage="data-dz-errormessage"></span>
                        </div>

                        <!-- Dropdown for file options -->
                        <div class="dropdown font-sans-serif">
                            <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal dropdown-caret-none" 
                                    type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="fas fa-ellipsis-h"></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end border py-2">
                                <a class="dropdown-item" href="#!" data-dz-remove="data-dz-remove">Remove File</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reason Textarea -->
            <div class="mb-3 mt-3 px-2">
                <label for="reason" class="form-label">Reason</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason..."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="px-2 pb-3">
                <button type="button" class="btn btn-primary" id="submit-proof">Submit</button>
            </div>

        </form>
    </div>
</div>
