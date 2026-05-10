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
            <img src="${imageUrl}" class="rounded me-2" alt="Product Image" style="width: 30px; height: 30px;">
            <strong class="me-auto">${title}</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
            <div class="text-end"><a href="${linkUrl}" class="btn btn-primary mt-2" target="_blank">View Product</a></div>
        </div>
    `;

    toastContainer.appendChild(toast);

    const toastElement = new bootstrap.Toast(toast, { autohide: false }); // Prevent auto-hide
    toastElement.show();
}

// Function to fetch notifications from the API and display them
async function fetchNotifications(apiEndpoint) {
    try {
        const response = await fetch(apiEndpoint);
        if (!response.ok) throw new Error('Failed to fetch notifications.');

        const notifications = await response.json();
        notifications.forEach(({ title, message, imageUrl, linkUrl }) => {
            showBootstrapToast(title, message, imageUrl, linkUrl);
        });
    } catch (error) {
        console.error('Error fetching notifications:', error);
        showBootstrapToast('Error', 'Failed to fetch notifications. Please try again later.', '', '');
    }
}

// Automatically fetch notifications when the page loads
window.onload = function() {
    fetchNotifications('../API/undersafety.php'); // Your API endpoint
};
