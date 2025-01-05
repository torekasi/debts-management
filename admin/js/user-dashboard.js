function loadTransactions(userId, page = 1) {
    fetch(`ajax/get_user_transactions.php?user_id=${userId}&page=${page}&type=transactions`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update table content
                const tbody = document.querySelector('#transactions-table tbody');
                tbody.innerHTML = data.data.map(item => `
                    <tr>
                        <td class="px-4 py-2">${item.date}</td>
                        <td class="px-4 py-2">${item.description}</td>
                        <td class="px-4 py-2 text-right">RM ${parseFloat(item.amount).toFixed(2)}</td>
                    </tr>
                `).join('');

                // Update pagination
                updatePagination('transactions-pagination', data.pagination, loadTransactions, userId);
            }
        });
}

function loadPayments(userId, page = 1) {
    fetch(`ajax/get_user_transactions.php?user_id=${userId}&page=${page}&type=payments`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update table content
                const tbody = document.querySelector('#payments-table tbody');
                tbody.innerHTML = data.data.map(item => `
                    <tr>
                        <td class="px-4 py-2">${item.date}</td>
                        <td class="px-4 py-2">${item.payment_method}</td>
                        <td class="px-4 py-2">${item.reference_number}</td>
                        <td class="px-4 py-2 text-right">RM ${parseFloat(item.amount).toFixed(2)}</td>
                    </tr>
                `).join('');

                // Update pagination
                updatePagination('payments-pagination', data.pagination, loadPayments, userId);
            }
        });
}

function updatePagination(containerId, pagination, loadFunction, userId) {
    const container = document.getElementById(containerId);
    const { current_page, total_pages } = pagination;
    
    let html = '<div class="flex justify-center space-x-2 mt-4">';
    
    // Previous button
    html += `<button onclick="loadFunction(${userId}, ${current_page - 1})" 
        class="px-3 py-1 rounded ${current_page === 1 ? 'bg-gray-200 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}"
        ${current_page === 1 ? 'disabled' : ''}>
        Previous
    </button>`;
    
    // Page numbers
    for (let i = 1; i <= total_pages; i++) {
        if (i === current_page) {
            html += `<button class="px-3 py-1 rounded bg-blue-500 text-white">${i}</button>`;
        } else {
            html += `<button onclick="loadFunction(${userId}, ${i})" 
                class="px-3 py-1 rounded hover:bg-gray-200">${i}</button>`;
        }
    }
    
    // Next button
    html += `<button onclick="loadFunction(${userId}, ${current_page + 1})" 
        class="px-3 py-1 rounded ${current_page === total_pages ? 'bg-gray-200 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}"
        ${current_page === total_pages ? 'disabled' : ''}>
        Next
    </button>`;
    
    html += '</div>';
    container.innerHTML = html;
}

// Initialize tables when page loads
document.addEventListener('DOMContentLoaded', function() {
    const userId = document.querySelector('[data-user-id]').dataset.userId;
    loadTransactions(userId);
    loadPayments(userId);
});
