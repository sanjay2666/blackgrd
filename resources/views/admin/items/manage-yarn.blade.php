<!DOCTYPE html>
<html lang="en">
<head>
@include('admin.common.head')
</head>
<body class="hold-transition sidebar-mini">
    <div id="preloader"><div id="status"></div></div>
    <div class="wrapper">
        @include('admin.common.header')
        @include('admin.common.sidebar')
        <div class="content-wrapper">
            <section class="content-header">
                <div class="header-icon"><small><i class="fa fa-list"></i></small></div>
                <div class="header-title">
                    <h1>Manage Yarn</h1> 
                </div>
            </section>

            <section class="content">
                {!! display_message('message') !!}
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading">
                                <a class="btn btn-add" href="{{ route('admin.items.index', ['item_type_id' => 8]) }}"><i class="fa fa-list"></i> Items List</a>
                                <div class="btn-group">
                                    <h4>Manage Yarn for <span style="text-decoration:underline">{{ $item->item_name }}</span></h4>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr class="info">
                                                <th>Process</th>
                                                <th>Yarn</th>
                                                <th>Reed/Pick</th>
                                                <th>Quantity</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($requirements as $row)
                                                <tr id="yarn-row-{{ $row->id }}">
                                                    <td>{{ $row->process->process_name ?? $row->process_id }}</td>
                                                    <td>{{ $row->yarnItem->item_name ?? '' }}</td>
                                                    <td>Reed / Pick : {{ $row->reed_peak }}</td>
                                                    <td>{{ $row->yarn_quantity }} {{ $row->unit }}</td>
                                                    <td><button type="button" class="btn btn-danger btn-xs" onclick="deleteYarn({{ $row->id }})">Delete</button></td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center">No yarn added.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <h4>Add Yarn</h4>
                                <form method="POST" action="{{ route('admin.items.add-manage-yarn') }}" autocomplete="off">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $item->item_id }}">

                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="myTable">
                                            <thead>
                                                <tr class="info">
                                                    <th>Process</th>
                                                    <th>Yarn</th>
                                                    <th>Reed/Pick</th>
                                                    <th>Quantity</th>
                                                    <th>Unit</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <select onchange="changeProcess(1)" class="form-control processid_1" name="process_id[]" required>
                                                            <option value="">Select Process</option>
                                                            @foreach ($processOptions as $processId => $processName)
                                                                <option value="{{ $processId }}">{{ $processName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-control" name="yarn_id[]" required>
                                                            <option value="">Select Yarn</option>
                                                            @foreach ($yarnItems as $yarnItem)
                                                                <option value="{{ $yarnItem->item_id }}">{{ $yarnItem->item_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <span id="readpk-1"></span>
                                                        <input type="number" min="1" class="form-control" name="reed_peak[]" required>
                                                    </td>
                                                    <td><input type="number" min="1" class="form-control" name="yarn_quantity[]" required></td>
                                                    <td>{{ $yarnItems->first()?->unitType?->unit_type_name ?? 'See Yarn Item' }}</td>
                                                    <td><button type="button" class="btn btn-success btn-xs" onclick="addRowNew()">Add Row</button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="reset-button">
                                        <a href="{{ route('admin.items.index', ['item_type_id' => 8]) }}" class="btn btn-warning">Cancel</a>
                                        <button type="submit" class="btn btn-success">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        @include('admin.common.footer')
    </div>

    @include('admin.common.formfooterscript')
    <script>
        var processOptions = @json($processOptions);
        var yarnItems = @json($yarnItems->map(function ($row) {
            return ['item_id' => $row->item_id, 'item_name' => $row->item_name];
        })->values());

        function addRowNew() {
            var tableBody = document.getElementById("myTable").getElementsByTagName("tbody")[0];
            var rowCount = tableBody.rows.length + 1;
            var newRow = tableBody.insertRow(tableBody.rows.length);

            var processOptionsHtml = '<option value="">Select Process</option>';
            for (var processId in processOptions) {
                processOptionsHtml += '<option value="' + processId + '">' + processOptions[processId] + '</option>';
            }

            var yarnOptionsHtml = '<option value="">Select Yarn</option>';
            for (var i = 0; i < yarnItems.length; i++) {
                yarnOptionsHtml += '<option value="' + yarnItems[i].item_id + '">' + yarnItems[i].item_name + '</option>';
            }

            newRow.innerHTML = ''
                + '<td><select onchange="changeProcess(' + rowCount + ')" class="form-control processid_' + rowCount + '" name="process_id[]" required>' + processOptionsHtml + '</select></td>'
                + '<td><select class="form-control" name="yarn_id[]" required>' + yarnOptionsHtml + '</select></td>'
                + '<td><span id="readpk-' + rowCount + '"></span><input type="number" min="1" class="form-control" name="reed_peak[]" required></td>'
                + '<td><input type="number" min="1" class="form-control" name="yarn_quantity[]" required></td>'
                + '<td>See selected Yarn Item unit</td>'
                + '<td><button type="button" class="btn btn-danger btn-xs" onclick="deleteRow(this)">Delete</button></td>';
        }

        function deleteRow(button) {
            var row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }

        function changeProcess(rowNumber) {
            var processId = $(".processid_" + rowNumber).val();

            if (processId == 1) {
                $("#readpk-" + rowNumber).html("EPI ");
            } else if (processId == 2) {
                $("#readpk-" + rowNumber).html("PPI ");
            } else {
                $("#readpk-" + rowNumber).html("");
            }
        }

        function deleteYarn(id) {
            if (!confirm("Do you really want to delete this record?")) {
                return;
            }

            $.ajax({
                type: "DELETE",
                url: "{{ url('/admin/items/delete-yarn') }}/" + id,
                data: { _token: "{{ csrf_token() }}" },
                success: function() {
                    $("#yarn-row-" + id).hide();
                },
                error: function() {
                    alert("Record delete nahi ho paya. Please try again.");
                }
            });
        }
    </script>
</body>
</html>
