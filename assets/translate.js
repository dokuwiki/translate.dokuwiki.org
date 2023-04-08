/*
 * Javascript File for the page with the translation form
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.less';


// loads the jquery package from node_modules
import $ from 'jquery';
import 'autosize'; //originally at 1.16.7, via yarn only 1.18.13 available

// start the Stimulus application
import './bootstrap';

// provides the translation warnings

$(document).ready(function() {

    var TranslationTable = function() {
        this.table = $('table tbody');
        this.allElements = $('table tbody tr');
        this.paginationNote = $('#pagination__note');
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
        this.itemsPerPage = 30;
        this.lastPage = Math.ceil(this.itemCount / this.itemsPerPage);
        this.paginationSpace = 3;
        this.pagination = $('.pagination');
        this.pagination.empty();

        // hide pagination on fewer items
        this.setupEvents();
        if (this.usePagination()) {
            this.drawPaginationBar();
        }
        this.drawPaginationBarHint();
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
        var regex = new RegExp(this.escapeRegExp(this.filterText), 'i');
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

    /**
     * @see http://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
     */
    TranslationTable.prototype.escapeRegExp = function(str) {
        return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
    };

    TranslationTable.prototype.draw = function() {
        this.elements.hide();
        var startIndex = (this.page-1) * this.itemsPerPage;
        var endIndex;
        if (this.usePagination()) {
            endIndex = startIndex + this.itemsPerPage;
            if (endIndex >= this.itemCount) {
                endIndex = this.itemCount;
            }
        } else {
            endIndex = this.itemCount;
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
            if ($(this).attr('id') === 'save__button') {
                that.filterText = '';
                that.filter();
            }
        });
    };


    TranslationTable.prototype.drawFilterControls = function() {
        let parent = $('.translation-filter');
        parent.append($('<div class="col-md-12"><h4>Filter</h4></div>'));

        let form = $('<div class="col-md-4"></div>');
        form.append($('<label for="table-filter">Filter translations for:</label>'));
        let filter = $('<input id="table-filter" type="text" />');
        form.append(filter);
        parent.append(form);

        var that = this;
        let changed = function() {
            let newVal = $(this).val();
            if (that.filterText == newVal) {
                return;
            }
            that.filterText = newVal;
            that.startOver();
        };
        filter.keyup(changed);

        parent.append($('<div class="col-md-6">This filter will search in both, translated and original text. '
            + 'Additionally, it will search in the path of the translated file and the translation keys.'
            + '</div>'));
    };

    TranslationTable.prototype.drawPaginationBarHint = function() {
        if (this.usePagination()) {
            this.paginationNote.show();
        } else {
            this.paginationNote.hide();
        }
    };

    TranslationTable.prototype.drawPaginationBar = function() {
        this.pagination.empty();
        let ul = $('<ul></ul>');
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
        for (let i = start; i <= end; i++) {
            ul.append(this.paginationPageElement(i));
        }
    };

    TranslationTable.prototype.paginationPageElement = function(page) {
        let element = this.paginationElement(page);
        let that = this;
        element.click(function() {
            that.selectPage(jQuery(this).text());
        });
        if (this.page == page) {
            element.addClass('active')
        }
        return element;
    };

    TranslationTable.prototype.paginationElement = function(text) {
        let page = $('<li></li>');
        let link = $('<a></a>');
        link.text(text);
        page.append(link);
        return page;
    };

    TranslationTable.prototype.paginationSpacer = function() {
        let page = $('<li class="disabled"></li>');
        let link = $('<a>…</a>');
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
        let first = $('table td:visible textarea:first');
        let tmp = first.val();
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
        let warnElement = element.parents('tr');

        if (element.val() === '') {
            warnElement.addClass('warning');
            if (popover) element.popover('show');
            return;
        }

        let originalTranslation = warnElement.find('td:first');
        let regex = new RegExp(
            "%[bcdeEufFgGosxX]{1}",
            "g"
        );
        let leftMatch = originalTranslation.text().match(regex);
        let rightMatch = element.val().match(regex);
        if (leftMatch != null || rightMatch != null) {

            if (rightMatch == null || leftMatch == null ||
                rightMatch.length !== leftMatch.length) {
                warnElement.addClass('warning');
                element.popover({
                    placement: 'top',
                    trigger: 'manual',
                    content: 'The translated text is missing a placeholder like %s or %d. It needs to be kept as is when translating.',
                    title: 'Missing placeholder'
                });
                if (popover) element.popover('show');
                return;
            }
        }

        let leftSpaces = originalTranslation.find('span').length;
        regex = new RegExp(
            "([ \t]+)\n",
            "g"
        );
        let rightSpaces = element.val().match(regex);
        if (rightSpaces == null) {
            rightSpaces = 0;
        } else {
            rightSpaces = rightSpaces.length;
        }

        if (leftSpaces !== rightSpaces) {
            warnElement.addClass('warning');
            element.popover({
                placement: 'top',
                trigger: 'manual',
                content: 'The translated text has missing trailing whitespaces. Please check the highlighted parts in the original version.',
                title: 'Missing whitespaces'
            });
            if (popover) element.popover('show');
            return;
        }

        warnElement.removeClass('warning');
        element.popover('destroy');
    };



    var table = new TranslationTable();
});