<?php

namespace App\Admin\Controllers\Flow;

use App\Models\Flow\Operator;
use App\Http\Controllers\Controller;
use App\User as Member;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OperatorController extends Controller
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
        return $content
            ->header('角色管理')
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
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('角色管理')
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
            ->header('角色管理')
            ->description('新增')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Operator);
        $grid->model()->orderBy("id", "desc");

        $grid->id('Id');
        $grid->name('角色名称');
        $grid->alias('角色变量');
        $grid->column('user.name', '用户');

        $grid->filter(function ($filter) {
            $filter->like("name", "角色名称");
            $filter->equal("user_id", "用户")->Select(Member::pluck("name", "id"));
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Operator::findOrFail($id));

        $show->id('Id');
        $show->name('Name');
        $show->alias('Alias');
        $show->user_id('User id');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Operator);

        $form->text('name', '角色名称');
        $form->text('alias', '角色变量');
        $form->select('user_id', '操作都')
            ->options(Member::pluck("name", "id"));

        $form->saving(function (Form $form) {
            if (starts_with($form->alias, '_')) {
                admin_error("变量第一个字段不可以是_");
                return back();
            }
        });
        return $form;
    }
}
