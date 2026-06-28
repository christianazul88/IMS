<?php

session_start();

if (!isset($_SESSION['password_verified'])) {
    header("Location:index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>One More Thing ☕</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

:root{
    --paper:#fffdf7;
    --ink:#2b2b2b;
}

/* -------------------------------- */
/* NOTEBOOK BACKGROUND */
/* -------------------------------- */

body{

    min-height:100svh;
    overflow:hidden;

    background:
    repeating-linear-gradient(
        to bottom,
        #fffdf7,
        #fffdf7 32px,
        #ececec 33px
    );

    font-family:
    "Comic Sans MS",
    "Trebuchet MS",
    cursive;

    position:relative;
}

/* -------------------------------- */
/* COFFEE DECORATIONS */
/* -------------------------------- */

.floating-coffee{
    position:fixed;
    width:120px;
    opacity:.12;
    pointer-events:none;

    animation:
    floatCoffee 8s ease-in-out infinite;
}

.c1{
    top:4%;
    left:2%;
}

.c2{
    top:12%;
    right:4%;
}

.c3{
    bottom:10%;
    left:5%;
}

.c4{
    bottom:8%;
    right:6%;
}

.c5{
    top:45%;
    left:45%;
}

@keyframes floatCoffee{

    0%,100%{
        transform:
        translateY(0px)
        rotate(-5deg);
    }

    50%{
        transform:
        translateY(-15px)
        rotate(5deg);
    }
}

/* -------------------------------- */
/* CARD */
/* -------------------------------- */

.memory-card{

    background:white;

    border:4px solid #222;

    border-radius:25px;

    padding:2rem;

    box-shadow:
    8px 8px 0 #222;

    transform:rotate(1deg);
}

.title{

    font-size:
    clamp(2.2rem,5vw,3.5rem);

    color:#222;
}

.subtitle{

    color:#666;

    line-height:1.8;
}

/* -------------------------------- */
/* INPUT */
/* -------------------------------- */

.form-control{

    height:60px;

    border:3px solid #222;

    border-radius:18px;

    font-size:1.3rem;

    text-align:center;

    letter-spacing:.4rem;
}

.form-control:focus{

    border-color:#222;

    box-shadow:none;
}

.btn-doodle{

    height:60px;

    border:3px solid #222;

    border-radius:18px;

    background:#ffd56f;

    color:#222;

    font-weight:bold;
}

.btn-doodle:hover{

    background:#ffca3a;
}

/* -------------------------------- */
/* HINT AREA */
/* -------------------------------- */

.message{

    min-height:35px;

    color:#7a4f38;

    font-size:.95rem;
}

/* -------------------------------- */
/* FOOTER */
/* -------------------------------- */

.footer{

    color:#888;

    font-size:.85rem;
}

/* -------------------------------- */
/* RANDOM ME PNG */
/* -------------------------------- */

.popup-me{

    position:fixed;

    width:90px;

    z-index:999;

    pointer-events:none;

    animation:
    meAppear 5s forwards;
}

.popup-text{

    position:fixed;

    background:white;

    border:2px solid #222;

    border-radius:15px;

    padding:.35rem .6rem;

    font-size:.8rem;

    z-index:1000;

    animation:
    meAppear 5s forwards;
}

/* -------------------------------- */
/* SUCCESS SCENE */
/* -------------------------------- */

#successScene{

    position:fixed;

    inset:0;

    background:
    rgba(255,253,247,.96);

    display:flex;

    justify-content:center;

    align-items:center;

    opacity:0;

    visibility:hidden;

    z-index:99999;

    transition:
    opacity .8s ease;
}

#successScene.show{

    opacity:1;

    visibility:visible;
}

.success-content{

    text-align:center;
}

.success-me{

    width:220px;

    animation:
    bounceMe 1.2s ease infinite;
}

.success-bubble{

    display:inline-block;

    margin-top:20px;

    background:white;

    border:4px solid #222;

    border-radius:25px;

    padding:1rem 1.5rem;

    font-size:1.2rem;

    box-shadow:
    5px 5px 0 #222;
}

@keyframes bounceMe{

    0%,100%{

        transform:
        translateY(0);
    }

    50%{

        transform:
        translateY(-10px);
    }
}

@keyframes meAppear{

    0%{
        opacity:0;
        transform:
        scale(.5)
        rotate(-10deg);
    }

    15%{
        opacity:1;
        transform:
        scale(1)
        rotate(5deg);
    }

    85%{
        opacity:1;
    }

    100%{
        opacity:0;
        transform:
        translateY(-20px);
    }
}

