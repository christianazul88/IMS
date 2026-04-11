// Initialize the chart
var myChart = echarts.init(document.getElementById('salesCapital'));

// Function to fetch data from the server and render the chart
function fetchData() {
    // Fetch data from the PHP script
    fetch('../API/users_logs/salesCapital.php')
        .then(response => response.json())  // Parse the JSON response
        .then(data => {
            // Prepare the chart options
            var option = {
                
                tooltip: {},
                legend: {
                    data: ['Capital', 'Sales', 'Profit']
                },
                xAxis: {
                    data: data.months // X-axis is the months from the server
                },
                yAxis: {},
                series: [
                    {
                        name: 'Capital',
                        type: 'bar',
                        data: data.capital // Capital data from the server
                    },
                    {
                        name: 'Sales',
                        type: 'bar',
                        data: data.sales // Sales data from the server
                    },
                    {
                        name: 'Profit',
                        type: 'bar',
                        data: data.profit // Profit data from the server
                    }
                ]
            };

            // Set the options to the chart instance
            myChart.setOption(option);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
}

// Call the function to fetch data and render the chart
fetchData();
