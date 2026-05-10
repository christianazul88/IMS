<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap Toast Notifications Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Toast Container (added dynamically by JavaScript) -->
    
    <!-- Optional Content -->
    <div class="container mt-5">
        <h1>Test Bootstrap Toast Notifications</h1>
        <p>Make sure the API endpoints return valid JSON data for the notifications.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Prolong Items JS -->
    <script>
        // Function to create and show a Bootstrap toast
        function showBootstrapToast(title, message, imageUrl, linkUrl) {
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            const toastId = `toast-${Date.now()}`;
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = 'toast';
            toast.role = 'alert';
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <img src="${imageUrl}" class="rounded me-2" alt="Image" style="width: 30px; height: 30px;">
                    <strong class="me-auto">${title}</strong>
                    <small>Just now</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                    <div class="text-end"><a href="${linkUrl}" class="btn btn-primary mt-2" target="_blank">View</a></div>
                </div>
            `;

            toastContainer.appendChild(toast);

            const toastElement = new bootstrap.Toast(toast, { autohide: false }); // Prevent auto-hide
            toastElement.show();
        }

        // Function to fetch notifications from multiple API endpoints and display them
        async function fetchNotificationsFromEndpoints(apiEndpoints) {
            try {
                // Fetch data from all API endpoints
                const responses = await Promise.all(apiEndpoints.map(endpoint => fetch(endpoint)));

                // Check if any response failed
                if (responses.some(response => !response.ok)) {
                    throw new Error('Failed to fetch notifications from one or more endpoints.');
                }

                // Parse JSON from all responses
                const notificationsArrays = await Promise.all(responses.map(response => response.json()));

                // Flatten the notifications arrays
                const allNotifications = notificationsArrays.flat();

                // Display notifications
                allNotifications.forEach(({ title, message, imageUrl, linkUrl }) => {
                    showBootstrapToast(title, message, imageUrl, linkUrl);
                });
            } catch (error) {
                console.error('Error fetching notifications:', error);
                showBootstrapToast('Error', 'Failed to fetch notifications. Please try again later.', '', '');
            }
        }

        // Automatically fetch notifications when the page loads
        window.onload = function() {
            const apiEndpoints = ['../API/prolongnotif.php', '../API/undersafety.php'];
            fetchNotificationsFromEndpoints(apiEndpoints);
        };
    </script>
</body>
</html>
