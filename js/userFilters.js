/**
 * Created by moridrin on 14-12-16.
 */
window.onload = function () {
    jQuery(document).ready(function ($) {
        var old_filter_area = $('.subsubsub');
        old_filter_area.before('<h2 style="margin-bottom: 0;">Filters</h2>');
        old_filter_area.after('<br/><br/><form name="filter_form" method="post"><div id="filter_area"></div></form>');
        if (variables.filtersLocation == 'replace') {
            old_filter_area.remove();
        } else if (variables.filterLocation == 'under') {
            old_filter_area.after('<br/><br/>');
        }
        var filter_area = $('#filter_area');
        filter_area.html(variables.filters);
    });
};