<div class="form-group col-sm-8 ">
    <table class="table table-condensed" style="font-size: 12px;">
        <tr>
            <td>流程ID</td>
            <td>{{ $userFlow->id  }}</td>
            <td>申请人</td>
            <td>{{ $userFlow->user->name ?? null }}</td>
            <td>申请时间</td>
            <td>{{ $userFlow->created_at  }}</td>
        </tr>
    </table>
</div>
<div class="col-sm-4 hidden-print">
    <a class="btn btn-success"
       href="/admin/userflows/{{ $userFlow->flow_id }}/new?userflow={{ $userFlow->id }}">重发</a>
    <button class="btn btn-default" type="button" id="print-flow">打印</button>
</div>
<div class="clearfix"></div>

