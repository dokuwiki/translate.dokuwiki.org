/*
 * Javascript File for the page with the translation form
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.less';


// loads the jquery package from node_modules
import $ from 'jquery';

// start the Stimulus application
import './bootstrap';

import autosize from "autosize/dist/autosize";
import 'bootstrap';

// provides the translation warnings

$(function() {

    const TranslationTable = function () {
        this.table = $('table tbody');
        this.allRows = $('table tbody tr');
        this.paginationNote = $('#pagination__note');
        this.filterText = '';
        this.filteredRows = new Array(this.allRows.length).fill(true);
        this.drawFilterControls();
        this.startOver();
    };


    TranslationTable.prototype.startOver = function() {
        this.applyFilter();

        // all page related indexes are 1 based, only selectPage and draw are using 0 based index
        this.page = 1;
        this.itemsPerPage = 30;
        this.lastPage = Math.ceil(this.itemCount / this.itemsPerPage);
        this.paginationSpace = 3;
        this.pagination = $('.pag-nav');
        this.pagination.empty();

        // hide pagination on fewer items
        this.setupEvents(); //TODO probably only once needed, move to constructor?
        if (this.usePagination()) {
            this.drawPaginationBar();
        }
        this.drawPaginationBarHint();
        this.drawTable();
    };

    TranslationTable.prototype.usePagination = function() {
        return this.itemCount > this.itemsPerPage * 2;
    };

    TranslationTable.prototype.applyFilter = function() {
        this.itemCount = this.filteredRows.length;
        if (this.filterText === '') {
            this.filteredRows.fill(true);
            return;
        }

        const regex = new RegExp(this.escapeRegExp(this.filterText), 'i');
        this.filteredRows.fill(false);
        for (let i = 0; i < this.allRows.length; i++) {
            let row = $(this.allRows.get(i));
            if (row.attr('data-translation-key').match(regex)) {
                this.filteredRows[i] = true;
                continue;
            }
            if (row.find('td:first').text().match(regex)) {
                this.filteredRows[i] = true;
                continue;
            }
            if (row.find('td textarea').val().match(regex)) {
                this.filteredRows[i] = true;
                continue;
            }
            this.itemCount--;
        }
    };

    /**
     * @see http://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
     * which points now to: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions#escaping
     */
    TranslationTable.prototype.escapeRegExp = function(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"); // $& means the whole matched string
    };

    /**
     * Shows the page set by this.page, considers the filtering
     */
    TranslationTable.prototype.drawTable = function() {
        this.allRows.hide();
        let startIndex = (this.page-1) * this.itemsPerPage;
        let endIndex;
        if (this.usePagination()) {
            endIndex = startIndex + this.itemsPerPage;
            if (endIndex >= this.itemCount) {
                endIndex = this.itemCount;
            }
        } else {
            endIndex = this.itemCount;
        }


        let filteredIndex = -1;
        for(let j = 0; j < this.allRows.length; j++) {
            if(this.filteredRows[j]) {
                filteredIndex++;
                if(filteredIndex < startIndex) continue;

                let element = jQuery(this.allRows.get(j));
                let textarea = element.find('textarea');
                element.show();
                this.checkTextarea(textarea, false);
                autosize(textarea);

                if(filteredIndex === endIndex-1) break;
            }
        }
    };

    TranslationTable.prototype.setupEvents = function() {
        const that = this;
        this.allRows.find('textarea').on('keyup', function() {
            that.checkTextarea($(this), true);
        });

        this.allRows.find('textarea').on('click', function() {
            that.checkTextarea($(this), true);
        });

        this.allRows.find('textarea').on('focusout', function() {
            $(this).popover('destroy');
        });

        $(window).on('beforeunload', function() {
            return "Do you really want to leave the page? All changes will be lost.";
        });

        $('button[type=submit]').on('click', function() {
            $(window).off('beforeunload');
            if ($(this).attr('id') === 'save__button') {
                that.filterText = '';
                that.applyFilter();
            }
        });
    };


    TranslationTable.prototype.drawFilterControls = function() {
        let parent = $('.translation-filter');
        parent.append($('<div class="col-md-12"><h4>Filter</h4></div>'));

        let form = $('<div class="col-md-4"></div>');
        form.append($('<label for="table-filter">Filter translations for:</label>'));
        let filter = $('<input id="table-filter" type="text" class="form-control"/>');
        form.append(filter);
        parent.append(form);

        const that = this;
        let changed = function() {
            let newVal = $(this).val();
            if (that.filterText === newVal) {
                return;
            }
            that.filterText = newVal;
            that.startOver();
        };
        filter.on('keyup', changed);

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
        let ul = $('<ul class="pagination"></ul>');
        this.pagination.append(ul);


        this.nextButton = this.paginationElement('»');
        this.prevButton = this.paginationElement('«');

        const that = this;
        this.nextButton.on('click', function() {
            that.nextPage();
        });
        this.prevButton.on('click', function() {
            that.prevPage();
        });

        if (this.page > 1) {
            ul.append(this.prevButton);
        }

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
        if (this.page + 5 === this.lastPage) {
            ul.append(this.paginationPageElement(this.lastPage-1));
        }
        if (this.page + 4 <= this.lastPage) {
            ul.append(this.paginationPageElement(this.lastPage));
        }

        if (this.page !== this.lastPage) {
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
        element.on('click', function() {
            that.selectPage(jQuery(this).text());
        });
        if (this.page === page) {
            element.addClass('active')
        }
        return element;
    };

    TranslationTable.prototype.paginationElement = function(text) {
        let aria = '';
        if(text === '«' ) {
            aria = ' aria-label="Previous"';
        }
        if(text === '»' ) {
            aria = ' aria-label="Next"';
        }
        let page = $('<li></li>');
        let link = $('<a' + aria + '></a>');
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
        this.drawTable();
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

    /**
     * Checks textarea and warn if empty or when unequal number of placeholders and missing whitespace
     * @param element
     * @param popover
     */
    TranslationTable.prototype.checkTextarea = function(element, popover) {
        let warnElement = element.parents('tr');

        if (element.val() === '') {
            warnElement.addClass('warning');
            if (popover) element.popover('show');
            return;
        }

        let originalTranslation = warnElement.find('td:first');
        //https://stackoverflow.com/a/8915445/1043588
        let regex = new RegExp(
                "%(?:\\d+\\$)?[+-]?(?:[ 0]|'.)?-?\\d*(?:\\.\\d+)?[bcdeEufFgGosxX]",
                "g"
            );

        let leftMatch = originalTranslation.text().match(regex);
        let rightMatch = element.val().match(regex);
        if (leftMatch != null || rightMatch != null) {
            if (rightMatch == null || leftMatch == null ||
                rightMatch.length !== leftMatch.length) {
                warnElement.addClass('warning-msg');
                element.popover({
                    placement: 'top',
                    trigger: 'manual',
                    content: 'The translated text is missing a placeholder like %s or %d. Placeholders needs to be kept when translating.<br><br>'
                        + ' Tip: For changing of the order use %1$s and %2$s, where the numbers refer to their original position',
                    html: true,
                    title: 'Missing placeholder'
                });
                if (popover) element.popover('show');
                return;
            }
        }

        let leftSpaces = originalTranslation.find('span.highlight-whitespace').length;
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
            warnElement.addClass('warning-msg');
            element.popover({
                placement: 'top',
                trigger: 'manual',
                content: 'The translated text has missing trailing whitespaces. Please check the highlighted parts in the original version.',
                title: 'Missing whitespaces'
            });
            if (popover) element.popover('show');
            return;
        }

        warnElement.removeClass('warning').removeClass('warning-msg');
        element.popover('destroy');
    };


    const table = new TranslationTable();
});