$(function () {
    var datas = [];
    
    var showList = function (input) {
        var input = $(input);
        var result = $(input).parent().next();
        var keyword = input.val();
        var keywords = input.val().trim().toLowerCase().split(/[\s\-]+/);
        if (keyword == "" || keywords.length <= 0) {
            result.hide();
            return;
        }
        var str = "";
        // perform local searching
        datas.forEach(function (data) {
            var isMatch = true;
            if (!data.title || data.title.trim() === '') {
                data.title = "Untitled";
            }
            var data_title = data.title.trim().toLowerCase();
            var data_content = data.content.trim().replace(/<[^>]+>/g, "").toLowerCase();
            var data_url = DOCS_MODE === 'html' ? (data.root ? "./" : "../" + data.type + "/") + data.url : "?md=" + data.relative;
            var index_title = -1;
            var index_content = -1;
            var first_occur = -1;
            // only match artiles with not empty contents
            if (data_content !== '') {
                keywords.forEach(function (keyword, i) {
                    index_title = data_title.indexOf(keyword);
                    index_content = data_content.indexOf(keyword);

                    if (index_title < 0 && index_content < 0) {
                        isMatch = false;
                    } else {
                        if (index_content < 0) {
                            index_content = 0;
                        }
                        if (i == 0) {
                            first_occur = index_content;
                        }
                        // content_index.push({index_content:index_content, keyword_len:keyword_len});
                    }
                });
            } else {
                isMatch = false;
            }
            // show search results
            if (isMatch) {
                str += "<li><a href='" + data_url + "' class='search-result-title'><h3>" + data_title + "</h3>";
                var content = data.content.trim().replace(/<[^>]+>/g, "");
                if (first_occur >= 0) {
                    // cut out 100 characters
                    var start = first_occur - 20;
                    var end = first_occur + 80;

                    if (start < 0) {
                        start = 0;
                    }

                    if (start == 0) {
                        end = 100;
                    }

                    if (end > content.length) {
                        end = content.length;
                    }

                    var match_content = content.substr(start, end);

                    // highlight all keywords
                    keywords.forEach(function (keyword) {
                        var regS = new RegExp(keyword, "gi");
                        match_content = match_content.replace(regS, "<em class=\"search-keyword\">" + keyword + "</em>");
                    });

                    str += "<p class=\"search-result\">" + match_content + "...</p>"
                }
                str += "</a></li>";
            }
        });

        if (str == "") {
            str = "<li>not found!</li>";
        }
        str = '<ul class="search-result-list">' + str + '</ul>';
        result.html(str).show();
    };

    //获得焦点
    $(document).on("focus keyup", ".local-search-input", function () {
        var that = this;
        if (datas.length === 0) {
            $.ajax({
                url: SEARCH_URL,
                dataType: "json",
                success: function (ret) {
                    datas = ret;
                    showList(that);
                }
            });
        } else {
            showList(that);
        }
    });
    $(document).on("blur", ".local-search-input", function () {
        //$(".local-search-result").hide();
    });
});