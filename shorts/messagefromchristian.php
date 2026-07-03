<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Message From Christian</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

html,
body{
    height:100%;
    margin:0;
    background:#ffffff;
    font-family:Arial, Helvetica, sans-serif;
}

/* ------------------------------
   Loading Screen
------------------------------ */

#loadingScreen{
    position:fixed;
    inset:0;
    z-index:99999;

    display:flex;
    justify-content:center;
    align-items:center;
    flex-direction:column;

    background:#ffffff;

    transition:opacity .8s ease, visibility .8s ease;
}

#loadingScreen.hide{
    opacity:0;
    visibility:hidden;
}

#loadingAnimation{
    width:220px;
    max-width:65vw;
    height:auto;

    user-select:none;
    pointer-events:none;

    animation:pulse 1.1s ease-in-out infinite;
}

.loading-text{
    margin-top:30px;
    font-size:1rem;
    color:#666;
    text-align:center;
}

/* ------------------------------
   Main Content
------------------------------ */

#mainContent{
    display:none;
    min-height:100vh;

    justify-content:center;
    align-items:center;
}

.message-card{

    max-width:700px;
    width:100%;

    background:#fff;

    padding:40px;

    border-radius:20px;

    box-shadow:0 10px 35px rgba(0,0,0,.08);

    text-align:center;
}

.message-card h1{
    margin-bottom:20px;
}

.message-card p{
    font-size:1.1rem;
    line-height:1.8;
}

/* ------------------------------
   Animation
------------------------------ */

@keyframes pulse{

    0%{
        transform:scale(1);
    }

    50%{
        transform:scale(1.05);
    }

    100%{
        transform:scale(1);
    }

}

/* ------------------------------
   Mobile
------------------------------ */

@media (max-width:768px){

    #loadingAnimation{
        width:170px;
        max-width:75vw;
    }

    .loading-text{
        font-size:.95rem;
        padding:0 20px;
    }

    .message-card{
        padding:25px;
        margin:15px;
    }

    .message-card h1{
        font-size:1.8rem;
    }

    .message-card p{
        font-size:1rem;
    }

}

</style>

</head>
<body>

<!-- ==========================
     Loading Screen
=========================== -->

<div id="loadingScreen">

    <img
        id="loadingAnimation"
        src="assets/ani0.png"
        alt="Loading">

    <div class="loading-text">
        Wait lang tinitimpla ko pa lorilay...
    </div>

</div>

<!-- ==========================
     Main Content
=========================== -->


<script>

document.addEventListener("DOMContentLoaded", function(){

    const frames = [
        "assets/ani0.png",
        "assets/ani1.png",
        "assets/ani2.png",
        "assets/ani3.png"
    ];

    const img = document.getElementById("loadingAnimation");

    let currentFrame = 0;

    // Change image every 120ms
    const frameSpeed = 120;

    const animation = setInterval(function(){

        currentFrame++;

        if(currentFrame >= frames.length){
            currentFrame = 0;
        }

        img.src = frames[currentFrame];

    }, frameSpeed);

    // Redirect after 15 seconds
    setTimeout(function(){

        clearInterval(animation);

        window.location.href = "messagefromchan2.php";

    }, 15000);

});

</script>

</body>
</html>