<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECharts with Dynamic API Data</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-4">
        <!-- Bootstrap Row -->
        <div class="row">
            <!-- Bootstrap Column with Card -->
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center">Outbound Products Over Time</h5>
                        <div id="SpecificItemChart" style="height: 400px;"></div> <!-- Chart Container -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to fetch data from the API
        async function fetchChartData() {
            try {
                const uniqueBarcode = '1000992-1'; // Replace with a dynamic value if needed
                const response = await fetch(`../config/total_outbound_specific_product.php?prod=${uniqueBarcode}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                console.log('API Response:', data); // Debugging log

                const months = data.map(item => item.month); // Extract the month labels
                const totals = data.map(item => item.total_outbound); // Extract the total outbound counts
                
                // Reverse the months and totals to display from the earliest to the latest
                months.reverse();
                totals.reverse();
                
                return { months, totals };
            } catch (error) {
                console.error('Error fetching chart data:', error);
                return { months: [], totals: [] }; // Return empty data in case of error
            }
        }

        async function initChart() {
            const { months, totals } = await fetchChartData();

            // Verify data before proceeding
            if (months.length === 0 || totals.length === 0) {
                console.error('No data available for the chart.');
                return;
            }

            const chart = echarts.init(document.getElementById('SpecificItemChart'));

            const options = {
                title: {
                    text: 'Outbound Products Over Time',
                    left: 'center'
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: '{b}: {c}'
                },
                xAxis: {
                    type: 'category',
                    data: months, // Now showing from earliest to latest month
                    name: 'Month'
                },
                yAxis: {
                    type: 'value',
                    name: 'Total Outbound'
                },
                series: [
                    {
                        name: 'Total Outbound',
                        type: 'line',
                        data: totals, // Now showing from earliest to latest total
                        smooth: true,
                        lineStyle: {
                            color: '#5470C6',
                            width: 2
                        },
                        itemStyle: {
                            color: '#5470C6'
                        }
                    }
                ]
            };

            chart.setOption(options);
        }

        // Initialize the chart
        initChart();
    </script>
</body>
</html>
