var Select2 = function () {
    return {
        template: "<select ref='self'><slot></slot></select>",
        mounted: function () {
            var self = this;
            $(this.$refs.self).select2(this.config).on("select2:select", function () {
                var val = $(self.$refs.self).select2("val");
                self.$emit("change", val);
            });

            if (this.value) {
                $(this.$refs.self).select2("val", [this.value]);
            }
        },
        props: ["config", "value"],
        model: {
            prop: 'value',
            event: 'change'
        },
    }
};
