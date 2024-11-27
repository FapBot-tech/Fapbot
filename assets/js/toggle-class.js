module.exports = {
    init: () => {
        let divs = document.querySelectorAll('[data-toggle-class]');

        const toggleClass = (div, classToAdd) => {
            div.classList.toggle(classToAdd);
        };

        divs.forEach( div=> {
            if (div.dataset.initialized) return;

            div.dataset.initialized = "true";

            div.addEventListener('click', () => {
                toggleClass(div, div.dataset.toggleClass);
            });
        });
    }
};