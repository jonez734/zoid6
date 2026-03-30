function buttonclicked()
{
    const element = document.querySelector('#box');
    element.setAttribute("style", "display: block;")
    element.classList.add('animate__animated', 'animate__zoomIn');
}

(function(window, document, undefined)
{
    // code that should be taken care of right away

    window.onload = init;

    function handleAnimationEnd(event) 
    {
      button = document.querySelector("#button");
      event.stopPropagation();
      //element.classList.remove("animate__animated", "animate__zoomIn");
      console.log("handleAnimationEnd.100: disabling start button");
      button.setAttribute("disabled", "disabled");
    }

    function init()
    {
      element = document.querySelector("#box");
      console.log("javascript is running");
//      element = document.querySelector("#box");
      element.addEventListener("animationend", handleAnimationEnd, {once: true});
    }

})(window, document, undefined);
