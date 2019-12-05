window.flowVue = new Vue({
    el: "#main-form",
    data: {
        form: {
            type: "Text",
        },
        names: {},
        datatypes: {},
        datasources: {},
        forms: [],
        script: "",
        successMsgArr: [],
        warningMsgArr: [],
        nodes: [
            {
                step: 1,
                next: 0,
                name: "",
                op: '_self',
                type: 0,
                conditions: [],
            }
        ],
        conditionOperator: 'eq',
        conditionOperators: {
            eq: "等于",
            ne: "不等于",
            gt: "大于",
            ge: "大于等于",
            lt: "小于",
            le: "小于等于",
            between: "范围",
            "in": "区间",
        },
        currentNode: {
            conditions: []
        },
        conditionObj: {
            op: "eq",
            value3: []
        }
    },
    computed: {
        showDataSource() {
            return this.form.type in {
                'select': 1,
                'checkbox': 1,
            }
        },
        formNodes() {
            return JSON.stringify(this.nodes);
        },
        formForms() {
            return JSON.stringify(this.forms);
        }
    },
    updated() {
        if (this.script) {
            try {
                eval(this.script);
            } catch (err) {
                console.log(err);
            }
            this.script = "";
        }
    },
    mounted() {
        $("#select-control").select2({
            placeholder: "请选择"
        }).on("select2:select", () => {
            let val = $("#select-control").select2("val");
            this.form = {};
            axios.get("/admin/flow/control/" + val).then(p => {
                this.form = p.data;
                $("#select-type").val(p.data.type).trigger("change");
            })
        });

        $("#select-type").select2().on("select2:select", () => {
            this.form.type = $("#select-type").select2("val");
        });
    },
    methods: {
        add() {
            console.log("add called");
            if (!this.form.name) {
                this.warning("缺少控件name");
                return
            }

            if (!this.form.label) {
                this.warning("缺少控件名称");
                return
            }

            if (this.form.name in this.names) {
                this.warning("控件已存在");
                return
            }

            axios.post('/admin/flow/render', this.form).then(p => {
                if (p.data.html) {
                    let form = {
                        ...this.form,
                        html: p.data.html,
                        options: p.data.options || {},
                        js: p.data.js,
                        css: p.data.css,
                        script: p.data.script,
                    };
                    this.addForm(form)
                }
            })
        },
        addForm(form) {
            this.forms.push(form);

            this.datasources[form.name] = form.options;
            this.names[form.name] = form.label;
            this.datatypes[form.name] =
                ["Select", "MultipleSelect", "ListBox"].indexOf(form.type) === -1 ? "scalar" : "array";

            if (form.js) {
                for (let src of form.js) {
                    let js = document.createElement('script');
                    js.src = src;
                    document.head.appendChild(js)
                }
            }

            if (form.css) {
                for (let href of form.css) {
                    let css = document.createElement('link');
                    css.rel = "stylesheet";
                    css.href = href;
                    document.head.appendChild(css)
                }
            }

            if (form.script) {
                this.script += form.script;
            }
        },
        save() {
            axios.post('/admin/flow/saveControl', this.form).then(p => {
                this.success(p.data.message);
            }).catch(p => {
                this.warning(p.response.data.message);
            })
        },
        del(id) {
            let dom = this.forms.splice(id, 1);
            if (dom.length > 0) {
                delete this.names[dom[0].name];
            }
        },
        success(msg) {
            this.successMsgArr.push(msg);
        },
        warning(msg) {
            this.warningMsgArr.push(msg);
        },

        // 结点操作
        addNode(thisNode, config = {}) {
            let key = this.nodes.indexOf(thisNode);
            if (key === -1) {
                this.warning("结点错误 ");
                return;
            }

            // and or 结点 在最后一个结点后面操作
            while (this.nodes[key + 1] && this.nodes[key].step === this.nodes[key + 1].step) {
                key++;
            }

            let node = {};
            node.conditions = (config.conditions || []);
            node.op = '_self';
            node.step = config.step || parseInt(thisNode.step) + 1;
            node.type = config.type || 0;

            this.nodes.splice(key + 1, 0, node);

            // 重新撸结点顺序
            let needSort = this.nodes[key + 1] && this.nodes[key + 1].step !== this.nodes[key].step;
            if (!needSort) {
                return
            }
            for (let i = key + 1; i < this.nodes.length - 1; i++) {
                this.nodes[i + 1].step++;
            }
        },
        delNode(thisNode) {
            let key = this.nodes.indexOf(thisNode);
            let needSort = this.nodes[key + 1] && this.nodes[key + 1].step !== this.nodes[key].step;
            this.nodes.splice(key, 1);

            // 重新撸结点顺序
            if (!needSort) {
                return;
            }
            for (let i = key + 1; i < this.nodes.length; i++) {
                this.nodes[i].step--;
            }
        },
        showAddConditionDialog(node) {
            this.currentNode = node;
            this.conditionObj = {
                op: "eq",
                value3: []
            };
            $("#conditionDialog").modal();
        },
        addCond() {
            this.currentNode.conditions.push(this.conditionObj);
        },

        changeCondition() {
            if (this.conditionObj.op === 'in') {
                this.script = 'conditionValueSelect2();';
            }
        },
        changeConditionName() {
            let type = this.datatypes[this.conditionObj.name];
            if (type === 'array') {
                this.conditionObj.op = 'in';
                this.script = 'conditionValueSelect2();';
            }
        },
        delCond(key) {
            this.currentNode.conditions.splice(key, 1);
        },
        preview() {
            console.log("preview");
            let form = document.createElement('form');
            form.action = '/admin/flow/preview';
            form.method = 'POST';
            form.target = '_blank';
            let forms = document.createElement("input");
            forms.type = "hidden";
            forms.name = "forms";
            forms.value = JSON.stringify(this.forms);
            let nodes = document.createElement("input");
            nodes.type = "hidden";
            nodes.name = "nodes";
            nodes.value = JSON.stringify(this.nodes);

            form.appendChild(nodes);
            form.appendChild(forms);

            document.body.appendChild(form);
            form.submit();
        },
        submit(ev) {
            let form = ev.target.form;
            if (!form.checkValidity()) {
                this.warning("请填写必填字段");
                return;
            }

            form.submit();
        }
    },
    filters: {
        cond2text(cond) {
            let obj = {
                eq: "等于",
                ne: "不等于",
                gt: "大于",
                ge: "大于等于",
                lt: "小于",
                le: "小于等于",
            };
            if (cond.op in obj) {
                return flowVue.names[cond.name]
                    + " "
                    + obj[cond.op]
                    + " "
                    + cond.value;
            }

            if (cond.op === 'between') {
                return flowVue.names[cond.name]
                    + " 从 "
                    + cond.value1
                    + " 到 "
                    + cond.value2;
            }

            if (cond.op === 'in') {
                let values = [];
                for (let item of cond.value3) {
                    let strText = flowVue.datasources[cond.name][item].value;
                    if (strText) {
                        values.push(strText);
                    }
                }
                return flowVue.names[cond.name]
                    + " 属于: "
                    + values.concat(",");
            }
            return '';
        },
    },
    components :{
        select2: new Select2()
    }
});

function conditionValueSelect2() {
    $("#conditionValueSelect").select2().on("select2:select", el => {
        let value = $("#conditionValueSelect").select2("val");
        window.flowVue.conditionObj.value3 = value;
    }).on("select2:unselect", el => {
        let value = $("#conditionValueSelect").select2("val");
        window.flowVue.conditionObj.value3 = value;
    });
}
