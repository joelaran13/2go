<?php
require 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_stock'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("UPDATE menu SET is_available = ? WHERE id = ?");
        $stmt->execute([$_POST['is_available'], $_POST['item_id']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $stmt = $pdo->prepare("INSERT INTO menu (stall_id, name, price, category, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$_POST['stall_id'], $_POST['name'], $_POST['price'], $_POST['category'], $_POST['description'], $_POST['image']]);
}

$stalls = $pdo->query("SELECT * FROM stalls")->fetchAll(PDO::FETCH_ASSOC);
$menuItemsList = $pdo->query("SELECT menu.*, stalls.name as stall_name, COALESCE(is_available, 1) as is_available FROM menu JOIN stalls ON menu.stall_id = stalls.id ORDER BY stalls.name, menu.name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2GO! - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script> tailwind.config = { darkMode: 'class' } </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex h-screen overflow-hidden transition-colors duration-200">

    <aside class="w-64 bg-white dark:bg-gray-800 shadow-xl hidden md:flex flex-col z-10 transition-colors">
        <div class="p-6 border-b dark:border-gray-700">
            <h1 class="text-2xl font-bold text-orange-600 dark:text-orange-500"><i class="fas fa-user-shield"></i> 2GO! Admin</h1>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <button onclick="switchView('orders')" id="nav-orders" class="w-full text-left block px-4 py-3 bg-orange-50 dark:bg-gray-700 text-orange-600 dark:text-orange-400 rounded-xl font-bold transition-colors">
                <i class="fas fa-clock mr-2"></i> Live Orders
            </button>
            <button onclick="switchView('menu')" id="nav-menu" class="w-full text-left block px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors">
                <i class="fas fa-utensils mr-2"></i> Manage Menu
            </button>
            <button onclick="switchView('report')" id="nav-report" class="w-full text-left block px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors">
                <i class="fas fa-chart-line mr-2"></i> Sales Report
            </button>
            <button onclick="toggleTheme()" class="w-full text-left block px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors">
                <span class="dark:hidden"><i class="fas fa-moon mr-2"></i> Dark Mode</span>
                <span class="hidden dark:inline"><i class="fas fa-sun mr-2 text-yellow-400"></i> Light Mode</span>
            </button>
            <a href="logout.php" class="block px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </nav>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto relative">
        <div class="max-w-7xl mx-auto">
            <!-- Mobile Header -->
            <div class="md:hidden flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Admin Panel</h1>
                <div class="flex items-center gap-4">
                    <button onclick="toggleTheme()" class="p-2 text-gray-600 dark:text-gray-300"><i class="fas fa-moon dark:hidden"></i><i class="fas fa-sun hidden dark:inline text-yellow-400"></i></button>
                    <a href="logout.php" class="text-red-500"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <!-- VIEW: Live Orders -->
            <div id="ordersView">
                <!-- Add Item Form -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm mb-8 border border-gray-200 dark:border-gray-700 transition-colors">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Add New Menu Item</h2>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Stall</label>
                            <select name="stall_id" class="w-full p-2 border dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white">
                                <?php foreach($stalls as $stall): ?><option value="<?= $stall['id'] ?>"><?= htmlspecialchars($stall['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2"><label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Item Name</label><input type="text" name="name" required class="w-full p-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white" placeholder="e.g. Nasi Lemak"></div>
                        <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Price (RM)</label><input type="number" step="0.10" name="price" required class="w-full p-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white" placeholder="0.00"></div>
                        <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Emoji</label><input type="text" name="image" value="🥘" class="w-full p-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white"></div>
                        <div class="md:col-span-2"><label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Category</label><select name="category" class="w-full p-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white"><option>Rice</option><option>Noodles</option><option>Drinks</option><option>Western</option><option>Snacks</option></select></div>
                        <div class="md:col-span-3"><label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Description</label><input type="text" name="description" class="w-full p-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white" placeholder="Spicy sambal..."></div>
                        <div class="md:col-span-1 flex items-end"><button type="submit" name="add_item" class="w-full bg-green-600 text-white font-bold py-2 rounded-lg hover:bg-green-700 transition-colors">Add Item</button></div>
                    </form>
                </div>

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Incoming Orders</h2>
                    <div id="liveIndicator" class="flex items-center gap-2 text-green-500 text-sm font-bold animate-pulse"><span class="w-2 h-2 bg-green-500 rounded-full"></span> LIVE</div>
                </div>
                <div id="ordersContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="col-span-2 text-center py-10 text-gray-400 dark:text-gray-500"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Connecting to kitchen feed...</p></div></div>
            </div>

            <!-- VIEW: Manage Menu -->
            <div id="menuView" class="hidden">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Manage Menu Availability</h2>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                                <tr><th class="px-6 py-4 font-bold text-gray-500 dark:text-gray-400 uppercase text-xs">Item</th><th class="px-6 py-4 font-bold text-gray-500 dark:text-gray-400 uppercase text-xs">Stall</th><th class="px-6 py-4 font-bold text-gray-500 dark:text-gray-400 uppercase text-xs">Category</th><th class="px-6 py-4 font-bold text-gray-500 dark:text-gray-400 uppercase text-xs">Price</th><th class="px-6 py-4 font-bold text-gray-500 dark:text-gray-400 uppercase text-xs text-right">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <?php foreach($menuItemsList as $item): $is_available = $item['is_available']; ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                    <td class="px-6 py-4"><div class="flex items-center gap-3"><span class="text-2xl"><?= $item['image'] ?></span><div><p class="font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($item['name']) ?></p><p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($item['description']) ?></p></div></div></td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($item['stall_name']) ?></td>
                                    <td class="px-6 py-4"><span class="px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($item['category']) ?></span></td>
                                    <td class="px-6 py-4 font-bold text-gray-800 dark:text-white">RM <?= number_format($item['price'], 2) ?></td>
                                    <td class="px-6 py-4 text-right"><button id="btn-stock-<?= $item['id'] ?>" onclick="toggleStock(<?= $item['id'] ?>, <?= $is_available ?>)" class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $is_available ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' ?>"><?= $is_available ? '<i class="fas fa-check-circle mr-1"></i> In Stock' : '<i class="fas fa-times-circle mr-1"></i> Out of Stock' ?></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW: Sales Report -->
            <div id="reportView" class="hidden">
                <div class="flex justify-between items-center mb-8">
                    <div><h2 class="text-2xl font-bold text-gray-800 dark:text-white">Financial Statement</h2><p class="text-gray-500 dark:text-gray-400">Overview of your stall performance</p></div>
                    <div class="flex gap-3"><button onclick="exportCSV()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 transition-colors shadow-lg shadow-green-200 dark:shadow-none"><i class="fas fa-file-csv"></i> Export CSV</button><button onclick="printStatement()" class="bg-gray-800 hover:bg-gray-700 dark:bg-white dark:hover:bg-gray-200 text-white dark:text-gray-900 px-4 py-2 rounded-lg font-bold flex items-center gap-2 transition-colors shadow-lg"><i class="fas fa-print"></i> Print</button></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg"><div class="flex justify-between items-start mb-4"><div class="p-3 bg-white/20 rounded-xl"><i class="fas fa-wallet text-2xl"></i></div><span class="text-xs font-bold bg-white/20 px-2 py-1 rounded">ALL TIME</span></div><p class="text-green-100 text-sm font-medium">Total Revenue</p><h3 id="reportTotalRevenue" class="text-3xl font-bold mt-1">RM 0.00</h3></div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg"><div class="flex justify-between items-start mb-4"><div class="p-3 bg-white/20 rounded-xl"><i class="fas fa-calendar-day text-2xl"></i></div><span class="text-xs font-bold bg-white/20 px-2 py-1 rounded">TODAY</span></div><p class="text-blue-100 text-sm font-medium">Today's Sales</p><h3 id="reportTodayRevenue" class="text-3xl font-bold mt-1">RM 0.00</h3></div>
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg"><div class="flex justify-between items-start mb-4"><div class="p-3 bg-white/20 rounded-xl"><i class="fas fa-shopping-bag text-2xl"></i></div></div><p class="text-orange-100 text-sm font-medium">Total Orders</p><h3 id="reportTotalOrders" class="text-3xl font-bold mt-1">0</h3></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700"><h3 class="font-bold text-gray-800 dark:text-white">Recent Transactions</h3></div>
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm text-gray-600 dark:text-gray-400"><thead class="bg-gray-50 dark:bg-gray-700/50 uppercase text-xs font-bold text-gray-500 dark:text-gray-400"><tr><th class="px-6 py-4">Order ID</th><th class="px-6 py-4">Date</th><th class="px-6 py-4">Customer</th><th class="px-6 py-4">Items</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Amount</th></tr></thead><tbody id="reportTableBody" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody></table></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let currentOrders = [];

        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) { html.classList.remove('dark'); localStorage.theme = 'light'; }
            else { html.classList.add('dark'); localStorage.theme = 'dark'; }
        }

        function switchView(viewName) {
            const views = ['ordersView', 'menuView', 'reportView'];
            const navs = ['nav-orders', 'nav-menu', 'nav-report'];
            views.forEach(v => document.getElementById(v).classList.add('hidden'));
            const inactiveClass = "text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700";
            const activeClass = "bg-orange-50 dark:bg-gray-700 text-orange-600 dark:text-orange-400";
            const commonClass = "w-full text-left block px-4 py-3 rounded-xl font-bold transition-colors";
            navs.forEach(n => document.getElementById(n).className = `${commonClass} ${inactiveClass}`);

            if (viewName === 'orders') { document.getElementById('ordersView').classList.remove('hidden'); document.getElementById('nav-orders').className = `${commonClass} ${activeClass}`; }
            else if (viewName === 'menu') { document.getElementById('menuView').classList.remove('hidden'); document.getElementById('nav-menu').className = `${commonClass} ${activeClass}`; }
            else { document.getElementById('reportView').classList.remove('hidden'); document.getElementById('nav-report').className = `${commonClass} ${activeClass}`; updateReportUI(); }
        }

        function toggleStock(id, currentStatus) {
            const newStatus = currentStatus === 1 ? 0 : 1;
            const btn = document.getElementById('btn-stock-' + id);
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...'; btn.className = "px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider bg-gray-200 text-gray-500 cursor-wait"; btn.disabled = true;
            const formData = new FormData(); formData.append('toggle_stock', '1'); formData.append('item_id', id); formData.append('is_available', newStatus);
            fetch('admin.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if(data.success) {
                    btn.disabled = false; btn.setAttribute('onclick', `toggleStock(${id}, ${newStatus})`);
                    if (newStatus === 1) { btn.className = "px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all duration-200 bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400"; btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i> In Stock'; }
                    else { btn.className = "px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all duration-200 bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400"; btn.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Out of Stock'; }
                } else { alert('Error updating stock: ' + (data.error || 'Unknown error')); location.reload(); }
            }).catch(err => { console.error(err); location.reload(); });
        }

        function loadOrders(isAuto = false) {
            fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'get_orders', status: 'all' }) })
            .then(res => res.json()).then(data => {
                if(data.success) { currentOrders = data.orders; renderOrders(data.orders); if (!document.getElementById('reportView').classList.contains('hidden')) { updateReportUI(); } }
            }).catch(err => console.error(err));
        }

        function updateReportUI() {
            let totalRevenue = 0; let todayRevenue = 0; const totalOrders = currentOrders.length; const today = new Date().toISOString().split('T')[0];
            currentOrders.forEach(order => { const amount = parseFloat(order.total_amount); totalRevenue += amount; if (order.created_at.startsWith(today)) { todayRevenue += amount; } });
            document.getElementById('reportTotalRevenue').innerText = 'RM ' + totalRevenue.toFixed(2); document.getElementById('reportTodayRevenue').innerText = 'RM ' + todayRevenue.toFixed(2); document.getElementById('reportTotalOrders').innerText = totalOrders;
            const tbody = document.getElementById('reportTableBody'); tbody.innerHTML = '';
            const recentOrders = currentOrders.slice(0, 10);
            recentOrders.forEach(order => {
                const date = new Date(order.created_at).toLocaleDateString('en-MY');
                tbody.innerHTML += `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"><td class="px-6 py-4 font-mono text-xs">#${order.id}</td><td class="px-6 py-4">${date}</td><td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-300">${order.user_name}</td><td class="px-6 py-4 text-xs">${order.items.length} items</td><td class="px-6 py-4"><span class="px-2 py-1 rounded text-xs font-bold uppercase ${order.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">${order.status}</span></td><td class="px-6 py-4 text-right font-bold text-gray-800 dark:text-white">RM ${parseFloat(order.total_amount).toFixed(2)}</td></tr>`;
            });
        }

        function exportCSV() {
            if (currentOrders.length === 0) { alert("No data to export"); return; }
            const headers = ["Order ID", "Date", "Customer", "Items", "Status", "Amount", "Remark"];
            const rows = currentOrders.map(order => {
                const itemsStr = order.items.map(i => `${i.quantity}x ${i.item_name}`).join("; ").replace(/"/g, '""');
                const userName = order.user_name.replace(/"/g, '""');
                const remark = (order.remark || "").replace(/"/g, '""');
                return [`#${order.id}`, order.created_at, `"${userName}"`, `"${itemsStr}"`, order.status.toUpperCase(), parseFloat(order.total_amount).toFixed(2), `"${remark}"`].join(",");
            });
            const csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n" + rows.join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            const date = new Date().toISOString().split('T')[0];
            link.setAttribute("href", encodedUri); link.setAttribute("download", `sales_report_${date}.csv`);
            document.body.appendChild(link); link.click(); document.body.removeChild(link);
        }

        function printStatement() {
            const today = new Date().toLocaleDateString('en-MY', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const totalRev = document.getElementById('reportTotalRevenue').innerText;
            const todayRev = document.getElementById('reportTodayRevenue').innerText;
            const totalOrd = document.getElementById('reportTotalOrders').innerText;
            const printWindow = window.open('', '_blank');
            const tableRows = currentOrders.map(order => `<tr><td>#${order.id}</td><td>${order.created_at}</td><td>${order.user_name}</td><td>${order.status.toUpperCase()}</td><td style="text-align:right">RM ${parseFloat(order.total_amount).toFixed(2)}</td></tr>`).join('');
            printWindow.document.write(`<html><head><title>Sales Statement</title><style>body{font-family:sans-serif;padding:40px;color:#333}.header{margin-bottom:30px;border-bottom:2px solid #333;padding-bottom:20px}.title{font-size:24px;font-weight:bold;text-transform:uppercase;margin:0}.subtitle{font-size:14px;color:#666;margin-top:5px}.stats-grid{display:flex;gap:20px;margin-bottom:30px}.stat-box{border:1px solid #ddd;padding:15px;border-radius:8px;flex:1;text-align:center;background:#f9f9f9}.stat-label{display:block;font-size:12px;text-transform:uppercase;color:#666}.stat-value{display:block;font-size:20px;font-weight:bold;margin-top:5px}table{width:100%;border-collapse:collapse;font-size:12px}th{text-align:left;background:#eee;padding:8px;border-bottom:1px solid #ddd}td{padding:8px;border-bottom:1px solid #eee}.footer{margin-top:40px;font-size:10px;text-align:center;color:#999}</style></head><body><div class="header"><h1 class="title">2GO! - Financial Statement</h1><p class="subtitle">Generated on ${today}</p></div><div class="stats-grid"><div class="stat-box"><span class="stat-label">Total Revenue</span><span class="stat-value">${totalRev}</span></div><div class="stat-box"><span class="stat-label">Today's Sales</span><span class="stat-value">${todayRev}</span></div><div class="stat-box"><span class="stat-label">Total Orders</span><span class="stat-value">${totalOrd}</span></div></div><table><thead><tr><th>Order ID</th><th>Date/Time</th><th>Customer</th><th>Status</th><th style="text-align:right">Amount</th></tr></thead><tbody>${tableRows}</tbody></table><div class="footer"><p>End of Statement</p></div><script>window.onload=function(){window.print()}<\/script></body></html>`);
            printWindow.document.close();
        }

        function renderOrders(orders) {
            const container = document.getElementById('ordersContainer');
            if (orders.length === 0) { container.innerHTML = `<div class="col-span-full text-center py-20 bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700"><p class="text-gray-400 dark:text-gray-500">No active orders right now.</p></div>`; return; }
            const html = orders.map((order, index) => {
                const date = new Date(order.created_at).toLocaleString('en-MY', { hour: 'numeric', minute: '2-digit', hour12: true });
                let statusBadge = '', actionBtns = '', borderClass = 'border-gray-100 dark:border-gray-700';
                
                if (order.status === 'pending') {
                    statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>';
                    actionBtns = `<button onclick="updateStatus(${order.id}, 'cooking')" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg transition-colors font-medium shadow-sm">Start Cooking</button>`;
                    borderClass = 'border-l-4 border-l-yellow-400 border-gray-100 dark:border-gray-700';
                } else if (order.status === 'cooking') {
                     statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">Cooking</span>';
                     actionBtns = `<button onclick="updateStatus(${order.id}, 'ready')" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg transition-colors font-medium shadow-sm">Mark Ready</button>`;
                     borderClass = 'border-l-4 border-l-blue-500 border-gray-100 dark:border-gray-700';
                } else if (order.status === 'ready') {
                     statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300">Ready</span>';
                     actionBtns = `<button onclick="updateStatus(${order.id}, 'completed')" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg transition-colors font-medium shadow-sm">Complete</button>`;
                     borderClass = 'border-l-4 border-l-orange-500 border-gray-100 dark:border-gray-700';
                } else {
                     statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Completed</span>';
                     actionBtns = `<button disabled class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed py-2 rounded-lg font-medium">Archived</button>`;
                     borderClass = 'border-l-4 border-l-green-500 border-gray-100 dark:border-gray-700';
                }

                const itemsHtml = order.items.map(item => `<div class="flex justify-between text-sm py-1 border-b border-dashed border-gray-100 dark:border-gray-700 last:border-0"><span class="text-gray-600 dark:text-gray-300"><span class="font-bold text-gray-800 dark:text-white">x${item.quantity}</span> ${item.item_name}</span><span class="font-medium text-gray-500 dark:text-gray-400">RM ${(item.price * item.quantity).toFixed(2)}</span></div>`).join('');
                
                // Show remark if present
                let remarkHtml = '';
                if (order.remark && order.remark.trim() !== '') {
                    remarkHtml = `<div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded text-xs text-yellow-800 dark:text-yellow-200"><span class="font-bold">Note:</span> ${order.remark}</div>`;
                }

                return `<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border ${borderClass} transition-colors animate-fade-in relative overflow-hidden group"><div class="flex justify-between items-start mb-4"><div><h3 class="font-bold text-gray-800 dark:text-white text-lg flex items-center gap-2">Order #${order.id}</h3><p class="text-sm text-gray-500 dark:text-gray-400 font-medium">${order.user_name}</p><p class="text-xs text-gray-400 mt-1"><i class="far fa-clock"></i> ${date}</p></div><div class="flex flex-col items-end gap-2">${statusBadge}</div></div><div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 mb-4 space-y-1">${itemsHtml}${remarkHtml}<div class="flex justify-between font-bold pt-2 text-lg text-gray-800 dark:text-white border-t border-gray-200 dark:border-gray-600 mt-2"><span>Total</span><span>RM ${parseFloat(order.total_amount).toFixed(2)}</span></div></div><div class="flex gap-2"><button onclick="printInvoice(${index})" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg transition-colors" title="Print Invoice"><i class="fas fa-print"></i></button>${actionBtns}</div></div>`;
            }).join('');
            if (container.innerHTML !== html) { container.innerHTML = html; }
        }

        function updateStatus(orderId, status) {
            fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'update_status', order_id: orderId, status: status }) })
            .then(res => res.json()).then(data => { if(data.success) { loadOrders(); } });
        }

        function printInvoice(index) {
            const order = currentOrders[index];
            if (!order) return;
            const printWindow = window.open('', '_blank');
            const date = new Date(order.created_at).toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
            const itemsHtml = order.items.map(item => `<tr style="border-bottom: 1px solid #eee;"><td style="padding: 8px;">${item.item_name}</td><td style="padding: 8px; text-align: center;">${item.quantity}</td><td style="padding: 8px; text-align: right;">${parseFloat(item.price).toFixed(2)}</td><td style="padding: 8px; text-align: right;">${(item.price * item.quantity).toFixed(2)}</td></tr>`).join('');
            
            let remarkHtml = '';
            if (order.remark && order.remark.trim() !== '') {
                remarkHtml = `<div style="margin-bottom: 15px; font-size: 12px; font-style: italic;"><strong>Note:</strong> ${order.remark}</div>`;
            }

            printWindow.document.write(`<html><head><title>Invoice #${order.id}</title><style>body{font-family:'Courier New',Courier,monospace;padding:20px;max-width:400px;margin:0 auto;color:#000}.header{text-align:center;margin-bottom:20px;border-bottom:2px dashed #000;padding-bottom:15px}h1{margin:0 0 5px 0;font-size:24px;text-transform:uppercase}.subtitle{margin:0;font-size:12px}.meta{margin-bottom:20px;font-size:13px;line-height:1.5}table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px}th{text-align:left;border-bottom:1px solid #000;padding:5px;font-weight:bold}.total{text-align:right;font-size:18px;font-weight:bold;border-top:2px dashed #000;padding-top:10px;margin-top:10px}.footer{margin-top:30px;text-align:center;font-size:12px;border-top:1px solid #eee;padding-top:10px}@media print{.no-print{display:none}}</style></head><body><div class="header"><h1>2GO!</h1><p class="subtitle">Official Receipt</p></div><div class="meta"><div><strong>Order ID:</strong> #${order.id}</div><div><strong>Date:</strong> ${date}</div><div><strong>Customer:</strong> ${order.user_name}</div><div><strong>Payment:</strong> ${order.payment_method || 'QR Pay'}</div><div><strong>Status:</strong> ${order.status.toUpperCase()}</div></div>${remarkHtml}<table><thead><tr><th>Item</th><th style="text-align: center;">Qty</th><th style="text-align: right;">Price</th><th style="text-align: right;">Total</th></tr></thead><tbody>${itemsHtml}</tbody></table><div class="total">Total: RM ${parseFloat(order.total_amount).toFixed(2)}</div><div class="footer"><p>Thank you for your order!</p><p>Please come again.</p></div><script>window.onload=function(){window.print()}<\/script></body></html>`);
            printWindow.document.close();
        }

        document.addEventListener('DOMContentLoaded', () => { loadOrders(); setInterval(() => loadOrders(true), 5000); });
    </script>
</body>
</html>