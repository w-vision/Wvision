(function pageLoader() {
  $(window).on('load', () => {
    const loader = $('.page-loader');
    UIkit.util.animate(loader, 'uk-animation-fade', 400, false, true).then(() => loader.remove());
  });
}(jQuery, UIkit));
