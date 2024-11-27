let tooltips = require('./tooltips');

module.exports = {
    init: () => {
        let button = document.querySelectorAll('.btn-delete-click');

        button.forEach( el => {
            el.addEventListener('click', () => {
                el.classList.add('disabled');
                el.innerHTML = ' <div class="spinner-grow spinner-grow-sm" role="status">\n' +
                    '  <span class="visually-hidden">Loading...</span>\n' +
                    '</div>';

                fetch(el.dataset.href)
                    .then(() => {
                        el.classList.add('d-none');

                        if(el.dataset.element) {
                            let element = document.getElementById(el.dataset.element);
                            element.classList.add('table-danger');
                            element.setAttribute('data-bs-toggle', 'tooltip');
                            element.setAttribute('data-bs-placement', 'top');
                            element.setAttribute('data-bs-title', 'This message has been deleted');

                            tooltips.init();
                        }
                    });
            });
        });
    }
}