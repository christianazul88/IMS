<?php
session_start();

// 1 = Monday, 2 = Tuesday, ..., 7 = Sunday
if (date('N') != 3) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Sorry, it's taking more time than usual ☕</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#4b2e2e,#7b4f37,#d2b48c);
    font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}

.wait-card{
    width:100%;
    max-width:650px;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(12px);
    border:none;
    border-radius:25px;
    padding:45px;
    box-shadow:0 20px 50px rgba(0,0,0,.25);
}

.coffee{
    font-size:4rem;
}

.quote{
    font-size:1rem;
    line-height:1.8;
    color:#555;
}

.footer-text{
    font-size:.9rem;
    color:#888;
}
</style>

</head>
<body>

<div class="card wait-card text-center">

    <div class="coffee mb-3">☕</div>

    <h2 class="fw-bold mb-3">Come back on Wednesday.</h2>

    <p class="text-danger fw-semibold">
        Have patience lods, visit mo to ha...
    </p>

    <hr>

    <p class="quote">
        I know waiting isn't fun, but I hope you'll come back on Wednesday.
        I wanted to prepare this properly instead of rushing it.
    </p>

    <p class="quote">
        And if you're reading this later... just know that I still respect
        myself. This wasn't made to chase anyone or to change anyone's mind.
        I simply wanted to leave things with a proper goodbye—something I felt
        was worth doing, at least once.

        PS: Sorry, dito sa domain ng client ko nilagay para ma-online to. limited lang kasi yung mga free eh.
    </p>

    <div class="footer-text mt-4">
        See you on Wednesday. (Not literally ha, I mean etong webpage)
    </div>

</div>

</body>
</html>
<?php
exit;
}
?>