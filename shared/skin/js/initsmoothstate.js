// initsmoothstate.js
// Initializes smoothState.js for smooth AJAX page transitions
//
// Smoothstate intercepts internal navigation links and loads page content via AJAX,
// creating smooth CSS animations instead of full page reloads.
//
// Configuration is set via the SmoothStateConfig object below.
// Sites can customize by overriding SmoothStateConfig properties in their page.tmpl
// before this script is loaded.
//
// Requirements:
// - jQuery 3.7.1+
// - jquery.smoothState.js plugin loaded
// - CSS animations via animate.css (fadeIn, is-exiting classes)
// - HTML: <body id="body" class="scene element fadeIn">
//
// Configuration variables defined in _zoid6vars.scss can be overridden per-site:
// $smoothstate-animation, $smoothstate-duration, $smoothstate-prefetch,
// $smoothstate-cache-length, $smoothstate-debug, $smoothstate-exit-class
//
// @see zoid6/handbook/SMOOTHSTATE.md for detailed documentation
// @see https://github.com/miguel-perez/smoothState.js for plugin documentation

'use strict';

(function ($) {
  // Initialize smoothState on page content containers (#head and #body)
  // These are the primary elements that get replaced on AJAX navigation
  var content = $('#head, #body').smoothState({
    // Enable debug logging to browser console
    debug: true,
    
    // Prefetch page content on hover/touchstart for better perceived performance
    prefetch: true,
    
    // Number of pages to keep in memory cache for instant back/forward navigation
    cacheLength: 2,
    
    // onStart: Animation fired when user clicks a navigation link
    // Runs before AJAX request, triggers exit animation (fade out)
    onStart: {
      // Duration of exit animation in milliseconds
      duration: 250,
      
      // Render function: applies CSS class to trigger fade-out animation
      // Called by smoothState when navigation begins
      render: function () {
        // toggleAnimationClass() is a public smoothState method
        // Adds "is-exiting" class to #head and #body to trigger CSS fade-out
        content.toggleAnimationClass("is-exiting");
        console.log("smoothState: page exit animation triggered (is-exiting class added)");
      }
    }
  }).data("smoothState");
  
  console.log("smoothState initialized - smooth page transitions enabled");

})(jQuery);
