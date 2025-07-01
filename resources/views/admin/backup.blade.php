@extends('layouts.admin')

@section('title')
    Auto Backup
@endsection

@section('content-header')
    <h1>Auto Backup <small>Manage automatic backup settings.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Auto Backup</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 col-lg-5">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Automatic Backup Settings</h3>
                </div>
                <form method="post" action="{{ route('admin.backup.save') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="run_at">Run At Every Day</label>
                            <input type="time" name="run_at" id="run_at" class="form-control" value="{{ old('run_at', $run_at) }}">
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="days">Days to Store</label>
                            <div class="input-group">
                                <input type="number" name="days" id="days" class="form-control" value="{{ old('days', $days) }}">
                                <span class="input-group-addon">Day(s)</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="weeks">Weeks to Store</label>
                            <div class="input-group">
                                <input type="number" name="weeks" id="weeks" class="form-control" value="{{ old('weeks', $weeks) }}">
                                <span class="input-group-addon">Week(s)</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="months">Months to Store</label>
                            <div class="input-group">
                                <input type="number" name="months" id="months" class="form-control" value="{{ old('months', $months) }}">
                                <span class="input-group-addon">Month(s)</span>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="backup_name">Backup Name</label>
                            <input type="text" id="backup_name" name="backup_name" class="form-control" value="{{ old('backup_name', $name) }}">
                            <small class="text-muted"><code>[DATE]</code> will be replaced to the current date.</small>
                        </div>
                        <div class="form-group">
                            <label for="excluded_nodes">Excluded Nodes</label>
                            <select id="excluded_nodes" name="excluded_nodes[]" class="form-control" multiple>
                                @foreach (\Pterodactyl\Models\Node::all() as $node)
                                    <option value="{{ $node->id }}" {{ in_array($node->id, old('excluded_nodes', $excluded_nodes)) ? 'selected' : '' }}>{{ $node->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-success pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xs-12 col-lg-2">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Help</h3>
                </div>
                <div class="box-body">
                    <p>
                        Backup <b>creation</b> and <b>deletion</b> will be processed every day at <b>{{ $run_at }}</b>.
                    </p>
                    <p>
                        <b>Days:</b> It'll store every backup in the last <b>{{ $days }}</b> days.
                    </p>
                    <p>
                        <b>Weeks:</b> It'll store every 1st backup of week in the last <b>{{ $weeks }}</b> weeks. (counted before the days)
                    </p>
                    <p>
                        <b>Months:</b> It'll store every 1st backup of month in the last <b>{{ $months }}</b> months. (counted before the weeks)
                    </p>
                    <p>
                        <b>All the old backups will be deleted.</b>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent

    <script>
        $('#excluded_nodes').select2({
            placeholder: '- Select Excluded Node(s) -',
        });
    </script>
@endsection
