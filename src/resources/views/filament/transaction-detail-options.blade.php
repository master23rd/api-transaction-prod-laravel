<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500">Product</p>
            <p class="text-base font-semibold">{{ $record->product->name }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Quantity</p>
            <p class="text-base font-semibold">{{ $record->quantity }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Unit Price</p>
            <p class="text-base font-semibold">Rp {{ number_format($record->price, 0, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Subtotal</p>
            <p class="text-base font-semibold">Rp {{ number_format($record->total_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    @if($record->options->count() > 0)
        <div class="border-t pt-4">
            <p class="text-sm font-medium text-gray-500 mb-2">Selected Options</p>
            <div class="flex flex-wrap gap-2">
                @foreach($record->options as $option)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                        {{ $option->productOption->name }}
                        @if($option->price > 0)
                            <span class="ml-1 text-xs">(+Rp {{ number_format($option->price, 0, ',', '.') }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
