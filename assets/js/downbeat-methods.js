/**
 * Downbeat AJAX connectors - Methods for interacting with custom post type
 */
var downbeatWP = {

    /**
     * Loads saved sessions from CPT
     * @return JSON [ {id,title,config} , {id,title,config} ]
     */
    'load' : function() {
        jQuery.post(
            dbwp.ajaxurl, {
                'action'    :   'downbeat_load'
            },
            function (response) {
                return response;
            },
            'json'
        )
    },

    /**
     * Saves Downbeat session
     * @param STRING title
     * @param STRING config
     * @return JSON {id, success, error}
     */
    'save' : function( title , config ) {
        jQuery.post(
            dbwp.ajaxurl, {
                'action'	:   'downbeat_save',
                'title'     :   title,
                'config'    :   config
            },
            function (response) {
                return response;
            },
            'json'
        )
    },

    /**
     * Updates Downbeat session
     * @param INT id
     * @param STRING title
     * @param STRING config
     * @return JSON {id , success , error}
     */
    'update' : function( id , title , config) {
        jQuery.post(
            dbwp.ajaxurl, {
                'action'	:   'downbeat_update',
                'id'        :   id,
                'title'     :   title,
                'config'    :   config
            },
            function (response) {
                return response;
            },
            'json'
        )
    },

    /**
     * Deletes Downbeat session from CPT
     * @param id
     * @reutn JSON {id,success,error}
     */
    'delete' : function(id) {
        JQuery.post(
            dbwp.ajaxurl, {
                'action'	:   'downbeat_delete',
                'id'        :   id
            },
            function (response) {
                /* Example success response */

                return response;
            },
            'json'
        )
    }
}