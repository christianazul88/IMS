<?php

$visitFile = "visit.json";

// Create the file if it doesn't exist
if (!file_exists($visitFile)) {
    file_put_contents($visitFile, json_encode([
        "loginpage" => 0
    ], JSON_PRETTY_PRINT));
}

// Read current data
$data = json_decode(file_get_contents($visitFile), true);

// If the key doesn't exist, initialize it
if (!isset($data['loginpage'])) {
    $data['loginpage'] = 0;
}

// Increment
$data['loginpage']++;

// Save back to JSON
file_put_contents($visitFile, json_encode($data, JSON_PRETTY_PRINT));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Wabi Sabi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f8f9fa;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-card{
    max-width:400px;
    width:100%;
    border:none;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.card-body{
    padding:40px;
}

#message{
    display:none;
}

.clues{
    font-size:.95rem;
    color:#6c757d;
}
</style>

</head>
<body>

<div class="card login-card">
    <div class="card-body">

        <h3 class="text-center mb-3">🔒 Secret Message</h3>

        <p class="text-center text-muted">
            Enter the password to continue.
        </p>

        <div class="mb-3">
            <input
                type="password"
                id="password"
                class="form-control form-control-lg"
                placeholder="Enter password"
                autofocus
            >
        </div>

        <button
            class="btn btn-dark w-100"
            onclick="checkPassword()"
        >
            Unlock
        </button>

        <div id="message" class="alert alert-danger mt-3 mb-0"></div>

        <div class="mt-4 clues">
            <strong>Clues:</strong>
            <ul class="mb-0">
                <li>Treat mo to na parang ATM</li>
                <li id="extraClue"></li>
            </ul>
        </div>

    </div>
</div>

<script>
//==================================================
// CHANGE YOUR PASSWORD HERE
//==================================================
const correctPassword = "050444";

//==================================================
// ADD YOUR OWN EXTRA CLUE HERE
// Leave blank if none.
//==================================================
const additionalClue = "ATM pin mo to sa BDO, sakin ka nagpapawithdraw noon remember?";

// Show additional clue only if not empty
document.getElementById("extraClue").textContent = additionalClue;

function checkPassword(){

    const enteredPassword = document.getElementById("password").value;
    const message = document.getElementById("message");

    if(enteredPassword === correctPassword){

        window.location.href = "messagefromchristian.php";

    }else{

        message.style.display = "block";
        message.textContent = "Incorrect password. Try again.";
    }

}

// Press Enter to submit
document.getElementById("password").addEventListener("keypress", function(e){
    if(e.key === "Enter"){
        checkPassword();
    }
});
</script>

</body>
</html>