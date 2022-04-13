/* jshint ignore:start */
define(['jquery','core/log'], function($, log, def) {

    "use strict";
    log.debug('Trigger alert button: initialising');

    return {

        buttonid: null,
        instanceprops: null,

        //pass in config, and register any events
        init: function(props){
            log.debug(props);
            this.instanceprops=props;
            this.buttonid=props.buttonid;
            this.register_events();

        },

        register_events: function() {
            var ip = this.instanceprops;
            var that = this;
            var triggerbutton = $('#' + this.buttonid);

            triggerbutton.click(function (e) {
                e.preventDefault();
                var message = M.util.get_string('triggeralert_message', def.compoment);
                alert(message);
            });
        }
    };//end of returned object
});//total end