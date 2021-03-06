/**
 * DO NOT EDIT THIS FILE.
 * See the following change record for more information,
 * https://www.drupal.org/node/2815083
 * @preserve
 **/

(function (CKEDITOR) {

  CKEDITOR.plugins.add( 'pagebuilder', {

    init: function( editor ) {

      if ( editor.contextMenu ) {
        editor.contextMenu.addListener( function( element ) {
          return { PageBuilder: CKEDITOR.TRISTATE_OFF };
        });

        _.forEach(editor.config.pagebuilder.menuGroups, function(element, i) {
          editor.addMenuGroup( element );
        });

        _.forEach(editor.config.pagebuilder.menuItems, function (element, i) {

          let command = 'command_' + i;
          editor.addCommand( command, insertTemplate( element.path ) );

          let menuItem = {
            label: element.label,
            command: command,
            group: element.group
          };

          if (!_.isEmpty(element.items)) {
            let items = {};
            _.forEach(element.items, function(e, k) {
              items[e] = CKEDITOR.TRISTATE_OFF;
            });
            menuItem['getItems'] = function() {
              return items;
            };
          }

          if (!_.isEmpty(element.icon)) {
            menuItem['icon'] = element.icon;
          }
          editor.addMenuItem(i, menuItem);
        });
      }

      function insertTemplate( template ) {
        return {
          exec: function( editor ) {
            getTemplate(template)
              .then(data => {
                editor.insertHtml( data );
              })
              .catch();
          }
        };
      }

      async function getTemplate(endpoint) {
        if (!_.isEmpty(endpoint)) {
          const res = await fetch(endpoint);

          if (!res.ok) {
            throw new Error(res.status);
          }
          const data = await res.text();
          return data;
        }
      }

    }
  });

})(CKEDITOR);
