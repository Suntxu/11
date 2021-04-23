define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $("form.edit-form").data("validator-options", {
                display: function (elem) {
                    return $(elem).closest('tr').find("td:first").text();
                }
            });
            Form.api.bindevent($("form.edit-form"));
        },
    };
    return Controller;
});
