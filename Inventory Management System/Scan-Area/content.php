<div class="row justify-content-center align-items-center" style="min-height: 100dvh;">

    <div class="col-md-6 col-lg-5">

        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4 p-md-5 text-center">

                <div class="mb-4">
                    <div class="display-6">📍</div>
                    <h3 class="fw-bold mt-2 mb-1">Scan Location</h3>
                    <p class="text-muted mb-0">
                        Scan a rack or bin barcode to open its location page
                    </p>
                </div>

                <form id="scanForm">
                    <input 
                        type="text"
                        id="locationInput"
                        class="form-control form-control-lg text-center border-2 py-3"
                        placeholder="Scan location barcode..."
                        autocomplete="off"
                        autofocus
                        inputmode="none"
                    >

                    <button type="submit" class="d-none">Go</button>
                </form>

                <div class="mt-4">
                    <span class="badge bg-light text-dark border px-3 py-2" id="statusBadge">
                        Ready to scan location
                    </span>
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

    badge.textContent = "Searching location...";
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
            badge.textContent = "Opening location...";
            window.location.href = data.url;
        } else {
            badge.textContent = "Location not found";
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