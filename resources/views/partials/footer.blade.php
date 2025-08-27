

    <!-- Footer -->
    <footer class="bg-glass backdrop-blur-md shadow-md mt-15 p-4 rounded-xl flex items-center justify-between w-full fixed bottom-0 left-0">
        <div class="flex items-center space-x-3">
            <!-- Light/Dark/Auto Mode Toggle -->
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun"></flux:radio>
                <flux:radio value="dark" icon="moon"></flux:radio>
                <flux:radio value="system" icon="computer-desktop"></flux:radio>
            </flux:radio.group>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Footer Information -->
            <p class="text-sm text-gray-600">© {{ date('Y') }}</p>
            <!-- App Logo aligned to the right -->
            <x-app-logo class="ml-4" />
        </div>
    </footer>
