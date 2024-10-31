(function($) {
    $(document).ready( function() {

        $('.flipbook').on('click', function () {
            var id = $(this).attr('data-id');
            $.ajax({
                type: 'POST',
                url: ajaxParams.ajaxUrl,
                async: false,
                data: {
                    action: 'responsive_flipbooks_flipbook_focus',
                    id: id,
                },
                success: function(data) {
                    window.top.location.reload();
                },
                error: function() {
                    return 0;
                }
            });
        });

        if($('.flipbooks-wrapper').length <= 0){
            $.ajax({
                type: 'POST',
                url: ajaxParams.ajaxUrl,
                async: false,
                data: {
                    action: 'responsive_flipbooks_flipbook_focus_off',
                },
                success: function (data) {
                    if(data){
                        var l10n = wp.media.view.l10n;
                        var frame = new wp.media.view.MediaFrame.Post({});
                        frame.on( 'open', function() {
                            wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
                                template: function(view){
                                    return  wp.media.template('custom-flipbook-setting')(view);
                                }
                            });

                            var selection = frame.state().get('selection');
                            var library = frame.state('gallery-edit').get('library');
                            var ids = flipbook_data_ids;
                            ids.forEach(function(id) {
                                var attachment = wp.media.attachment(id);
                                attachment.fetch();
                                selection.add( attachment ? [ attachment ] : [] );
                            });

                            frame.states.models[4].attributes.title = l10n.editFlipbookTitle;
                            frame.states.models[5].attributes.title = l10n.addToFlipbookTitle;
                            frame.options.isflipbook = true;
                            frame.options.isflipbookList = true;
                            frame.options.flipbook = flipbook_id;

                            frame.setState('gallery-edit');
                            ids.forEach(function(id) {
                                var attachment = wp.media.attachment(id);
                                attachment.fetch();
                                library.add( attachment ? [ attachment ] : [] );
                            });

                            library.gallery.attributes = {
                                'title' : flipbook_title,
                                'style': flipbook_style,
                                'display': flipbook_display,
                            };

                        });
                        frame.on('close', function () {
                            flipbook_title = '';
                            flipbook_style = null;
                            flipbook_display = null;
                        });

                        frame.open();
                    }
                },
                error: function () {
                    return 0;
                }
            });
        }
    });
})(jQuery);
