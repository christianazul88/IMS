<div class="row justify-content-center align-items-center" style="min-height:100dvh;">

    <div class="col-md-7 col-lg-6">

        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4 p-md-5">

                <!-- Header -->
                <div class="text-center mb-4">
                    <div style="font-size:3rem;">📦</div>

                    <h2 class="fw-bold mb-2">
                        Scan Approved Ticket
                    </h2>

                    <p class="text-muted mb-0">
                        Scan the barcode attached to an approved inventory control ticket.
                    </p>
                </div>

                <!-- Scanner -->
                <form id="scanForm">

                    <input
                        type="text"
                        id="locationInput"
                        class="form-control form-control-lg text-center border-2 py-3"
                        placeholder="Scan ticket barcode..."
                        autocomplete="off"
                        autofocus
                        inputmode="none"
                    >

                    <button type="submit" class="d-none">Submit</button>

                </form>

                <!-- Status -->
                <div class="text-center mt-3">
                    <span
                        class="badge bg-light text-dark border px-3 py-2"
                        id="statusBadge"
                    >
                        Ready to scan ticket
                    </span>
                </div>

                <!-- Workflow Information -->
                <div class="alert alert-info border-0 shadow-sm mt-5">

                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-route me-2"></i>
                        Entering an Area Code Instead?
                    </h5>

                    <p class="mb-3">
                        If you are starting a new inventory count, you should
                        <strong>enter an Area Code first</strong>.
                    </p>

                    <div class="bg-white rounded p-3 border mb-3">

                        <div class="mb-2">
                            <strong>Step 1:</strong> Click the button below to enter area code.
                        </div>

                    </div>

                    <div class="d-grid">
                        <a
                            href="../Enter-Area/"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Area Code
                        </a>
                    </div>

                    <hr>

                    <small class="text-muted">
                        This page is only for scanning barcodes printed on approved
                        Inventory Control Tickets that are already attached to
                        boxes, pallets, or batches of items.
                    </small>

                </div>

            </div>
        </div>

    </div>

</div>

<script>
const input = document.getElementById("locationInput");
const badge = document.getElementById("statusBadge");

function keepFocus() {
    setTimeout(() => input.focus(), 50);
}

window.onload = keepFocus;
window.addEventListener("click", keepFocus);
window.addEventListener("touchstart", keepFocus);

document.getElementById("scanForm").addEventListener("submit", function(e) {
    e.preventDefault();
    fetchLocation();
});

input.addEventListener("keydown", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        fetchLocation();
    }
});

function fetchLocation() {

    const id = input.value.trim();

    if (!id) return;

    badge.textContent = "Searching ticket...";
    badge.className = "badge bg-primary text-white border px-3 py-2";

    fetch("get_location.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            badge.textContent = "Opening ticket...";
            badge.className = "badge bg-success text-white border px-3 py-2";

            window.location.href = data.url;

        } else {

            badge.textContent = "Ticket not found";
            badge.className = "badge bg-danger text-white border px-3 py-2";

            input.value = "";
            keepFocus();
        }

    })
    .catch(() => {

        badge.textContent = "Server error";
        badge.className = "badge bg-danger text-white border px-3 py-2";

        keepFocus();

    });
}
</script>