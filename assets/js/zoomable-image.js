module.exports = {
    init: () => {
        let images = document.querySelectorAll('.img-zoomable');

        const toggleClass = (image) => {
            image.classList.toggle('img-zoomed');
        };

        images.forEach( image => {
            if (image.dataset.initialized) return;

            image.dataset.initialized = true;

            image.addEventListener('click', () => {
                toggleClass(image);
            });
        });
    }
};