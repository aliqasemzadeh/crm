<div class="max-w-4xl mx-auto p-6 space-y-6">

    <flux:card>
        <flux:heading size="lg">
            Sepidar Device Integration
        </flux:heading>

        <flux:subheading>
            Register device and fetch Items using secure encryption
        </flux:subheading>
    </flux:card>

    {{-- Step 1 --}}
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="md">Step 1 · Register Device</flux:heading>
                <flux:text class="text-sm text-gray-500">
                    Registers device and retrieves RSA public key
                </flux:text>
            </div>

            <flux:button
                wire:click="registerDevice"
                variant="primary"
                icon="key"
            >
                Register Device
            </flux:button>
        </div>

        @if($deviceTitle)
            <flux:separator class="my-4" />

            <flux:badge color="green">
                Device Registered
            </flux:badge>

            <flux:input
                label="Device Title"
                value="{{ $deviceTitle }}"
                readonly
            />

            <flux:textarea
                label="Public Key (XML)"
                rows="6"
                readonly
            >
{{ $publicKeyXml }}
            </flux:textarea>
        @endif
    </flux:card>

    {{-- Step 2 --}}
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="md">Step 2 · Get Items</flux:heading>
                <flux:text class="text-sm text-gray-500">
                    Calls Items API using encrypted headers
                </flux:text>
            </div>

            <flux:button
                wire:click="getItems"
                icon="list-bullet"
                :disabled="!$publicKeyXml"
            >
                Get Items
            </flux:button>
        </div>

        @if($itemsResponse)
            <flux:separator class="my-4" />

            <flux:badge color="blue">
                Items Response
            </flux:badge>

            <flux:textarea
                rows="10"
                readonly
            >
{{ $itemsResponse }}
            </flux:textarea>
        @endif
    </flux:card>

</div>
