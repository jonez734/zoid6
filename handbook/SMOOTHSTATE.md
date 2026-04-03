# Smoothstate: AJAX Page Transitions

## Overview

Smoothstate.js is a jQuery plugin that creates smooth CSS-based page transitions for zoid6 sites. Instead of hard page reloads with white flashes, users experience fade-in/fade-out animations while content loads via AJAX.

**Status:** ✅ Currently working in asimov. Being rolled out to achilles, empyre, rgs, and zoid6.

## How It Works

### Basic Flow

1. User clicks a navigation link
2. Smoothstate intercepts the click and shows exit animation (fade out)
3. New page content loads in the background via AJAX
4. Exit animation completes (250ms)
5. New content is injected into the DOM
6. Entry animation plays (fade in)
7. History is updated (HTML5 pushState for back/forward support)

### Animation Lifecycle

```
User clicks link
    ↓
onStart callback fires (250ms exit animation)
    → toggleAnimationClass("is-exiting") 
    → #head and #body fade out
    ↓
AJAX request in background
    ↓
Content arrives
    ↓
onReady callback (if defined)
    → New content injected
    → fadeIn class triggers entry animation
    ↓
Complete - user sees smooth transition
```

## Configuration

### Default Settings

Configuration is defined in `/zoid6/shared/skin/scss/_zoid6vars.scss`:

```scss
$smoothstate-animation: "fadeIn";        // Animation type (fadeIn, slideLeft, slideUp, etc.)
$smoothstate-duration: 250;              // Exit animation duration (ms)
$smoothstate-prefetch: true;             // Pre-load pages on hover
$smoothstate-cache-length: 2;            // Pages to keep in memory
$smoothstate-debug: true;                // Console logging enabled
$smoothstate-exit-class: "is-exiting";   // Exit animation class
```

### Per-Site Customization

Sites can override these variables in their own `_vars.scss`:

```scss
@import "zoid6vars";

// Custom smoothstate settings for this site
$smoothstate-animation: "slideLeft";     // Different transition effect
$smoothstate-duration: 500;              // Slower transitions
$smoothstate-cache-length: 5;            // Cache more pages
$smoothstate-debug: false;               // Disable debug logging
```

### JavaScript Config Override

For runtime customization, override in `page.tmpl` before the initsmoothstate.js script:

```html
<script>
  // Custom config for this page
  window.SmoothStateConfig = {
    debug: false,
    prefetch: false,  // Don't prefetch on this page
    cacheLength: 1,
    onStart: {
      duration: 500,
      render: function() {
        content.toggleAnimationClass("is-exiting");
      }
    }
  };
</script>
<script defer src="{$smarty.const.SHAREDSKINURL}js/initsmoothstate.js"></script>
```

## Available Animations

The `$smoothstate-animation` variable controls which CSS animation class is applied to the body during page entry. Available animations (from `animate.css`):

| Animation | Effect |
|-----------|--------|
| `fadeIn` (default) | Smooth opacity fade from 0 to 1 |
| `slideInLeft` | Slide in from left edge |
| `slideInRight` | Slide in from right edge |
| `slideInUp` | Slide up from bottom |
| `slideInDown` | Slide down from top |
| `zoomIn` | Scale from small to full size |
| `rotateIn` | Rotate into view |
| `bounceIn` | Bounce into view |
| `rollIn` | Roll into view |

To use a different animation, override in your site's `_vars.scss`:

```scss
$smoothstate-animation: "slideInLeft";   // Slides page in from left
```

The exit animation (fade out) is controlled by the `$smoothstate-exit-class` variable and always uses the CSS class toggle mechanism defined in the onStart callback.

## Required HTML Structure

### Body Element

The body element **must** have these attributes and classes:

```html
<body id="body" class="scene element fadeIn">
  <!-- page content -->
</body>
```

**Why each is required:**
- `id="body"` - Smoothstate selector to target for content replacement
- `class="scene"` - Marks this as an animated container
- `class="element"` - Marks element as ready for animation
- `class="fadeIn"` - Initial entry animation class

### Head Element

The head element must have `id="head"`:

```html
<head id="head" itemscope itemtype="https://schema.org/WebSite">
  <!-- meta tags, styles, scripts -->
</head>
```

Smoothstate updates the head when pages load to handle meta tags, titles, and other document properties.

## Required CSS & Dependencies

### 1. jQuery 3.7.1+

```html
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
```

### 2. Smoothstate Plugin

```html
<script defer src="{$smarty.const.ENGINEURL}js/jquery.smoothState.js"></script>
```

### 3. Smoothstate Initialization

```html
<script defer src="{$smarty.const.SHAREDSKINURL}js/initsmoothstate.js"></script>
```

### 4. CSS Animations

The site must load `animate.css` which provides the animation classes:

```html
<link rel="stylesheet" type="text/css" href="{$smarty.const.ENGINESKINURL}css/animate.css">
```

**Animation Classes Used:**
- `.fadeIn` - Entry animation (opacity 0→1)
- `.is-exiting` - Exit animation (applied during transition)

## Enabling Smoothstate for a Site

### Checklist

