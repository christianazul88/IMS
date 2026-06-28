<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>For Lorelie ☕</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

:root{
    --paper:#fffdf7;
    --ink:#2b2b2b;
    --coffee:#b57a56;
}

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
/* Spanish Latte Decorations */
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
/* Main Card */
/* -------------------------------- */

.memory-card{

    background:white;

    border:4px solid #222;

    border-radius:25px;

    padding:2rem;

    box-shadow:
    8px 8px 0 #222;

    transform:rotate(-1deg);
}

.title{

    font-size:
    clamp(2.3rem,5vw,3.8rem);

    color:#222;
}

.subtitle{

    color:#666;

    line-height:1.8;
}

.question{

    font-size:1.1rem;

    color:#444;
}

/* -------------------------------- */
/* Input */
/* -------------------------------- */

.form-control{

    height:60px;

    border:3px solid #222;

    border-radius:18px;

    font-size:16px;
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
/* Hint */
/* -------------------------------- */

.message{

    min-height:30px;

    color:#7a4f38;

    font-size:.95rem;
}

/* -------------------------------- */
/* Footer */
/* -------------------------------- */

.footer{

    color:#888;

    font-size:.85rem;
}

/* -------------------------------- */
/* Random Me.png */
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

    padding:.35rem .6rem;

    border-radius:15px;

    font-size:.8rem;

    z-index:1000;

    animation:
    meAppear 5s forwards;
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
Hi, Lorelie.
</h1>

<p class="subtitle mb-4">

I made something for you.<br>
But first...

</p>

<p class="question mb-4">

What's your favorite iced coffee flavor?

</p>

</div>

<input
type="password"
id="password"
class="form-control mb-3"
placeholder="Your answer...">

<div
id="message"
class="message text-center mb-3">

☕ Hint: You order this almost every time.

</div>

<button
id="continueBtn"
class="btn btn-doodle w-100">

Continue

</button>

<div class="footer text-center mt-4">

Some memories fade.<br>
Some favorite coffee orders don't.

</div>

</div>

</div>

</div>

</div>

<script>

/* ----------------------------- */
/* PASSWORD HINTS */
/* ----------------------------- */

let attempts = 0;

const clues = [

"☕ It has 12 letters.",
"☕ Two words. No spaces.",
"☕ The first word is a nationality.",
"☕ The second word is a coffee drink.",
"☕ Starts with 'Spanish'.",
"☕ Okay, I'm helping too much now 😆"

];

const message =
document.getElementById("message");

document
.getElementById("continueBtn")
.addEventListener("click", async () => {

    const password =
    document.getElementById("password")
    .value;

    const response =
    await fetch(
        "check_password.php",
        {
            method:"POST",

            headers:{
                "Content-Type":
                "application/x-www-form-urlencoded"
            },

            body:
            "password=" +
            encodeURIComponent(password)
        }
    );

    const data =
    await response.json();

    if(data.success){

        message.innerHTML =
        "☕ Of course you'd remember that.";

        document.body.classList.add(
            "fade-out"
        );

        setTimeout(() => {

            window.location =
            "pin.php";

        }, 800);

        return;
    }

    message.innerHTML =
    clues[
        Math.min(
            attempts,
            clues.length - 1
        )
    ];

    attempts++;
});

/* ----------------------------- */
/* RANDOM ME PNG */
/* ----------------------------- */

const doodleMessages = [

"👀",
"hehe",
"good luck",
"almost",
"☕",
"still here",
"you got this",
"hi",
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

/* first spawn */
setTimeout(
    spawnMe,
    1500
);

</script>

</body>
</html>