<?php
require 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}

$stalls_stmt = $pdo->query("SELECT * FROM stalls");
$stalls = $stalls_stmt->fetchAll(PDO::FETCH_ASSOC);

$menu_stmt = $pdo->query("SELECT *, COALESCE(is_available, 1) as is_available FROM menu");
$menu_items = $menu_stmt->fetchAll(PDO::FETCH_ASSOC);

$current_name = $_SESSION['name'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2GO! - Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <style>
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .toast-enter { animation: slideInRight 0.3s ease-out forwards; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200 min-h-screen pb-20">

    <!-- Navbar -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10 p-4 transition-colors">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2 cursor-pointer" onclick="showStalls()">
                <i class="fas fa-utensils text-orange-500"></i> 2GO!
            </h1>
            <div class="flex gap-4 items-center">
                <button onclick="toggleTheme()" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline text-yellow-400"></i>
                </button>
                <button onclick="showContact()" class="p-2 text-gray-600 dark:text-gray-400 hover:text-orange-500 transition-colors" title="Contact Us">
                    <i class="fas fa-envelope-open-text text-xl"></i>
                </button>
                <button onclick="toggleCart()" class="relative p-2">
                    <i class="fas fa-shopping-bag text-gray-600 dark:text-gray-300 text-xl"></i>
                    <span id="cartCount" class="absolute top-0 right-0 bg-orange-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center hidden">0</span>
                </button>
                <button onclick="toggleProfileModal()" class="p-2 text-gray-600 dark:text-gray-400 hover:text-orange-500 transition-colors">
                    <i class="fas fa-user-circle text-xl"></i>
                </button>
                <a href="logout.php" class="text-gray-500 dark:text-gray-400 hover:text-red-500"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </nav>

    <!-- Notification Toast Container -->
    <div id="toastContainer" class="fixed top-20 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <div class="max-w-6xl mx-auto p-4">
        <!-- STALLS VIEW -->
        <div id="stallsView" class="animate-fade-in">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Select a Stall</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($stalls as $stall): ?>
                <div onclick="openStall(<?= $stall['id'] ?>, '<?= htmlspecialchars($stall['name']) ?>')" 
                     class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all cursor-pointer border border-transparent hover:border-orange-500 group">
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 bg-orange-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-4xl group-hover:scale-110 transition-transform">
                            <?= $stall['image'] ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-gray-800 dark:text-white"><?= htmlspecialchars($stall['name']) ?></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($stall['description']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Order History Section -->
            <div class="mt-12">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">My Orders</h2>
                    <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-1 gap-1">
                        <button onclick="loadOrders('all')" id="filter-all" class="filter-btn px-4 py-1.5 rounded-md text-sm font-medium transition-all bg-white dark:bg-gray-600 shadow-sm text-gray-800 dark:text-white">All</button>
                        <button onclick="loadOrders('pending')" id="filter-pending" class="filter-btn px-4 py-1.5 rounded-md text-sm font-medium transition-all text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Pending</button>
                        <button onclick="loadOrders('completed')" id="filter-completed" class="filter-btn px-4 py-1.5 rounded-md text-sm font-medium transition-all text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Completed</button>
                    </div>
                </div>
                <div id="ordersContainer" class="space-y-4">
                    <div class="text-center py-8 text-gray-400">Loading orders...</div>
                </div>
            </div>
        </div>

        <!-- MENU VIEW -->
        <div id="menuView" class="hidden">
            <button onclick="showStalls()" class="mb-4 flex items-center text-gray-600 dark:text-gray-400 hover:text-orange-500 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to Stalls
            </button>
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 id="currentStallName" class="text-3xl font-bold text-gray-800 dark:text-white">Stall Name</h2>
                    <p class="text-gray-500 dark:text-gray-400">Select your delicious meal</p>
                </div>
            </div>
            <div id="menuGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
        </div>

        <!-- CONTACT VIEW -->
        <div id="contactView" class="hidden animate-fade-in">
            <button onclick="showStalls()" class="mb-6 flex items-center text-gray-600 dark:text-gray-400 hover:text-orange-500 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to Home
            </button>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-6">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Get in Touch</h2>
                        <p class="text-gray-500 dark:text-gray-400">Have an issue with an order or a suggestion? We'd love to hear from you.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300"><i class="fas fa-phone-alt"></i></div>
                            <div><h4 class="font-bold text-gray-800 dark:text-white">Support Hotline</h4><p class="text-sm text-gray-500 dark:text-gray-400">+60 12-345 6789</p></div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center text-green-600 dark:text-green-300"><i class="fas fa-map-marker-alt"></i></div>
                            <div><h4 class="font-bold text-gray-800 dark:text-white">Student Hub</h4><p class="text-sm text-gray-500 dark:text-gray-400">Level 2, Student Center Complex</p></div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <form onsubmit="submitContact(event)" class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 h-full flex flex-col justify-center">
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Message</label>
                            <textarea rows="6" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none dark:text-white" placeholder="Tell us more about your experience..." required></textarea>
                        </div>
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-orange-200 dark:shadow-none flex items-center justify-center gap-2 group">
                            <span>Send Message</span> <i class="fas fa-paper-plane transition-transform group-hover:translate-x-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Settings Modal -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-[80] flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-sm shadow-2xl relative p-6 animate-fade-in-up">
            <button onclick="toggleProfileModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="fas fa-times"></i></button>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6">Profile Settings</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name</label>
                    <input type="text" id="profileName" value="<?= htmlspecialchars($current_name) ?>" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password (Optional)</label>
                    <input type="password" id="profilePass" placeholder="Leave empty to keep current" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                </div>
                <button onclick="saveProfile()" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-lg transition-colors">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div id="orderDetailModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-[60] flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-0 w-full max-w-sm shadow-2xl overflow-hidden animate-fade-in-up relative">
            <div class="bg-orange-500 p-6 text-white text-center relative">
                <button onclick="closeOrderModal()" class="absolute top-4 right-4 text-white/80 hover:text-white"><i class="fas fa-times text-xl"></i></button>
                <p class="text-sm font-medium text-orange-100 uppercase tracking-widest mb-1">Order Number</p>
                <h2 id="modalOrderId" class="text-5xl font-extrabold">#0000</h2>
            </div>
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                    <span id="modalStatus" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-yellow-100 text-yellow-600">Pending</span>
                </div>
                
                <!-- Remark Section in Detail Modal -->
                <div id="modalRemarkContainer" class="hidden mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded-lg text-sm text-yellow-800 dark:text-yellow-200">
                    <span class="font-bold block mb-1">Remark:</span>
                    <p id="modalRemarkText" class="italic"></p>
                </div>

                <h4 class="font-bold text-gray-800 dark:text-white mb-3">Items</h4>
                <div id="modalItems" class="space-y-3 mb-6 max-h-48 overflow-y-auto pr-2"></div>
                <div class="flex justify-between items-center pt-4 border-t border-dashed border-gray-200 dark:border-gray-700">
                    <span class="font-medium text-gray-500 dark:text-gray-400">Total Amount</span>
                    <span id="modalTotal" class="text-2xl font-bold text-gray-800 dark:text-white">RM 0.00</span>
                </div>
                <div class="mt-6 text-center"><p class="text-xs text-gray-400">Show this screen to the stall owner when collecting.</p></div>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-end backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 w-full max-w-md h-full shadow-xl flex flex-col transition-colors">
            <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Your Cart</h2>
                <button onclick="toggleCart()" class="text-gray-500 dark:text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            
            <div id="cartItems" class="flex-1 overflow-y-auto p-4 space-y-4"></div>
            
            <!-- REMARK INPUT -->
            <div class="px-4 pb-2 bg-gray-50 dark:bg-gray-800/50">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Special Requests</label>
                <textarea id="orderRemark" rows="2" class="w-full px-4 py-2 text-sm border dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-orange-500 outline-none" placeholder="e.g. Less spicy, no veggie..."></textarea>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t dark:border-gray-600">
                <div class="flex justify-between font-bold text-lg mb-4 text-gray-800 dark:text-white">
                    <span>Total</span>
                    <span id="cartTotal">RM 0.00</span>
                </div>
                <button onclick="showQR()" id="checkoutBtn" disabled class="w-full bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed text-white py-3 rounded-xl font-bold transition-colors">Checkout</button>
            </div>
        </div>
    </div>

    <!-- QR Payment Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center max-w-sm w-full shadow-2xl transition-colors">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-qrcode text-3xl text-blue-600 dark:text-blue-300"></i>
            </div>
            <h3 class="text-xl font-bold mb-2 text-gray-800 dark:text-white">Scan DuitNow / QR</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Total: <span id="qrTotal" class="font-bold text-orange-600 dark:text-orange-400"></span></p>
            <div class="bg-white p-2 rounded-lg inline-block shadow-inner mb-4 border-2 border-gray-200">
                <div class="w-48 h-48 bg-white grid grid-cols-4 grid-rows-4 gap-1 p-2">
                    <?php for($i=0; $i<16; $i++): ?>
                        <div class="<?= rand(0,1) ? 'bg-black' : 'bg-transparent' ?> rounded"></div>
                    <?php endfor; ?>
                </div>
            </div>
            <button onclick="processPayment()" class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-xl font-bold shadow-lg transition-transform active:scale-95">
                <i class="fas fa-check-circle mr-2"></i> Payment Successful
            </button>
            <button onclick="document.getElementById('qrModal').classList.add('hidden')" class="mt-4 text-gray-500 dark:text-gray-400 text-sm hover:underline">Cancel Transaction</button>
        </div>
    </div>

    <!-- Payment Success / Ticket Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-[70] flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 text-center max-w-sm w-full shadow-2xl relative overflow-hidden animate-fade-in-up">
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-receipt text-4xl text-green-600 dark:text-green-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Order Placed!</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 px-4">Thank you, please wait and pick up the food once it says ready.</p>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6 border border-dashed border-gray-200 dark:border-gray-600">
                <p class="text-xs uppercase tracking-widest text-gray-400 mb-1">Ticket Number</p>
                <div id="successOrderId" class="text-5xl font-extrabold text-orange-600 dark:text-orange-500">#0000</div>
            </div>
            <div class="text-left mb-6">
                <p class="text-xs font-bold text-gray-400 uppercase mb-2">Purchase Details</p>
                <div id="successItems" class="text-sm text-gray-600 dark:text-gray-300 space-y-2 max-h-32 overflow-y-auto pr-2 custom-scrollbar"></div>
                <div class="border-t border-gray-100 dark:border-gray-700 mt-3 pt-3 flex justify-between font-bold text-gray-800 dark:text-white">
                    <span>Total Paid</span>
                    <span id="successTotal">RM 0.00</span>
                </div>
            </div>
            <button onclick="window.location.reload()" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-orange-200 dark:shadow-none">
                Got it, Thanks!
            </button>
        </div>
    </div>

    <script>
        const allMenuItems = <?= json_encode($menu_items) ?>;
        let cart = [];
        let currentFilter = 'all';
        let currentOrdersList = []; 
        let knownOrderStatuses = {}; 

        function initTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        initTheme();

        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                html.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }

        // --- Notification Logic ---
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            
            let colors = 'bg-white dark:bg-gray-800 border-l-4 border-blue-500 text-gray-800 dark:text-white';
            let icon = 'fas fa-info-circle text-blue-500';
            
            if(type === 'success') {
                colors = 'bg-white dark:bg-gray-800 border-l-4 border-green-500 text-gray-800 dark:text-white';
                icon = 'fas fa-check-circle text-green-500';
            }

            toast.className = `p-4 rounded shadow-lg flex items-center gap-3 w-72 pointer-events-auto toast-enter ${colors}`;
            toast.innerHTML = `<i class="${icon} text-xl"></i><div class="flex-1"><p class="font-bold text-sm">Notification</p><p class="text-xs opacity-90">${message}</p></div><button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function checkNotifications(orders) {
            orders.forEach(order => {
                const prevStatus = knownOrderStatuses[order.id];
                if (prevStatus && prevStatus !== order.status) {
                    if (order.status === 'ready') {
                        showToast(`Order #${order.id} is READY for pickup!`, 'success');
                        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                        audio.volume = 0.5;
                        audio.play().catch(e => console.log('Audio play blocked'));
                    } else if (order.status === 'cooking') {
                        showToast(`Order #${order.id} is now being prepared.`);
                    }
                }
                knownOrderStatuses[order.id] = order.status;
            });
        }

        // --- Navigation ---
        function showStalls() {
            document.getElementById('stallsView').classList.remove('hidden');
            document.getElementById('menuView').classList.add('hidden');
            document.getElementById('contactView').classList.add('hidden');
            loadOrders(currentFilter); 
        }
        function showContact() {
            document.getElementById('stallsView').classList.add('hidden');
            document.getElementById('menuView').classList.add('hidden');
            document.getElementById('contactView').classList.remove('hidden');
        }
        function openStall(stallId, stallName) {
            document.getElementById('stallsView').classList.add('hidden');
            document.getElementById('menuView').classList.remove('hidden');
            document.getElementById('contactView').classList.add('hidden');
            document.getElementById('currentStallName').innerText = stallName;
            const stallItems = allMenuItems.filter(item => item.stall_id == stallId);
            renderMenu(stallItems);
        }

        function renderMenu(items) {
            const grid = document.getElementById('menuGrid');
            grid.innerHTML = '';
            if(items.length === 0) {
                grid.innerHTML = '<p class="text-gray-500 dark:text-gray-400 col-span-3 text-center py-10">No items available at this stall yet.</p>';
                return;
            }
            items.forEach(item => {
                const isOutOfStock = item.is_available == 0;
                let buttonHtml = isOutOfStock ? `<button disabled class="bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed w-full px-3 py-1.5 rounded-lg text-xs font-bold uppercase">Sold Out</button>` : `<button onclick='addToCart(${JSON.stringify(item)})' class="bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300 w-8 h-8 rounded-lg hover:bg-orange-500 hover:text-white transition flex items-center justify-center"><i class="fas fa-plus"></i></button>`;
                let imageClass = isOutOfStock ? 'opacity-50 grayscale' : '';
                let textColor = isOutOfStock ? 'text-gray-400 dark:text-gray-600' : 'text-gray-800 dark:text-white';
                grid.innerHTML += `<div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all flex gap-4 border border-transparent dark:border-gray-700 relative overflow-hidden">${isOutOfStock ? '<div class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg z-10 shadow-sm">SOLD OUT</div>' : ''}<div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-xl text-4xl flex items-center justify-center flex-shrink-0 ${imageClass} transition-all">${item.image}</div><div class="flex-1 flex flex-col justify-between"><div><h3 class="font-bold ${textColor} line-clamp-1">${item.name}</h3><p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">${item.description || ''}</p></div><div class="flex justify-between items-end mt-2"><span class="font-bold text-orange-600 dark:text-orange-400 text-lg ${isOutOfStock ? 'opacity-50' : ''}">RM ${parseFloat(item.price).toFixed(2)}</span>${buttonHtml}</div></div></div>`;
            });
        }

        // --- Cart & Payment ---
        function toggleCart() {
            const modal = document.getElementById('cartModal');
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
            renderCart();
        }
        function addToCart(item) {
            const existing = cart.find(i => i.id === item.id);
            if (existing) { existing.qty++; } else { cart.push({...item, qty: 1}); }
            updateCartCount();
        }
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartCount();
            renderCart();
        }
        function updateCartCount() {
            const count = cart.reduce((sum, i) => sum + i.qty, 0);
            const badge = document.getElementById('cartCount');
            badge.innerText = count;
            badge.classList.toggle('hidden', count === 0);
        }
        function renderCart() {
            const container = document.getElementById('cartItems');
            container.innerHTML = '';
            let total = 0;
            if (cart.length === 0) {
                container.innerHTML = `<div class="text-center text-gray-400 mt-10"><i class="fas fa-shopping-basket text-4xl mb-3 opacity-30"></i><p>Your cart is empty</p></div>`;
            }
            cart.forEach((item, index) => {
                total += item.price * item.qty;
                container.innerHTML += `<div class="flex gap-4 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg"><div class="text-2xl">${item.image}</div><div class="flex-1"><h4 class="font-medium text-gray-800 dark:text-white text-sm">${item.name}</h4><p class="text-xs text-orange-600 dark:text-orange-300 font-bold">RM ${(item.price * item.qty).toFixed(2)}</p></div><div class="flex items-center gap-3"><span class="text-sm font-bold text-gray-600 dark:text-gray-300">x${item.qty}</span><button onclick="removeFromCart(${index})" class="text-red-400 hover:text-red-600"><i class="fas fa-trash-alt"></i></button></div></div>`;
            });
            document.getElementById('cartTotal').innerText = 'RM ' + total.toFixed(2);
            document.getElementById('checkoutBtn').disabled = cart.length === 0;
            document.getElementById('checkoutBtn').classList.toggle('opacity-50', cart.length === 0);
        }
        function showQR() {
            if(cart.length === 0) return;
            document.getElementById('qrTotal').innerText = document.getElementById('cartTotal').innerText;
            document.getElementById('qrModal').classList.remove('hidden');
            document.getElementById('cartModal').classList.add('hidden');
            document.getElementById('cartModal').classList.remove('flex');
        }
        function processPayment() {
            const remark = document.getElementById('orderRemark').value; // Get remark
            fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'place_order', items: cart, remark: remark })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('qrModal').classList.add('hidden');
                    document.getElementById('successOrderId').innerText = '#' + data.order_id;
                    document.getElementById('successTotal').innerText = document.getElementById('cartTotal').innerText;
                    const itemsContainer = document.getElementById('successItems');
                    itemsContainer.innerHTML = cart.map(item => `<div class="flex justify-between"><span>${item.qty}x ${item.name}</span><span class="font-medium">RM ${(item.price * item.qty).toFixed(2)}</span></div>`).join('');
                    document.getElementById('successModal').classList.remove('hidden');
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => console.error(err));
        }

        // --- Profile & Contact ---
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }
        function saveProfile() {
            const name = document.getElementById('profileName').value;
            const password = document.getElementById('profilePass').value;
            if(!name) { alert('Name cannot be empty'); return; }
            fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'update_profile', name: name, password: password })
            }).then(res => res.json()).then(data => {
                if(data.success) { alert('Profile updated successfully!'); toggleProfileModal(); }
                else { alert('Error updating profile: ' + data.message); }
            });
        }
        function submitContact(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalContent = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending...';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                btn.classList.replace('bg-orange-600', 'bg-green-600'); btn.classList.replace('hover:bg-orange-700', 'hover:bg-green-700');
                showToast('Message sent successfully!', 'success');
                setTimeout(() => { e.target.reset(); btn.disabled = false; btn.innerHTML = originalContent; btn.classList.replace('bg-green-600', 'bg-orange-600'); btn.classList.replace('hover:bg-green-700', 'hover:bg-orange-700'); }, 2000);
            }, 1000);
        }

        // --- Orders List & Modal ---
        function loadOrders(status, isAuto = false) {
            currentFilter = status;
            const container = document.getElementById('ordersContainer');
            if (!isAuto) {
                document.querySelectorAll('.filter-btn').forEach(btn => { btn.classList.remove('bg-white', 'dark:bg-gray-600', 'shadow-sm', 'text-gray-800', 'dark:text-white'); btn.classList.add('text-gray-600', 'dark:text-gray-400'); });
                const activeBtn = document.getElementById('filter-' + status);
                if(activeBtn) { activeBtn.classList.add('bg-white', 'dark:bg-gray-600', 'shadow-sm', 'text-gray-800', 'dark:text-white'); activeBtn.classList.remove('text-gray-600', 'dark:text-gray-400'); }
                container.innerHTML = '<div class="text-center py-4 text-gray-400"><i class="fas fa-spinner fa-spin"></i> Updating...</div>';
            }
            fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'get_orders', status: status }) })
            .then(res => res.json()).then(data => {
                if (data.success) { currentOrdersList = data.orders; checkNotifications(data.orders); renderOrders(data.orders); }
                else if (!isAuto) { container.innerHTML = '<div class="text-red-500 text-center">Failed to load orders</div>'; }
            }).catch(err => console.error(err));
        }
        function renderOrders(orders) {
            const container = document.getElementById('ordersContainer');
            if (orders.length === 0) { container.innerHTML = `<div class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl"><p class="text-gray-400">No orders found.</p></div>`; return; }
            const html = orders.map((order, index) => {
                const date = new Date(order.created_at).toLocaleString('en-MY', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
                let statusColor = order.status === 'completed' ? 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300' : order.status === 'ready' ? 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300' : order.status === 'cooking' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300' : 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-300';
                const itemsHtml = order.items.map(item => `<span class="text-xs text-gray-500 dark:text-gray-400 block">${item.quantity}x ${item.item_name}</span>`).join('');
                return `<div onclick="openOrderModal(${index})" class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 flex justify-between items-center shadow-sm animate-fade-in cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors group"><div><div class="flex items-center gap-2 mb-1"><span class="font-bold text-gray-800 dark:text-gray-200 group-hover:text-orange-600 transition-colors">Order #${order.id}</span><span class="px-2 py-0.5 text-xs rounded-full font-bold uppercase ${statusColor}">${order.status}</span></div><div class="mb-1">${itemsHtml}</div><p class="text-xs text-gray-400">${date}</p></div><div class="text-right"><span class="font-bold text-gray-800 dark:text-white text-lg block">RM ${parseFloat(order.total_amount).toFixed(2)}</span><span class="text-xs text-orange-500 font-medium">View Ticket <i class="fas fa-chevron-right ml-1"></i></span></div></div>`;
            }).join('');
            if(container.innerHTML !== html) { container.innerHTML = html; }
        }
        function openOrderModal(index) {
            const order = currentOrdersList[index];
            if(!order) return;
            document.getElementById('modalOrderId').innerText = '#' + order.id;
            document.getElementById('modalTotal').innerText = 'RM ' + parseFloat(order.total_amount).toFixed(2);
            document.getElementById('modalStatus').innerText = order.status;
            
            // Remark Logic in Modal
            const remarkContainer = document.getElementById('modalRemarkContainer');
            if (order.remark && order.remark.trim() !== "") {
                document.getElementById('modalRemarkText').innerText = order.remark;
                remarkContainer.classList.remove('hidden');
            } else {
                remarkContainer.classList.add('hidden');
            }

            document.getElementById('modalItems').innerHTML = order.items.map(item => `<div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300"><span class="font-bold text-gray-800 dark:text-white">x${item.quantity}</span> ${item.item_name}</span><span class="font-medium text-gray-500 dark:text-gray-400">RM ${(item.price * item.quantity).toFixed(2)}</span></div>`).join('');
            document.getElementById('orderDetailModal').classList.remove('hidden');
        }
        function closeOrderModal() { document.getElementById('orderDetailModal').classList.add('hidden'); }

        document.addEventListener('DOMContentLoaded', () => { loadOrders('all'); setInterval(() => loadOrders(currentFilter, true), 5000); });
    </script>
</body>
</html>