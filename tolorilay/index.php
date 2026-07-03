<?php
session_start();

// 1 = Monday, 2 = Tuesday, ..., 7 = Sunday
if (date('N') != 1) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Not Yet ☕</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="d-flex justify-content-center align-items-center vh-100 bg-light">

        <div class="text-center">
            <h1>☕</h1>
            <h2>Come back on Monday.</h2>
            <p>Can't show it yet.</p>
        </div>

    </body>
    </html>
    <?php
    exit;
}
?>