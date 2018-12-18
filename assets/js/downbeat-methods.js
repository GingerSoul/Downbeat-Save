/**
 * Downbeat AJAX connectors - Methods for interacting with custom post type
 */
(function () {
    var downbeatUserSessionCRUD = {
        load,
        save,
        update,
        delete: delete_,
    }

    // when the app finishes init and dispatches this method, send the CRUD methods to it
    document.addEventListener('downbeat:appInited', ({detail}) => detail(downbeatUserSessionCRUD))

    /**
     * Loads saved sessions from CPT
     * @return JSON  [ {id,title,config}, {id,title,config} ]
     */
    function load () {
        return new Promise((resolve, reject) => {
            jQuery.post(
                dbwp.ajaxurl, {
                    action: 'downbeat_load'
                },
                resolve,
                'json'
            )
        })
    }

    /**
     * Saves Downbeat session
     * @param STRING title
     * @param STRING config
     * @return JSON {id}
     */
    function save (title, config) {
        return new Promise((resolve, reject) => {
            jQuery.post(
                dbwp.ajaxurl, {
                    action: 'downbeat_save',
                    title,
                    config,
                },
                resolve,
                'json'
            )
        })
    }

    /**
     * Updates Downbeat session
     * @param INT id
     * @param STRING title
     * @param STRING config
     * @return JSON {id}
     */
    function update (id, title, config) {
        return new Promise((resolve, reject) => {
            jQuery.post(
                dbwp.ajaxurl, {
                    action: 'downbeat_update',
                    id,
                    title,
                    config,
                },
                resolve,
                'json'
            )
        })
    }

    /**
     * Deletes Downbeat session from CPT
     * @param id
     * @reutn JSON {id}
     */
    function delete_ (id) {
        return new Promise((resolve, reject) => {
            jQuery.post(
                dbwp.ajaxurl, {
                    action: 'downbeat_delete',
                    id
                },
                resolve,
                'json'
            )
        })
    }

})()
