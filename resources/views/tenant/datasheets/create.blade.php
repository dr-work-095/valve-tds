<x-layouts.dashboard :page-title="'New datasheet'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6">
                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Step 1 of 2</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">New datasheet</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Enter project details and choose the valve configuration.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.datasheets.prepare') }}">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="client_name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Client name</label>
                        <input id="client_name" name="client_name" required maxlength="255" value="{{ old('client_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        @error('client_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="project_reference" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Project reference</label>
                        <input id="project_reference" name="project_reference" maxlength="255" value="{{ old('project_reference') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        @error('project_reference')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="document_number" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Document number</label>
                        <input id="document_number" name="document_number" maxlength="100" value="{{ old('document_number') }}" placeholder="Auto-generated if blank" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        @error('document_number')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="valve_model_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Valve model</label>
                        <select id="valve_model_id" name="valve_model_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            <option value="">Select a ready model</option>
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}" @selected((string) old('valve_model_id') === (string) $model->id)>{{ $model->name }} — {{ $model->valve_category }} — {{ $model->valve_type }}</option>
                            @endforeach
                        </select>
                        @error('valve_model_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="dimension_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Dimension</label>
                        <select id="dimension_id" name="dimension_id" required disabled class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-700">
                            <option value="">Select a valve model first</option>
                        </select>
                        <p id="dimension-error" class="mt-1 hidden text-sm text-red-700" role="alert"></p>
                        @error('dimension_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button id="next-step" type="submit" disabled class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Next
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const modelSelect = document.getElementById('valve_model_id');
            const dimensionSelect = document.getElementById('dimension_id');
            const dimensionError = document.getElementById('dimension-error');
            const nextButton = document.getElementById('next-step');
            const lookupUrl = @json(route('tenant.datasheets.lookups.model', '__MODEL__'));
            const oldDimensionId = @json((string) old('dimension_id', ''));

            function updateNextButton() {
                nextButton.disabled = dimensionSelect.disabled || !dimensionSelect.value;
            }

            async function loadDimensions() {
                dimensionSelect.replaceChildren(new Option('Loading dimensions…', ''));
                dimensionSelect.disabled = true;
                updateNextButton();
                dimensionError.classList.add('hidden');

                if (!modelSelect.value) {
                    dimensionSelect.replaceChildren(new Option('Select a valve model first', ''));
                    updateNextButton();
                    return;
                }

                try {
                    const response = await fetch(lookupUrl.replace('__MODEL__', encodeURIComponent(modelSelect.value)));
                    if (!response.ok) {
                        throw new Error('The valve dimensions could not be loaded.');
                    }

                    const data = await response.json();
                    dimensionSelect.replaceChildren(new Option(
                        data.dimensions.length ? 'Select a dimension' : 'No dimensions available',
                        '',
                    ));
                    data.dimensions.forEach((dimension) => {
                        const label = [
                            dimension.size_dn,
                            dimension.size_inch,
                            dimension.pressure_class,
                            dimension.end_connection,
                        ].filter(Boolean).join(' / ');
                        dimensionSelect.add(new Option(
                            label,
                            dimension.id,
                            false,
                            String(dimension.id) === oldDimensionId,
                        ));
                    });
                    dimensionSelect.disabled = data.dimensions.length === 0;
                    updateNextButton();
                } catch (error) {
                    console.error(error);
                    dimensionSelect.replaceChildren(new Option('Unable to load dimensions', ''));
                    dimensionError.textContent = 'Unable to load dimensions. Please try selecting the model again.';
                    dimensionError.classList.remove('hidden');
                    updateNextButton();
                }
            }

            modelSelect.addEventListener('change', loadDimensions);
            dimensionSelect.addEventListener('change', updateNextButton);
            if (modelSelect.value) {
                loadDimensions();
            }
        </script>
    @endpush
</x-layouts.dashboard>
