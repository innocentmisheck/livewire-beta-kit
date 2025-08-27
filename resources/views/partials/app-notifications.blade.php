<!-- Notification Popup -->
<div id="notificationPopup" class="absolute  w-full max-h-[70vh] bg-white dark:bg-neutral-800 rounded-lg shadow-xl z-50 overflow-y-auto hidden">
    <div class="sticky top-0 bg-white dark:bg-gray-800 p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
        <button id="closeNotification" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <!-- Notification Content -->
    <div class="p-3 space-y-3">
        <!-- Sample Notification -->
        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
            <p class="text-sm font-medium text-gray-900 dark:text-white">Hello at Coins!</p>
            <p class="text-xs text-gray-600 dark:text-gray-300">Welcome to our cryptocurrency platform</p>
            <span class="text-xs text-gray-500 dark:text-gray-400">Just now</span>
        </div>
        <!-- Add more notification formats -->
        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
            <p class="text-sm font-medium text-gray-900 dark:text-white">Price Alert</p>
            <p class="text-xs text-gray-600 dark:text-gray-300">Bitcoin reached $60,000</p>
            <span class="text-xs text-gray-500 dark:text-gray-400">5 minutes ago</span>
        </div>
        <div class="pb-3">
            <p class="text-sm font-medium text-gray-900 dark:text-white">Transaction Complete</p>
            <p class="text-xs text-gray-600 dark:text-gray-300">You sent 0.5 ETH successfully</p>
            <span class="text-xs text-gray-500 dark:text-gray-400">1 hour ago</span>
        </div>
    </div>
</div>

<script>
    // Existing menu toggle script
    document.getElementById('menuToggle').addEventListener('click', function () {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Notification toggle
    const notificationPopup = document.getElementById('notificationPopup');
    const closeNotification = document.getElementById('closeNotification');
    
    // Show notification by default
    notificationPopup.classList.remove('hidden');

    // Automatically hide after 1 seconds (1000 milliseconds)
    setTimeout(() => {
        notificationPopup.classList.add('hidden');
    }, 5000);

    // Close notification when X is clicked
    closeNotification.addEventListener('click', function() {
        notificationPopup.classList.add('hidden');
    });

    // Optional: Add a button to show notifications if needed
    // You could add a bell icon button next to the menuToggle to show/hide notifications
</script>