@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Logistic :: Shipclues</h5>
                <small class="text-muted">Couriers from your Shipclues account are offered to every seller while this is active.</small>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('logistics') }}" class="btn btn-outline-secondary">Back to logistics</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <p class="mb-3">
            Status:
            @if ($provider && (string) $provider->status === '1' && ($hasKey || $envKey))
                <span class="badge bg-success">Live for all sellers</span>
            @elseif (!$hasKey && !$envKey)
                <span class="badge bg-warning text-dark">API key missing</span>
            @else
                <span class="badge bg-secondary">Inactive</span>
            @endif
            @if ($envKey)
                <span class="text-muted ms-2">(using SHIPCLUES_API_KEY from .env)</span>
            @endif
        </p>

        <form method="POST" action="{{ route('shipclues.settings.save') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="api_key">API key</label>
                <input type="password" class="form-control" id="api_key" name="api_key" autocomplete="off"
                       placeholder="{{ $hasKey ? 'Saved — leave blank to keep it' : 'Paste from dashboard.shipclues.in > Tools > API Docs' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="markup_percent">Margin on Shipclues cost (%)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="markup_percent" name="markup_percent"
                       value="{{ old('markup_percent', $settings['markup_percent']) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="markup_flat">Extra per shipment (₹)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="markup_flat" name="markup_flat"
                       value="{{ old('markup_flat', $settings['markup_flat']) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="1" @selected(!$provider || (string) $provider->status === '1')>Active (all sellers)</option>
                    <option value="0" @selected($provider && (string) $provider->status !== '1')>Inactive</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('shipclues.settings', ['test' => 1]) }}" class="btn btn-outline-primary ms-2">Test live rates</a>
            </div>
        </form>

        @if (is_array($test))
            <hr>
            <h6>Live rates: 110053 → 500001, 500 g, prepaid (seller price incl. your margin)</h6>
            @if (count($test))
                <table class="table table-sm table-striped mt-2" style="max-width:640px">
                    <thead><tr><th>Courier</th><th>Zone</th><th class="text-end">Seller price</th></tr></thead>
                    <tbody>
                    @foreach ($test as $r)
                        <tr><td>{{ $r['courierName'] }}</td><td>{{ $r['zone'] }}</td><td class="text-end">₹{{ number_format($r['courierCharge'], 2) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning mt-2">No rates came back. Check the API key and that couriers are enabled on your Shipclues account.</div>
            @endif
        @endif
    </div>
</div>
@endsection
