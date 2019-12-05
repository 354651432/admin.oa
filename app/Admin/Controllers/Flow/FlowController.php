<?php

namespace App\Admin\Controllers\Flow;

use Admin;
use App\Http\Controllers\Controller;
use App\Models\Flow\Flow;
use App\Models\Flow\FlowForm;
use App\Models\Flow\FlowFormItem;
use App\Services\FlowService;
use App\Services\UserFlowServices;
use App\User as Member;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Form\Footer;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Session;

class FlowController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $this->renderScript();
        return $content
            ->header('流程模板')
            ->description('列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * @param $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('流程模板')
            ->description('编辑')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('流程模板')
            ->description('创建')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Flow);

        $grid->model()->orderBy("id", "desc");
        $grid->id('Id');
        $grid->name('名称')->display(function () {
            $label1 = "";
            if ($this->forms->count() <= 0) {
                $label1 = "<span class='label label-warning'>没有表单</span>";
            }

            $label2 = "";
            if ($this->nodes->count() <= 0) {
                $label2 = "<span class='label label-warning'>没有结点</span>";
            }

            return "{$this->name}&nbsp {$label1}&nbsp{$label2}";
        });

        $grid->directory1('一级目录');
        $grid->directory2('二级目录');
        $grid->created_at('创建时间');

        $grid->actions(function (Actions $act) {
            $act->disableEdit();
            $act->disableView();
            $act->disableDelete();
            $id = $act->getKey();

            $act->append(<<<HTML
<a href="/admin/flow/$id/edit" class="btn btn-primary btn-xs">修改</a>&nbsp;
HTML
            );
            $act->append(<<<HTML
<a href="/admin/flow/$id/edit-form" class="btn btn-success btn-xs">修改表单</a>&nbsp;
HTML
            );
            $act->append(<<<HTML
<a href="/admin/flow/$id/edit-node" class="btn btn-info btn-xs">修改结点</a>&nbsp;
HTML
            );

            $act->append(<<<HTML
<a href="javascript:void(0);" data-id="$id" class="grid-row-delete btn btn-xs btn-warning">
    删除
</a>&nbsp;
HTML
            );
        });

        $grid->filter(function ($filter) {
            $filter->like("name", "名称");
            $filter->like("directory1", "一级目录");
            $filter->like("directory2", "二级目录");
        });

        return $grid;
    }

    private function renderScript()
    {
        $script = <<<JS
$('.grid-row-delete').unbind('click').click(function() {
    var id = $(this).data('id');
    swal({
        title: "确认删除?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "删除",
        showLoaderOnConfirm: true,
        cancelButtonText: "取消",
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    method: 'post',
                    url: '/admin/flow/' + id,
                    data: {
                        _method:'delete',
                        _token:LA.token,
                    },
                    success: function (data) {
                        $.pjax.reload('#pjax-container');

                        resolve(data);
                    }
                });
            });
        }
    }).then(function(result) {
        var data = result.value;
        if (typeof data === 'object') {
            if (data.status) {
                swal(data.message, '', 'success');
            } else {
                swal(data.message, '', 'error');
            }
        }
    });
});
JS;
        Admin::script($script);
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Flow::findOrFail($id));

        $show->id('Id');
        $show->name('Name');
        $show->title_fiels('Title fiels');
        $show->location('Location');
        $show->is_delete('Is delete');
        $show->can_apply('Can apply');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Flow);

        $form->text('name', '名称')->required();
        $form->text('directory1', '一级目录')
            ->attribute(["list" => "dir1", "autocomplete" => "off"])
            ->required();
        $form->text('directory2', '二级目录')
            ->attribute(["list" => "dir2", "autocomplete" => "off"])
            ->required();

        $form->number('weight', '排序')->help("数值大的在前边");

        $form->text('title_fiels', '标题字段')->help("字段逗号分隔");

        $form->multipleSelect('can_apply', '权限控制')
            ->options(Member::all()->pluck("name", "id"));

        $form->footer(function (Footer $footer) {
            $footer->disableViewCheck();
            $footer->disableCreatingCheck();
            $footer->disableEditingCheck();
        });
        $service = new FlowService();
        $form->html($service->buildDataList("dir1", Flow::distinct()->pluck("directory1")->toArray()));
        $form->html($service->buildDataList("dir2", Flow::distinct()->pluck("directory2")->toArray()));

        $form->saved(function ($form) {
            $id = $form->model()->id;
            $flow = Flow::find($id);
            if ($flow->forms->count() <= 0) {
                return redirect("/admin/flow/$id/edit-form");
            }
        });
        return $form;
    }

    /**
     * ajax 获取已保存的控件
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function control($id)
    {
        return FlowFormItem::query()->whereId($id)->first();
    }

    /**
     * ajax 控件渲染
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function formRender()
    {
        $form = request()->all();
        $form = new FlowForm($form);
        if (empty($form->name)) {
            return response()->json(["html" => '']);
        }

        $field = \App\Admin\Form\FlowForm::makeFlowField($form);
        if (empty($field)) {
            return response()->json(["html" => '']);
        }

        return response()->json([
            "html" => $field->getHtml(),
            "js" => $field->getJs(),
            "css" => $field->getCss(),
            "script" => $field->getScript(),
            "options" => $field->getOptions(),
        ]);
    }

    /**
     * 保存控件
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function saveControl()
    {
        $this->validate(request(), [
            "title" => "required",
            "name" => "required",
            "type" => "required",
            "label" => "required",
        ]);
//        if (FlowFormItem::where("name", request("name"))->exists()) {
//            return response()->json([
//                "message" => "控件已存在，保存失败"
//            ]);
//        }
        $data = request()->post();
        if (isset($data ["id"])) {
            FlowFormItem::find($data ["id"])->update($data);
        } else {
            FlowFormItem::create($data);
        }

        return response()->json([
            "message" => "保存成功"
        ]);
    }

    /**
     * 预览
     * @param Content $content
     * @param FlowService $flowService
     * @return Content
     * @throws \Exception
     */
    public function preview(Content $content, FlowService $flowService)
    {
        if (request()->isMethod("GET")) {
            return $content;
        }
        $nodes = request("nodes", old("_nodes", ""));

        $forms = request("forms", old("_forms", ""));
        $forms = json_decode($forms);

        $flowService->buildAssert($forms);

        $users = new Form\Field\Select("_apply_user", "模拟用户");

        $users->options(Member::query()->pluck("name", "id"))
            ->value(Admin::user()->id);
        $view = view("flow.preview")
            ->with("forms", $forms)
            ->with("nodes", $nodes)
            ->with("users", $users)
            ->with("ispreview", 1);

        Admin::css("/css/apply.css");
        return $content->header("流程")
            ->description('预览')
            ->body($view);
    }

    /**
     * 预审
     * @param FlowService $flowService
     * @param Content $content
     * @return Content
     * @throws \Exception
     */
    public function preApply(FlowService $flowService, Content $content)
    {
        if (request()->isMethod("GET")) {
            return $content;
        }
        $data = request()->post();
        $data = array_except($data, ['_nodes', '_forms', '_token', '_validate', '_apply_user']);

        $nodes = request("_nodes", "[]");
        $nodes = json_decode($nodes);

        $validate = request("_validate");
        $validate = array_filter($validate);

        $validator = \Validator::make($data, $validate);
        if ($validator->fails()) {
            $message = $validator->errors()->first("*");
            admin_error($message);

            return back(307)->withInput();
        }

        $nodes = $flowService->preview($data, $nodes, request("_apply_user"));
        $flow = session("flow");

        $form = new \App\Admin\Form\FlowForm($data, $flow);

        $service = app()->make(UserFlowServices::class);
        $notify_users = $service->getNotifyUsers($flow);
        $users = [];
        if ($notify_users) {
            foreach (explode(",", $notify_users) as $user_id) {
                $users[] = Member::withoutGlobalScopes()->find($user_id)->name;
            }
        }

        $view = view('flow.apply-node')
            ->with("data", $nodes)
            ->with("title", $form->title())
            ->with("notify_users", $users);
        return $content->header("流程模板")
            ->description('预览')
            ->body(new Box(" ", $view));
    }

    /**
     * 新增 修改表单页面
     * @param $id
     * @param Content $content
     * @return Content
     */
    public function editForm($id, Content $content)
    {
        $flow = Flow::findorFail($id);
        $forms = [];
        foreach ($flow->forms as $form) {
            $form->append(['html', 'js', 'css', 'script', 'data']);
            $forms[$form->name] = $form;
        }

        $view = view("flow.edit-form")
            ->with("forms", $forms)
            ->with("flow_id", $id)
            ->with("controlTyps", config("flow.controls", []))
            ->with("controls", FlowFormItem::query()->pluck("title", "id"));

        return $content
            ->header($flow->name)
            ->description('编辑表单')
            ->body($view);
    }

    /**
     * 新增 修改表单页面 POST 数据处理
     * @param $id
     * @param FlowService $flowService
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function storeForm($id, FlowService $flowService)
    {
        $forms = request("forms");
        if (empty($forms)) {
            admin_error("form为空");
            return back();
        }

        $forms = json_decode($forms, true);
        $flowService->storeForms($id, $forms);
        if ($message = $flowService->validateCheck($forms)) {
            admin_error("验证规则出错：$message");
            return back();
        }

        $flow = Flow::find($id);
        if ($flow->nodes->count() > 0) {
            return redirect('/admin/flow');
        }

        return redirect("/admin/flow/$id/edit-node");
    }

    /**
     * 新增修改结点页面
     * @param $id
     * @param Content $content
     * @param FlowService $flowService
     * @return Content
     */
    public function editNode($id, Content $content, FlowService $flowService)
    {
        $flow = Flow::findOrFail($id);
        $nodes = $flow->nodes;
        $forms = [];

        foreach ($flow->forms as $form) {
            $form->append(["data"]);
            $forms[$form->name] = $form;
        }

        $view = view("flow.edit-node")
            ->with("nodes", $nodes)
            ->with("forms", $forms)
            ->with("flow_id", $id)
            ->with("operators", $flowService->getOperators());

        Session::put("flow", $flow);
        return $content
            ->header($flow->name)
            ->description('编辑结点')
            ->body($view);
    }

    /**
     * 新增修改结点页面 POST 数据处理
     * @param $id
     * @param FlowService $flowService
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function storeNode($id, FlowService $flowService)
    {
        $nodes = request("nodes");
        if (empty($nodes)) {
            return back()->withInput()->withErrors(["message" => "form为空"]);
        }
        $nodes = json_decode($nodes, true);
        try {
            $flowService->storeNodes($id, $nodes);
        } catch (\Exception $ex) {
            admin_error($ex->getMessage());
            return back()->withInput()->withErrors(["message" => "form为空"]);
        }

        return redirect("/admin/flow");
    }
}
