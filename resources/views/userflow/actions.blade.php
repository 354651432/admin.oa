<form action="/admin/userflows/approval"
      method="POST"
      class="hidden-print"
      onsubmit="$('#apply-button').attr('disabled','disabled')">
    {{ csrf_field() }}
    <input type="hidden" name="node-id" value="{{ $node->id??0 }}">
    <input type="hidden" name="result" id="result">

    <div class="row col-md-12 actions">
        <div class="col-sm-10">
            @if($node)
                <button class="btn btn-info"
                        onclick="apply(1)"
                        type="button">同意
                </button>
                <button class="btn btn-warning"
                        onclick="apply(2)"
                        type="button">拒绝
                </button>
                <button class="btn btn-danger"
                        onclick="apply(4)"
                        type="button">转签
                </button>
                <button class="btn btn-primary"
                        onclick="apply(5)"
                        type="button">加签
                </button>
            @endif
            @if($flow->canCancel())
                <a class="btn btn-danger"
                   href="/admin/userflows/{{ $flow->id }}/cancel"
                   type="button">撤回
                </a>
            @endif
        </div>
    </div>

    <div class="modal fade" id="applyDialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 id="apply-title">审核</h4>
                </div>
                <div class="modal-body form-horizontal">
                    <div class="form-group" id="user-select">
                        <label class="col-sm-2 control-label">转给</label>
                        <div class="col-sm-3">
                            <select name="to-user" id="to-user" class="form-control">
                                <option value="">请选择</option>
                                @foreach($users as $id => $user)
                                    <option value="{{ $id }}">{{ $user }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <textarea name="apply-text" id="apply-text" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="apply-button" class="btn btn-primary btn-sm">确定</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="/js/html2canvas.min.js"></script>
<script>
    function apply(result) {
        // 转签 加签 需要用户
        if (result === 4 || result === 5) {
            $("#user-select").show();
        } else {
            $("#user-select").hide();
        }

        // 同意的时候 可以不写意见
        if (result === 1) {
            $("#apply-text").removeAttr("required");
        } else {
            $("#apply-text").attr("required", "required");
        }

        $("#result").val(result);
        $('#applyDialog').modal('show');

        var arr = {1: "同意", 2: "拒绝", 3: "不同意继续", 4: "转签", 5: "加签"};
        $("#apply-title").text(arr[result]);
    }

    $(function () {
        $("#to-user").select2();

        $('#print-flow').click(function () {
            // window.print();

            var form = document.querySelector("#flow-form");
            var nodes = document.querySelector("#flow-nodes");
            document.body.innerHTML = "";
            document.body.appendChild(form);
            document.body.appendChild(nodes);
            $("input,label,td,textarea,span").css({
                color: "black",
                "font-family": "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif",
                "font-weight": "bold",
                "font-size": "14px",
                "vertical-align": "middle !important",
            });
            document.body.style.width = "1095px";
            document.body.style.marginTop = "10px";
            document.body.style.marginLeft = "7px";

            html2canvas(document.body).then(function (canvas) {
                document.body.innerHTML = "";
                document.body.appendChild(canvas);
                window.print();
                window.location.reload();
            });
        })
    })
</script>
