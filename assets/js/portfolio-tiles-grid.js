(function () {
    'use strict';

    if ( window.ptgPortfolioTilesInit ) {
        window.ptgPortfolioTilesInit();
        return;
    }

    var prefetchedSources = new Set();
    var raf = window.requestAnimationFrame ? window.requestAnimationFrame.bind( window ) : function ( callback ) {
        callback();
    };

    function prefetchImage( item ) {
        if ( ! item ) {
            return;
        }

        var img = item.querySelector( 'img' );
        if ( ! img ) {
            return;
        }

        var src = img.currentSrc || img.getAttribute( 'src' );
        if ( ! src || prefetchedSources.has( src ) ) {
            return;
        }

        prefetchedSources.add( src );
        var preload = new Image();
        preload.src = src;
    }

    function markImageLifecycle( item, img ) {
        var apply = function () {
            item.classList.add( 'has-img' );
        };

        if ( img.complete ) {
            apply();
            return;
        }

        img.addEventListener( 'load', apply, { once: true } );
        img.addEventListener( 'error', apply, { once: true } );
    }

    function prefersReducedMotion() {
        if ( 'matchMedia' in window ) {
            return window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
        }

        return false;
    }

    function initGrid( grid ) {
        if ( ! grid || grid.dataset.ptgBound === '1' ) {
            return;
        }

        grid.dataset.ptgBound = '1';

        var dataset = grid.dataset;
        var reveal = dataset.ptgReveal || 'on-scroll';
        var prefetchMode = dataset.ptgPrefetch || 'near';
        var stagger = parseInt( dataset.ptgStagger, 10 );
        var staggerMs = isNaN( stagger ) ? 0 : stagger;
        var thresholdValue = parseFloat( dataset.ptgThreshold );
        var observerThreshold = isNaN( thresholdValue ) ? 0.15 : thresholdValue;
        var rootMargin = dataset.ptgRootmargin || '0px';

        var items = Array.prototype.slice.call( grid.querySelectorAll( '.ptg-item' ) );
        if ( items.length === 0 ) {
            return;
        }

        var reduceMotion = prefersReducedMotion();
        var supportsObserver = 'IntersectionObserver' in window;
        var effectiveReveal = reduceMotion ? 'none' : reveal;

        items.forEach( function ( item, idx ) {
            if ( ! item.dataset.idx ) {
                item.dataset.idx = String( idx );
            }

            var parsedIdx = parseInt( item.dataset.idx, 10 );
            var itemIndex = isNaN( parsedIdx ) ? idx : parsedIdx;

            if ( effectiveReveal === 'on-scroll' && staggerMs > 0 ) {
                var delay = itemIndex >= 0 ? itemIndex * staggerMs : idx * staggerMs;
                item.style.transitionDelay = delay > 0 ? delay + 'ms' : '';
            } else {
                item.style.transitionDelay = '';
            }

            if ( effectiveReveal !== 'on-scroll' ) {
                item.classList.add( 'is-visible' );
            }

            var img = item.querySelector( 'img' );
            if ( img ) {
                markImageLifecycle( item, img );
            }
        } );

        if ( effectiveReveal !== 'on-scroll' || ! supportsObserver ) {
            if ( prefetchMode === 'all' ) {
                items.forEach( prefetchImage );
            }

            items.forEach( function ( item ) {
                item.classList.add( 'is-visible' );
            } );

            return;
        }

        if ( prefetchMode === 'all' ) {
            items.forEach( prefetchImage );
        }

        var observer = new IntersectionObserver( function ( entries, obs ) {
            entries.forEach( function ( entry ) {
                if ( ! entry.isIntersecting && entry.intersectionRatio <= 0 ) {
                    return;
                }

                var target = entry.target;

                if ( prefetchMode === 'near' || prefetchMode === 'all' ) {
                    prefetchImage( target );
                }

                raf( function () {
                    target.classList.add( 'is-visible' );
                } );

                obs.unobserve( target );
            } );
        }, {
            root: null,
            rootMargin: rootMargin,
            threshold: observerThreshold
        } );

        items.forEach( function ( item ) {
            observer.observe( item );
        } );
    }

    function initAll() {
        var grids = document.querySelectorAll( '.ptg-grid' );
        grids.forEach( initGrid );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', initAll );
    } else {
        initAll();
    }

    window.addEventListener( 'load', initAll );

    window.ptgPortfolioTilesInit = initAll;
})();
