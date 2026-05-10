document.addEventListener("DOMContentLoaded", function () {
    let data = [];

    fetch("../API/outboundForForecasting.php")
        .then(response => response.json())
        .then(result => {
            if (result.status === "success") {
                data = result.data;
            }
        });

    document.getElementById("forecastButton").addEventListener("click", function () {
        const selectedPeriod = parseInt(document.getElementById("forecastPeriod").value, 10);
        const today = new Date();
        const pastDate = new Date();
        pastDate.setDate(today.getDate() - selectedPeriod);

        const filteredData = data.filter(item => {
            const itemDate = new Date(item.date_sent);
            return itemDate >= pastDate && itemDate <= today;
        });

        const productForecasts = {};

        filteredData.forEach(item => {
            if (!productForecasts[item.product_id]) {
                productForecasts[item.product_id] = {
                    ...item,
                    totalUsage: 0,
                    count: 0,
                };
            }
            productForecasts[item.product_id].totalUsage += item.daily_usage;
            productForecasts[item.product_id].count += 1;
        });

        const forecastResults = Object.values(productForecasts).map(item => {
            const avgDailyUsage = item.totalUsage / item.count;
            const localLeadTime = item.shipment_data.find(s => s.supplier_type === "local")?.lead_time || 1;
            const internationalLeadTime = item.shipment_data.find(s => s.supplier_type === "international")?.lead_time || 30;
            const safetyStock = avgDailyUsage * 2;
            const reorderPoint = avgDailyUsage * localLeadTime + safetyStock;

            return {
                ...item,
                avgDailyUsage: avgDailyUsage.toFixed(2),
                reorderPoint: reorderPoint.toFixed(2),
                localLeadTime,
                internationalLeadTime,
            };
        });

        displayForecast(forecastResults);
    });
});

function displayForecast(forecastData) {
    const outputDiv = document.getElementById("forecastOutput");
    outputDiv.innerHTML = "";

    forecastData.forEach(item => {
        const card = document.createElement("div");
        card.className = "forecast-card";
        card.innerHTML = `
            <h3>${item.product_description}</h3>
            <p><strong>Brand:</strong> ${item.brand_name}</p>
            <p><strong>Category:</strong> ${item.category_name}</p>
            <p><strong>Average Daily Usage:</strong> ${item.avgDailyUsage}</p>
            <p><strong>Local Lead Time:</strong> ${item.localLeadTime} days</p>
            <p><strong>International Lead Time:</strong> ${item.internationalLeadTime} days</p>
            <p><strong>Reorder Point:</strong> ${item.reorderPoint}</p>
        `;
        outputDiv.appendChild(card);
    });
}
