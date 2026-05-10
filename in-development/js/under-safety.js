// Fetch data from the API
async function fetchWarehouseData() {
    try {
        const response = await fetch('../API/undersafety-inventory.php'); // Replace with your actual API endpoint
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        const swiperWrapper = document.getElementById('undersafety-stocks');
        swiperWrapper.innerHTML = '';

        // Create slides
        data.forEach(warehouse => {
            const slide = document.createElement('div');
            slide.classList.add('swiper-slide');
            slide.innerHTML = `
                <div class="row">
                    <div class="col">
                        <p id="weekly-sales-amount" class="font-sans-serif lh-1 mb-1 fs-5">${warehouse.quantity}</p> 
                        <span class="badge badge-subtle-success rounded-pill fs-11">${warehouse.warehouse}</span> 
                    </div>
                    <div class="col-auto ps-0">
                        <div class="h-100"><span class="far fa-chart-bar"></span></div>
                    </div>
                </div>
            `;
            swiperWrapper.appendChild(slide);
        });

        // Dynamically configure loop mode
        const loopMode = data.length > 1;

        // Initialize Swiper
        new Swiper('.swiper-container', {
            loop: loopMode,
            slidesPerView: 1,
            spaceBetween: 10,
        });

    } catch (error) {
        console.error('Error fetching warehouse data:', error);
    }
}

// Call the function to fetch and display data
fetchWarehouseData();
