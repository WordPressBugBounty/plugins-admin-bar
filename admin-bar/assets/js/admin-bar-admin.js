/******/ (() => { // webpackBootstrap
/*!***********************************!*\
  !*** ./dev/js/admin-bar-admin.js ***!
  \***********************************/
(function ($) {
  // Notice Hide
  $("body").on("click", ".admin-bar-upgrade-popup .popup-dismiss", function (evt) {
    evt.preventDefault();
    $(this).closest(".admin-bar-upgrade-popup").fadeOut(200);
  });

  // Notice Show
  $("body").on("click", ".disabled", function (evt) {
    evt.preventDefault();
    $(".admin-bar-upgrade-popup").fadeIn(200);
  });
})(jQuery);
/******/ })()
;
//# sourceMappingURL=admin-bar-admin.js.map