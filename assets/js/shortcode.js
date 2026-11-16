(function () {
  'use strict';

  function hydrate(node) {
    if (!node || !node.dataset.id || node.dataset.process) return;
    node.dataset.process = '1';

    var imageNode = node.querySelector('.ninjadraw-image');
    if (!imageNode) return;

    var config = window.yalogica_ninjadraw_global;

    fetch(config.api.public_url + '/images/' + node.dataset.id)
      .then(function (response) { return response.json(); })
      .then(function (response) {
        if (response && response.success) {
          imageNode.innerHTML = response.image;
          node.classList.add('ninjadraw-ready');
        }
        delete node.dataset.process;
      })
      .catch(function () {
        delete node.dataset.process;
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('.ninjadraw'), hydrate);
  });
})();