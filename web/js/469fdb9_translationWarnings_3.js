
$(document).ready(function() {

    var TranslationTable = function() {
        this.elements = $('table tbody tr');
        this.page = 0;
        this.itemsPerPage = 10;
        this.itemCount = this.elements.size();
        this.lastPage = Math.ceil(this.itemCount / this.itemsPerPage) -1;
        this.nextButton = this.paginationElement('»', this.page+1);
        this.prevButton = this.paginationElement('«', this.page-1);

        this.setupPaginationBar();
        this.setupEvents();
        this.draw();
        /*
        setTimeout(function(){
            //execute the next task

            var task = jQuery(textAreas.get(0));
            textAreas.splice(0, 1);
            checkTextarea(task, false);
            task.autosize();

            if (textAreas.length > 0){
                setTimeout(arguments.callee, 1);
            }
        }, 1);*/
    };

    TranslationTable.prototype.draw = function() {
        this.elements.hide();
        var startIndex = this.page * this.itemsPerPage;
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
        $('body').scrollTo('table');
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

    TranslationTable.prototype.setupPaginationBar = function() {
        this.pagination = $('<div class="pagination"></div>');
        var ul = $('<ul></ul>');
        this.pagination.append(ul);
        ul.append(this.prevButton);
        this.prevButton.addClass('disabled');

        var that = this;
        this.nextButton.click(function() {
            that.nextPage();
        });
        this.prevButton.click(function() {
            that.prevPage();
        });

        for (var i = 0; i < this.lastPage; i++) {
            var element = this.paginationElement(i+1);
            element.click(function() {
                that.selectPage(jQuery(this).text()-1);
            });
            ul.append(element);
        }

        ul.append(this.nextButton);

        this.pagination.insertAfter($('table'));
    };

    TranslationTable.prototype.paginationElement = function(text) {
        var page = $('<li></li>');
        var link = $('<a></a>');
        var that = this;
        link.text(text);
        page.append(link);
        return page;
    };

    TranslationTable.prototype.selectPage = function(newPage) {
        if (newPage < 0) newPage = 0;
        this.pagination.find('li').removeClass('active');
        this.pagination.find('li:nth-child('+newPage+')').addClass('active');

        if (newPage == 0) {
            this.prevButton.addClass('disabled');
        } else {
            this.prevButton.removeClass('disabled');
        }

        if (newPage == this.lastPage) {
            this.nextButton.addClass('disabled');
        } else {
            this.nextButton.removeClass('disabled');
        }

        this.page = newPage;
        this.draw();
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