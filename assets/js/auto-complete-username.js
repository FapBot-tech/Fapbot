module.exports = {
    init: () => {
        let inputs = document.querySelectorAll('.auto-complete-username input');
        let timeoutID = null;

        inputs.forEach( input => {
            input.addEventListener('input', () => {
                clearTimeout(timeoutID);
                timeoutID = setTimeout(function() {
                    if (input.value?.trim() !== '') {
                        input.parentElement.classList.add('loading');
                        input.parentElement.classList.remove('success');
                        let results = input.parentElement.parentElement.querySelector('.results');
                        results.classList.add('closed');

                        fetch('https://fapbot.tech/chat_user/autocomplete/' + input.value, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            priority: "high"
                        })
                        .then(response => response.json())
                        .then(response => {
                            input.parentElement.classList.remove('loading');
                            if (response.length === 1 && response[0].username === input.value) {
                                input.parentElement.classList.add('success');
                                return;
                            }

                            let output = '';
                            response.forEach((result) => {
                                output += '<div class="result" data-value="' + result.username + '" data-target="' + input.name + '">' + result.username + '</div>';
                            });

                            results.innerHTML = output;
                            results.classList.remove('closed');
                            module.exports.initResults();
                        })
                    }
                }, 500);
            });
        });
    },

    initResults: () => {
        let results = document.querySelectorAll('.result');

        results.forEach( result => {
            result.addEventListener('click', (e) => {
                const resultsDiv = result.parentElement;
                let input = null;

                if (result.dataset.target !== undefined) {
                    input = document.querySelector('input[name="'+result.dataset.target+'"]');
                } else {
                    input = resultsDiv.parentElement.querySelector('input');
                }

                if (input.dataset.origin !== undefined) {
                    let hiddenElement = document.querySelector('input[name="'+input.dataset.origin+'"]');
                }

                input.value = result.dataset.value;
                input.dispatchEvent(new Event('change'));
                resultsDiv.classList.add('closed');
            });
        });
    }
}