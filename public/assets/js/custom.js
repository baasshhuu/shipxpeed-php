const config = JSON.parse($('input[name="config"]').val() || '{}');

const deleteMessageSwalConfig = {
    title: 'Are you sure?',
    text: "Once deleted, you will not be able to recover this record..!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e7515a',
    cancelButtonColor: '#3b3f5c',
    confirmButtonText: 'Yes, delete it!',
    customClass: {
        confirmButton: 'btn btn-danger',
        denyButton: 'btn btn-dark',
        cancelButton: 'btn btn-dark',
    }
}

const makeid = (length = 50) => {
    var result = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

const slugify = (string, saprator = "-") => {
    const newText = string
        .toLowerCase()
        .replace(/ /g, saprator)
        .replace(/[^\w-]+/g, "");

    return newText
};

const copyToClipboard = (textToCopy) => {
    // navigator clipboard api needs a secure context (https)
    if (navigator.clipboard && window.isSecureContext) {
        // navigator clipboard api method'
        return navigator.clipboard.writeText(textToCopy);
    } else {
        // text area method
        let textArea = document.createElement("textarea");
        textArea.value = textToCopy;
        // make the textarea out of viewport
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        return new Promise((res, rej) => {
            // here the magic happens
            document.execCommand('copy') ? res() : rej();
            textArea.remove();
        });
    }
}

$(function () {
    $("#overlay").hide().removeClass('bg-loader')

    $('.form-control').on('change', function () {
        $(this).siblings('.invalid-feedback').remove();
    })

    $.extend(true, $.fn.dataTable.defaults, {
        processing: true,
        serverSide: true,
        searchDelay: 800,
        aLengthMenu: [5, 10, 20, 50, 100],
        oLanguage: {
            sProcessing: '<div class="fa-3x text-secondary"><i class="fas fa-spinner fa-spin"></i></div>',
            sZeroRecords: '<b class="text-danger">No results found</b>',
            sEmptyTable: '<b class="text-danger">No data available in this table</b>',
            sInfo: "Showing _START_ to _END_ of _TOTAL_ entries.",
            sInfoEmpty: "Showing records from 0 to 0 of a total of 0 records",
            sSearch: 'Search :', //'<span class="text-secondary px-3 py-2 border rounded-2"><i class="fa-solid fa-search"></i></span>',
            sSearchPlaceholder: "Search Here..",
            oPaginate: {
                sFirst: '<i class="fa-solid fa-chevrons-left"></i>',
                sLast: '<i class="fa-solid fa-chevrons-right"></i>',
                sNext: '<i class="fa fa-chevron-right" ></i>',
                sPrevious: '<i class="fa fa-chevron-left" ></i>'
            },
        }
    });

    $.validator.setDefaults({
        // onkeyup: true,
        onfocusout: function (element) { $(element).valid() },
        debug: false,
        errorClass: "error fs--1",
        errorElement: "span",
        errorPlacement: function (error, element) {
            if ($(element).parent().hasClass('input-group')) {
                error.appendTo(element.parents().eq(1));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element, errorClass, validClass) {
            if ($(element).parent().hasClass('input-group')) {
                $(element).addClass(errorClass).removeClass(validClass);
            } else {
                $(element).addClass(errorClass).removeClass(validClass);
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            if ($(element).parent().hasClass('input-group')) {
                $(element).addClass(validClass).removeClass(errorClass);
            } else {
                $(element).addClass(validClass).removeClass(errorClass);
            }
        },
    });

    $.ajaxSetup({
        cache: false,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
        },
        beforeSend: (xhr, req,) => {
            if (["POST", "DELETE", "PUT"].includes(req.type)) $("#overlay").show();
        },
        success: () => $("#overlay").hide(),
        complete: () => $("#overlay").hide(),
        error: function (jqXHR, exception) {
            $("#overlay").hide();
            if (jqXHR.status === 0) {
                toastr.error('Not connect.n Verify Network.');
            } else if (jqXHR.status == 404) {
                toastr.error('Requested page not found. [404]');
            } else if (jqXHR.status == 500) {
                toastr.error('Internal Server Error [500].');
            } else if (jqXHR.status == 401) {
                toastr.error("HTTP Error 401 Unauthorized.");
            } else if (jqXHR.status == 419) {
                toastr.error("CSRF token Mismatch or Page Expired.");
            } else if (exception === 'parsererror') {
                toastr.error('Requested JSON parse failed.');
            } else if (exception === 'timeout') {
                toastr.error('Time out error.');
            } else if (exception === 'abort') {
                toastr.error('Ajax request aborted.');
            } else {
                toastr.error("Error: " + jqXHR?.responseText)
            }
        },
    });
})
