/*
 * Turn.js responsive book
 */
(function () {
    'use strict';

    var module = {
        init: function (id) {
            var me = this;

            if (document.addEventListener) {
                this.el = document.getElementById(id);
                this.resize();
                this.plugins();

                if(jQuery(me.el).hasClass('single')) {
                    jQuery(me.el).turn('display', 'single');
                    this.ratio = 0.89;
                }
                else {
                    this.ratio = 1.38;
                }

                jQuery(window).on('resize', function (e) {
                    var size = me.resize();
                    jQuery(me.el).turn('size', size.width, size.height);
                });
            }
            jQuery(window).trigger('resize');
        },
        resize: function () {
            this.el.style.width = '';
            this.el.style.height = '';

            var width = this.el.clientWidth,
                height = Math.round(width / this.ratio),
                padded = Math.round(document.body.clientHeight * 0.9);

            if (height > padded) {
                height = padded;
                width = Math.round(height * this.ratio);
            }

            this.el.style.width = width + 'px';
            this.el.style.height = height + 'px';

            return {
                width: width,
                height: height
            };
        },
        plugins: function () {
            jQuery(this.el).turn({
                gradients: true,
                acceleration: true
            });
        }
    };

    jQuery.each(jQuery('.flipping-book'), function(key, item){
        module.init(jQuery(item).attr('id'));
    });
}());
