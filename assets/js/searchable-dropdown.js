module.exports = {
    refreshSelectedItems: (options) => {
        let helpText = document.getElementById('selectedChannels');
        helpText.innerText = '';

        for (let option of options) {
            if (option.selected) {
                if (helpText.innerText !== '')
                    helpText.innerText = helpText.innerText + ',';

                helpText.innerText = helpText.innerText + ' #' + option.dataset.name;
            }
        }
    },

    resetList: (select, options) => {
        let resetOptions = [];
        for (let currentOption of select.options) {
            resetOptions.push(currentOption);
        }

        resetOptions.forEach(option => {
            option.remove();
        })

        options.forEach(option => {
            select.append(option);
        })
    },

    filterList: (select, searchTerm) => {
        let removeOptions = [];
        let options = select.options;

        for (let option of options) {
            option.disabled = !option.dataset.name.includes(searchTerm);

            if (option.disabled) {
                removeOptions.push(option);
            }
        }

        removeOptions.forEach(option => {
            option.remove();
        })
    },

    init: () => {
        let inputs = document.querySelectorAll('.search-input');

        inputs.forEach( input => {
            let selects = document.querySelectorAll('select[name="' + input.dataset.target + '"]');
            let select = selects[0];
            let options = select.options;
            let oldOptions = [];
            module.exports.refreshSelectedItems(options);

            let selectAllButton = input.parentElement.querySelector('#select_all');
            if (selectAllButton !== null) {
                selectAllButton.addEventListener('click', () => {
                    for (let option of options) {
                        option.selected = true;
                    }

                    module.exports.refreshSelectedItems(options);
                });
            }

            for (let option of options) {
                oldOptions.push(option);
            }

            input.addEventListener('input', () => {
                let searchTerm = input.value.toLowerCase();
                searchTerm = searchTerm.replace(' ', '-');

                module.exports.resetList(select, oldOptions);

                module.exports.filterList(select, searchTerm);

                module.exports.refreshSelectedItems(options);
            });

            select.addEventListener('change', () => {
                module.exports.refreshSelectedItems(options);
            })
        });
    },
}