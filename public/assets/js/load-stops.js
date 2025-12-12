let stopIndex = $(".stop-row").length || 0;

$("#add-stop-btn").on("click", function () {
    $.get("/admin/loads/stop-row-template/" + stopIndex, function (html) {
        $("#stops-container").append(html);
        stopIndex++;
    });
});

// Remove stop
$(document).on("click", ".remove-stop-btn", function () {
    $(this).closest(".stop-row").remove();
});
