// Fetch expense distribution data
async function fetchExpenseData() {
    try {
        const response = await fetch('api/get_expense_distribution.php');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching expense data:', error);
        return null;
    }
}

// Fetch monthly trend data
async function fetchTrendData() {
    try {
        const response = await fetch('api/get_monthly_trend.php');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching trend data:', error);
        return null;
    }
}

// Initialize expense distribution chart
async function initExpenseChart() {
    const data = await fetchExpenseData();
    if (!data) return;

    const ctx = document.getElementById('expenseChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#36A2EB'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}

// Initialize monthly trend chart
async function initTrendChart() {
    const data = await fetchTrendData();
    if (!data) return;

    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Income',
                    data: data.income,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true
                },
                {
                    label: 'Expenses',
                    data: data.expenses,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Initialize all charts when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initExpenseChart();
    initTrendChart();
});

// Add transaction form submission
const addTransactionForm = document.getElementById('addTransactionForm');
if (addTransactionForm) {
    addTransactionForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('api/add_transaction.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message
                showAlert('success', 'Transaction added successfully!');
                // Refresh the page to show new data
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('danger', result.message || 'Error adding transaction');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while adding the transaction');
        }
    });
}

// Show alert message
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Format currency input
const currencyInputs = document.querySelectorAll('input[type="number"][data-currency]');
currencyInputs.forEach(input => {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9.]/g, '');
        if (value) {
            value = parseFloat(value).toFixed(2);
            e.target.value = value;
        }
    });
});

// Initialize tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
}); 