jQuery(function($) {

    $('select[name=complete_book_id]').on('change', function(){
        flipBookActions.quizHideAll( true );
        flipBookActions.quizReDisplay( $(this).val() );
    });
    $('#book_typechecklist input').on('click', function(){
        $('#book_typechecklist input').each(function(indx, element){$(this).prop('checked', false);});
        $(this).attr('checked', 'checked');
        flipBookActions.flipBookStart(this, false);
    });

    // if (typeof wp === 'undefined' || typeof wp.media.controller === 'undefined') return true;
    var wpmedia = wp.media;
    var Library = wp.media.controller.Library;
    var oldMediaFrame = wp.media.view.MediaFrame.Post;
    var l10n = wp.media.view.l10n;

    // Extending the current media library frame to add a new tab
    wp.media.view.MediaFrame.Post = oldMediaFrame.extend({
        initialize: function() {
            // Calling the initalize method from the current frame before adding new functionality
            oldMediaFrame.prototype.initialize.apply( this, arguments );
            var options = this.options;

            // Adding new tab
            this.states.add([
                new Library({
                    id:         'inserts',
                    title:      'Create Flipbook',
                    priority:   40,
                    toolbar:    'main-gallery',
                    filterable: 'uploaded',
                    library:  wp.media.query( _.defaults({
                        type: 'image',
                        galleryType: 'flipbook',
                    }, options.library ) ),
                    multiple:   'add',
                    editable:   true,
                    displaySettings: true,
                    displayUserSettings: true
                }),
            ]);
        },

        mainGalleryToolbar: function( view ) {
            var controller = this;
            this.selectionStatusToolbar( view );
            oldMediaFrame.prototype.mainGalleryToolbar.call( this, view );
            var collections = {};
            var isflipbook = typeof view.library.props.attributes.galleryType !== 'undefined';

            controller.states.models[4].attributes.title = isflipbook ? l10n.editFlipbookTitle : l10n.editGalleryTitle;
            controller.states.models[5].attributes.title = isflipbook ? l10n.addToFlipbookTitle : l10n.addToGalleryTitle;

            if(isflipbook) {
                wp.media._flipbookDefaults = {
                    itemtag: 'dl',
                    icontag: 'dt',
                    captiontag: 'dd',
                    columns: '3',
                    link: 'post',
                    size: 'thumbnail',
                    order: 'ASC',
                    parent_id: wp.media.view.settings.post && wp.media.view.settings.post.id,
                    orderby : 'menu_order ID'
                };

                if ( wp.media.view.settings.flipbookDefaults ) {
                    wp.media.flipbookDefaults = _.extend( {}, wp.media._flipbookDefaults, wp.media.view.settings.flipbookDefaults );
                } else {
                    wp.media.flipbookDefaults = wp.media._flipbookDefaults;
                }

                wp.media.gallery = new wp.media.collection({
                    tag: 'flipbook',
                    type : 'image',
                    editTitle : wp.media.view.l10n.editFlipbookTitle,
                    defaults : wp.media.flipbookDefaults,
                    setDefaults: function( attrs ) {
                        var self = this, changed = ! _.isEqual( wp.media.flipbookDefaults, wp.media._flipbookDefaults );
                        _.each( wp.media.flipbookDefaults, function( value, key ) {
                            attrs[ key ] = self.coerce( attrs, key );
                            if ( value === attrs[ key ] && ( ! changed || value === wp.media.flipbookDefaults[ key ] ) ) {
                                delete attrs[ key ];
                            }
                        } );

                        return attrs;
                    },
                    shortcode: function( attachments ) {

                        var props = attachments.props.toJSON(),
                            attrs = _.pick( props, 'orderby', 'order' ),
                            shortcode, clone;

                        if ( attachments.type ) {
                            attrs.type = attachments.type;
                            delete attachments.type;
                        }

                        if ( attachments['gallery'] ) {
                            _.extend( attrs, attachments['gallery'].toJSON() );
                        }

                        // Convert all gallery shortcodes to use the `ids` property.
                        // Ignore `post__in` and `post__not_in`; the attachments in
                        // the collection will already reflect those properties.
                        attrs.ids = attachments.pluck('id');

                        // Copy the `uploadedTo` post ID.
                        if ( props.uploadedTo ) {
                            attrs.parent_id = props.uploadedTo;
                        }
                        // Check if the gallery is randomly ordered.
                        delete attrs.orderby;

                        if ( attrs._orderbyRandom ) {
                            attrs.orderby = 'rand';
                        } else if ( attrs._orderByField && attrs._orderByField != 'rand' ) {
                            attrs.orderby = attrs._orderByField;
                        }

                        delete attrs._orderbyRandom;
                        delete attrs._orderByField;

                        // If the `ids` attribute is set and `orderby` attribute
                        // is the default value, clear it for cleaner output.
                        if ( attrs.ids && 'post__in' === attrs.orderby ) {
                            delete attrs.orderby;
                        }

                        attrs = this.setDefaults( attrs );
                        attrs.title = (typeof attrs.title !== 'undefined') ? attrs.title : '(no title)';

                        if (isflipbook) {
                            $.ajax({
                                type: 'POST',
                                url: ajaxParams.ajaxUrl,
                                async: false,
                                data: {
                                    action: 'responsive_flipbooks_save',
                                    title: attrs.title,
                                    ids: attrs.ids,
                                    display: (typeof attrs.display !== 'undefined') ? attrs.display : '',
                                    style: (typeof attrs.style !== 'undefined') ? attrs.style : '',
                                },
                                success: function(data) {
                                    attrs.id = data;
                                },
                                error: function() {
                                    return 0;
                                }
                            });
                        }

                        if (typeof attrs.title !== 'undefined') {
                            delete attrs.title;
                            delete attrs.ids;
                        }
                        shortcode = new wp.shortcode({
                            tag:    this.tag,
                            attrs:  attrs,
                            type:   'single'
                        });


                        // Use a cloned version of the gallery.
                        clone = new wp.media.model.Attachments( attachments.models, {
                            props: props
                        });
                        clone[ this.tag ] = attachments[ this.tag ];
                        collections[ shortcode.string() ] = clone;

                        return shortcode;
                    },
                });
                _.extend(wp.media.gallery.defaults, {
                  style: 'book',
                  display: 'double',
                });

                wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
                  template: function(view){
                    return  wp.media.template('custom-flipbook-setting')(view);
                  }
                });
            } else {
                wp.media.gallery = new wp.media.collection({
                    tag: 'gallery',
                    type : 'image',
                    editTitle : wp.media.view.l10n.editGalleryTitle,
                    defaults : wp.media.galleryDefaults,
                    setDefaults: function( attrs ) {
                        var self = this, changed = ! _.isEqual( wp.media.galleryDefaults, wp.media._galleryDefaults );
                        _.each( this.defaults, function( value, key ) {
                            attrs[ key ] = self.coerce( attrs, key );
                            if ( value === attrs[ key ] && ( ! changed || value === wp.media._galleryDefaults[ key ] ) ) {
                                delete attrs[ key ];
                            }
                        } );
                        return attrs;
                    }
                });
                wp.media.gallery.defaults = wp.media.galleryDefaults;
                wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
                  template: function(view){
                    return wp.media.template('gallery-settings')(view);
                  }
                });

            }

            view.set( 'gallery', {
                style:    'primary',
                text:   isflipbook ? l10n.createNewFlipbook : l10n.createNewGallery,
                priority: 60,
                requires: { selection: true },

                click: function() {
                    var selection = controller.state().get('selection'),
                        edit = controller.state('gallery-edit'),
                        models = selection.where({ type: 'image' });

                    edit.set( 'library', new wp.media.model.Selection( models, {
                        props:    selection.props.toJSON(),
                        multiple: true
                    }) );


                    this.controller.options.isflipbook = isflipbook;

                    this.controller.setState('gallery-edit');

                    this.controller.modal.focusManager.focus();
                }
            });
        },
        galleryEditToolbar: function() {
            var editing = this.state().get('editing');
            var isflipbook = this.options.isflipbook;
            var isflipbookList = this.options.isflipbookList;

            if(!isflipbookList)
                this.toolbar.set( new wp.media.view.Toolbar({
                    controller: this,
                    items: {
                        insert: {
                            style:    'primary',
                            text:   function() {
                                if(editing) {
                                    return isflipbook ? l10n.updateFlipbook : l10n.updateGallery;
                                }
                                return isflipbook ? l10n.insertFlipbook : l10n.insertGallery;

                            },
                            priority: 80,
                            requires: { library: true },
                            click: function() {
                                var controller = this.controller,
                                    state = controller.state();

                                controller.close();

                                state.trigger( 'update', state.get('library') );

                                // Restore and reset the default state.
                                controller.setState( controller.options.state );
                                controller.reset();
                            }
                        }
                    }
                }) );
            else
                this.toolbar.set( new wp.media.view.Toolbar({
                    controller: this,
                    items: {
                        deleteFlipbook: {
                            style: 'primary',
                            text: 'Delete flipbook',
                            priority: 90,
                            requires: {library: true},
                            click: function () {
                                var controller = this.controller;
                                $.ajax({
                                    type: 'POST',
                                    url: ajaxParams.ajaxUrl,
                                    async: false,
                                    data: {
                                        action: 'responsive_flipbooks_delete',
                                        id: controller.options.flipbook,
                                    },
                                    success: function(data) {
                                        controller.close();
                                    },
                                    error: function() {
                                        return 0;
                                    }
                                });
                            }
                        },
                        insert: {
                            style:    'primary',
                            text:   l10n.updateFlipbook,
                            priority: 80,
                            requires: { library: true },
                            click: function() {
                                var controller = this.controller,
                                    state = controller.state();

                                controller.close();

                                var newIds = [];
                                state.get('library').models.forEach(function (new_model) {
                                    newIds.push(new_model['id']);
                                });
                                var attrs = state.get('library').gallery.attributes;

                                $.ajax({
                                    type: 'POST',
                                    url: ajaxParams.ajaxUrl,
                                    async: false,
                                    data: {
                                        action: 'responsive_flipbooks_update',
                                        id: controller.options.flipbook,
                                        ids: newIds,
                                        title: (typeof attrs.title !== 'undefined') && !Array.isArray(attrs.title) ? attrs.title : '(no title)',
                                        style: (typeof attrs.style !== 'undefined') && !Array.isArray(attrs.style) ? attrs.style : 'double',
                                        display: (typeof attrs.display !== 'undefined') && !Array.isArray(attrs.display) ? attrs.display : 'book',
                                    },
                                    success: function(data) {
                                        state.trigger( 'update', state.get('library') );
                                        console.log(data);
                                        tinyMCE.activeEditor.selection.setContent('<p>' + data + '</p>');
                                        controller.close();
                                    },
                                    error: function() {
                                        return 0;
                                    }
                                });
                            }
                        }
                    }
                }) );
        },
        galleryAddToolbar: function() {
            var isflipbook = this.options.isflipbook;
            this.toolbar.set( new wp.media.view.Toolbar({
                controller: this,
                items: {
                    insert: {
                        style:    'primary',
                        text:    isflipbook ? l10n.addToFlipbook : l10n.addToGallery,
                        priority: 80,
                        requires: { selection: true },

                        /**
                         * @fires wp.media.controller.State#reset
                         */
                        click: function() {
                            var controller = this.controller,
                                state = controller.state(),
                                edit = controller.state('gallery-edit');

                            edit.get('library').add( state.get('selection').models );
                            state.trigger('reset');
                            controller.setState('gallery-edit');
                        }
                    }
                }
            }) );
        },
        galleryMenu: function( view ) {
            var lastState = this.lastState(),
                previous = lastState && lastState.id,
                isflipbook = this.options.isflipbook,
                isflipbookList = this.options.isflipbookList,
                frame = this;

            view.set({
                cancel: {
                    text:   isflipbook || isflipbookList ?  l10n.cancelFlipbookTitle : l10n.cancelGalleryTitle,
                    priority: 20,
                    click:    function() {
                        if ( !isflipbookList && previous ) {
                            frame.setState( previous );
                        } else {
                            frame.close();
                        }

                        // Keep focus inside media modal
                        // after canceling a gallery
                        this.controller.modal.focusManager.focus();
                    }
                },
                separateCancel: new wp.media.View({
                    className: 'separator',
                    priority: 40,
                })
            });
        },
    });

    var flipBookActions = {
        flipBookStart: function(elem){
            bookTypeId = $(elem).val();
            var is_checked = ( $(elem).attr('checked')=='checked') ? true:false;
            if (is_checked && bookTypeCheckBox[bookTypeId] == 'book-sample')
              this.bookSampleChange(is_checked);
            if (is_checked && bookTypeCheckBox[bookTypeId] == 'complete-book')
              this.completeBookChange(is_checked);
        },
        bookSampleChange: function( is_checked ){
            this.chElemVisibility('#full_flipbook_id', is_checked );
            this.chElemVisibility('#samples_book_product', is_checked);
            // this.quizHideAll( false );
            // this.quizReDisplay($('select[name=complete_book_id]').val());
        },
        completeBookChange: function( is_checked ){
            this.chElemVisibility('#full_flipbook_id', !is_checked );
            this.chElemVisibility('#samples_book_product', !is_checked);
            // this.quizShowAll();
        },
        chElemVisibility : function(elem, visibility){
            var showed = $(elem).is(":visible");
            if ( visibility ){
                $(elem).show('fast');
            } else {
                $(elem).hide('fast');
            }
        },
        quizHideAll : function( uncheck ) {
            $("#flipbook_quizzes input[type=checkbox]").each( function(indx, element){
                $(this).parent('div').hide();
                if ( uncheck )
                    $(this).attr('checked', false);
            });
        },
        quizShowAll : function() {
            $("#flipbook_quizzes input[type=checkbox]").each( function(indx, element){
                $(this).parent('div').show();
            });
        },

    }
    // for start hide all elements
    flipBookActions.chElemVisibility('#full_flipbook_id', false );
    flipBookActions.chElemVisibility('#samples_book_product', false);
    // flipBookActions.quizHideAll( false );
    $('#book_typechecklist input').each(function(indx, element){
        flipBookActions.flipBookStart(element);
    });
});
