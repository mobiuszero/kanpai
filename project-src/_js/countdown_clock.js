var countdown_elem = $('#countdown');
var countdown_date = '2017/10/15';

countdown_elem.countdown(countdown_date).on('update.countdown', function (event) {
    var $this = $(this).html(event.strftime(
        '    <div class="countdown__wrapper">\n' +
        '        <div class="countdown_item">\n' +
        '            <div class="countdown__date">%D</div>\n' +
        '            <div class="countdown__date_label">day%!d</div>\n' +
        '        </div>\n' +
        '        <div class="countdown_item">\n' +
        '            <div class="countdown__date">%H</div>\n' +
        '            <div class="countdown__date_label">hour%!H</div>\n' +
        '        </div>\n' +
        '        <div class="countdown_item">\n' +
        '            <div class="countdown__date">%M</div>\n' +
        '            <div class="countdown__date_label">minute%!M</div>\n' +
        '        </div>\n' +
        '        <div class="countdown_item">\n' +
        '            <div class="countdown__date">%S</div>\n' +
        '            <div class="countdown__date_label">second%!S</div>\n' +
        '        </div>\n' +
        '    </div>'
    ));
});