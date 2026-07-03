<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Para kay Lori hay nakooo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

<style>
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:url('assets/bg2.jpg') center/cover no-repeat;
    background-size:cover;
    background-position:center;
    font-family:'Poppins', sans-serif;
}

.card-coffee{
    width:100%;
    max-width:520px;
    background:rgba(255,248,240,.45)
    backdrop-filter:blur(8px);
    border:none;
    border-radius:25px;
    box-shadow:0 20px 40px rgba(0,0,0,.25);
    padding:50px 40px;
}

.coffee-icon{
    font-size:55px;
}

h1{
    font-family:'Playfair Display', serif;
    color:#5A3E2B;
}

.music-label{
    color:#6c5b4c;
    font-size:1.05rem;
    margin-top:20px;
    margin-bottom:35px;
    line-height:1.7;
}

.btn-coffee{
    background:#6F4E37;
    color:white;
    border:none;
    border-radius:50px;
    padding:14px 45px;
    font-size:1.1rem;
    transition:.3s;
}

.btn-coffee:hover{
    background:#5A3E2B;
    transform:translateY(-2px);
}

.footer-text{
    margin-top:35px;
    color:#8d7a69;
    font-size:.9rem;
    font-style:italic;
}
</style>
</head>

<body>

<div class="card-coffee text-center">

    <div class="coffee-icon mb-3">
        ☕
    </div>

    <h1 class="mb-3">
        Timpla ka muna, medyo short to boi. thanks...
    </h1>

    <div class="music-label">
        🎵 Add tayo ng kaartehan kahit galit ka pa din.  
    </div>

    <button class="btn btn-coffee" id="playBtn">
        ▶ Play
    </button>

    <div class="footer-text">
        Milo ka muna Lori.
    </div>

</div>

<script>
document.getElementById("playBtn").addEventListener("click", function () {

    // Optional tiny animation
    this.innerHTML = "Loading...";
    this.disabled = true;

    setTimeout(function () {
        window.location.href = "messagefromblue.php";
    }, 400);

});
</script>

</body>
</html>