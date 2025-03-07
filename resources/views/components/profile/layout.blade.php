<div class="flex items-start max-md:flex-col">
    <!-- Main Content Section -->
    <div class="flex-1 self-stretch max-md:pt-6">
{{--        <flux:heading>{{ $heading ?? 'Wallet Settings' }}</flux:heading>--}}
{{--        <flux:subheading>{{ $subheading ?? 'Manage your wallet, transactions, and security settings here.' }}</flux:subheading>--}}
        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
