
$(document).ready(function() {

    var TranslationTable = function() {
        this.elements = $('table tbody tr');

        // all page related indexes are 1 based, only selectPage and draw are using 0 based index
        this.page = 1;
        this.itemsPerPage = 10;
        this.itemCount = this.elements.size();
        this.lastPage = Math.ceil(this.itemCount / this.itemsPerPage)-1;
        this.paginationSpace = 3;
        this.pagination = $('.pagination');

        // hide pagination on less items
        if (this.itemCount > this.itemsPerPage * 2) {
            this.setupEvents();
            this.drawPaginationBar();
            this.draw();
        }
    };

    TranslationTable.prototype.draw = function() {
        this.elements.hide();
        var startIndex = (this.page-1) * this.itemsPerPage;
        var endIndex = startIndex + this.itemsPerPage;
        if (endIndex >= this.itemCount) {
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