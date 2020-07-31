define([
    'jquery',
    'ko',
    'uiComponent'
], function (
    $,
    ko,
    Component
) {
    'use strict';

    const imagesDefault = 10;

    var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
    var countImage = 0;
    lazyImages.forEach(function (lazyImage) {
        countImage = countImage + 1;
        if (countImage <= imagesDefault) {
            lazyImage.src = lazyImage.dataset.src;
            lazyImage.classList.remove("lazy");
        }
    });

    return Component.extend({

        initialize: function () {
            let self = this;

                let lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
                let active = false;

                const lazyLoad = function () {
                    if (active === false) {
                        active = true;

                        setTimeout(function () {
                            lazyImages.forEach(function (lazyImage) {
                                if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyImage).display !== "none") {
                                    lazyImage.src = lazyImage.dataset.src;
                                    lazyImage.classList.remove("lazy");
                                    lazyImages = lazyImages.filter(function (image) {
                                        return image !== lazyImage;
                                    });

                                    if (lazyImages.length === 0) {
                                        document.removeEventListener("scroll", lazyLoad);
                                        window.removeEventListener("resize", lazyLoad);
                                        window.removeEventListener("orientationchange", lazyLoad);
                                    }
                                }
                            });

                            active = false;
                        }, 100);
                    }
                };

                document.addEventListener("scroll", lazyLoad);
                window.addEventListener("resize", lazyLoad);
                window.addEventListener("orientationchange", lazyLoad);
        }

    });
});