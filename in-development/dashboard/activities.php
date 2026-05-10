<!-- <div id="activities"></div>


<script>
    // Load activity_display.php into #activities on page load
    $('#activities').load('activity_display.php');

    function checkForNewActivity() {
        $.get('../config/check_activity.php', function(response) {
            if (response.includes("New rows added")) {
                // Reload #activities
                $('#activities').load('activity_display.php');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error checking activity:', error);
        });
    }

    // Check every 30 seconds
    setInterval(checkForNewActivity, 30000);
</script> -->