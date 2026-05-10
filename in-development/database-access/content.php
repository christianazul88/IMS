<style>
    body {
        font-family: sans-serif;
        text-align: center;
        margin-top: 100px;
    }
    #progress {
        display: none;
        margin-top: 20px;
    }
    #downloadBtn {
        padding: 12px 24px;
        font-size: 16px;
        cursor: pointer;
    }
</style>

<h2>Click to Download Backup</h2>
    <button id="downloadBtn">Download Backup</button>
    <div id="progress">🔄 Creating backup... Please wait.</div>

    <script>
        document.getElementById("downloadBtn").addEventListener("click", () => {
            const btn = document.getElementById("downloadBtn");
            const progress = document.getElementById("progress");

            btn.disabled = true;
            progress.style.display = "block";

            fetch("backup.php")
                .then(res => {
                    if (!res.ok) throw new Error("Backup failed");
                    return res.blob();
                })
                .then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "backup.zip";
                    a.click();
                    URL.revokeObjectURL(url);
                })
                .catch(err => alert("❌ Error: " + err.message))
                .finally(() => {
                    btn.disabled = false;
                    progress.style.display = "none";
                });
        });
    </script>