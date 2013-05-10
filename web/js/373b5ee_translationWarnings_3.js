
$(document).ready(function() {

    function checkTextarea(element, popover) {
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
    }

    var textAreas = $('textarea');

    textAreas.keyup(function() {
        checkTextarea($(this), true);
    });

    textAreas.click(function() {
        checkTextarea($(this), true);
    });

    textAreas.focusout(function() {
        $(this).popover('destroy');
    });


    /*textAreas.each(function() {
        checkTextarea($(this));
    })*/

    setTimeout(function(){
        //execute the next task

        var task = jQuery(textAreas.get(0));
        textAreas.splice(0, 1);
        checkTextarea(task, false);
        //task.expandingTextarea();

        if (textAreas.length > 0){
            setTimeout(arguments.callee, 1);
        }
    }, 1);


});