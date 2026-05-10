<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales, Capital, and Profit</title>
    <!-- Include Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include ECharts library -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.3.3/dist/echarts.min.js"></script>
    <style>
        /* Set the size of the chart container */
        #salesCapital {
            width: 100%;
            height: 400px;
        }
    </style>
</head>
<body>

    <div class="container mt-5">
        <!-- Create a row and column inside a card -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Sales, Capital, and Profit</h4>
                    </div>
                    <div class="card-body">
                        <!-- Create a div for the chart -->
                        <div id="salesCapital"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include your custom JavaScript file -->
    <script src="../../assets/js/salesCapital.js"></script>

    <!-- Include Bootstrap 5 JS (optional for any interactive elements) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
