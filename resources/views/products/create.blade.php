<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Product
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('products.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block font-semibold">Product Name</label>
                        <input type="text" name="name" class="border w-full p-2 rounded" required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-semibold">Description</label>
                        <textarea name="description" class="border w-full p-2 rounded"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block font-semibold">Price (USD)</label>
                        <input type="number" step="0.01" name="price_usd" class="border w-full p-2 rounded" required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-semibold">Price (JPY)</label>
                        <input type="number" step="0.01" name="price_jpy" class="border w-full p-2 rounded" required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-semibold">Stock</label>
                        <input type="number" name="stock" class="border w-full p-2 rounded" required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-semibold">Warehouse</label>
                        <select name="warehouse_id" class="border w-full p-2 rounded" required>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">
                                    {{ $warehouse->name }} ({{ $warehouse->location }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">
                            Create Product
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
