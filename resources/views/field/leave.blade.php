@extends("field.table")

@section("script")
    <script>
        $(function () {
            window.update = function () {
                $(".input-dates").click(function () {
                    var from = $(this).parents("tr").find(".input-from").val();
                    var to = $(this).parents("tr").find(".input-to").val();
                    var days = parseInt((new Date(to) - new Date(from)) / 1000 / 60 / 60 / 24);
                    if (days >= 0) {
                        $(this).val(days + 1);
                    }
                })
            };
            window.update();
        })
    </script>
@endsection
