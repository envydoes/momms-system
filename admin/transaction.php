<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1" name="viewport" />
  <title>Owner Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>
<body class="bg-white">
  <div class="min-h-screen flex bg-white">
    <!-- Sidebar -->
    <aside class="bg-[#c62839] w-72 flex flex-col items-center py-8 rounded-tr-3xl rounded-br-3xl select-none">
      <div class="flex items-center mb-10 w-full px-4">
        <a href="previous_page.html" class="text-white font-bold text-lg flex items-center">
          <i class="fas fa-arrow-left mr-2"></i> Dashboard
        </a>
      </div>
      <img alt="Momms Cafe logo" class="w-36 h-36 rounded-full mb-8" height="150" src="LOGO.png" width="150"/>
      <nav class="w-full flex flex-col gap-8 px-6">
        <a class="text-white font-bold text-lg text-center uppercase hover:underline" href="ricemenu.html">Overview</a>
        <a class="text-white font-bold text-lg text-center uppercase hover:underline" href="employee_page.html">Profile</a>
        <a class="text-yellow-500 font-bold text-lg text-center uppercase hover:underline" href="shawarma.html">Transactions</a>
        <a class="text-white font-bold text-lg text-center uppercase hover:underline" href="ricemenu.html">Orders</a>
        <a class="text-white font-bold text-lg text-center uppercase hover:underline" href="login.html">Logout</a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6">
      <div class="flex flex-col bg-white rounded-md shadow-md">
        <!-- Top bar -->
        <div class="flex flex-wrap items-center bg-gray-200 rounded-t-md px-4 py-3 gap-4 select-none">
          <label class="flex items-center space-x-2 text-gray-400 font-semibold cursor-pointer">
            <input id="selectAll" class="w-6 h-6 rounded border-gray-300 bg-white cursor-pointer" type="checkbox" />
            <span class="text-lg">Select All</span>
          </label>
          <button id="deleteBtn" aria-label="Delete" class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-300 text-gray-400 cursor-not-allowed" disabled>
            <i class="fas fa-trash-alt"></i>
          </button>
          <div class="ml-auto flex items-center gap-4">
            <input id="searchInput" type="text" placeholder="Search orders..." class="px-3 py-1 rounded border border-gray-400 text-sm" />
            <select id="sortDropdown" class="px-3 py-1 rounded border border-gray-400 text-sm">
              <option value="default">Default</option>
              <option value="date">Sort by Date</option>
              <option value="id">Sort by ID</option>
              <option value="order">Sort by Order</option>
              <option value="quantity">Sort by Quantity</option>
              <option value="amount">Sort by Amount</option>
            </select>
          </div>
        </div>

        <!-- Table header -->
        <div class="grid grid-cols-[60px_120px_60px_1fr_1fr_1fr_80px] bg-[#d0011b] text-white font-bold text-lg px-4 py-3 rounded-t-none rounded-b-md select-none">
          <div></div>
          <div class="flex items-center">Date</div>
          <div class="flex items-center">ID</div>
          <div class="flex items-center">Order</div>
          <div class="flex items-center">Quantity</div>
          <div class="flex items-center">Total Amount</div>
          <!--<div class="flex items-center justify-end">Amount</div> -->
        </div>

        <!-- Table rows -->
        <div id="ordersContainer" class="divide-y divide-gray-300 max-h-[600px] overflow-auto">
          <!-- Orders will be dynamically loaded here -->
        </div>
      </div>
    </main>
  </div>

  <script>
    const selectAllCheckbox = document.getElementById('selectAll');
    const deleteBtn = document.getElementById('deleteBtn');
    const sortDropdown = document.getElementById('sortDropdown');
    const searchInput = document.getElementById('searchInput');
    const ordersContainer = document.getElementById('ordersContainer');

    let transactions = [];
    let sortAscending = true;
    let currentSortKey = 'default';

    // Fetch orders from server
    async function fetchOrders() {
      const response = await fetch('get_orders.php');
      transactions = await response.json();
      renderOrders();
    }

    // Render the orders
    function renderOrders() {
      const searchTerm = searchInput.value.toLowerCase();
      let filtered = transactions.filter(t => 
        t.order.toLowerCase().includes(searchTerm) ||
        t.date.toLowerCase().includes(searchTerm) ||
        t.id.toString().includes(searchTerm)
      );

      // Sorting
      if (currentSortKey !== 'default') {
        filtered.sort((a, b) => {
          if (currentSortKey === 'amount' || currentSortKey === 'quantity' || currentSortKey === 'id') {
            return sortAscending ? a[currentSortKey] - b[currentSortKey] : b[currentSortKey] - a[currentSortKey];
          } else {
            return sortAscending
              ? a[currentSortKey].localeCompare(b[currentSortKey])
              : b[currentSortKey].localeCompare(a[currentSortKey]);
          }
        });
      }

      ordersContainer.innerHTML = ''; // Clear existing

      if (filtered.length === 0) {
        ordersContainer.innerHTML = '<p class="text-center text-gray-500 py-6">No transactions found.</p>';
        return;
      }

      filtered.forEach(t => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-[60px_120px_60px_1fr_1fr_1fr_80px] px-4 py-2';
        row.innerHTML = `
          <input class="order-checkbox w-6 h-6 rounded border-gray-300 bg-white cursor-pointer" type="checkbox" data-order-id="${t.id}" />
          <div>${t.date}</div>
          <div>#${t.id}</div>
          <div>${t.order}</div>
          <div>${t.quantity}</div>
          <div>₱ ${parseFloat(t.amount).toFixed(2)}</div>
        `;
        ordersContainer.appendChild(row);
      });

      attachCheckboxListeners();
      updateButtonsState();
    }

    // Attach checkbox listeners
    function attachCheckboxListeners() {
      const checkboxes = ordersContainer.querySelectorAll('.order-checkbox');
      checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
          const allChecked = Array.from(checkboxes).every(cb => cb.checked);
          selectAllCheckbox.checked = allChecked;
          updateButtonsState();
        });
      });
    }

    // Handle select all checkbox toggle
    selectAllCheckbox.addEventListener('change', () => {
      const checkboxes = ordersContainer.querySelectorAll('.order-checkbox');
      checkboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
      });
      updateButtonsState();
    });

    // Enable/disable delete button
    function updateButtonsState() {
      const checked = ordersContainer.querySelectorAll('.order-checkbox:checked');
      const anyChecked = checked.length > 0;

      deleteBtn.disabled = !anyChecked;
      deleteBtn.classList.toggle('bg-red-600', anyChecked);
      deleteBtn.classList.toggle('text-white', anyChecked);
      deleteBtn.classList.toggle('cursor-pointer', anyChecked);
      deleteBtn.classList.toggle('bg-gray-300', !anyChecked);
      deleteBtn.classList.toggle('text-gray-400', !anyChecked);
      deleteBtn.classList.toggle('cursor-not-allowed', !anyChecked);
    }

    // Delete selected orders
    deleteBtn.addEventListener('click', async () => {
      const checkedBoxes = ordersContainer.querySelectorAll('.order-checkbox:checked');
      if (checkedBoxes.length === 0) return;

      const ids = Array.from(checkedBoxes).map(cb => parseInt(cb.getAttribute('data-order-id')));
      const uniqueIds = [...new Set(ids)];

      const confirm = await Swal.fire({
        title: `Delete ${uniqueIds.length} order(s)?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280'
      });

      if (confirm.isConfirmed) {
        const response = await fetch('delete_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order_ids: uniqueIds })
        });
        const result = await response.json();

        if (result.success) {
          await fetchOrders();
          selectAllCheckbox.checked = false;
          Swal.fire('Deleted!', 'Orders deleted successfully.', 'success');
        } else {
          Swal.fire('Error', result.error || 'Something went wrong.', 'error');
        }
      }
    });

    // Search input event
    searchInput.addEventListener('input', renderOrders);

    // Sort dropdown change
    sortDropdown.addEventListener('change', (e) => {
      currentSortKey = e.target.value;
      sortAscending = true;
      renderOrders();
    });

    sortDropdown.addEventListener('click', () => {
      if (currentSortKey !== 'default') {
        sortAscending = !sortAscending;
        renderOrders();
      }
    });

    // Initial load
    fetchOrders();
  </script>
</body>
</html>
