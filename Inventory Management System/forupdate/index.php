<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Upload</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f4f4f4;
            padding:40px;
        }

        .container{
            background:#fff;
            padding:30px;
            border-radius:10px;
            width:400px;
            margin:auto;
            box-shadow:0 0 10px rgba(0,0,0,0.1);
        }

        h2{
            margin-bottom:20px;
        }

        input[type=file]{
            margin-bottom:20px;
            width:100%;
        }

        button{
            padding:10px 20px;
            background:#007bff;
            border:none;
            color:#fff;
            border-radius:5px;
            cursor:pointer;
        }

        button:hover{
            background:#0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Upload CSV File</h2>

    <form action="process.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>

        <button type="submit">Upload CSV</button>
    </form>
</div>

</body>
</html>