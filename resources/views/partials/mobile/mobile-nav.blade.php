<!-- Mobile Menu Button -->
<div class="md:hidden">
    <button id="menuToggle" class="text-gray-700 dark:text-gray-300 focus:outline-none flex items-center">
        <x-app-logo/>
    </button>
</div>

<!-- Mobile Menu -->
<div id="mobileMenu" class="lg:hidden md:hidden fixed top-16 mt-1 left-0 w-full bg-white dark:bg-gray-900 rounded-xl shadow-lg p-4 flex flex-col space-y-4 z-10 hidden">
    <!-- Market Link with Chart Icon -->
    <a href="/app" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
        Market
    </a>

    <!-- Wallet Link with Wallet Icon -->
    <a href="/wallet/settings" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        Wallet
    </a>

    <!-- Notifications Link with Bell Icon -->
    <a href="#" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a3 3 0 00-6 0v.083A6 6 0 002 11v3.159c0 .538-.214 1.055-.595 1.436L0 17h5m10 0v1a3 3 0 01-6 0v-1m6 0H9"></path>
        </svg>
        Notifications
    </a>

    <!-- Settings Link with Cog Icon -->
    <a href="/settings/profile" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37 1 .608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        Settings
    </a>
</div>

<script>
    window.addEventListener("DOMContentLoaded", function () {
        console.log("Script running");

        const menuToggle = document.getElementById("menuToggle");
        const mobileMenu = document.getElementById("mobileMenu");

        if (!menuToggle || !mobileMenu) {
            console.error("Menu elements not found:", { menuToggle, mobileMenu });
            return;
        }

        // Toggle menu visibility with improved event handling
        menuToggle.addEventListener("click", function (event) {
            event.stopPropagation();
            console.log("Menu toggle clicked, hidden class:", mobileMenu.classList.contains("hidden"));
            mobileMenu.classList.toggle("hidden");
            console.log("After toggle, hidden class:", mobileMenu.classList.contains("hidden"));
        });

        // Close menu when clicking a link
        document.querySelectorAll("#mobileMenu a").forEach(link => {
            link.addEventListener("click", function () {
                console.log("Menu link clicked, closing menu");
                mobileMenu.classList.add("hidden");
            });
        });

        // Close menu if clicking outside, but only if menu is visible
        document.addEventListener("click", function (event) {
            if (!mobileMenu.classList.contains("hidden") && 
                !mobileMenu.contains(event.target) && 
                !menuToggle.contains(event.target)) {
                console.log("Clicked outside, closing menu");
                mobileMenu.classList.add("hidden");
            }
        });

        // Add escape key listener to close menu
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !mobileMenu.classList.contains("hidden")) {
                console.log("Escape key pressed, closing menu");
                mobileMenu.classList.add("hidden");
            }
        });

        // Debug initial visibility
        console.log("Initial menu state - hidden:", mobileMenu.classList.contains("hidden"));
    });
</script>