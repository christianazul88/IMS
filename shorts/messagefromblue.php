<?php
session_start();

$visitFile = "visit.json";

// Create the file if it doesn't exist
if (!file_exists($visitFile)) {
    file_put_contents($visitFile, json_encode([
        "readmessage" => 0
    ], JSON_PRETTY_PRINT));
}

// Read current data
$data = json_decode(file_get_contents($visitFile), true);

// If the key doesn't exist, initialize it
if (!isset($data['readmessage'])) {
    $data['readmessage'] = 0;
}

// Increment
$data['readmessage']++;

// Save back to JSON
file_put_contents($visitFile, json_encode($data, JSON_PRETTY_PRINT));

?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Message From Blue</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:
        linear-gradient(rgba(0,0,0,.35), rgba(0,0,0,.35)),
        url("assets/bg1.jpg") center center / cover no-repeat fixed;
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Arial, sans-serif;
}

.container-box{
    max-width:700px;
    padding:40px;
    text-align:center;
    background:rgba(255,255,255,.75);
    backdrop-filter:blur(8px);
    -webkit-backdrop-filter:blur(8px);
    border-radius:20px;
}


#tapOverlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.8);
    color:#fff;
    display:none;
    justify-content:center;
    align-items:center;
    flex-direction:column;
    text-align:center;
    cursor:pointer;
    z-index:9999;
}

#tapOverlay h2{
    margin-bottom:10px;
}

#title{
    opacity:0;
    transition:opacity 1.5s ease;
}

#title.show{
    opacity:1;
}
</style>

</head>
<body>

<!-- Background Music -->
<audio id="bgMusic" autoplay loop>
    <source src="song.mp3" type="audio/mpeg">
    Your browser does not support audio.
</audio>

<div class="container-box">
    <h1 id="title" style="display:none;">Max mo volume ng iPhone mo please!!!</h1>

    <p id="message" style="display:none;"></p>
</div>

<div id="tapOverlay">
    <h2>🎵 Tap Anywhere</h2>
    <p>To start the music.</p>
</div>

<script>

const music = document.getElementById("bgMusic");
const overlay = document.getElementById("tapOverlay");

// Try autoplay
window.addEventListener("load", () => {

    let promise = music.play();

    if (promise !== undefined) {

        promise
        .then(() => {
            overlay.style.display = "none";
            // startTypewriter();
        })
        .catch(() => {
            overlay.style.display = "flex";
        });

    }

});

// If autoplay is blocked (mostly iPhone Safari)
overlay.addEventListener("click", () => {

    music.play().then(() => {

        music.currentTime = 2;

        overlay.style.display = "none";

        setTimeout(startTypewriter, 1800);

    })

});

const fullMessage = `Hi Lori, alam kong macucurious mga tao jan sa note, kaya secure ko na lang dito. Hindi kita kapiling pero... nvm.

This is the first time na binilhan kita ng Spanish Latte, and I hope you like it. PS: lagyan mo na lang yelo ha.

Goodluck kung saan ka man next na magwowork, do it happy ha... lets chase the career we really want. I know you can do it, and I will pray for that to happen. 

I am really sorry, I failed to protect you. pasensya ka na talaga, God knows I tried, even acting that I hate you in front of them, so they would stop bringing up your name.
and that also helped me to move forward.... or sort of? tae kase bat may pa-hug pa, sayo ko lang naranasan yon, at ikaw lang ginusto ko during those times and I do realized na until now LOka Loka ka. sa http://lpoims.com/tolorilay/ na lang yung iba. huling effort ko na sayo yon, and di ko na ulit gagawin sa kahit kanino(unless may magpapagawa na client).

When Over October sang this nung Summer Blast, ikaw iniisip ko, malas ko. anyway....
Matulog ka araw-araw at gabi-gabi ng mahimbing...
One last time... 
Aking Wabi-sabi.

Till we meet again, just maybe..

-From Chan... :'(
`;

function startTypewriter() {

    const title = document.getElementById("title");
    const message = document.getElementById("message");

    title.style.display = "block";

    setTimeout(() => {
        title.classList.add("show");
    }, 100);
    message.style.display = "block";

    let i = 0;

    function type() {

        if (i >= fullMessage.length) {
            return;
        }

        const char = fullMessage.charAt(i);

        if (char === "\n") {
            message.innerHTML += "<br>";
        } else {
            message.innerHTML += char;
        }

        i++;

        // Base typing speed (slow and natural)
        let delay = 120 + Math.random() * 80;

        // Longer pauses after punctuation
        if (char === "." || char === "," || char === "!" || char === "?") {
            delay += 500;
        }

        // Short pause after a new paragraph
        if (char === "\n") {
            delay += 700;
        }

        setTimeout(type, delay);

    }

    type();

}


// setTimeout(startTypewriter, 1800);
</script>

</body>
</html>