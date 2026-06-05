<?php

$data = '
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Document Title -->
    <title>Falcon | Dashboard &amp; Web App Template</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
</head>

<body>
   <h1 class="text-success">Hello WOrld</h1>
   <div class="row">
    <div class="col-lg-4 col-md-4 col-xl-4 col-sm-4 bg-primary">6</div>
    <div class="col-lg-4 col-md-4 col-xl-4 col-sm-4 bg-secondary">5</div>
    <div class="col-lg-4 col-md-4 col-xl-4 col-sm-4 bg-warning">4</div>
    <div class="col-lg-4 col-md-4 col-xl-4 col-sm-4 bg-danger">3</div>
    <div class="col-lg-4 col-md-4 col-xl-4 col-sm-4 bg-dark">2</div>
    <div class="col-lg-4 col-md-4 col-xl-4 col-sm-4 bg-primary">1</div>
   </div>
</body>

</html>

';
$pdfname = "Inbound Receipt #111.pdf";

include "generate-pdf.php";
