/**
 * Indian Rupee (₹) Currency Formatting Functions
 * Ensures consistent currency display in JavaScript
 */

// Format amount in Indian Rupees with proper symbol and formatting
function formatINR(amount, decimals = 2, showSymbol = true) {
    const num = parseFloat(amount) || 0;
    const formatted = num.toLocaleString('en-IN', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
    return showSymbol ? '₹' + formatted : formatted;
}

// Format amount for Indian number system (lakhs, crores)
function formatINRShort(amount, showSymbol = true) {
    const num = parseFloat(amount) || 0;
    const symbol = showSymbol ? '₹' : '';
    
    if (num >= 10000000) { // 1 crore
        return symbol + (num / 10000000).toFixed(2) + ' Cr';
    } else if (num >= 100000) { // 1 lakh
        return symbol + (num / 100000).toFixed(2) + ' L';
    } else if (num >= 1000) { // 1 thousand
        return symbol + (num / 1000).toFixed(2) + 'K';
    } else {
        return symbol + num.toFixed(2);
    }
}

// Format currency for Chart.js tooltips
function formatChartCurrency(value) {
    return '₹' + parseFloat(value).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format currency for form inputs (without symbol)
function formatCurrencyInput(value) {
    const num = parseFloat(value) || 0;
    return num.toFixed(2);
}

// Parse currency string back to number
function parseCurrency(currencyString) {
    if (typeof currencyString === 'number') return currencyString;
    
    // Remove currency symbol, commas, and other non-numeric characters except decimal point
    const cleaned = currencyString.toString().replace(/[₹,\s]/g, '');
    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? 0 : parsed;
}

// Auto-format currency input fields
function setupCurrencyInputs() {
    const currencyInputs = document.querySelectorAll('input[data-currency="true"]');
    
    currencyInputs.forEach(input => {
        // Format on blur
        input.addEventListener('blur', function() {
            const value = parseCurrency(this.value);
            this.value = formatCurrencyInput(value);
        });
        
        // Allow only numbers, decimal point, and basic editing keys
        input.addEventListener('keypress', function(e) {
            const allowedKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', 'Backspace', 'Delete', 'Tab', 'Enter'];
            const key = e.key;
            
            if (!allowedKeys.includes(key)) {
                e.preventDefault();
                return false;
            }
            
            // Prevent multiple decimal points
            if (key === '.' && this.value.includes('.')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Initialize currency formatting when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupCurrencyInputs();
    
    // Format existing currency displays
    const currencyDisplays = document.querySelectorAll('[data-currency-display]');
    currencyDisplays.forEach(element => {
        const amount = element.getAttribute('data-amount');
        if (amount) {
            element.textContent = formatINR(amount);
        }
    });
});

// Export functions for use with modules (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatINR,
        formatINRShort,
        formatChartCurrency,
        formatCurrencyInput,
        parseCurrency,
        setupCurrencyInputs
    };
}