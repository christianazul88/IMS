<!doctype html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Romantic Theme</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../css/style.css">

</head>

<body>

<div id="stars"></div>
<div id="petals"></div>
<div id="mist"></div>

<section class="hero">

    <div class="container">

        <div class="content-box">

            <h1>Prove mo muna na ikaw si Lori.</h1>

            <hr>

            <div id="content">

                <form action="config/login.php" method="POST">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" placeholder="Enter your first name lods." name="name">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="pw">Password</label>
                            <input type="password" class="form-control" placeholder="middle name mo lods" name="pw">
                        </div>
                        <div class="col-12 mb-3">
                            <button class="btn btn-outline-light" type="submit">Continue</button>
                        </div>
                    </div>
                </form>

            </div>

        </div>

    </div>

</section>

<script src="../js/script.js"></script>

</body>
</html>