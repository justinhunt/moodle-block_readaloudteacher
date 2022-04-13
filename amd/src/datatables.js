define(['jquery','core/log','https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js'], function($,log, datatables) {
    "use strict"; // jshint ;_;

/*
This file contains class and ID definitions.
 */

    log.debug('ReadAloud Teacher Datatables helper: initialising');

    return{
        //pass in config, amd set up table
        init: function(props){
            //pick up opts from html
            var that=this;
            var thetable=$('#' + props.tableid);
            if(props.filterlabel){
                props.tableprops.initComplete = function(){
                    var api = $('#' + props.tableid).dataTable().api();
                    var column= api.column(props.filtercolumn);
                    var location = props.tableid + '_length';
                    that.add_filter_column(column,location,props.filterlabel);
                };
                props.tableprops.dom = 'lrtip';
            }
            this.dt=thetable.DataTable(props.tableprops);

        },

        add_filter_column: function(column,location,filterlabel){
            var readinglabel = $('<label>' + filterlabel + '</label>').prependTo('#' + location );
            var select = $('<select></select>')
                .appendTo(readinglabel)
                .on( 'change', function () {
                    var val = $.fn.dataTable.util.escapeRegex(
                        $(this).val()
                    );

                    column
                        .search( val ? '^'+val+'$' : '', true, false )
                        .draw();
                });
            column.data().unique().sort().each( function ( d, j ) {
                select.append( '<option value="'+d+'">'+d+'</option>' )
            });

            select.trigger("change");
        }
    };//end of return value
});