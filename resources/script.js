;
(function ($) {
    $(document).ready(function () {
        var val = $('#XML_RPC_naver').val();

        if (val == 'no-use') {
            $('#XML_RPC_naver option[value="'+NaverSync.fix_cate+'"]').attr('selected', 'selected');
        }
    });
})(jQuery);