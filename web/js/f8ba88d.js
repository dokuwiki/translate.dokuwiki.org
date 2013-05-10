
$(document).ready(function() {

    var TranslationTable = function() {
        this.table = $('table tbody');
        this.allElements = $('table tbody tr');
        this.allElements.detach();
        this.filterText = '';
        this.elements = $();
        this.drawFilterControls();
        this.startOver();
    };


    TranslationTable.prototype.startOver = function() {

        this.elements.detach();
        this.filter();

        // all page related indexes are 1 based, only selectPage and draw are using 0 based index
        this.page = 1;
        this.itemCount = this.elements.size();
        this.itemsPerPage = 10;
        this.lastPage = Math.ceil(this.itemCount / this.itemsPerPage)-1;
        this.paginationSpace = 3;
        this.pagination = $('.pagination');
        this.pagination.empty();

        // hide pagination on less items
        this.setupEvents();
        if (this.usePagination()) {
            this.drawPaginationBar();
        }
        this.draw();
    };

    TranslationTable.prototype.usePagination = function() {
        return this.itemCount > this.itemsPerPage * 2;
    };

    TranslationTable.prototype.filter = function() {
        if (this.filterText == '') {
            this.elements = this.allElements;
            this.elements.appendTo(this.table);
            return;
        }

        var newElements = [];
        var regex = new RegExp(this.filterText);

        this.allElements.each(function(index, val) {
            if ($(this).attr('data-translation-key').match(regex)) {
                newElements.push(this);
                return;
            }
            if ($(this).find('td:first').text().match(regex)) {
                newElements.push(this);
                return;
            }
            if ($(this).find('td textarea').val().match(regex)) {
                newElements.push(this);
            }
        });
        this.elements = $(newElements);
        this.elements.appendTo(this.table);
    };

    TranslationTable.prototype.draw = function() {
        this.elements.hide();
        var startIndex = (this.page-1) * this.itemsPerPage;
        var endIndex;
        if (this.usePagination()) {
            endIndex = startIndex + this.itemsPerPage;
            if (endIndex >= this.itemCount) {
                endIndex = this.itemCount -1;
            }
        } else {
            endIndex = this.itemCount -1;
        }

        for (var i = startIndex; i < endIndex; i++) {
            var element = jQuery(this.elements.get(i));
            var textarea = element.find('textarea');
            element.show();
            this.checkTextarea(textarea, false);
            textarea.autosize({});
        }
    };

    TranslationTable.prototype.setupEvents = function() {
        var that = this;
        this.elements.find('textarea').keyup(function() {
            that.checkTextarea($(this), true);
        });

        this.elements.find('textarea').click(function() {
            that.checkTextarea($(this), true);
        });

        this.elements.find('textarea').focusout(function() {
            $(this).popover('destroy');
        });

        $(window).bind('beforeunload', function() {
            return "Do you really want to leave the page? All changes will be lost.";
        });

        $('input[type=submit]').click(function() {
            $(window).unbind('beforeunload');
            if ($(this).attr('id') == 'save__button') {
                that.filterText = '';
                that.filter();
            }
        });
    };


    TranslationTable.prototype.drawFilterControls = function() {
        var parent = $('.translation-filter');
        parent.append($('<div class="span12"><h4>Filter</h4></div>'));

        var form = $('<div class="span4"></div>');
        form.append($('<label for="table-filter">Filter translations for:</label>'));
        var filter = $('<input id="table-filter" type="text" class="input-xlarge" />');
        form.append(filter);
        parent.append(form);

        var that = this;
        var changed = function() {
            var newVal = $(this).val();
            if (that.filterText == newVal) {
                return;
            }
            that.filterText = newVal;
            that.startOver();
        };
        filter.keyup(changed);

        parent.append($('<div class="span6">This filter will search in both, translated and original text.'
                + 'Additionally it will search in the path of the translated file and the translation keys.'
                + '</div>'));
    };

    TranslationTable.prototype.drawPaginationBar = function() {
        this.pagination.empty();
        var ul = $('<ul></ul>');
        this.pagination.append(ul);


        if (this.page > 1) {
            ul.append(this.prevButton);
        }

        this.nextButton = this.paginationElement('»', this.page+1);
        this.prevButton = this.paginationElement('«', this.page-1);

        var that = this;
        this.nextButton.click(function() {
            that.nextPage();
        });
        this.prevButton.click(function() {
            that.prevPage();
        });

        if (this.page <= this.paginationSpace + 3) {
            this.paginationPageElements(1, this.page + this.paginationSpace, ul);
        } else {
            ul.append(this.paginationPageElement(1));
            ul.append(this.paginationSpacer());
            this.paginationPageElements(this.page - this.paginationSpace, this.page + this.paginationSpace, ul);
        }
        if (this.page + 5 < this.lastPage) {
            ul.append(this.paginationSpacer());
        }
        if (this.page + 5 == this.lastPage) {
            ul.append(this.paginationPageElement(this.lastPage-1));
        }
        if (this.page + 4 <= this.lastPage) {
            ul.append(this.paginationPageElement(this.lastPage));
        }

        if (this.page != this.lastPage) {
            ul.append(this.nextButton);
        }
    };

    TranslationTable.prototype.paginationPageElements = function(start, end, ul) {
        if (end > this.lastPage) {
            end = this.lastPage;
        }
        for (var i = start; i <= end; i++) {
            ul.append(this.paginationPageElement(i));
        }
    };

    TranslationTable.prototype.paginationPageElement = function(page) {
        var element = this.paginationElement(page);
        var that = this;
        element.click(function() {
            that.selectPage(jQuery(this).text());
        });
        if (this.page == page) {
            element.addClass('active')
        }
        return element;
    };

    TranslationTable.prototype.paginationElement = function(text) {
        var page = $('<li></li>');
        var link = $('<a></a>');
        link.text(text);
        page.append(link);
        return page;
    };

    TranslationTable.prototype.paginationSpacer = function() {
        var page = $('<li class="disabled"></li>');
        var link = $('<a>…</a>');
        page.append(link);
        return page;
    };

    TranslationTable.prototype.selectPage = function(newPage) {
        newPage--;
        if (newPage < 0) newPage = 0;

        this.page = newPage+1;
        this.draw();
        this.drawPaginationBar();
        this.focusFirstTextarea();
    };

    TranslationTable.prototype.focusFirstTextarea = function() {
        var first = $('table td:visible textarea:first');
        var tmp = first.val();
        first.focus();
        first.val(tmp);
    };

    TranslationTable.prototype.nextPage = function() {
        this.selectPage(this.page + 1);
    };

    TranslationTable.prototype.prevPage = function() {
        this.selectPage(this.page - 1);
    };

    TranslationTable.prototype.checkTextarea = function(element, popover) {
        var warnElement = element.parents('tr');

        if (element.val() == '') {
            warnElement.addClass('warning');
            element.popover({
                placement: 'top',
                trigger: 'manual',
                content: 'Element not translated',
                title: 'Warning'
            });
            if (popover) element.popover('show');
            return;
        }

        var originalTranslation = warnElement.find('td:first');
        var regex = new RegExp(
            "%[bcdeEufFgGosxX]{1}",
            "g"
        );
        var leftMatch = originalTranslation.text().match(regex);
        var rightMatch = element.val().match(regex);
        if (leftMatch != null || rightMatch != null) {

            if (rightMatch == null || leftMatch == null ||
                rightMatch.length != leftMatch.length) {
                warnElement.addClass('warning');
                element.popover({
                    placement: 'top',
                    trigger: 'manual',
                    content: 'Different count of % placeholders',
                    title: 'Warning'
                });
                if (popover) element.popover('show');
                return;
            }
        }

        warnElement.removeClass('warning');
        element.popover('destroy');
    };

    var table = new TranslationTable();
});
/*!
	jQuery Autosize v1.16.7
	(c) 2013 Jack Moore - jacklmoore.com
	updated: 2013-03-20
	license: http://www.opensource.org/licenses/mit-license.php
*/
(function(e){var t,o,n={className:"autosizejs",append:"",callback:!1},i="hidden",s="border-box",a="lineHeight",l='<textarea tabindex="-1" style="position:absolute; top:-999px; left:0; right:auto; bottom:auto; border:0; -moz-box-sizing:content-box; -webkit-box-sizing:content-box; box-sizing:content-box; word-wrap:break-word; height:0 !important; min-height:0 !important; overflow:hidden;"/>',r=["fontFamily","fontSize","fontWeight","fontStyle","letterSpacing","textTransform","wordSpacing","textIndent"],c="oninput",h="onpropertychange",p=e(l).data("autosize",!0)[0];p.style.lineHeight="99px","99px"===e(p).css(a)&&r.push(a),p.style.lineHeight="",e.fn.autosize=function(a){return a=e.extend({},n,a||{}),p.parentNode!==document.body&&(e(document.body).append(p),p.value="\n\n\n",p.scrollTop=9e4,t=p.scrollHeight===p.scrollTop+p.clientHeight),this.each(function(){function n(){o=b,p.className=a.className,e.each(r,function(e,t){p.style[t]=f.css(t)})}function l(){var e,s,l;if(o!==b&&n(),!d){d=!0,p.value=b.value+a.append,p.style.overflowY=b.style.overflowY,l=parseInt(b.style.height,10),p.style.width=Math.max(f.width(),0)+"px",t?e=p.scrollHeight:(p.scrollTop=0,p.scrollTop=9e4,e=p.scrollTop);var r=parseInt(f.css("maxHeight"),10);r=r&&r>0?r:9e4,e>r?(e=r,s="scroll"):u>e&&(e=u),e+=x,b.style.overflowY=s||i,l!==e&&(b.style.height=e+"px",w&&a.callback.call(b)),setTimeout(function(){d=!1},1)}}var u,d,g,b=this,f=e(b),x=0,w=e.isFunction(a.callback);f.data("autosize")||((f.css("box-sizing")===s||f.css("-moz-box-sizing")===s||f.css("-webkit-box-sizing")===s)&&(x=f.outerHeight()-f.height()),u=Math.max(parseInt(f.css("minHeight"),10)-x,f.height()),g="none"===f.css("resize")||"vertical"===f.css("resize")?"none":"horizontal",f.css({overflow:i,overflowY:i,wordWrap:"break-word",resize:g}).data("autosize",!0),h in b?c in b?b[c]=b.onkeyup=l:b[h]=l:b[c]=l,e(window).on("resize",function(){d=!1,l()}),f.on("autosize",function(){d=!1,l()}),l())})}})(window.jQuery||window.Zepto);