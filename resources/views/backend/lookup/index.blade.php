@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<section>
    <div class="container-fluid">
        <h4 class="mb-1">{{ $meta['label'] }}</h4>
        <p class="text-muted">{{ $meta['help'] }}</p>
        <a href="#" data-toggle="modal" data-target="#lookupCreateModal" class="btn btn-info"><i class="dripicons-plus"></i> Add {{ $meta['singular'] }}</a>
    </div>

    <div class="table-responsive mt-3">
        <table id="lookup-table" class="table">
            <thead>
                <tr>
                    <th>{{ $meta['singular'] }}</th>
                    @if($meta['has_code'])<th>Code</th>@endif
                    @if($type === 'charger_model')<th>Linked products</th>@endif
                    <th>Status</th>
                    <th class="not-exported">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    @if($meta['has_code'])<td>{{ $item->code }}</td>@endif
                    @if($type === 'charger_model')<td>{{ $usage[$item->id] ?? 0 }}</td>@endif
                    <td>
                        @if($item->is_active)<span class="badge badge-success">Active</span>@else<span class="badge badge-secondary">Inactive</span>@endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-default lookup-edit-btn"
                                data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-code="{{ $item->code }}" data-active="{{ $item->is_active ? 1 : 0 }}"
                                data-toggle="modal" data-target="#lookupEditModal"><i class="dripicons-document-edit"></i> Edit</button>
                        {{ Form::open(['route' => ['lookups.destroy', $type, $item->id], 'method' => 'DELETE', 'style' => 'display:inline']) }}
                        <button type="submit" class="btn btn-sm btn-default" onclick="return confirm('Remove this entry?')"><i class="dripicons-trash"></i> Delete</button>
                        {{ Form::close() }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="lookupCreateModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
      {!! Form::open(['route' => ['lookups.store', $type], 'method' => 'post']) !!}
      <div class="modal-header">
        <h5 class="modal-title">Add {{ $meta['singular'] }}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" required class="form-control" placeholder="{{ $type === 'processor' ? 'e.g. Ryzen 5 7535HS (will be formatted automatically)' : ($type === 'charger_model' ? 'e.g. HP 65W Type-C' : '') }}">
        </div>
        @if($meta['has_code'])
        <div class="form-group">
          <label>Code</label>
          <input type="text" name="code" class="form-control" placeholder="Optional short code">
        </div>
        @endif
        <input type="submit" value="Save" class="btn btn-primary">
      </div>
      {{ Form::close() }}
    </div>
  </div>
</div>

<div id="lookupEditModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
      <form id="lookup-edit-form" method="POST" action="">
      @csrf
      @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Edit {{ $meta['singular'] }}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" required class="form-control">
        </div>
        @if($meta['has_code'])
        <div class="form-group">
          <label>Code</label>
          <input type="text" name="code" class="form-control">
        </div>
        @endif
        <div class="form-group">
          <div class="checkbox">
            <input type="checkbox" name="is_active" id="lookup-active" value="1">
            <label for="lookup-active">Active (shown in dropdowns)</label>
          </div>
        </div>
        <input type="submit" value="Update" class="btn btn-primary">
      </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    var kgMenu = {!! json_encode(in_array($type, ['cargo_company', 'hand_carry_person']) ? 'purchase' : 'product') !!};
    $("ul#" + kgMenu).siblings('a').attr('aria-expanded','true');
    $("ul#" + kgMenu).addClass("show");
    $("ul#" + kgMenu + " #lookup-{{ $type }}-menu").addClass("active");

    $(document).on('click', '.lookup-edit-btn', function () {
        var b = $(this);
        $('#lookup-edit-form').attr('action', '{{ url("lookups/".$type) }}/' + b.data('id'));
        $('#lookupEditModal input[name="name"]').val(b.data('name'));
        $('#lookupEditModal input[name="code"]').val(b.data('code'));
        $('#lookup-active').prop('checked', b.data('active') == 1);
    });

    $('#lookup-table').DataTable({
        order: [],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: -1 }],
        language: { search: 'Search' }
    });
</script>
@endpush