/* -------------------------------- */
/* PAGE FADE */
/* -------------------------------- */

.fade-out{

    opacity:0;

    transition:
    opacity .8s ease;
}

</style>

</head>

<body>

<!-- Coffee Decorations -->

<img src="assets/spanishlatte.png" class="floating-coffee c1">
<img src="assets/spanishlatte.png" class="floating-coffee c2">
<img src="assets/spanishlatte.png" class="floating-coffee c3">
<img src="assets/spanishlatte.png" class="floating-coffee c4">
<img src="assets/spanishlatte.png" class="floating-coffee c5">

<div class="container">

<div class="row justify-content-center align-items-center min-vh-100">

<div class="col-11 col-md-8 col-lg-5">

<div class="memory-card">

<div class="text-center">

<h1 class="title mb-3">
One More Thing...
</h1>

<p class="subtitle mb-4">

Of course you'd remember that.<br>
Now there's just one last thing.

</p>

<p class="text-secondary">

Enter the six digits you know by heart.

</p>

</div>

<input
type="password"
maxlength="6"
id="pin"
class="form-control mb-3"
placeholder="••••••">

<div
id="message"
class="message text-center mb-3">

☕ Hint: It's six digits.

</div>

<button
id="continueBtn"
class="btn btn-doodle w-100">

Continue

</button>

<div class="footer text-center mt-4">

You're closer than you think.

</div>

</div>

</div>

</div>

</div>

<script>

/* ------------------------- */
/* PIN VALIDATION */
/* ------------------------- */

let attempts = 0;

const clues = [

"☕ It's six digits.",
"☕ Starts with 05.",
"☕ The first three digits are 050.",
"☕ You're halfway there.",
"☕ Almost.",
"☕ At this point I'm helping too much 😆"

];

const message =
document.getElementById("message");

document
.getElementById("continueBtn")
.addEventListener("click", async () => {

    const pin =
    document.getElementById("pin")
    .value;

    const response =
    await fetch(
        "check_pin.php",
        {
            method:"POST",

            headers:{
                "Content-Type":
                "application/x-www-form-urlencoded"
            },

            body:
            "pin=" +
            encodeURIComponent(pin)
        }
    );

    const data =
    await response.json();

    const finalMessages = [

        "Nice.<br>You made it.",

        "Of course you did.<br>☕",

        "Good job.<br>Now keep going.",

        "See?<br>You remembered.",

        "Welcome.<br>I've been waiting."
    ];

    if(data.success){

        const scene =
        document.getElementById(
            "successScene"
        );

        scene.classList.add(
            "show"
        );

        setTimeout(() => {

            window.location =
            "dashboard.php";

        }, 2500);

        return;
    }


    document.querySelector(
    ".success-bubble"
    ).innerHTML =
    finalMessages[
    Math.floor(
    Math.random() *
    finalMessages.length
    )
    ];
    message.innerHTML =
    clues[
        Math.min(
            attempts,
            clues.length - 1
        )
    ];

    attempts++;
});

/* ------------------------- */
/* RANDOM ME POPUPS */
/* ------------------------- */

const doodleMessages = [

"👀",
"hehe",
"almost",
"you got this",
"☕",
"still here",
"good luck",
"nice try 😆"

];

function spawnMe(){

    const x =
    Math.random() *
    (window.innerWidth - 120);

    const y =
    Math.random() *
    (window.innerHeight - 120);

    const img =
    document.createElement("img");

    img.src =
    "assets/me.png";

    img.className =
    "popup-me";

    img.style.left =
    x + "px";

    img.style.top =
    y + "px";

    document.body.appendChild(img);

    const bubble =
    document.createElement("div");

    bubble.className =
    "popup-text";

    bubble.innerText =
    doodleMessages[
        Math.floor(
            Math.random() *
            doodleMessages.length
        )
    ];

    bubble.style.left =
    (x + 20) + "px";

    bubble.style.top =
    (y - 25) + "px";

    document.body.appendChild(
        bubble
    );

    setTimeout(() => {

        img.remove();
        bubble.remove();

    }, 5000);
}

setInterval(
    spawnMe,
    4500
);

setTimeout(
    spawnMe,
    1500
);

</script>

<div id="successScene">

    <div class="success-content">

        <img
            src="assets/me.png"
            class="success-me"
            alt="me">

        <div class="success-bubble">
            Nice.<br>
            You made it.
        </div>

    </div>

</div>



</body>
</html>