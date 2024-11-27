module.exports = {
    init: () => {
        let tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');

        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }
};