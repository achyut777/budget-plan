// Filter categories based on selected type
document.getElementById('type').addEventListener('change', function() {
    const selectedType = this.value;
    const categorySelect = document.getElementById('category');
    const options = categorySelect.options;

    // First, hide all options except the placeholder
    for (let i = 0; i < options.length; i++) {
        if (i === 0) continue; // Skip the placeholder option
        const option = options[i];
        if (selectedType === '') {
            option.style.display = '';
        } else if (option.dataset.type === selectedType) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }

    // Reset category selection
    categorySelect.value = '';
});

// Format currency input
const currencyInputs = document.querySelectorAll('input[data-currency]');
currencyInputs.forEach(input => {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9.]/g, '');
        if (value) {
            value = parseFloat(value).toFixed(2);
            e.target.value = value;
        }
    });
});

// Handle transaction form submission
const addTransactionForm = document.getElementById('addTransactionForm');
if (addTransactionForm) {
    addTransactionForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const type = document.getElementById('type').value;
        const categorySelect = document.getElementById('category');
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        
        // Validate type and category match
        if (selectedOption && selectedOption.dataset.type !== type) {
            showAlert('danger', 'Selected category does not match the transaction type');
            return;
        }
        
        try {
            const response = await fetch('api/add_transaction.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', 'Transaction added successfully!');
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

// Handle transaction deletion
document.querySelectorAll('.delete-transaction').forEach(button => {
    button.addEventListener('click', async function() {
        if (confirm('Are you sure you want to delete this transaction?')) {
            const transactionId = this.dataset.id;
            
            try {
                const response = await fetch('api/delete_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: transactionId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Transaction deleted successfully!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Error deleting transaction');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting the transaction');
            }
        }
    });
});

// Handle transaction editing
document.querySelectorAll('.edit-transaction').forEach(button => {
    button.addEventListener('click', async function() {
        const transactionId = this.dataset.id;
        
        try {
            const response = await fetch(`api/get_transaction.php?id=${transactionId}`);
            const transaction = await response.json();
            
            if (transaction.success) {
                // Populate form with transaction data
                document.getElementById('type').value = transaction.data.type;
                document.getElementById('category').value = transaction.data.category_id;
                document.getElementById('amount').value = transaction.data.amount;
                document.getElementById('date').value = transaction.data.date;
                document.getElementById('description').value = transaction.data.description;
                
                // Add transaction ID to form
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = transactionId;
                addTransactionForm.appendChild(idInput);
                
                // Change form submit button text
                const submitButton = addTransactionForm.querySelector('button[type="submit"]');
                submitButton.textContent = 'Update Transaction';
                
                // Scroll to form
                addTransactionForm.scrollIntoView({ behavior: 'smooth' });
            } else {
                showAlert('danger', result.message || 'Error fetching transaction data');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while fetching transaction data');
        }
    });
});

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

// Initialize tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
}); 