// Session timeout warning
function checkSessionStatus() {
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'warning') {
                showTimeoutWarning();
            }
        })
        .catch(error => console.error('Error checking session:', error));
}

function showTimeoutWarning() {
    const warningDiv = document.createElement('div');
    warningDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3';
    warningDiv.style.zIndex = '9999';
    warningDiv.innerHTML = `
        <strong>Session Timeout Warning!</strong> Your session will expire in 5 minutes.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(warningDiv);
    
    // Auto dismiss after 5 minutes
    setTimeout(() => {
        warningDiv.remove();
    }, 5 * 60 * 1000);
}

// Check session status every minute
setInterval(checkSessionStatus, 60 * 1000);

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}); 