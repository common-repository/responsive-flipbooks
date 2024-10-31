jQuery(document).ready(function($) {
  if(typeof tinymce !== 'undefined') {
      /**
      * Create a tinymce button "Insert Flipbook"
      */
      tinymce.create('tinymce.plugins.addFlipbook', {
        init: function(editor, url) {
            editor.addButton('add_flipbook', {
              title: 'Insert Flipbook',
              icon: 'icon dashicons-before dashicons-welcome-learn-more',
              onclick: function() {
                var booksAvailable = $.ajax({
                      url: ajax_object.ajaxurl,
                      type: 'post',
                      data: {
                          action: 'responsive_flipbooks_get_flipbook_ajax',
                          bookNonce: ajax_object.bookNonce
                      },
                      dataType: 'json'
                    });
                var booksFormattedArray = booksAvailable.complete(function(data) {
                      if(data.readyState == 4 && data.status == 200) {
                        dialogThrobber.hide();
                        return booksOptionFormatter(data.responseText, formattedArray);
                      }
                    });
                var formattedArray = [{text: 'select flipbook', value: '0'}];

                editor.windowManager.open({
                      title: 'Select a flipbook to insert',
                      id: 'insert-flipbook-sample',
                      body:[{
                          type: 'listbox',
                          name: 'flipbook_content',
                          // name: 'flipbook_id',
                          label: 'Flipbook',
                          values: formattedArray,
                        },
                      ],
                      onsubmit: function(e) {
                        var send_to_editor="";
                        if (e.data.flipbook_content) {
                          send_to_editor+=e.data.flipbook_content;
                        }
                        // if (e.data.flipbook_id != 0) {
                        //   send_to_editor+='[flipbook id="'+e.data.flipbook_id+'"]';
                        // }
                        editor.insertContent(send_to_editor);
                      }
                });

                dialogThrobber = initThrobber('insert-flipbook-sample-body');
                dialogThrobber.show(2);
              }
            });
          },
          createControl: function(n, cm) {
            return null;
          }
      });

      tinymce.PluginManager.add('add_flipbook', tinymce.plugins.addFlipbook);

      /**
     * Helper function. Creates a new throbber object.
     * @param elmId parent element id (e.g. windowId-body).
     */
    function initThrobber(elmId) {
      var dialogSelector = document.getElementById(elmId);
      dialogThrobber = new tinymce.ui.Throbber(dialogSelector, false);

      return dialogThrobber;
    }

    /**
     * Helper function. Formats ajax responce into select options.
     */
    function booksOptionFormatter(booksArray, resultArray) {
      booksArray = JSON.parse(booksArray);
      $.each(booksArray, function(index, object) {
        resultArray.push({text: object.title, value: object.content});
        // resultArray.push({text: object.title, value: object.id});
      });

      return resultArray;
    }

  }else {
    console.info('tinymce is not defined');
  }
});
