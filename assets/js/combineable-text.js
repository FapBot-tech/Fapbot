let AutoCompleteUsername = require('./auto-complete-username');

module.exports = {
    getInputs: (name) => {
        return document.querySelectorAll('input.' + name);
    },

    getAddButtons: (name) => {
        return document.querySelectorAll('span.' + name);
    },

    init: (searchUser) => {
        let groups = document.querySelectorAll('.combinable-text');


        groups.forEach( group => {
            let inputs = module.exports.getInputs(group.dataset.target);
            let addButtons = module.exports.getAddButtons(group.dataset.target);
            let hiddenInput = document.querySelectorAll('input[name="' + group.dataset.hidden + '"]')[0];

            if (hiddenInput.value !== null) {
                inputs[0].value = hiddenInput.value;
            }

            addButtons.forEach(button => {
                button.addEventListener('click', event => {
                    let clone = event.target.parentElement.cloneNode(true);
                    let newInput = clone.querySelector('input.' + group.dataset.target);
                    newInput.name = group.dataset.target + '_' + (inputs.length + 1);
                    newInput.dataset.lookup = group.dataset.target + '_' + (inputs.length + 1);
                    newInput.value = '';

                    button = clone.querySelector('button');
                    button.dataset.target = group.dataset.target + '_' + (inputs.length + 1);

                    let results = document.querySelector('.results');
                    group.removeChild(results);

                    group.append(clone);
                    group.append(results);
                    event.target.remove();

                    let helptext = document.querySelectorAll('div.' + group.dataset.target)[0];
                    helptext.classList.remove('hidden');
                    helptext.classList.add('d-inline');

                    searchUser.init();
                    module.exports.init(searchUser);
                    AutoCompleteUsername.init();
                });
            });

            inputs.forEach(input => {
               input.addEventListener('change', () => {
                    let combinedText = "";

                    inputs.forEach(existingInput => {
                        if (existingInput.value.trim() !== '')
                            combinedText += existingInput.value + " ";
                    })

                    hiddenInput.value = combinedText.trim();
               });
            });
        });
    }
}