- [ ] Site's `page.tmpl` loads jQuery 3.7.1+
- [ ] Site's `page.tmpl` loads `jquery.smoothState.js`
- [ ] Site's `page.tmpl` loads `initsmoothstate.js` from `{$smarty.const.SHAREDSKINURL}js/`
- [ ] Site's `page.tmpl` loads `animate.css`
- [ ] Body element has `id="body"` and `class="scene element fadeIn"`
- [ ] Head element has `id="head"`
- [ ] Site tested in modern browsers (IE 10+, all current browsers)

### Implementation Example

See **asimov/www/skin/tmpl/page.tmpl** for a working reference:

```html
<!DOCTYPE html>
<html lang="en-us" id="html">
<head id="head" itemscope itemtype="https://schema.org/WebSite">
  {block name="head"}
  <meta charset="utf-8">
  <link rel="stylesheet" type="text/css" href="{$smarty.const.ENGINESKINURL}css/animate.css" media="screen">
  <!-- other stylesheets -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script>var ENGINEURL="{$smarty.const.ENGINEURL}";</script>
  <script src="{$smarty.const.ENGINEURL}js/bbsengine6.js"></script>
  
  {* Smoothstate: smooth AJAX page transitions *}
  <script defer src="{$smarty.const.ENGINEURL}js/jquery.smoothState.js"></script>
  <script defer src="{$smarty.const.SHAREDSKINURL}js/initsmoothstate.js"></script>
  {/block}
</head>
<body id="body" class="scene element fadeIn">
  {include file="pageheader.tmpl"}
  {include file="topbar.tmpl" data=$data|default:NULL}
  {block name="content"}
    <main>{block name="main"}{/block}</main>
  {/block}
  {include file="pagefooter.tmpl"}
</body>
</html>
```

## Browser Support

Smoothstate requires HTML5 History API (`pushState`):
- ✅ Chrome 18+
- ✅ Firefox 4+
- ✅ Safari 5.1+
- ✅ Edge (all versions)
- ⚠️ IE 10+ (requires polyfill for full support)

If History API is not available, smoothstate gracefully degrades and navigation works normally.

## Troubleshooting

### Smoothstate Not Working

**Check:**
1. Browser console for errors (enable `$smoothstate-debug: true`)
2. jQuery is loaded before smoothState plugin
3. Body has `id="body"` attribute
4. `animate.css` is loaded
5. Page has no JavaScript errors

**Debug:**
Enable debug mode in _zoid6vars.scss:
```scss
$smoothstate-debug: true;
```

Then check browser console (`F12 > Console`) for smoothState debug messages.

### Navigation Too Fast / Too Slow

Adjust the transition duration:
```scss
// In site's _vars.scss
$smoothstate-duration: 500;  // Slower (default: 250ms)
```

### Prefetch Using Too Much Bandwidth

Disable prefetch:
```scss
$smoothstate-prefetch: false;
```

### Cache Issues

If pages seem to show stale content, reduce cache:
```scss
$smoothstate-cache-length: 1;  // Cache only current page
```

## Advanced Customization

### Custom Exit Animation

Edit the `onStart` callback in `zoid6/shared/skin/js/initsmoothstate.js`:

```javascript
onStart: {
  duration: 250,
  render: function () {
    content.toggleAnimationClass("is-exiting");
    // Add custom animation logic here
    console.log("Custom exit animation running");
  }
}
```

### Custom Entry Animation

Add an `onReady` callback:

```javascript
onReady: {
  duration: 250,
  render: function () {
    // Add custom entry logic here
    content.addClass('is-entering');
  }
}
```

### Custom Target Selectors

Change which elements get replaced:

```javascript
// Default: #head, #body
var content = $('#head, #body').smoothState(config);

// Custom: replace entire #app container
var content = $('#app').smoothState(config);
```

## Performance Notes

### Benefits
- **Perceived speed:** Page feels faster due to instant visual feedback
- **Reduced bandwidth:** Prefetch can warm cache
- **Smoother UX:** No white flashes between pages

### Costs
- **Prefetch overhead:** Pre-loads pages on hover (can use extra bandwidth)
- **Cache memory:** Stores 2 pages in memory by default
- **JavaScript size:** Adds ~10KB (minified plugin)

### Optimization Tips
1. Set `cacheLength: 1` for low-memory devices
2. Disable `prefetch: false` on slow connections
3. Use `debug: false` in production (reduces console spam)

## Related Files

- **Initialization:** `/zoid6/shared/skin/js/initsmoothstate.js`
- **Configuration:** `/zoid6/shared/skin/scss/_zoid6vars.scss` (variables section)
- **CSS Animations:** `/bbsengine6/skin/scss/animate.scss`
- **Working Reference:** `/asimov/www/skin/tmpl/page.tmpl`
- **Plugin Source:** `/bbsengine6/js/jquery.smoothState.js`
- **Plugin Docs:** https://github.com/miguel-perez/smoothState.js

## Rollout Status

| Site | Status | Notes |
|------|--------|-------|
| asimov | ✅ Working | Original implementation (reference site) |
| achilles | 🔄 In Progress | Updated to use zoid6/shared version |
| empyre | 🔄 In Progress | Updated to use zoid6/shared version |
| rgs | 🔄 In Progress | Updated to use zoid6/shared version |
| zoid6/shared | ✅ Ready | Shared implementation for all sites |
