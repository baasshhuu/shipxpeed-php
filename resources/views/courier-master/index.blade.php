@extends('layouts.app')

@section('content')
<style>
.cm-card{background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.cm-section-title{font-size:16px;font-weight:600;margin-bottom:15px}
.cm-form-row{margin-bottom:15px}
.cm-form-row label{display:block;font-weight:600;margin-bottom:6px;font-size:13px}
.cm-radio-group{display:flex;gap:20px}
.cm-slab-row{display:flex;gap:10px;margin-bottom:8px;align-items:center}
.cm-slab-row input{flex:1}
.cm-table{width:100%;border-collapse:collapse}
.cm-table th, .cm-table td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:13px;vertical-align:top}
.cm-logo-thumb{width:36px;height:36px;object-fit:contain;border:1px solid #eee;border-radius:4px}
.cm-code-badge{background:#f0f0f0;padding:2px 8px;border-radius:4px;font-family:monospace;font-size:12px}
.cm-edit-form{display:none;background:#f9f9fc;padding:15px;border-radius:8px;margin-top:8px}
.cm-slab-tag{display:inline-block;background:#eef;padding:2px 8px;border-radius:10px;font-size:11px;margin:2px}
</style>
<div class="container-fluid">
    <div class="cm-card">
        <h4>Courier Master</h4>
        <p class="text-muted" style="margin-bottom:0">Add and manage master couriers, sub-courier accounts, and their slabs.</p>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="cm-card">
        <div class="cm-section-title">Add New Courier</div>
        <form action="{{ route('courier.master.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="cm-form-row">
                <label>Master Courier Name</label>
                <select name="master_courier_id" id="cm-master-select" class="form-control" onchange="document.getElementById('cm-new-master-row').style.display = this.value === '' ? 'block' : 'none';">
                    <option value="">+ Add New Master Courier</option>
                    @foreach($couriers as $courier)
                        <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cm-form-row" id="cm-new-master-row">
                <label>New Master Courier Name</label>
                <input type="text" name="new_master_courier_name" class="form-control" placeholder="e.g. Delhivery">
            </div>
            <div class="cm-form-row">
                <label>Sub Courier Name</label>
                <input type="text" name="sub_courier_name" class="form-control" placeholder="e.g. Delhivery Zapdeal" required>
            </div>
            <div class="cm-form-row">
                <label>Mode Type</label>
                <div class="cm-radio-group">
                    <label><input type="radio" name="mode_type" value="Air"> Air</label>
                    <label><input type="radio" name="mode_type" value="Surface" checked> Surface</label>
                </div>
            </div>
            <div class="cm-form-row">
                <label>Load Type</label>
                <div class="cm-radio-group">
                    <label><input type="radio" name="load_type" value="B2B"> B2B</label>
                    <label><input type="radio" name="load_type" value="B2C" checked> B2C</label>
                </div>
            </div>
            <div class="cm-form-row">
                <label>Courier Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            <div class="cm-form-row">
                <label>Slabs</label>
                <div id="cm-slabs-container">
                    <div class="cm-slab-row">
                        <input type="text" name="slabs[]" class="form-control" placeholder="Slab name e.g. Zapdeal Standard">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cmAddSlabRow()">+ Add Another Slab</button>
            </div>
            <button type="submit" class="btn btn-primary">Save Courier</button>
        </form>
    </div>

    <div class="cm-card">
        <div class="cm-section-title">Existing Couriers</div>
        <table class="cm-table">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Master Courier</th>
                    <th>Sub Courier</th>
                    <th>Code</th>
                    <th>Load Type</th>
                    <th>Mode Type</th>
                    <th>Slabs</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td>
                        @if($account->logo)
                            <img src="{{ asset('storage/' . $account->logo) }}" class="cm-logo-thumb">
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $account->master_courier_name }}</td>
                    <td>{{ $account->name }}</td>
                    <td><span class="cm-code-badge">{{ $account->code }}</span></td>
                    <td>{{ $account->courier_type }}</td>
                    <td>{{ $account->mode_type }}</td>
                    <td>
                        @forelse($slabsByAccount->get($account->id, []) as $slab)
                            <span class="cm-slab-tag">{{ $slab->name }}</span>
                        @empty
                            <span class="text-muted">None</span>
                        @endforelse
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('edit-form-{{ $account->id }}').style.display = document.getElementById('edit-form-{{ $account->id }}').style.display === 'block' ? 'none' : 'block';">Edit</button>
                    </td>
                </tr>
                <tr>
                    <td colspan="8">
                        <div class="cm-edit-form" id="edit-form-{{ $account->id }}">
                            <form action="{{ route('courier.master.update', $account->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="cm-form-row">
                                    <label>Sub Courier Name</label>
                                    <input type="text" name="sub_courier_name" class="form-control" value="{{ $account->name }}" required>
                                </div>
                                <div class="cm-form-row">
                                    <label>Mode Type</label>
                                    <div class="cm-radio-group">
                                        <label><input type="radio" name="mode_type" value="Air" {{ $account->mode_type === 'Air' ? 'checked' : '' }}> Air</label>
                                        <label><input type="radio" name="mode_type" value="Surface" {{ $account->mode_type === 'Surface' ? 'checked' : '' }}> Surface</label>
                                    </div>
                                </div>
                                <div class="cm-form-row">
                                    <label>Load Type</label>
                                    <div class="cm-radio-group">
                                        <label><input type="radio" name="load_type" value="B2B" {{ $account->courier_type === 'B2B' ? 'checked' : '' }}> B2B</label>
                                        <label><input type="radio" name="load_type" value="B2C" {{ $account->courier_type === 'B2C' ? 'checked' : '' }}> B2C</label>
                                    </div>
                                </div>
                                <div class="cm-form-row">
                                    <label>Replace Logo (optional)</label>
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                </div>
                                <div class="cm-form-row">
                                    <label>Add More Slabs</label>
                                    <input type="text" name="new_slabs[]" class="form-control" placeholder="New slab name (optional)">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Courier</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-muted">No couriers added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
function cmAddSlabRow(){
    var container = document.getElementById('cm-slabs-container');
    var row = document.createElement('div');
    row.className = 'cm-slab-row';
    row.innerHTML = '<input type="text" name="slabs[]" class="form-control" placeholder="Slab name">';
    container.appendChild(row);
}
</script>
@endsection
