<?php
/* dashboard.php */
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Lorelie's Playlist ☕</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    min-height:100vh;
    overflow-x:hidden;
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
}

.floating{
    position:fixed;
    width:120px;
    opacity:.12;
    pointer-events:none;
    z-index:1;
}

.c1{top:4%;left:2%;}
.c2{top:12%;right:4%;}
.c3{bottom:10%;left:5%;}
.c4{bottom:8%;right:6%;}
.c5{top:45%;left:45%;}

.doodle-card{
    background:white;
    border:4px solid #222;
    border-radius:24px;
    box-shadow:8px 8px 0 #222;
}

.song-card{
    cursor:pointer;
    transition:.2s;
    border:3px solid transparent;
}

.song-card:hover{
    transform:translateY(-4px);
}

.song-selected{
    border:3px solid #1db954 !important;
}

.song-cover{
    width:100%;
    aspect-ratio:1/1;
    object-fit:cover;
    border-radius:12px;
}

.loading-frame{
    width:220px;
    max-width:100%;
}

#dashboardContent{
    display:none;
}

.popup-me{
    position:fixed;
    width:90px;
    pointer-events:none;
    z-index:9999;
    animation:fadePop 5s forwards;
}

.popup-text{
    position:fixed;
    background:white;
    border:2px solid #222;
    border-radius:16px;
    padding:.35rem .6rem;
    font-size:.8rem;
    z-index:10000;
    animation:fadePop 5s forwards;
}

@keyframes fadePop{
    0%{opacity:0;transform:scale(.5);}
    15%{opacity:1;transform:scale(1);}
    85%{opacity:1;}
    100%{opacity:0;transform:translateY(-20px);}
}

</style>

</head>
<body>

<img src="assets/spanishlatte.png" class="floating c1">
<img src="assets/spanishlatte.png" class="floating c2">
<img src="assets/spanishlatte.png" class="floating c3">
<img src="assets/spanishlatte.png" class="floating c4">
<img src="assets/spanishlatte.png" class="floating c5">

<audio id="player"></audio>

<!-- Loading Modal -->

<div
class="modal show"
id="loadingModal"
style="display:block;background:rgba(255,255,255,.95);">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content doodle-card">

<div class="modal-body text-center p-5">

<img
id="loadingFrame"
src="assets/ani0.png"
class="loading-frame">

<h3 class="mt-4">
Preparing something...
</h3>

<p id="loadingMessage">
Brewing a Spanish Latte ☕
</p>

<div class="progress mt-4">

<div
id="loadingBar"
class="progress-bar"
style="width:0%">
</div>

</div>

<div
id="countdown"
class="mt-3">
15 seconds remaining
</div>

</div>

</div>

</div>

</div>

<!-- Spotify Modal -->

<div
class="modal"
id="songModal"
tabindex="-1">

<div class="modal-dialog modal-xl modal-dialog-scrollable">

<div class="modal-content doodle-card">

<div class="modal-header">
<h4>🎵 Choose A Song</h4>
</div>

<div class="modal-body">

<p>
Pick anything you want.
(You definitely have a choice 😆)
</p>

<div
class="row g-3"
id="songGrid">
</div>

<div class="text-center mt-4">

<div id="selectedSongText">
No song selected
</div>

<button
id="confirmSong"
class="btn btn-success mt-3"
disabled>

Confirm Selection

</button>

</div>

</div>

</div>

</div>

</div>

<!-- Reveal Modal -->

<div
class="modal"
id="revealModal"
tabindex="-1">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content doodle-card">

<div class="modal-body text-center p-5">

<img
src="assets/me.png"
width="180">

<h3 class="mt-4">
🎧 Wait...
</h3>

<p id="revealText">
Loading your selection...
</p>

</div>

</div>

</div>

</div>

<!-- Dashboard -->

<div
id="dashboardContent"
class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="doodle-card p-4">

<h1>
Hi, Lorelie ☕
</h1>

<p>
The music is playing.
You may continue scrolling.
</p>

<hr>

<h4>
Now Playing
</h4>

<div id="nowPlaying">
Pusong Ligaw
</div>

</div>

</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* -------------------------------- */
/* RANDOM ME PNG POPUPS */
/* -------------------------------- */

