// @see https://stackoverflow.com/questions/1373142/preloading-css-images
$(':hidden').each(function() {
  var backgroundImage = $(this).css("background-image");
  if (backgroundImage != 'none') {
    tempImage = new Image();
    tempImage.src = backgroundImage;
  }
});
