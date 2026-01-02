$(function () {

    // Initialize Select2
    $(".select2").select2({
        width: "100%",
        allowClear: true
    });

    // Auto-filter vehicles by selected driver
    $("select[name='assigned_driver_id']").on("change", function () {
        const driver = $(this).val();
        $("#vehicle-select option").each(function () {
            const vDriver = $(this).data("driver");
            if (!driver || driver == vDriver || $(this).val() === "") {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        $("#vehicle-select").val("").trigger("change");
    });


});