const doodleMessages = [

    "👀",
    "hehe",
    "☕",
    "still here",
    "good luck",
    "almost",
    "you got this",
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

    bubble.innerHTML =
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

/* -------------------------------- */
/* LOADING MODAL */
/* -------------------------------- */

const frames = [

    "assets/ani0.png",
    "assets/ani1.png",
    "assets/ani2.png",
    "assets/ani3.png"

];

let frameIndex = 0;

setInterval(() => {

    document
    .getElementById(
        "loadingFrame"
    )
    .src =
    frames[
        frameIndex %
        frames.length
    ];

    frameIndex++;

}, 250);

const loadingMessages = [

    "☕ Brewing a Spanish Latte...",
    "☕ Looking for doodles...",
    "☕ Gathering screenshots...",
    "☕ Finding memories...",
    "☕ Almost ready...",
    "☕ One more moment..."

];

let loadingMsgIndex = 0;

setInterval(() => {

    document
    .getElementById(
        "loadingMessage"
    )
    .innerHTML =
    loadingMessages[
        loadingMsgIndex %
        loadingMessages.length
    ];

    loadingMsgIndex++;

}, 2500);

let seconds = 15;

const loadingTimer =
setInterval(() => {

    seconds--;

    document
    .getElementById(
        "countdown"
    )
    .innerHTML =
    seconds +
    " seconds remaining";

    document
    .getElementById(
        "loadingBar"
    )
    .style.width =
    ((15 - seconds) / 15) * 100 + "%";

    if(seconds <= 0){

        clearInterval(
            loadingTimer
        );

        document
        .getElementById(
            "loadingModal"
        )
        .remove();

        showSongModal();
    }

}, 1000);

/* -------------------------------- */
/* SONG SELECTION */
/* -------------------------------- */

let selectedSong = null;

function showSongModal(){

    const modal =
    new bootstrap.Modal(
        document.getElementById(
            "songModal"
        )
    );

    modal.show();

    const grid =
    document.getElementById(
        "songGrid"
    );

    grid.innerHTML = "";

    for(let i=1;i<=20;i++){

        grid.innerHTML += `

        <div class="col-6 col-md-4 col-lg-3">

            <div
            class="card song-card h-100"
            data-song="${i}">

                <img
                src="assets/${i}.jpg"
                class="song-cover">

                <div class="card-body">

                    <strong>
                    Song ${i}
                    </strong>

                    <div class="small text-muted">
                    Track ${i}
                    </div>

                </div>

            </div>

        </div>

        `;
    }

    document
    .querySelectorAll(
        ".song-card"
    )
    .forEach(card => {

        card.addEventListener(
            "click",
            () => {

                document
                .querySelectorAll(
                    ".song-card"
                )
                .forEach(c =>
                    c.classList.remove(
                        "song-selected"
                    )
                );

                card.classList.add(
                    "song-selected"
                );

                selectedSong =
                card.dataset.song;

                document
                .getElementById(
                    "selectedSongText"
                )
                .innerHTML =
                "Selected: Song " +
                selectedSong;

                document
                .getElementById(
                    "confirmSong"
                )
                .disabled = false;
            }
        );

    });

}

/* -------------------------------- */
/* REVEAL */
/* -------------------------------- */

document
.getElementById(
    "confirmSong"
)
.addEventListener(
    "click",
    () => {

        startPlaylist();
        player.volume = 0;
        let volume = 0;

        const fade = setInterval(() => {

            volume += 0.05;

            player.volume = volume;

            if(volume >= 1){

                clearInterval(fade);

            }

        },200);

        bootstrap
        .Modal
        .getInstance(
            document.getElementById(
                "songModal"
            )
        )
        .hide();

        const reveal =
        new bootstrap.Modal(
            document.getElementById(
                "revealModal"
            )
        );

        reveal.show();

        const revealText =
        document.getElementById(
            "revealText"
        );

        setTimeout(() => {

            revealText.innerHTML =
            "You picked Song " +
            selectedSong;

        }, 1000);

        setTimeout(() => {

            revealText.innerHTML =
            "Good choice. Pero mas gusto ko to.";

        }, 4000);

        

        setTimeout(() => {

            reveal.hide();

            document
            .getElementById(
                "dashboardContent"
            )
            .style.display =
            "block";

            

        }, 8500);

    }
);

/* -------------------------------- */
/* PLAYLIST */
/* -------------------------------- */

const playlist = [

    
    "songs/Panaginip-nicole.mp3",
    
    "songs/pusongligaw.mp3",
    "songs/song2.mp3",
    "songs/song3.mp3",
    "songs/song4.mp3",
    "songs/song5.mp3",
    "songs/song6.mp3",
    "songs/song7.mp3",
    "songs/song8.mp3",
    "songs/song9.mp3",
    "songs/song10.mp3",
    "songs/song11.mp3",
    "songs/song12.mp3",
    "songs/song13.mp3",
    "songs/song14.mp3",
    "songs/song15.mp3",
    "songs/song16.mp3",
    "songs/song17.mp3",
    "songs/song18.mp3",
    "songs/song19.mp3",
    "songs/song20.mp3"

];

let currentTrack = 0;

const player =
document.getElementById(
    "player"
);

function startPlaylist(){

    currentTrack = 0;

    player.src =
    playlist[currentTrack];

    player.play();

    document
    .getElementById(
        "nowPlaying"
    )
    .innerHTML =
    "Panaginip - Nicole";
}

player.addEventListener(
    "ended",
    () => {

        currentTrack++;

        if(
            currentTrack <
            playlist.length
        ){

            player.src =
            playlist[
                currentTrack
            ];

            player.play();

            let trackName =
            playlist[
                currentTrack
            ]
            .replace(
                "songs/",
                ""
            );

            document
            .getElementById(
                "nowPlaying"
            )
            .innerHTML =
            trackName;
        }
    }
);

</script>

</body>
</html>
