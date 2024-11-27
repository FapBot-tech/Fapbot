module.exports = {
    init: () => {
        let filterBlocks = document.querySelectorAll('.filter-block');

        filterBlocks.forEach(filterBlock => {
            let input = filterBlock.querySelector('input.filter-input');

            input.addEventListener('input', () => {
                let elements = filterBlock.querySelectorAll('.filter-item');

                elements.forEach(element => {
                    if (element.dataset.value.includes(input.value)) {
                        element.classList.remove('d-none');
                    } else {
                        element.classList.add('d-none');
                    }
                });
            });
        });
    },
};